<?php
/**
 * @filesource modules/eleave/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\View;

use Kotchasan\Date;
use Kotchasan\Language;

/**
 * แสดงรายละเอียดของเอกสาร (modal)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงฟอร์ม Modal สำหรับแสดงรายละเอียด
     * และส่งไปในอีเมล
     *
     * @param object $index
     * @param bool $email true บอกว่าเป็นอีเมล
     *
     * @return string
     */
    public function render($index, $email = false)
    {
        $content = [];
        $content[] = '<article class=modal_detail><header><h3 class=icon-file>{LNG_Details of} {LNG_Request for leave}</h3></header>';
        $content[] = '<table class="fullwidth">';
        $content[] = '<tr><td class="item"><span class="icon-customer">{LNG_Name}</span></td><td class="item"> : </td><td class="item">'.$index['name'].'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-verfied">{LNG_Leave type}</span></td><td class="item"> : </td><td class="item">'.$index['leave_type'].'</td></tr>';
        $category = \Eleave\Category\Model::init();
        foreach ($category->items() as $k => $label) {
            $content[] = '<tr><td class="item"><span class="icon-group">'.$label.'</span></td><td class="item"> : </td><td class="item">'.$category->get($k, $index[$k]).'</td></tr>';
        }
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Detail}/{LNG_Reasons for leave}</span></td><td class="item"> : </td><td class="item">'.nl2br($index['detail']).'</td></tr>';
        $row = '<tr><td class="item"><span class="icon-calendar">{LNG_Date of leave}</span></td><td class="item"> : </td><td class="item">';
        $leave_period = Language::get('LEAVE_PERIOD');
        if ($index['start_date'] == $index['end_date']) {
            $row .= Date::format($index['start_date'], 'd M Y').' '.$leave_period[$index['start_period']];
        } else {
            $row .= Date::format($index['start_date'], 'd M Y').' '.$leave_period[$index['start_period']].' - '.Date::format($index['end_date'], 'd M Y').' '.$leave_period[$index['end_period']];
        }
        $content[] = $row.'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-event">{LNG_Number of leave days}</span></td><td class="item"> : </td><td class="item">'.self::getdatstime($index['days']).' {LNG_days}</td></tr>';
        if ($index['start_period']==0) {
            $content[] = '<tr><td class="item"><span class="icon-clock">{LNG_Time}</span></td><td class="item"> : </td><td class="item"></td></tr>';
        } else {
            $content[] = '<tr><td class="item"><span class="icon-clock">{LNG_Time}</span></td><td class="item"> : </td><td class="item">'.nl2br(self::gettimestr($index['start_hour']).'.'.self::gettimestr($index['start_minutes']).' - '.self::gettimestr($index['end_hour']).'.'.self::gettimestr($index['end_minutes'])).'</td></tr>';
        }
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Communication}</span></td><td class="item"> : </td><td class="item">'.nl2br($index['communication']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-star0">{LNG_Status}</span></td><td class="item"> : </td><td class="item">'.self::showStatus(Language::get('LEAVE_STATUS'), $index['status'], !$email).'</td></tr>';
        if (!empty($index['reason'])) {
            $content[] = '<tr><td class="item"><span class="icon-comments">{LNG_Reason}</span></td><td class="item"> : </td><td class="item">'.$index['reason'].'</td></tr>';
        }
        if ($email) {
            $url = WEB_URL.'index.php?module=eleave-%MODULE%&amp;id='.$index['id'];
            $content[] = '<tr><td class="item">Url</td><td class="item"> : </td><td class="item"><a href="'.$url.'">'.$url.'</a></td></tr>';
        } else {
            $content[] = '<tr><td class="item"><span class="icon-download">{LNG_Attached file}</span></td><td class="item"> : </td><td class="item">'.\Download\Index\Controller::init($index['id'], 'eleave', self::$cfg->eleave_file_typies).'</td></tr>';
        }
        $content[] = '</table></article>';
        // คืนค่า HTML
        return implode("\n", $content);
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param float $days
     *
     * @return string
     */
    public function getdatstime($days)
    {
        // แยกชั่วโมงและนาทีออกจากกัน
        list($pDay, $pTime) = explode('.', $days);
        $result = $pTime*8;
        $zeros = self::searchzeros($pTime);
        if ($pTime!=null) {
            $x = "";
            for ($i=0; $i<$zeros; $i++) {
                $x += $x.'0';
            }
            $result = $x.$result;
        }
        return $pTime == null ? $pDay : $pDay.'.'.rtrim($result, '0');
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param string $number
     *
     * @return int
     */
    public function searchzeros($number)
    {
        $count = 0;
        // แปลงตัวเลขเป็นสตริงเพื่อให้สามารถตรวจสอบแต่ละตัวอักษรได้
        $numberStr = (string)$number;
        // ใช้ for loop ในการตรวจสอบตัวอักษรแต่ละตัว
        for ($i = 0; $i < strlen($numberStr); $i++) {
            if ($numberStr[$i] === '0') {
                $count++;
            } else {
                break; // หยุดเมื่อพบตัวอักษรที่ไม่ใช่ 0
            }
        }
        return $count;
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param string $tines
     *
     * @return string
     */
    public function gettimestr($tines)
    {
        // ตรวจสอบและเพิ่มศูนย์นำหน้าชั่วโมงถ้าจำเป็น
        if (strlen($tines) < 2) {
            $tines = '0' . $tines;
        }
        // ตรวจสอบและเติมศูนย์หลังจุดทศนิยมถ้าจำเป็น
        if (strlen($tines) < 2) {
            $tines = $tines . '0';
        }
        return $tines;
    }
}
