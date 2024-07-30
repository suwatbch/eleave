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
            ->select('ID', 'date', 'description')
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
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    // ตาราง
                    $table = $this->getTableName('holidays');
                    if ($action === 'delete') {
                        // ลบ
                        $result = $this->db()->delete($table, array('ID', $match[1]), 0);
                        if ($result) {
                            // log
                            \Index\Log\Model::add(0, 'holidays', 'Delete', '{LNG_Delete} {LNG_Leave type} ID : '.implode(', ', $match[1]), $login['id']);
                            // reload
                            $ret['location'] = 'reload';
                        } else {
                            $ret['alert'] = Language::get('Unable to delete the item');
                        }
                    } elseif ($action === 'published') {
                        // สถานะการเผยแพร่
                        $search = $this->db()->first($table, (int) $match[1][0]);
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $this->db()->update($table, $search->ID, array('published' => $published));
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->ID;
                            $ret['title'] = Language::get('PUBLISHEDS', '', $published);
                            $ret['class'] = 'icon-published'.$published;
                            // log
                            \Index\Log\Model::add(0, 'holidays', 'Save', $ret['title'].' ID : '.$match[1][0], $login['ID']);
                        } else {
                            $ret['alert'] = Language::get('Item not found');
                        }
                    }
                } else {
                    $ret['alert'] = Language::get('Invalid ID');
                }
            } else {
                $ret['alert'] = Language::get('Permission denied');
            }
        } else {
            $ret['alert'] = Language::get('Session expired or invalid referer');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
    
}
