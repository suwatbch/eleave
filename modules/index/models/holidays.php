<?php
/**
 * @filesource modules/index/model/holidays.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Holidays;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=index-holidays
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('id', 'date', 'description')
            ->from('holidays');
    }

 /**
 * รับค่าจาก action (Editholidays.php)
 *
 * @param Request $request
 */
public function action(Request $request)
{
    $ret = [];
    // session, referer, สามารถจัดการโมดูลได้, ไม่ใช่สมาชิกตัวอย่าง
    if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
        if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
            // รับค่าจากการ POST
            $action = $request->post('action')->toString();
            
            // กำหนดค่าตัวแปร $table
            $table = $this->getTableName('holidays');
            
            // id ที่ส่งมา
            if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                if ($action === 'delete') {
                    // ลบ
                    $this->db()->delete($table, array('id', $match[1]), 0);
                    // log
                    \Index\Log\Model::add(0, 'eleave', 'Delete', '{LNG_Delete} {LNG_Leave type} id : '.implode(', ', $match[1]), $login['id']);
                    // reload
                    $ret['location'] = 'reload';
                } elseif ($action === 'published') {
                    // สถานะการเผยแพร่
                    $search = $this->db()->first($table, (int) $match[1][0]);
                    if ($search) {
                        $published = $search->published == 1 ? 0 : 1;
                        $this->db()->update($table, $search->id, array('published' => $published));
                        // คืนค่า
                        $ret['elem'] = 'published_'.$search->id;
                        $ret['title'] = Language::get('PUBLISHEDS', '', $published);
                        $ret['class'] = 'icon-published'.$published;
                        // log
                        \Index\Log\Model::add(0, 'eleave', 'Save', $ret['title'].' id : '.$match[1][0], $login['id']);
                    }
                }
            } elseif ($action === 'add') {
                // ตรวจสอบวันหยุดที่ซ้ำกัน
                $date = $request->post('date')->toString();
                
                // ตรวจสอบว่ามีวันหยุดที่มีวันที่เหมือนกันอยู่ในฐานข้อมูลหรือไม่
                $existingHoliday = $this->db()->createQuery()
                    ->from($table)
                    ->where(array('date', $date));                    
                if ($existingHoliday) {
                    $ret['alert'] = Language::get('This holiday already exists.');
                } else {
                    // เพิ่มข้อมูลใหม่
                    $this->db()->insert($table, array(
                        'date' => $date,
                    ));
                    // log
                    \Index\Log\Model::add(0, 'eleave', 'Add', 'Add new holiday on '.$date, $login['id']);
                    // คืนค่า
                    $ret['alert'] = Language::get('Added successfully');
                    $ret['location'] = 'reload';
                }
            }
        }
    }
    if (empty($ret)) {
        $ret['alert'] = Language::get('Unable to complete the transaction');
    }
    // คืนค่าเป็น JSON
    echo json_encode($ret);
}

}


