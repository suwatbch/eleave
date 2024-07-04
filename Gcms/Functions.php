<?php
/**
 * @filesource Gcms/Functions.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

use Kotchasan\Language;

/**
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Functions
{
    /**
     * @param string $starttime
     * @param string $endtime
     * @return string
     */
    public static function showtime($starttime,$endtime)
    {
        $result = '';
        if (!empty($starttime) && !($starttime=='00:00' && $endtime=='00:00')){
            $result = $starttime.' - '.$endtime;
        }
        return $result;
    }

    /**
     * @param float $days
     * @param float $times
     * @return string
     */
    public static function gettimeleave($days,$times)
    {
        $Tempdays = $days == 0 ? '' : strval($days).' '.Language::get('days').' ';
        $Temptime = $times == 0 ? '' : strval($times).' '.Language::get('hours');
        $result = $Tempdays.$Temptime;
        return $result;
    }

    /**
     * @param float $days
     * @param float $times
     * @return string
     */
    public static function getttotalleave($days,$times)
    {
        $datetime = self::getdaysfromtimes($times);
        if ($datetime['days'] > 0){
            $days += $datetime['days'];
        }
        $times = $datetime['hours'];
        $Tempdays = $days == 0 ? '' : strval($days).' '.Language::get('days').' ';
        $Temptime = $times == 0 ? '' : strval($times).' '.Language::get('hours');
        $result = $Tempdays.$Temptime;
        if ($result == '') { $result = '0 '.Language::get('days'); }
        return $result;
    }

    /**
     * @param float $hours
     * @return float
     */
    public static function getdaysfromtimes($hours)
    {
        // กำหนดจำนวนชั่วโมงต่อวัน
        $hours_per_day = 8;

        // คำนวณจำนวนวัน (ใช้ floor เพื่อปัดเศษลง)
        $days = floor($hours / $hours_per_day);

        // คำนวณชั่วโมงที่เหลือ
        $remaining_hours = $hours - ($days * $hours_per_day);

        // ส่งผลลัพธ์กลับเป็น array
        return array('days' => $days, 'hours' => $remaining_hours);
    }

    /**
     * @param string $start_time
     * @param string $end_time
     * @param string $break_start
     * @param string $break_end
     * @param array $leave_periods
     * @param array $work_days
     * @param array $holidays
     * @return float
     */
    public static function calculate_leave_hours($start_time, $end_time, $break_start, $break_end, $leave_periods = [], $work_days = [], $holidays = []) {
        $start_datetime = new \DateTime($start_time);
        $end_datetime = new \DateTime($end_time);
        $break_start_datetime = new \DateTime($break_start);
        $break_end_datetime = new \DateTime($break_end);
    
        $total_leave_hours = 0;
    
        foreach ($leave_periods as $leave_period) {
            $leave_start = new \DateTime($leave_period['start']);
            $leave_end = new \DateTime($leave_period['end']);
    
            // ตรวจสอบว่าเป็นวันทำงานและไม่ใช่วันหยุด
            $leave_start_day = $leave_start->format('l');
            $leave_start_date = $leave_start->format('Y-m-d');
    
            if (in_array($leave_start_day, $work_days) && !in_array($leave_start_date, $holidays)) {
                // ตรวจสอบว่าเวลาลางานซ้อนกับเวลาทำงานหรือไม่
                if ($leave_end > $start_datetime && $leave_start < $end_datetime) {
                    // ปรับเวลาลางานให้ไม่เกินช่วงเวลาทำงาน
                    if ($leave_start < $start_datetime) {
                        $leave_start = $start_datetime;
                    }
                    if ($leave_end > $end_datetime) {
                        $leave_end = $end_datetime;
                    }
    
                    // ตรวจสอบและหักช่วงเวลาพักที่คาบเกี่ยวออก
                    if ($leave_start < $break_end_datetime && $leave_end > $break_start_datetime) {
                        if ($leave_start < $break_start_datetime && $leave_end > $break_end_datetime) {
                            // ช่วงลางานครอบคลุมทั้งช่วงพัก
                            $leave_interval = $leave_start->diff($leave_end);
                            $break_interval = $break_start_datetime->diff($break_end_datetime);
                            $leave_interval->h -= $break_interval->h;
                            $leave_interval->i -= $break_interval->i;
                        } elseif ($leave_start < $break_start_datetime) {
                            // ช่วงลางานครอบคลุมก่อนช่วงพัก
                            $leave_end = $break_start_datetime;
                            $leave_interval = $leave_start->diff($leave_end);
                        } elseif ($leave_end > $break_end_datetime) {
                            // ช่วงลางานครอบคลุมหลังช่วงพัก
                            $leave_start = $break_end_datetime;
                            $leave_interval = $leave_start->diff($leave_end);
                        } else {
                            // ช่วงลางานอยู่ในช่วงพักพอดี
                            continue;
                        }
                    } else {
                        $leave_interval = $leave_start->diff($leave_end);
                    }
    
                    $total_leave_hours += ($leave_interval->h + ($leave_interval->i / 60) + $leave_interval->days * 24);
                }
            }
        }
    
        return $total_leave_hours;
    }

    /**
     * @param string $start_date
     * @param string $end_date
     * @param array $work_days
     * @param array $holidays
     * @return float
     */
    public static function calculate_leave_days($leave_start, $leave_end, $work_days = [], $holidays = []) {
        $leave_start_datetime = new \DateTime($leave_start);
        $leave_end_datetime = new \DateTime($leave_end);
        $total_leave_days = 0;
    
        // ลูปผ่านทุกวันระหว่างวันเริ่มต้นและวันสิ้นสุดการลางาน
        for ($date = $leave_start_datetime; $date <= $leave_end_datetime; $date->modify('+1 day')) {
            $current_day = $date->format('l');
            $current_date = $date->format('Y-m-d');
    
            // ตรวจสอบว่าเป็นวันทำงานและไม่ใช่วันหยุดหรือไม่
            if (in_array($current_day, $work_days) && !in_array($current_date, $holidays)) {
                $total_leave_days++;
            }
        }
        return $total_leave_days;
    }
}
