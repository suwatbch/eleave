<?php
/**
 * @filesource modules/eleave/models/leave.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */


namespace Eleave\Leave;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-leave
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     *
     * @param int $id ID
     * @param array $login
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id, $login)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'id' => 0,
                'member_id' => $login['id'],
                'name' => $login['name'],
                'department' => empty($login['department']) ? '' : $login['department'][0]
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            return static::createQuery()
                ->from('leave_items I')
                ->join('user U', 'LEFT', array('U.id', 'I.member_id'))
                ->where(array('I.id', $id))
                ->first('I.*', 'U.name');
        }
    }

    /**
     * @param float  $leavetype
     * @param string $start_date
     * @param string $start_time
     * @param string $end_date
     * @param string $end_time
     * @return float
     */
    public static function gettimes($leavetype, $start_date, $start_time, $end_date, $end_time)
    {
        if ($leavetype == 1) {
            // สร้าง DateTime objects จาก input new \DateTime();
            $startDateTime = new \DateTime($start_date.' '.$start_time);
            $endDateTime = new \DateTime($end_date.' '.$end_time);

            // คำนวณความแตกต่างระหว่างสองช่วงเวลา
            $interval = $startDateTime->diff($endDateTime);

            // แปลงความแตกต่างเป็นชั่วโมงและนาที
            $hours = $interval->h;
            $minutes = $interval->i;

            // หากความแตกต่างมีมากกว่า 24 ชั่วโมงให้เพิ่มวันเข้าไป
            if ($interval->d > 0) {
                $hours += $interval->d * 24;
            }

            // แสดงผลลัพธ์เป็นรูปแบบชั่วโมง.นาที
            $times = $hours . '.' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
            return (float)$times > 8 ? 8 : (float)$times;
        }
        return 0;
    }

    /**
     * คืนค่ารายละเอียดกะที่เลือก
     * เป็น JSON
     *
     * @param Request $request
     */
    public function getShift(Request $request)
    {
        // session, referer, member
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            $shift = $this->createQuery()
                ->from('shift')
                ->where(array(
                    array('id', $request->post('id')->toInt())
                ))
                ->cacheOn()
                ->first('skipdate');
            if ($shift) {
                // คืนค่า JSON
                echo json_encode(array(
                    'skipdate' => $shift->skipdate
                ));
            } else {
                // คืนค่า JSON
                echo json_encode(array(
                    'skipdate' => 0
                ));
            }
        }
    }

    /**
     * คืนค่ารายละเอียดการลาที่เลือก
     * เป็น JSON
     *
     * @param Request $request
     */
    public function datas(Request $request)
    {
        // session, referer, member
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            $leave = $this->createQuery()
                ->from('leave')
                ->where(array(
                    array('id', $request->post('id')->toInt()),
                    array('published', 1)
                ))
                ->cacheOn()
                ->first('detail', 'num_days');
            if ($leave) {
                // คืนค่า JSON
                echo json_encode(array(
                    'detail' => '<b>'.Language::get('Leave conditions').' : </b>'.nl2br($leave->detail),
                    'num_days' => $leave->num_days
                ));
            } else {
                // คืนค่า JSON
                echo json_encode(array(
                    'detail' => '<b>  -- '.Language::get('Select leave').' -- </b>',
                    'num_days' => 0
                ));
            }
            // $leave_period = Language::get('LEAVE_PERIOD');
        }
    }

    /**
     * อ่านชื่อประเภทลา
     * ไม่พบคืนค่าข้อความว่าง
     *
     * @param int $id
     *
     * @return string
     */
    public static function leaveType($id)
    {
        $leave = static::createQuery()
            ->from('leave')
            ->where(array('id', $id))
            ->cacheOn()
            ->first('topic');
        return $leave ? $leave->topic : '';
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (leave.php)
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
                    'leave_id' => $request->post('leave_id')->toInt(),
                    'detail' => $request->post('detail')->textarea(),
                    'communication' => $request->post('communication')->textarea()
                );
                // ตรวจสอบรายการที่เลือก
                $index = self::get($request->post('id')->toInt(), $login);
                if ($save['leave_id'] == 0) {
                    // ไม่ได้เลือกการลา
                    $ret['ret_leave_id'] = Language::get('Select leave');  
                }
                if ($index && $login && $login['id'] == $index->member_id) {
                    // หมวดหมู่
                    $category = \Eleave\Category\Model::init();
                    foreach ($category->items() as $k => $label) {
                        if (Language::get('CATEGORIES', '', $k) === '') {
                            // หมวดหมู่ลา
                            $save[$k] = $request->post($k)->topic();
                        } else {
                            // หมวดหมู่สมาชิก (ใช้ข้อมูลสมาชิก)
                            $save[$k] = isset($index->{$k}) ? $index->{$k} : null;
                        }
                    }
                    // วันลา
                    $start_period = $request->post('start_period')->toInt();
                    $start_date = $request->post('start_date')->date();
                    $end_date = $request->post('end_date')->date();
                    $timetemp = '00:00';
                    $start_time = $request->post('start_time')->text() == '' ? $timetemp : $request->post('start_time')->text();
                    $end_time = $request->post('end_time')->text() == '' ? $timetemp : $request->post('end_time')->text();
                    if ($start_date == '') {
                        // ไม่ได้กรอกวันที่เริมต้น
                        $ret['ret_start_date'] = 'Please fill in';
                    }
                    if ($end_date == '') {
                        // ไม่ได้กรอกวันที่สิ้นสุดมา ใช้วันที่เดียวกันกับวันที่เริ่มต้น (ลา 1 วัน)
                        $end_date = $start_date;
                    }
                    $diff = Date::compare($start_date, $end_date);
                    if ($end_date < $start_date) {
                        // วันที่สิ้นสุด น้อยกว่าวันที่เริ่มต้น
                        $ret['ret_end_date'] = Language::get('End date must be greater than or equal to the start date');
                    } elseif ($start_date == $end_date) {
                        // ลาภายใน 1 วัน ใช้จำนวนวันลาจาก คาบการลา
                        // ต้องนี้ต้องเช็คกะเพิ่มถ้ากะข้ามวัน end สามารถ < start ได้
                        // $end_date = $start_date;
                        $save['days'] = self::$cfg->eleave_periods[$start_period];
                    } else {
                        // ตรวจสอบลาข้ามปีงบประมาณ
                        $end_year = date('Y', strtotime($end_date));
                        $start_year = date('Y', strtotime($start_date));
                        $check_year = max($end_year, $start_year);
                        $fiscal_year = $check_year.sprintf('-%02d-01', self::$cfg->eleave_fiscal_year);
                        if ($start_date < $fiscal_year && $end_date >= $fiscal_year) {
                            // ไม่สามารถเลือกวันลาข้ามปีงบประมาณได้
                            $ret['ret_start_date'] = Language::get('Unable to take leave across the fiscal year. If you want to take continuous leave, separate the leave form into two. within that fiscal year');
                        } else {
                            // ใช้จำนวนวันลาจากที่คำนวณ
                            $save['days'] = $diff['days'] + self::$cfg->eleave_periods[$start_period] + self::$cfg->eleave_periods[0] - 1;
                        }
                    }
                    $save['start_period'] = $start_period;
                    $save['start_date'] = $start_date;
                    $save['start_time'] = $start_time;
                    $save['end_date'] = $end_date;
                    $save['end_time'] = $end_time;
                    if ($save['days'] > 6 && $save['leave_id'] == 2) {
                        // ไม่สามารถลากิจได้มากกว่า 6 วัน
                        $ret['ret_end_date'] = Language::get('ไม่สามารถลากิจได้มากกว่า 6 วัน');
                    }
                    // ตรวจสอบวันลากิจและลาพักร้อน
                    $result = false;
                    $result_cota = "";
                    $result_items = "";
                    $leave_cota = 0;
                    if ($save['leave_id'] == 2 || $save['leave_id'] == 8) {
                        $result_cota = $this->createQuery()
                        ->from('leave_cota C')
                        ->join('user U', 'LEFT', array('U.username', 'C.username'))
                        ->where(array(
                            array('U.id', $login['id']),
                            array('C.leave_id', $save['leave_id'])
                        ))
                        ->cacheOn()
                        ->first('C.cota');

                        $result_items = [];
                        $result_items = static::createQuery()
                        ->select('SQL(SUM(days) AS sum)')
                        ->from('leave_items I')
                        ->where(array(
                            array('I.member_id', $login['id']),
                            array('I.leave_id', $save['leave_id']),
                            array('I.status', '<', 2)
                        ));
                        $result_itemsdata = $result_items->execute();
                        $leave_cota = $result_itemsdata[0]->sum == null ? 0 : $result_itemsdata[0]->sum;
                        $result = true;
                    }
                    if ($result && $result_cota != "" && $result_cota != false) {
                        if (($save['days'] + $leave_cota) > $result_cota->cota) {
                            $ret['ret_end_date'] = Language::get('วันลาของท่านมีไม่พอ');
                        }
                    } else if ($result && !$result_cota) {
                        $ret['ret_end_date'] = Language::get('ไม่พบโคต้าการลา');
                    }
                    // table
                    $table = $this->getTableName('leave_items');
                    // Database
                    $db = $this->db();
                    if (empty($ret)) {
                        // $table = $this->getTableName('leave_items');
                        // $db = $this->db();
                        if ($index->id == 0) {
                            $save['id'] = $db->getNextId($table);
                        } else {
                            $save['id'] = $index->id;
                        }
                        // อัปโหลดไฟล์แนบ
                        \Download\Upload\Model::execute($ret, $request, $save['id'], 'eleave', self::$cfg->eleave_file_typies, self::$cfg->eleave_upload_size);
                    }
                    if ($save['detail'] == '') {
                        // ไม่ได้กรอก detail
                        $ret['ret_detail'] = 'Please fill in';
                    }
                    if (empty($ret)) {
                        if ($index->id == 0) {
                            // ใหม่
                            $save['member_id'] = $login['id'];
                            $save['create_date'] = date('Y-m-d H:i:s');
                            $save['status'] = 0;
                            $db->insert($table, $save);
                        } else {
                            // แก้ไข
                            $db->update($table, $save['id'], $save);
                            $save['status'] = $index->status;
                            $save['member_id'] = $index->member_id;
                        }
                        // log
                        \Index\Log\Model::add($save['id'], 'eleave', 'Status', Language::get('LEAVE_STATUS', '', $save['status']).' ID : '.$save['id'], $login['id']);
                        if ($index->id == 0 || $save['status'] != $index->status) {
                            // ประเภทลา
                            $save['leave_type'] = self::leaveType($save['leave_id']);
                            // ส่งอีเมลแจ้งการขอลา
                            $ret['alert'] = \Eleave\Email\Model::send($save);
                        } else {
                            // ไม่ต้องส่งอีเมล
                            $ret['alert'] = Language::get('Saved successfully');
                        }
                        $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'eleave', 'status' => $save['status']));
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
