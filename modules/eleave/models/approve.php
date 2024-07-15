<?php
/**
 * @filesource modules/eleave/models/approve.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Approve;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-approve
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     *
     * @param int $id ID
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id)
    {
        return static::createQuery()
            ->from('leave_items I')
            ->join('leave F', 'LEFT', array('F.id', 'I.leave_id'))
            ->join('user U', 'LEFT', array('U.id', 'I.member_id'))
            ->where(array('I.id', $id))
            ->first('I.*', 'F.topic leave_type', 'U.username', 'U.name', 'U.shift_id');
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (approve.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, สมาชิก
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if ($request->post('cal_status')->toInt()) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'status' => $request->post('status')->toInt(),
                        'reason' => $request->post('reason')->topic()
                    );
                    if ($request->post('_status')->toInt() != 0) {
                        $save['status'] += 1;
                    }
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt());
                    // สามารถอนุมัติได้
                    if ($index && Login::checkPermission($login, 'can_approve_eleave')) {
                        if (Login::isAdmin()) {
                            // แอดมิน แก้ไขข้อมูลได้
                            $save['shift_id'] = $request->post('shift_id')->toInt();
                            $save['days'] = $request->post('cal_days')->toInt();
                            $save['times'] = $request->post('cal_times')->toFloat();
                            $save['leave_id'] = $request->post('leave_id')->toInt();
                            $save['department'] = $request->post('department')->topic();
                            $save['detail'] = $request->post('detail')->textarea();
                            $save['communication'] = $request->post('communication')->textarea();
                            // ไม่ได้เลือกการลา
                            if ($save['leave_id'] == 0) {
                                $ret['ret_leave_id'] = Language::get('Select leave');  
                            }
                            // วันลา
                            $start_period = $request->post('start_period')->toInt();
                            $start_date = $request->post('start_date')->date();
                            $end_date = $request->post('end_date')->date();
                            $timetemp = '00:00';
                            if ($start_period) {
                                $start_time = $request->post('start_time')->text() == ''  ? $timetemp : $request->post('start_time')->text();
                                $end_time = $request->post('end_time')->text() == '' ? $timetemp : $request->post('end_time')->text();
                            } else {
                                $start_time = $timetemp;
                                $end_time = $timetemp;
                            }
                            
                            $save['start_period'] = $start_period;
                            $save['start_date'] = $start_date;
                            $save['start_time'] = $start_time;
                            $save['end_date'] = $end_date;
                            $save['end_time'] = $end_time;
                            // ไม่สามารถลากิจได้มากกว่า 6 วัน
                            if ($save['days'] > 6 && $save['leave_id'] == 2) {
                                $ret['ret_end_date'] = Language::get('Unable to take leave for more than 6 days');
                            }
                            // ตรวจสอบวันลากิจและลาพักร้อน
                            $result = false;
                            $result_quota = "";
                            $leave_quota = 0;
                            if ($save['leave_id'] == 2 || $save['leave_id'] == 8) {
                                $result_quota = self::getQuota($index->member_id,$save['leave_id']);
                                $result_sum = self::getSumLeave($index->member_id,$save['leave_id']);
                                $leave_quota = $result_sum->sum == null ? 0 : $result_sum->sum;
                                $result = true;
                            }
                            if ($result && $result_quota != "" && $result_quota != false) {
                                if (($save['days'] + $leave_quota) > $result_quota->quota) {
                                    $ret['ret_end_date'] = Language::get('There arent enough leave days');
                                }
                            } else if ($result && !$result_quota) {
                                $ret['ret_end_date'] = Language::get('Leave quota not found');
                            }
                            if (empty($ret)) {
                                // อัปโหลดไฟล์แนบ
                                \Download\Upload\Model::execute($ret, $request, $index->id, 'eleave', self::$cfg->eleave_file_typies, self::$cfg->eleave_upload_size);
                            }
                            if ($save['detail'] == '') {
                                // ไม่ได้กรอก detail
                                $ret['ret_detail'] = 'Please fill in';
                            }
                        }
                        if (empty($ret)) {
                            // แก้ไข
                            $this->db()->update($this->getTableName('leave_items'), $index->id, $save);
                            // log
                            \Index\Log\Model::add($index->id, 'eleave', 'Status', Language::get('LEAVE_STATUS', '', $save['status']).' ID : '.$index->id, $login['id']);
                            if ($save['status'] != $index->status) {
                                $index->status = $save['status'];
                                $index->reason = $save['reason'];
                                // ส่งอีเมลแจ้งการขอลา
                                $ret['alert'] = \Eleave\Email\Model::send((array) $index);
                            } else {
                                // ไม่ต้องส่งอีเมล
                                $ret['alert'] = Language::get('Saved successfully');
                            }
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'eleave-report'));
                            // เคลียร์
                            $request->removeToken();
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * @param int $member_id
     * @param int $leave_id
     * @return static
     */
    public function getQuota($member_id, $leave_id)
    {
        $quota = $this->createQuery()
                ->from('leave_quota C')
                ->where(array(
                    array('C.member_id', $member_id),
                    array('C.leave_id', $leave_id)
                ))
                ->cacheOn()
                ->first('C.quota');
        return $quota;
    }

    /**
     * @param int $member_id
     * @param int $leave_id
     * @return static
     */
    public function getSumLeave($member_id, $leave_id)
    {
        $sum = $this->createQuery()
                ->from('leave_items I')
                ->where(array(
                    array('I.member_id', $member_id),
                    array('I.leave_id', $leave_id),
                    array('I.status', '<', 2)
                ))
                ->first('SQL(SUM(days) AS sum)');
        return $sum;
    }
}
