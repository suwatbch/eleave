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
        
            if ($request->post('cal_status')->toInt()) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'days' => $request->post('cal_days')->toInt(),
                        'times' => $request->post('cal_times')->toFloat(),
                        'leave_id' => $request->post('leave_id')->toInt(),
                        'detail' => $request->post('detail')->textarea(),
                        'communication' => $request->post('communication')->textarea()
                    );
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt(), $login);
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
                        // ไม่สามารถลากิจได้มากกว่า 6 วัน
                        if ($save['days'] > 6 && $save['leave_id'] == 2) {
                            $ret['ret_end_date'] = Language::get('Unable to take leave for more than 6 days');
                        }
                        // ตรวจสอบวันลากิจและลาพักร้อน
                        $result = false;
                        $result_quota = "";
                        $leave_quota = 0;
                        if ($save['leave_id'] == 2 || $save['leave_id'] == 8) {
                            $result_quota = self::getQuota($login['id'],$save['leave_id']);
                            $result_sum = self::getSumLeave($login['id'],$save['leave_id']);
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
                        // ผู้อนุมัติ m1
                        $save['member_id_m1'] = $login['m1'];
                        $save['member_id_m2'] = null;
                        if ($save['days'] > 2){
                            $save['member_id_m2'] = $login['m2'];
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
        $leave_period = Language::get('LEAVE_PERIOD');
        if ($start_date == $end_date) {
            $ret = Date::format($start_date, 'd M Y').' '.$leave_period[$start_period];
        } else {
            $ret = Date::format($start_date, 'd M Y').' '.$leave_period[$start_period]. ($start_period ? '' : ' - '.Date::format($end_date, 'd M Y').' '.$leave_period[0]);
        }

        // เริ่มการหากะ
        $Wstd = new \DateTime($start_date);
        $Wend = new \DateTime($end_date);
        $start_month = false;
        $end_month = false;
        $workdays = [];
        $workweek = [];
        $holidays= [];

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
        // เช็คต้องมีเลขกะ กะเปลี่ยนแปลงต้องหาเดือนให้เจอ วันที่เริ่มต้นต้องไม่น้อยกว่าวันที่สิ้นสุด
        if (!($start_month || $end_month) && $diff['days']>=0) {
            $shift_id = $shift_id == null ? 0 : $shift_id;
            $res['shift_id'] = $shift_id;
            $days = 0;
            $times = 0;
            $daysTimes = '';
            $shiftdata = self::getShifts($shift_id);
            $static = $shiftdata->static;

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
                    // แสดงเวลาที่เลือก
                    $showtime = \Gcms\Functions::showtime($start_time,$end_time);
                    $ret = $ret.' '.$showtime;

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
                // ตรวจสอบลาข้ามปีงบประมาณ
                $end_year = date('Y', strtotime($end_date));
                $start_year = date('Y', strtotime($start_date));
                $check_year = max($end_year, $start_year);
                $fiscal_year = $check_year.sprintf('-%02d-01', 1); // 1 = self::$cfg->eleave_fiscal_year
                if (!($start_date < $fiscal_year && $end_date >= $fiscal_year)) {

                    // ใช้จำนวนวันลาจากที่คำนวณ
                    $days = \Gcms\Functions::calculate_leave_days($start_date,$end_date,$static,$workdays,$workweek,$holidays);
                    
                    if ($days > 0) { 
                        $res['status'] = 1;
                        $res['days'] = (int)$days;
                    }
                }
            }
            $daysTimes = \Gcms\Functions::gettimeleave($days,$times);
            $Leavenotfound = $res['status'] ? ': '.Language::get('Leave not found') : null;
            $daysTimes = empty($daysTimes) ? $Leavenotfound : Language::get('Total').': '.$daysTimes;
            $ret = $ret.($res['status'] ? ' ' : '').$daysTimes;
        }

        // กำหนดตัวแปร trturn
        if (!$res['status']){

            $ret = $ret.' : '.Language::get('Leave not found');
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
                            array('yaer', $year),
                            array('month', 'IN', $month)
                        ))
                        ->cacheOn();
        $workdays = $workdays->execute();    

        $res_month_std = $this->createQuery()
                            ->from('shift_workdays')
                            ->where(array(
                                array('member_id', $member_id),
                                array('yaer', $year),
                                array('month', $month_std)
                            ))
                            ->cacheOn()
                            ->first('id');

        $res_month_end = $this->createQuery()
                            ->from('shift_workdays')
                            ->where(array(
                                array('member_id', $member_id),
                                array('yaer', $year),
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
                            array('yaer', $year),
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
     * @param int $member_id
     * @return static
     */
    public function getUser($member_id)
    {
        $user = $this->createQuery()
                ->from('user U')
                ->where(array(
                    array('U.id', $member_id)
                ))
                ->cacheOn()
                ->first('U.*');
        return $user;
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
