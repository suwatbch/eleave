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
     * คืนค่ารายละเอียดกะที่เลือก
     * เป็น JSON
     *
     * @param Request $request
     */
    public function getShift(Request $request)
    {
        // session, referer, member
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            $shift_id = $request->post('id')->toInt();
            $shift = $this->createQuery()
                ->from('shift')
                ->where(array(
                    array('id', $shift_id)
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

            // Database
            $db = $this->db();
            $pStatus = $request->post('status')->toInt();
            $pStatusOld = $request->post('statusOld')->toInt();
            $pId = $request->post('id')->toInt();
            // ตรวจสอบรายการที่เลือก
            $index = self::get($pId, $login);
            $Items = self::getleaveItems($pId);
            if ($Items->status == $pStatusOld) {

                // ทำการยกเลิกรายการลาด้วยตนเอง
                if (($pStatus == 0 || $pStatus == 1 || $pStatus == 4) && $pId > 0) {
                    
                    if ($index->status == 0 && $pStatus == 4) {
                        if ($Items->status == 0) {
                            $statusupdate['status'] = $pStatus;
                            if (!empty($index->member_id_m1) && $index->member_id_m1 > 0) {
                                $statusupdate['status_m1'] = $pStatus;
                            }
                            if (!empty($index->member_id_m2) && $index->member_id_m2 > 0) {
                                $statusupdate['status_m2'] = $pStatus;
                            }
                            $db->update($this->getTableName('leave_items'), $pId, $statusupdate);
                            $ret['alert'] = Language::get('Saved successfully');
                        }
                    } else if ($index->status == 1 && $pStatus == 4) {
                        if ($Items->status == 1) {
                            $pStatus -= 1;
                            $statusupdate['status'] = $pStatus;
                            if (!empty($index->member_id_m1) && $index->member_id_m1 > 0) {
                                $statusupdate['status_m1'] = $pStatus;
                            }
                            if (!empty($index->member_id_m2) && $index->member_id_m2 > 0) {
                                $statusupdate['status_m2'] = $pStatus;
                            }
                            $db->update($this->getTableName('leave_items'), $pId, $statusupdate);
                            
                            $save = [];
                            $save['id'] = $index->id;
                            $save['leave_id'] = $index->leave_id;
                            $save['member_id'] = $index->member_id;
                            $save['member_id_m1'] = $index->member_id_m1;
                            $save['member_id_m2'] = Null;
                            $save['status'] = $statusupdate['status'];
                            $save['status_m1'] = $statusupdate['status_m1'];
                            $save['status_m2'] = 0;
                            $save['leave_type'] = self::leaveType($index->leave_id);
                            $save['detail'] = $index->detail;
                            $save['start_period'] = $index->start_period;
                            $save['start_date'] = $index->start_date;
                            $save['end_date'] = $index->end_date;
                            $save['start_time'] = $index->start_time;
                            $save['end_time'] = $index->end_time;
                            $save['days'] = $index->days;
                            $save['times'] = $index->times;
                            $save['communication'] = $index->communication;
                            $save['reason'] = $index->reason;

                            // ส่งอีเมลแจ้งการขอลา
                            $ret['alert'] = \Eleave\Email\Model::send($save);
                        }
                    } else {
                        $ret['alert'] = Language::get('Saved successfully');
                    }
                    $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'eleave', 'status' => $pStatus));
                    // เคลียร์
                    $request->removeToken();
                }


                // เพิ่มรายการลา
                else if ($request->post('cal_status')->toInt() && $pStatus == 0 && $pId == 0) {
                    try {
                        // ค่าที่ส่งมา
                        $save = array(
                            'days' => $request->post('cal_days')->toInt(),
                            'times' => $request->post('cal_times')->toFloat(),
                            'leave_id' => $request->post('leave_id')->toInt(),
                            'detail' => $request->post('detail')->textarea(),
                            'communication' => $request->post('communication')->textarea()
                        );
                        // ไม่ได้เลือกการลา
                        if ($save['leave_id'] == 0) {
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
                            if ($start_period) {
                                $start_time = $request->post('start_time')->text() == ''  ? $timetemp : $request->post('start_time')->text();
                                $end_time = $request->post('end_time')->text() == '' ? $timetemp : $request->post('end_time')->text();
                            } else {
                                $start_time = $timetemp;
                                $end_time = $timetemp;
                            }
                            // กะลา
                            $save['shift_id'] = $login['shift_id'];
                            // เก็บกะหมุนเวียนลาแบบช่วงเวลา
                            if ($start_period && $save['shift_id']==0) {
                                $save['shift_id'] = $request->post('cal_shift_id')->toInt();
                            }

                            $save['start_period'] = $start_period;
                            $save['start_date'] = $start_date;
                            $save['start_time'] = $start_time;
                            $save['end_date'] = $end_date;
                            $save['end_time'] = $end_time;
                            $save['status'] = $pStatus;
                            $save['status_m1'] = 0;
                            $save['status_m2'] = 0;

                            // table
                            $table = $this->getTableName('leave_items');

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
                            
                            if (!empty($login['m1'])) {
                                $passapprove1 = self::getUser($login['m1']);
                                if (empty($passapprove1)){
                                    $ret['alert'] = Language::get('No approvers found');
                                } else {
                                    // ผู้อนุมัติ m1
                                    $save['member_id_m1'] = $login['m1'];
                                    $save['member_id_m2'] = null;
                                    if ($save['days'] > 2 && !empty($login['m2'])){
                                        // ผู้อนุมัติ m2
                                        $passapprove2 = self::getUser($login['m2']);
                                        if (empty($passapprove2)){
                                            $ret['alert'] = Language::get('No approvers found').' M2';
                                        } else {
                                            $save['member_id_m2'] = $login['m2'];
                                        }
                                    }
                                }
                            } else {
                                $ret['alert'] = Language::get('No approvers found');
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
            } else {
                $ret['alert'] = Language::get('Unable to complete the transaction');
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    // /**
    //  * คืนค่ารายละเอียดกะที่เลือก
    //  * เป็น JSON
    //  * @param Request $request
    //  */
    // public function setSelectTimeStart(Request $request)
    // {
    //     $queryParams = $request->getQueryParams();
    //     $shift_id = (int)$queryParams['shift_id'];
    //     $start_time = $queryParams['start_time'];
    //     $leave_time = self::getTime0fShift($shift_id);
    //     $leave_end_time = \Gcms\Functions::setTimes($leave_time,$start_time);
    //     $res['leave_end_time'] = $leave_end_time;
    //     $res['end_time'] = reset($leave_end_time);
    //     // คืนค่า JSON
    //     echo json_encode($res);
    // }
    /**
     * คืนค่ารายละเอียดกะที่เลือก
     * เป็น JSON
     * @param Request $request
     */
    public function calculateDuration(Request $request)
    {
        $queryParams = $request->getQueryParams();
        $start_time = $queryParams['start_time'];
        $end_time = $queryParams['end_time'];
        $times = \Gcms\Functions::calculateDuration($start_time,$end_time);
        $res['data'] = $times;
        // คืนค่า JSON
        echo json_encode($res);
    }

    /**
     * คืนค่ารายละเอียดกะที่เลือก
     * เป็น JSON
     * @param Request $request
     */
    public function leavealert(Request $request)
    {
        // รับค่า
        $queryParams = $request->getQueryParams();
        $leave_id = (int)$queryParams['leave_id'];
        $shift_id = (int)$queryParams['shift_id'];
        $member_id = (int)$queryParams['member_id'];
        $start_period = (int)$queryParams['start_period'];
        $start_date = $queryParams['start_date'];
        $start_time = $queryParams['start_time'];
        $end_date = $queryParams['end_date'];
        $end_time = $queryParams['end_time'];

        // เรียกชื่อที่จะแสดง
        $res = [];
        $ret = '';
        $res['status'] = 0;
        $res['days'] = 0;
        $res['times'] = 0;

        // เริ่มการหากะ
        $Wstd = new \DateTime($start_date);
        $Wend = new \DateTime($end_date);
        $start_month = false;
        $end_month = false;
        $workdays = [];
        $workweek = [];
        $holidays= [];
        $alertyear = false;
        $alerttime = false;
        $alertdays = false;

        // ตรวจสอบปีที่ลาต้องเป็นปีเดียวกัน
        $datenew = \Gcms\Functions::checkyearnow($start_date, $end_date);
        if ($datenew) {
            $alertyear = true;

            $leave_user = self::getUser($member_id);
            if ($shift_id==0 || $leave_user->shift_id==0) {
                $Wmonth = \Gcms\Functions::getSurroundingMonths($start_date);
                $workdays = self::getShiftWorkdays($start_period,$member_id,$Wstd->format('Y'),$Wmonth,$Wstd->format('m'),$Wend->format('m'),$start_date);
                $shift_id = $workdays->shift_id;
                $start_month = $workdays->start_month;
                $end_month = $workdays->end_month;
                $workdays = \Gcms\Functions::datanap($workdays->days, 'days');
            }

            $diff = Date::compare($start_date, $end_date);
            $alertmonth = !($start_month || $end_month);
            // เช็คต้องมีเลขกะ กะเปลี่ยนแปลงต้องหาเดือนให้เจอ วันที่เริ่มต้นต้องไม่น้อยกว่าวันที่สิ้นสุด
            if ($alertmonth && $diff['days']>=0) {
                $shift_id = $shift_id == null ? 0 : $shift_id;
                $res['shift_id'] = $shift_id;
                $days = 0;
                $times = 0;
                $daysTimes = '';
                $shiftdata = self::getShifts($shift_id);
                $static = $shiftdata->static;

                // ตรวจสอบกะฟิกกับหมุนเวียน
                if ($leave_user->shift_id==0) { $static=0;}
                if ($static) {
                    // กำหนดวันทำงาน
                    $workweek = json_decode($shiftdata->workweek, true);

                    // กำหนดวันหยุด
                    $holidays = self::getShiftHolidays($shift_id,$Wstd->format('Y'));
                    $holidays = \Gcms\Functions::datanap($holidays, 'holidays');
                }

                if ($start_period){
                    //คำนวณเวลางานแบบกะ 9 ซม.
                    $leavetimes = \Gcms\Functions::calculateDuration($start_time,$end_time);
                    if ($leavetimes > 0 && $leavetimes <= 9) {
                        $alerttime = true;

                        // ลาภายใน 1 วัน เช็คกะเพิ่มถ้ากะข้ามวัน end > start ได้
                        if ($shiftdata) {
                            $start_date_work = $start_date;
                            $end_date_work = $start_date;
                            if (!($diff['days'] < 0 || $diff['days'] > 1) && $shiftdata->skipdate) {
                                // กะข้าววัน วันที่สิ้นสุดมากกว่าวันที่เริ่มต้น 1 วัน
                                $add_one_date = new \DateTime($start_date);
                                $add_one_date->modify('+1 day');
                                $start_date_work = $add_one_date->format('Y-m-d');
                                $end_date_work = $add_one_date->format('Y-m-d');
                            }
                            
                            // จัดรูปแบบวันที่เป็นสตริง
                            $date_start = $start_date.' '.$shiftdata->start_time;
                            $date_end = $end_date_work .' '.$shiftdata->end_time;
                            $break_start = $start_date_work.' '.$shiftdata->start_break_time;
                            $break_end = $end_date_work.' '.$shiftdata->end_break_time;

                            // สร้างช่วงเวลาลา
                            $leave_periods = [['start_time' => $start_time, 'end_time' => $end_time]];

                            // เรียกใช้ฟังก์ชันและแสดงผลลัพธ์
                            $times = \Gcms\Functions::calculateLeaveDuration($date_start, $date_end, $break_start, $break_end, $leave_periods, $static, $workdays, $workweek, $holidays);

                            // แยกวันเวลา
                            if ($times >= 8) {
                                // 8 ซม. เท่ากัน 1 วัน
                                $days = 1;
                                $times = 0;
                                $res['status'] = 1;
                                $res['days'] = (int)$days;
                                $res['times'] = (float)$times;
                            } else if ($times > 0){
                                // คิดเป็นราย ซม.
                                $times = $times;
                                $res['status'] = 1;
                                $res['times'] = (float)$times;
                            }
                        }
                    }
                } else {
                    // ใช้จำนวนวันลาจากที่คำนวณ
                    if ($leave_id == 3 || $leave_id == 7) {
                        // ลาคลอกและลาบวช
                        $days = $diff['days'] +1;
                    } else {
                        // ลาหยุดทั่วไป 
                        $days = \Gcms\Functions::calculate_leave_days($start_date,$end_date,$static,$workdays,$workweek,$holidays);
                    }
                    if ($days > 0) { 
                        $res['status'] = 1;
                        $res['days'] = (int)$days;
                    } else {
                        $alertdays = true;
                    }

                    // ไม่สามารถลากิจได้มากกว่า 6 วัน
                    if ($res['days'] > 6 && $leave_id == 2) {
                        $res['status'] = 0;
                        $ret = Language::get('Unable to take leave for more than 6 days');
                    }
                    // ตรวจสอบเกินวันลา การลาบวช และ การลาคลอด และ ไปทหาร
                    else if ($leave_id == 3 || $leave_id == 5 || $leave_id == 7) {
                        $dataleave = self::getleave($leave_id);
                        // ตรวจสอบเพศ ไม่เจอให้ระบุเพศก่อน
                        if (!($leave_user->sex == 'f' || $leave_user->sex == 'm')) {
                            $res['status'] = 0;
                            $ret = Language::get('Unable to determine gender Please edit your personal information to specify your gender first');
                        }
                        // ผู้หญิงลาบวชไม่ได้
                        else if ($leave_user->sex == 'f' && $leave_id == 7) {
                            $res['status'] = 0;
                            $ret = Language::get('Gender does not match leave type');
                        }
                        // ผู้หญิงลาไปทหารไม่ได้
                        else if ($leave_user->sex == 'f' && $leave_id == 5) {
                            $res['status'] = 0;
                            $ret = Language::get('Gender does not match leave type');
                        }
                        // ผู้ชายลาคลอดไม่ได้
                        else if ($leave_user->sex == 'm' && $leave_id == 3) {
                            $res['status'] = 0;
                            $ret = Language::get('Gender does not match leave type');
                        }
                        // ตรวจสอบวันลาเกินวันที่กำหนด
                        else if ($dataleave->num_days < $res['days']) {
                            $res['status'] = 0;
                            $ret = Language::get('Born on time').' '.$dataleave->num_days.' '.Language::get('days');
                        }
                    }
                }

                // ตรวจสอบโคต้าคำนวณวันลา 1 2 3 5 7 8 ยกเว้น 6 ลาปฎิบัติงานนอกสถาที่
                if ($res['status']) {
                    $result = false;
                    $result_quota = "";
                    $leave_quota = 0;
                    if ($leave_id != 0 && $leave_id != 6) {
                        $year = date('Y', strtotime($start_date));
                        $result_quota = self::getQuota($year,$member_id,$leave_id);
                        $result_sum = self::getSumLeave($year,$member_id,$leave_id);
                        $leave_days = $result_sum->days == null ? 0 : $result_sum->days;
                        $leave_times = $result_sum->times == null ? 0 : $result_sum->times;
                        $leave_quota = \Gcms\Functions::calculateDaysTimes($leave_days,$leave_times);
                        $result = true;
                    }
                    if ($result && $result_quota != "" && $result_quota != false) {
                        $Chdays = $res['days'] + $leave_quota['days'];
                        $Chtimes = $res['times'] + $leave_quota['times'];
                        $leave_use = \Gcms\Functions::calculateDaysTimes($Chdays,$Chtimes);
                        $AllDaysTimes = \Gcms\Functions::sumdaystime($leave_use['days'],$leave_use['times']);

                        if ($AllDaysTimes > $result_quota->quota) {
                            $res['status'] = 0;
                            $ret = Language::get('There arent enough leave days');
                        }
                    } else if ($result && !$result_quota) {
                        $res['status'] = 0;
                        $ret = Language::get('Leave quota not found');
                    }
                }

                if (empty($ret)) {
                    $daysTimes = \Gcms\Functions::gettimeleave($days,$times);
                    $Leavenotfound = $res['status'] ? ': '.Language::get('Leave not found') : null;
                    $ret = empty($daysTimes) ? $Leavenotfound : $daysTimes;
                }
            }
        }

        // กำหนดตัวแปร trturn
        if (!$res['status']){
            if (!empty($ret)){
                $ret = $ret;
            } else if (!$alertyear) {
                $ret = Language::get('Unable to take leave over a year');
            } else if ($alerttime || !$alertmonth || $alertdays) {
                $ret = Language::get('Leave not found');
            } else {
                $ret = Language::get('Specified incorrect time period');
            }
        }
        
        $res['data'] =  $ret;
        // คืนค่า JSON
        echo json_encode($res);
    }

    /**
     * @param int $id
     * @return static
     */
    public function getLeaveOfId($id)
    {
        return $this->createQuery()
                    ->from('leave')
                    ->where(array('id', $id))
                    ->cacheOn()
                    ->first('topic');
    }

    /**
     * @param int $id
     * @param string $username
     * @return static
     */
    public static function getUserForU($id, $username)
    {
        if (!empty($id) && $id > 0) {
            $where =  array(
                array('id', $id)
            );
        } else {
            $where =  array(
                array('username', $username)
            );
        }
        return \Kotchasan\Model::createQuery()
                    ->from('user')
                    ->where($where)
                    ->cacheOn()
                    ->first('*');
    }

    /**
     * @param int $shift_id
     * @param int $member_id
     * @return array
     */
    public static function getTime0fShift($shift_id,$member_id)
    {
        $user = \Kotchasan\Model::createQuery()
                    ->select('*')
                    ->from('user')
                    ->where(array('id', $member_id))
                    ->cacheOn()
                    ->execute();

        $result = \Kotchasan\Model::createQuery()
                    ->select('*')
                    ->from('shift')
                    ->where(array('id', $shift_id))
                    ->cacheOn()
                    ->execute();

        $count = count($result) == 0 ? false : true ;
        $datetime = '';
        if ($count) {
            $data = $result[0];
            $datetime = $data->stat_date.' '.$data->start_time;
        }
        $shiftmember_id = 0;        
        if (count($user) > 0){
            $user = $user[0];
            $shiftmember_id = $user->shift_id;
            $datetime = $shiftmember_id == 0 ? '' : $datetime ;
        }
        $Time0fShift = \Gcms\Functions::genTimes($datetime);
        return $Time0fShift;
    }

    /**
     * @param int $shift_id
     * @return static
     */
    public function getShifts($shift_id)
    {
        return $this->createQuery()
                        ->from('shift')
                        ->where(array('id', $shift_id))
                        ->cacheOn()
                        ->first('*');
    }

    /**
     * @param int $start_period
     * @param int $member_id
     * @param int $year
     * @param array $month
     * @param string $month_std
     * @param string $month_end
     * @param string $start_date
     * @return object
     */
    public function getShiftWorkdays($start_period, $member_id, $year, $month = [], $month_std, $month_end, $start_date)
    {
        $workdays = $this->createQuery()
                        ->select('id','shift_id','days')
                        ->from('shift_workdays')
                        ->where(array(
                            array('member_id', $member_id),
                            array('year', $year),
                            array('month', 'IN', $month)
                        ))
                        ->cacheOn();
        $workdays = $workdays->execute();    

        $res_month_std = $this->createQuery()
                            ->from('shift_workdays')
                            ->where(array(
                                array('member_id', $member_id),
                                array('year', $year),
                                array('month', $month_std)
                            ))
                            ->cacheOn()
                            ->first('id');

        $res_month_end = $this->createQuery()
                            ->from('shift_workdays')
                            ->where(array(
                                array('member_id', $member_id),
                                array('year', $year),
                                array('month', $month_end)
                            ))
                            ->cacheOn()
                            ->first('id');
        
        
        $shift_id = 0;                    
        if ($start_period) {
            $shift = $this->createQuery()
                        ->from('shift_workdays')
                        ->where(array(
                            array('member_id', $member_id),
                            array('year', $year),
                            array('month', $month_std),
                            array('days', 'LIKE','%'.$start_date.'%'),
                        ))
                        ->cacheOn()
                        ->first('shift_id');
            // $shiftdata = $shift;
            $shift_id = $shift->shift_id ;    
        }
        $start_month = empty($res_month_std);
        $end_month = empty($res_month_end);
        $days = [];
        foreach ($workdays as $workday) {
            $days[] = (object) [ 'days' => $workday->days];
        }
        $res = (object) [
            'shift_id' => $shift_id,
            'start_month' => $start_month,
            'end_month' => $end_month,
            'days' => $days
        ];            
        return $res;
    }

    /**
     * @param int $shift_id
     * @param int $year
     * @return array
     */
    public function getShiftHolidays($shift_id, $year)
    {
        $holidays = $this->createQuery()
                        ->select('holidays')
                        ->from('shift_holidays')
                        ->where(array(
                            array('shift_id', $shift_id),
                            array('year', $year)
                        ))
                        ->cacheOn();
        return $holidays->execute();
    }

    /**
     * @param int $leave_id
     * @return static
     */
    public function getleave($leave_id)
    {
        return $this->createQuery()
                    ->from('leave')
                    ->where(array('id', $leave_id))
                    ->cacheOn()
                    ->first('*');
    }

    /**
     * @param int $leave_id
     * @return static
     */
    public static function getleaveofstatic($leave_id)
    {
        return static::createQuery()
                    ->from('leave')
                    ->where(array('id', $leave_id))
                    ->cacheOn()
                    ->first('*');
    }

    /**
     * @param int $member_id
     * @return static
     */
    public function getUser($member_id)
    {
        return $this->createQuery()
                ->from('user U')
                ->where(array(
                    array('U.id', $member_id)
                ))
                ->cacheOn()
                ->first('U.*');
    }

    /**
     * @param string $year
     * @param int $member_id
     * @param int $leave_id
     * @return static
     */
    public function getQuota($year, $member_id, $leave_id)
    {
        return $this->createQuery()
                ->from('leave_quota C')
                ->where(array(
                    array('C.year', $leave_id == 7 ? null : $year),
                    array('C.member_id', $member_id),
                    array('C.leave_id', $leave_id)
                ))
                ->cacheOn()
                ->first('C.*');
    }

    /**
     * @param string $year
     * @param int $member_id
     * @param int $leave_id
     * @return static
     */
    public function getSumLeave($year, $member_id, $leave_id)
    {
        $statusIn[] = 0;
        $statusIn[] = 1;
        $statusIn[] = 3;
        $where = array(
            array('I.member_id', $member_id),
            array('I.leave_id', $leave_id),
            array('I.status', 'IN', $statusIn)
        );
        if ($leave_id != 7) {
            $where[] =  array('I.start_date', 'LIKE', $year.'%');
        }
        return $this->createQuery()
                ->from('leave_items I')
                ->where($where)
                ->first('SQL(SUM(days) AS days, SUM(times) AS times)');
    }

    /**
     * @param int $id
     * @return static
     */
    public function getleaveItems($id)
    {
        return $this->createQuery()
                ->from('leave_items I')
                ->where(array('I.id', $id))
                ->first('I.*');
    }
}
