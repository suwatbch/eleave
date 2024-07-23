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
     * @param string $start_date
     * @param string $end_date
     * @return int
     */
    public static function checkyearnow($start_date, $end_date) {
        $startYear = date('Y', strtotime($start_date));
        $endYear = date('Y', strtotime($end_date));
        $datenew = date('Y');

        $res = 0;
        if ($startYear === $endYear && $endYear === $datenew) {
            $res = 1;
        }
        return $res; 
     }

    /**
     * @param string $start_time
     * @param string $end_time
     * @return float
     */
    public static function calculateDuration($start_time, $end_time) {
       // แปลงเวลาเริ่มต้นและเวลาสิ้นสุดเป็น timestamp
        $startTimestamp = strtotime($start_time);
        $endTimestamp = strtotime($end_time);

        // ถ้าเวลาสิ้นสุดน้อยกว่าเวลาเริ่มต้น ให้เพิ่ม 24 ชั่วโมงให้กับเวลาสิ้นสุด
        if ($endTimestamp < $startTimestamp) {
            $endTimestamp += 24 * 60 * 60; // เพิ่ม 24 ชั่วโมง (86400 วินาที)
        }

        // คำนวณความแตกต่างในหน่วยวินาที
        $durationInSeconds = $endTimestamp - $startTimestamp;

        // แปลงความแตกต่างเป็นชั่วโมงและนาที
        $hours = floor($durationInSeconds / 3600);
        $minutes = ($durationInSeconds / 60) % 60;
        $minutes = $minutes == 30 ? 0.5 : 0;
        $hours += $minutes;

        return $hours; 
    }

    /**
     * @param array $times_array
     * @param string $times_start
     * @return array
     */
    public static function setTimes($times_array, $times_start) {
        $result = [];
        $startFound = false;

        // เพิ่ม 30 นาทีให้กับเวลาที่เริ่มต้น
        $start_time = new \DateTime($times_start);
        $start_time->modify('+30 minutes');
        $adjusted_start = $start_time->format('H:i');

        foreach ($times_array as $time => $value) {
            if ($startFound || $time == $adjusted_start) {
                $startFound = true;
                $result[$time] = $value;
            }
        }
        return $result;
    }

    /**
     * @param string $datetime
     * @return array
     */
    public static function genTimes($datetime) {
        $times = [];
    
        if ($datetime != '') {
            $datetime = new \DateTime($datetime);
            $startHour = (int)$datetime->format('H');
            $startMinute = (int)$datetime->format('i');
            
            $endHour = ($startHour + 9) % 24;
            $endMinute = $startMinute;

            for ($hour = $startHour; ; $hour = ($hour + 1) % 24) {
                for ($minute = ($hour == $startHour) ? $startMinute : 0; $minute < 60; $minute += 30) {
                    $time = sprintf('%02d:%02d', $hour, $minute);
                    $times[$time] = $time;
                    if ($hour == $endHour && $minute == $endMinute) {
                        break 2;
                    }
                }
            }
        } else {
            for ($hour = 0; $hour < 24; $hour++) {
                for ($minute = 0; $minute < 60; $minute += 30) {
                    $time = sprintf('%02d:%02d', $hour, $minute);
                    $times[$time] = $time;
                }
            }
        }
        
        return $times;
    }

    /**
     * @param string $date
     * @return array
     */
    public static function getSurroundingMonths($date) {
        $date = new \DateTime($date);
        $current_month = (int)$date->format('m');
    
        $months = [];
    
        if ($current_month == 1) {
            // กรณีเดือนมกราคม (1)
            $months[] = 1;
            $months[] = 2;
        } elseif ($current_month == 12) {
            // กรณีเดือนธันวาคม (12)
            $months[] = 11;
            $months[] = 12;
        } else {
            // กรณีทั่วไป
            $months[] = $current_month - 1;
            $months[] = $current_month;
            $months[] = $current_month + 1;
        }
    
        return $months;
    }
    
    /**
     * @param array $data
     * @param string $key
     * @return array
     */
    public static function datanap($data, $key)
    {
        // ตรวจสอบว่าคีย์มีอยู่ในแต่ละองค์ประกอบหรือไม่
        if (empty($data) || !isset($data[0]->$key)) {
            return [];
        }
        
        // ดึงค่าออกมาโดยใช้คีย์ที่กำหนด
        $res = array_map(function($item) use ($key) {
            $value = $item->$key;
            // ตรวจสอบว่าค่าเป็น JSON string หรือไม่ และแปลงเป็น array
            $decoded_value = json_decode($value);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded_value : $value;
        }, $data);
        
        // แปลงค่า array ย่อยเป็น array เดียวถ้าจำเป็น
        $res = array_merge(...array_map(function($item) {
            return is_array($item) ? $item : [$item];
        }, $res));

        return $res;
    }

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
     * @param string $start_date
     * @param string $end_date
     * @param array $workweek
     * @param array $holidays
     * @return float
     */
    public static function calculate_static_leave_days($leave_start, $leave_end, $workweek = [], $holidays = []) {
        $leave_start_datetime = new \DateTime($leave_start);
        $leave_end_datetime = new \DateTime($leave_end);
        $total_leave_days = 0;
    
        // ลูปผ่านทุกวันระหว่างวันเริ่มต้นและวันสิ้นสุดการลางาน
        for ($date = $leave_start_datetime; $date <= $leave_end_datetime; $date->modify('+1 day')) {
            $current_day = $date->format('l');
            $current_date = $date->format('Y-m-d');
    
            // ตรวจสอบว่าเป็นวันทำงานและไม่ใช่วันหยุดหรือไม่
            if (in_array($current_day, $workweek) && !in_array($current_date, $holidays)) {
                $total_leave_days++;
            }
        }
        return $total_leave_days;
    }

    /**
     * @param string $start_date
     * @param string $end_date
     * @param array $workdays
     * @return float
     */
    public static function calculate_notstatic_leave_days($leave_start, $leave_end, $workdays = []) {
        $leave_start_datetime = new \DateTime($leave_start);
        $leave_end_datetime = new \DateTime($leave_end);
        $total_leave_days = 0;

        // ลูปผ่านทุกวันระหว่างวันเริ่มต้นและวันสิ้นสุดการลางาน
        for ($date = $leave_start_datetime; $date <= $leave_end_datetime; $date->modify('+1 day')) {
            $current_date = $date->format('Y-m-d');

            // ตรวจสอบว่าเป็นวันทำงานหรือไม่
            if (in_array($current_date, $workdays)) {
                $total_leave_days++;
            }
        }
        return $total_leave_days;
    }

    /**
     * @param string $leave_start
     * @param string $leave_end
     * @param int $static
     * @param array $workdays
     * @param array $workweek
     * @param array $holidays
     * @return float
     */
    public static function calculate_leave_days($leave_start, $leave_end, $static, $workdays = [], $workweek = [], $holidays = []) {
        $leave_start_datetime = new \DateTime($leave_start);
        $leave_end_datetime = new \DateTime($leave_end);
        $total_leave_days = 0;

        if ($static) {
            // ลูปผ่านทุกวันระหว่างวันเริ่มต้นและวันสิ้นสุดการลางาน
            for ($date = $leave_start_datetime; $date <= $leave_end_datetime; $date->modify('+1 day')) {
                $current_day = $date->format('l');
                $current_date = $date->format('Y-m-d');
        
                // ตรวจสอบว่าเป็นวันทำงานและไม่ใช่วันหยุดหรือไม่
                if (in_array($current_day, $workweek) && !in_array($current_date, $holidays)) {
                    $total_leave_days++;
                }
            }
        } else {
            // ลูปผ่านทุกวันระหว่างวันเริ่มต้นและวันสิ้นสุดการลางาน
            for ($date = $leave_start_datetime; $date <= $leave_end_datetime; $date->modify('+1 day')) {
                $current_date = $date->format('Y-m-d');

                // ตรวจสอบว่าเป็นวันทำงานหรือไม่
                if (in_array($current_date, $workdays)) {
                    $total_leave_days++;
                }
            }
        }
        return $total_leave_days;
    }

    /**
     * @param string $start_date
     * @param string $end_date
     * @param string $break_start
     * @param string $break_end
     * @param array $leave_periods
     * @param int $static
     * @param array $workdays
     * @param array $workweek
     * @param array $holidays
     * @return float
     */
    // รวมกะการทำงานเข้าในฟังชั่นเดียว
    public static function calculateLeaveDuration($start_date, $end_date, $break_start, $break_end, $leave_periods, $static, $workdays = [], $workweek = [], $holidays = []) {
        // แปลงเวลาเป็น timestamp
        $startTimestamp = strtotime($start_date);
        $endTimestamp = strtotime($end_date);
        $breakStartTimestamp = strtotime($break_start);
        $breakEndTimestamp = strtotime($break_end);

        // ตรวจสอบว่าต้องใช้การคำนวณแบบไหน
        if ($static == 0) {
            // แบบหมุนเวียนกะ
            $workday = date('Y-m-d', $startTimestamp);
            $nextWorkday = date('Y-m-d', strtotime($workday . ' +1 day'));
            if (!in_array($workday, $workdays) && !in_array($nextWorkday, $workdays)) {
                return 0; // ถ้าไม่ใช่วันทำงาน ให้คืนค่า 0
            }
        } else {
            // แบบคงที่
            $workdayName = date('l', $startTimestamp); // 'l' ให้ผลลัพธ์เป็นชื่อวันในสัปดาห์ เช่น Monday
            $workdayDate = date('Y-m-d', $startTimestamp); // วันที่ในรูปแบบ Y-m-d
            if (!in_array($workdayName, $workweek) || in_array($workdayDate, $holidays)) {
                return 0; // ถ้าไม่ใช่วันทำงาน หรือเป็นวันหยุด ให้คืนค่า 0
            }
        }

        $totalLeaveDuration = 0;
        foreach ($leave_periods as $period) {
            // แปลงเวลาลางานเป็น timestamp โดยไม่คำนึงถึงวันที่
            $leaveStart = strtotime(date('Y-m-d', $startTimestamp) . ' ' . $period['start_time']);
            $leaveEnd = strtotime(date('Y-m-d', $startTimestamp) . ' ' . $period['end_time']);
            
            // ตรวจสอบและจัดการกรณีที่เวลาลางานข้ามวัน
            if ($leaveEnd < $leaveStart) {
                $leaveEnd += 24 * 60 * 60;
            }

            // ตรวจสอบช่วงเวลาลางานที่ทับซ้อนกันในช่วงเวลาทำงาน
            $actualLeaveStart = max($leaveStart, $startTimestamp);
            $actualLeaveEnd = min($leaveEnd, $endTimestamp);

            if ($actualLeaveStart < $actualLeaveEnd) {
                $leaveDuration = $actualLeaveEnd - $actualLeaveStart;

                // หักเวลาพักออกจากเวลาลางานถ้ามีการคาบเกี่ยวกัน
                $overlapStart = max($actualLeaveStart, $breakStartTimestamp);
                $overlapEnd = min($actualLeaveEnd, $breakEndTimestamp);

                if ($overlapStart < $overlapEnd) {
                    $leaveDuration -= ($overlapEnd - $overlapStart);
                }

                $leaveDurationInHours = $leaveDuration / 3600;
                $totalLeaveDuration += $leaveDurationInHours;
            }
        }

        // ตรวจสอบกรณีที่เวลาลางานข้ามวันในกรณีของหมุนเวียนกะ
        if ($static == 0 && $totalLeaveDuration == 0) {
            foreach ($leave_periods as $period) {
                $leaveStart = strtotime(date('Y-m-d', $endTimestamp) . ' ' . $period['start_time']);
                $leaveEnd = strtotime(date('Y-m-d', $endTimestamp) . ' ' . $period['end_time']);
                if ($leaveEnd < $leaveStart) {
                    $leaveEnd += 24 * 60 * 60;
                }
                $actualLeaveStart = max($leaveStart, $startTimestamp);
                $actualLeaveEnd = min($leaveEnd, $endTimestamp);
                if ($actualLeaveStart < $actualLeaveEnd) {
                    $leaveDuration = $actualLeaveEnd - $actualLeaveStart;
                    $overlapStart = max($actualLeaveStart, $breakStartTimestamp);
                    $overlapEnd = min($actualLeaveEnd, $breakEndTimestamp);
                    if ($overlapStart < $overlapEnd) {
                        $leaveDuration -= ($overlapEnd - $overlapStart);
                    }
                    $leaveDurationInHours = $leaveDuration / 3600;
                    $totalLeaveDuration += $leaveDurationInHours;
                }
            }
        }

        return $totalLeaveDuration;
    }

    /**
     * @param string $start_time
     * @param string $end_time
     * @param string $break_start
     * @param string $break_end
     * @param array $leave_periods
     * @param array $workweek
     * @param array $holidays
     * @return float
     */
    public static function calculate_static_leave_hours($start_time, $end_time, $break_start, $break_end, $leave_periods = [], $workweek = [], $holidays = []) {
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
    
            if (in_array($leave_start_day, $workweek) && !in_array($leave_start_date, $holidays)) {
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
     * @param string $start_time
     * @param string $end_time
     * @param string $break_start
     * @param string $break_end
     * @param array $leave_periods
     * @param array $workdays
     * @return float
     */
    public static function calculate_notstatic_leave_hours($start_time, $end_time, $break_start, $break_end, $leave_periods = [], $workdays = []) {
        $start_datetime = new \DateTime($start_time);
    $end_datetime = new \DateTime($end_time);
    $break_start_datetime = new \DateTime($break_start);
    $break_end_datetime = new \DateTime($break_end);

    $total_leave_hours = 0;

    foreach ($leave_periods as $leave_period) {
        $leave_start = new \DateTime($leave_period['start']);
        $leave_end = new \DateTime($leave_period['end']);

        // ตรวจสอบว่าเป็นวันทำงานที่กำหนดหรือไม่
        $leave_start_date = $leave_start->format('Y-m-d');

        if (in_array($leave_start_date, $workdays)) {
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
}
