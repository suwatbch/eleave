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
 * คลาสสำหรับอ่านข้อมูลหมวดหมู่
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Functions
{
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
}
