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
                'Communication',
                'Reason',
            ));
            $header = [];
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
            $header[] = $lng['Communication'];
            $header[] = $lng['Reason'];
            $datas = [];
            $dataleave = \Eleave\Export\Model::csv($params);
            $leave_period = Language::get('LEAVE_PERIOD');
            foreach ($dataleave as $item) {
                $result = [];
                $result[] = '="'.Date::format($item->create_date, 'd M Y').'"';
                $result[] = '="'.$item->username.'"';
                $result[] = $item->name;
                $result[] = $item->department;
                $result[] = $item->topic;
                $result[] = $item->detail;
                if ($item->start_date == $item->end_date) {
                    $result[] = Date::format($item->start_date, 'd M Y').' '.$leave_period[$item->start_period];
                } else {
                    $result[] = Date::format($item->start_date, 'd M Y').' '.$leave_period[$item->start_period].($item->start_period ? '' : ' - '.Date::format($item->end_date, 'd M Y').' '.$leave_period[$item->end_period]);
                }
                $result[] = \Gcms\Functions::gettimeleave($item->days,$item->times);
                $result[] = \Gcms\Functions::showtime($item->start_time,$item->end_time);
                $result[] = self::approve_status($item->status);
                $result[] = $item->communication;
                $result[] = $item->reason;
                $datas[] = $result;
            }
            // export to CSV
            return self::sendCsv('leave', $header, $datas, 'UTF-8');
        }
        return false;
    }

    /**
     * ส่งออกไฟล์ CSV
     *
     * @param string $file ชื่อไฟล์ (ไม่รวม .csv)
     * @param array $header รายชื่อแถวหัวข้อ
     * @param array $datas ข้อมูล
     * @param string $charset การเข้ารหัสตัวอักษร (default: UTF-8)
     * @param bool $bom รวม BOM (default: true)
     * @return bool
     */
    public static function sendCsv($file, $header, $datas, $charset = 'UTF-8', $bom = true)
    {
        // Set response headers for the CSV file download
        header('Content-Type: text/csv;charset="'.$charset.'"');
        header('Content-Disposition: attachment;filename="'.$file.'.csv"');

        // Create a stream for output
        $f = fopen('php://output', 'w');

        // Add BOM for UTF-8 if requested
        if ($charset == 'UTF-8' && $bom) {
            fwrite($f, "\xEF\xBB\xBF");
        }

        // Convert charset to uppercase
        $charset = strtoupper($charset);

        // Write the CSV header if it's not empty
        if (!empty($header)) {
            fputcsv($f, self::convert($header, $charset));
        }

        // Write the CSV content row by row
        foreach ($datas as $item) {
            fputcsv($f, self::convert($item, $charset));
        }

        // Close the stream
        fclose($f);

        // Return success
        return true;
    }

    /**
     * Convert data array to the specified character encoding
     *
     * @param array $datas ข้อมูล
     * @param string $charset การเข้ารหัสตัวอักษร
     * @return array ข้อมูลที่ถูกแปลง
     */
    private static function convert($datas, $charset)
    {
        if ($charset != 'UTF-8') {
            foreach ($datas as $k => $v) {
                if ($v != '') {
                    $datas[$k] = iconv('UTF-8', $charset.'//IGNORE', $v);
                }
            }
        }
        return $datas;
    }

    /**
     * @param var $item
     * @return string
     */
    public static function datefoleave($item)
    {
        $res = "";
        if ($item->date == 0.5 && $item->start_period == 1) {
            $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันเช้า";
        } else if ($item->date == 0.5 && $item->start_period == 2) {
            $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันบ่าย";
        } else if ($item->date == 1 && $item->start_date == $item->end_date) {
            $res = Date::format($item->start_date, 'd M Y');
        } else if ($item->start_period < 2 && $item->end_period == 0) {
            $res = Date::format($item->start_date, 'd M Y')." - ".Date::format($item->end_date, 'd M Y');
        } else if ($item->start_period < 2 && $item->end_period == 1) {
            $res = Date::format($item->start_date, 'd M Y')." - ".Date::format($item->end_date, 'd M Y')." ครึ่งวันเช้า";
        } else if ($item->start_period < 2 && $item->end_period == 2) {
            $res = Date::format($item->start_date, 'd M Y')." - ".Date::format($item->end_date, 'd M Y')." ครึ่งวันบ่าย";
        } else if ($item->start_period == 2 && $item->end_period == 0) {
            $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันบ่าย - ".Date::format($item->end_date, 'd M Y');
        } else if ($item->start_period == 2 && $item->end_period == 1) {
            $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันบ่าย - ".Date::format($item->end_date, 'd M Y')." ครึ่งวันเช้า";
        } else if ($item->start_period == 2 && $item->end_period == 2) {
            $res = Date::format($item->start_date, 'd M Y')." ครึ่งวันบ่าย - ".Date::format($item->end_date, 'd M Y')." ครึ่งวันบ่าย";
        }
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
}
?>
