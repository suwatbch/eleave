<?php
/**
 * @filesource modules/eleave/views/csv.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Csv;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-export&typ=csv
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\KBase
{
    /**
     * export to CSV
     *
     * @param Request $request
     */
    public static function execute(Request $request)
    {
        // สามารถจัดการรายการลงทะเบียนได้
        if (Login::checkPermission(Login::isMember(), 'can_manage_enroll')) {
            $params = array(
                'from' => $request->request('from')->date(),
                'to' => $request->request('to')->date(),
                'leave_id' => $request->request('leave_id')->toInt(),
                'member_id' => $request->request('member_id')->toInt(),
                'status' => $request->request('status')->toInt(),
                'leave_status' => Language::get('LEAVE_STATUS'),
                'sort' => []
            );
            //เพิ่มทั้งหมด
            $add = array(-1 => Language::get('all items'));
            foreach ($add as $key => $value){
                $params['leave_status'] = array($key => $value) + $params['leave_status'];
            }
            $params['status'] = isset($params['leave_status'][$params['status']]) ? $params['status'] : -1;

            if (preg_match_all('/(create_date|name|leave_id|start_date|days|communication|reason|status)((\s(asc|desc))|)/', $request->get('sort')->toString(), $match, PREG_SET_ORDER)) {
                foreach ($match as $item) {
                    $params['sort'][] = $item[0];
                }
            }
            if (empty($params['sort'])) {
                $params['sort'][] = 'leave_id asc';
            }

            $lng = Language::getItems(array(
                'Transaction date',
                'Username',
                'Name',
                'Leave type',
                'Detail',
                'Date of leave',
                'days',
                'Time',
                'Status',
                'Reason',
            ));
            $header = [] ;
            $header[] = $lng['Transaction date'];
            $header[] = $lng['Username'];
            $header[] = $lng['Name'];
            $header[] = 'แผนก';
            $header[] = $lng['Leave type'];
            $header[] = $lng['Detail'];
            $header[] = $lng['Date of leave'];
            $header[] = $lng['days'];
            $header[] = $lng['Time'];
            $header[] = $lng['Status'];
            $header[] = $lng['Reason'];
            $datas = [];
            $dataleave = \Eleave\Export\Model::csv($params);
            foreach ($dataleave as $item) {
                $result = [];
                $result[] = "'".Date::format($item->create_date, 'd M Y');
                $result[] = "'".$item->username;
                $result[] = $item->name;
                $result[] = $item->department;
                $result[] = $item->topic;
                $result[] = $item->detail;
                $result[] = self::datefoleave($item);
                $result[] = self::getdatstime($item->days);
                if ($item->start_period == 0) {
                    $result[] = [];
                } else {
                    $result[] = self::gettimestr($item->start_hour).'.'.self::gettimestr($item->start_minutes).' - '.self::gettimestr($item->end_hour).'.'.self::gettimestr($item->end_minutes);
                }
                $result[] = self::approve_status($item->status);
                $result[] = $item->reason;
                $datas[] = $result;
            }
            // export to CSV
            return \Kotchasan\Csv::send('leave', $header, $datas, self::$cfg->eleave_csv_language);
        }
        return false;
    }

    /**
     * @param var $item
     * @return string
     */
    public static function datefoleave($item)
    {
        $res = "";
        if ($item->date < 1 && $item->start_period != 0 && $item->start_date == $item->end_date) {
            $res = "'".Date::format($item->start_date, 'd M Y')." ช่วงเวลา";
        } else if ($item->start_period == 0 && $item->start_date == $item->end_date) {
            $res = "'".Date::format($item->start_date, 'd M Y');
        } else {
            $res = "'".Date::format($item->start_date, 'd M Y')." - ".Date::format($item->end_date, 'd M Y');
        }
        // if ($item->date == 0.5 && $item->start_period == 1) {
        //     $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันเช้า";
        // } else if ($item->date == 0.5 && $item->start_period == 2) {
        //     $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันบ่าย";
        // } else if ($item->date == 1 && $item->start_date == $item->end_date) {
        //     $res = Date::format($item->start_date, 'd M Y');
        // } else if ($item->start_period < 2 && $item->end_period == 0) {
        //     $res = Date::format($item->start_date, 'd M Y')." - ".Date::format($item->end_date, 'd M Y');
        // } else if ($item->start_period < 2 && $item->end_period == 1) {
        //     $res = Date::format($item->start_date, 'd M Y')." - ".Date::format($item->end_date, 'd M Y')." ครึ่งวันเช้า";
        // } else if ($item->start_period < 2 && $item->end_period == 2) {
        //     $res = Date::format($item->start_date, 'd M Y')." - ".Date::format($item->end_date, 'd M Y')." ครึ่งวันบ่าย";
        // } else if ($item->start_period == 2 && $item->end_period == 0) {
        //     $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันบ่าย - ".Date::format($item->end_date, 'd M Y');
        // } else if ($item->start_period == 2 && $item->end_period == 1) {
        //     $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันบ่าย - ".Date::format($item->end_date, 'd M Y')." ครึ่งวันเช้า";
        // } else if ($item->start_period == 2 && $item->end_period == 2) {
        //     $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันบ่าย - ".Date::format($item->end_date, 'd M Y')." ครึ่งวันบ่าย";
        // }
        return $res;
    }

    /**
     * @param int $id
     * @return string
     */
    public static function approve_status($id)
    {
        $res = "";
        $status = Language::get('LEAVE_STATUS');
        foreach ($status as $k => $value) {
            if ($k == $id) {
                $res = $value;
                break;
            }
        }
        return $res;
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
