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
                        $save['leave_id'] = $request->post('leave_id')->toInt();
                        $save['department'] = $request->post('department')->topic();
                        $save['shift_id'] = $request->post('shift_id')->toInt();
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
                        $save['days'] = 0;
                        $save['times'] = 0;
                        if ($start_date == '') {
                            // ไม่ได้กรอกวันที่เริมต้น
                            $ret['ret_start_date'] = 'Please fill in';
                        }
                        if ($end_date == '') {
                            // ไม่ได้กรอกวันที่สิ้นสุดมา ใช้วันที่เดียวกันกับวันที่เริ่มต้น (ลา 1 วัน)
                            $end_date = $start_date;
                        }
                        
                        // $shiftdata = \Gcms\Model::getshift($save['shift_id']);
                        $shiftdata = $this->createQuery()
                        ->from('shift')
                        ->where(array('id', $save['shift_id']))
                        ->cacheOn()
                        ->first('*');

                        // กำหนดวันทำงาน
                        $workweek = json_decode($shiftdata->workweek, true);

                        // กำหนดวันหยุด
                        $holidays = [];
                        if ($shiftdata->holiday != null){
                            $holidays = json_decode($shiftdata->holiday, true);
                        }

                        if ($end_date < $start_date && !$start_period) {
                            // วันที่สิ้นสุด น้อยกว่าวันที่เริ่มต้น
                            $ret['ret_end_date'] = Language::get('End date must be greater than or equal to the start date');
                        } elseif ($start_period) {
                            $diff = Date::compare($start_date, $end_date);
                            // ลาภายใน 1 วัน เช็คกะเพิ่มถ้ากะข้ามวัน end > start ได้
                            if ($shiftdata) {
                                $start_date_work = $start_date;
                                $end_date_work = $start_date;
                                $skipdate = $shiftdata->skipdate;
                                if (!$skipdate) {
                                    // กะภายในวัน วันที่สิ้นสุดเท่ากันวันที่เริ่มต้น
                                    $end_date = $start_date;
                                    $end_date_work = $end_date;
                                } else {
                                    if ($diff['days'] < 0 || $diff['days'] > 1) {
                                        // กะข้ามวัน เลือกวันที่สิ้นสุด มากกว่า 1 วัน
                                        $ret['ret_end_date'] = Language::get('The end date is incorrect');
                                    } else {
                                        // กะข้าววัน วันที่สิ้นสุดมากกว่าวันที่เริ่มต้น 1 วัน
                                        $add_one_date = new \DateTime($start_date);
                                        $add_one_date->modify('+1 day');
                                        $start_date_work = $add_one_date->format('Y-m-d');
                                        $end_date_work = $add_one_date->format('Y-m-d');
                                    }
                                }
                                
                                // จัดรูปแบบวันที่เป็นสตริง
                                $date_start = $start_date.' '.$shiftdata->start_time;
                                $date_end = $end_date_work .' '.$shiftdata->end_time;
                                $break_start = $start_date_work.' '.$shiftdata->start_break_time;
                                $break_end = $end_date_work.' '.$shiftdata->end_break_time;
                                $leave_start = $start_date.' '.$start_time;
                                $leave_end = $end_date.' '.$end_time;

                                // ปรับเวลาลาให้ข้ามวันถ้าจำเป็น
                                if ($skipdate && (new \DateTime($start_time) < new \DateTime('12:00'))) {
                                    $ls_Temp = new \DateTime($leave_start);
                                    $ls_Temp->modify('+1 day');
                                    $leave_start = $ls_Temp->format('Y-m-d H:i');
                                }

                                // สร้างช่วงเวลาลา
                                $leave_periods = [['start' => $leave_start, 'end' => $leave_end]];

                                // เรียกใช้ฟังก์ชันและแสดงผลลัพธ์
                                $times = \Gcms\Functions::calculate_static_leave_hours($date_start, $date_end, $break_start, $break_end, $leave_periods, $workweek, $holidays);

                                // แยกวันเวลา
                                if ($times >= 8) {
                                    // 8 ซม. เท่ากัน 1 วัน
                                    $save['days'] = 1;
                                } else if ($times > 0){
                                    // คิดเป็นราย ซม.
                                    $save['times'] = $times;
                                } else {
                                    if ($diff['days'] >= 0 && $diff['days'] <= 1){
                                        // เวลาลาไม่ถูกต้อง
                                        $ret['ret_start_time'] = Language::get('The time is wrong');
                                        $ret['ret_end_time'] = Language::get('The time is wrong');
                                    }
                                }

                            } else {
                                $ret['ret_shift_id'] = Language::get('Work shift not found');
                            }
                        } else {
                            // ตรวจสอบลาข้ามปีงบประมาณ
                            $end_year = date('Y', strtotime($end_date));
                            $start_year = date('Y', strtotime($start_date));
                            $check_year = max($end_year, $start_year);
                            $fiscal_year = $check_year.sprintf('-%02d-01', 1); // 1 = self::$cfg->eleave_fiscal_year
                            if ($start_date < $fiscal_year && $end_date >= $fiscal_year) {
                                // ไม่สามารถเลือกวันลาข้ามปีงบประมาณได้
                                $ret['ret_start_date'] = Language::get('Unable to take leave across the fiscal year. If you want to take continuous leave, separate the leave form into two. within that fiscal year');
                            } else {
                                // ใช้จำนวนวันลาจากที่คำนวณ
                                $save['days'] = \Gcms\Functions::calculate_static_leave_days($start_date,$end_date,$workweek,$holidays);
                            }
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
                        $result_items = "";
                        $leave_quota = 0;
                        if ($save['leave_id'] == 2 || $save['leave_id'] == 8) {
                            $result_quota = $this->createQuery()
                            ->from('leave_quota C')
                            ->join('user U', 'LEFT', array('U.username', 'C.username'))
                            ->where(array(
                                array('U.id', $index->member_id),
                                array('C.leave_id', $save['leave_id'])
                            ))
                            ->cacheOn()
                            ->first('C.quota');

                            $result_items = [];
                            $result_items = static::createQuery()
                            ->select('SQL(SUM(days) AS sum)')
                            ->from('leave_items I')
                            ->where(array(
                                array('I.member_id', $index->member_id),
                                array('I.leave_id', $save['leave_id']),
                                array('I.status', '<', 2)
                            ));
                            $result_itemsdata = $result_items->execute();
                            $leave_quota = $result_itemsdata[0]->sum == null ? 0 : $result_itemsdata[0]->sum;
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
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
