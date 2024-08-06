<?php
/**
 * @filesource modules/index/models/shifts.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Shifts;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=index-shifts
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
            ->select('id','name','static','start_time','end_time','start_break_time','end_break_time')
            ->from('shift');
    }

    /**
     * รับค่าจาก action (shiftedit.php)
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
                    $table = $this->getTableName('shift');
                    if ($action === 'delete') {
                        // ลบ
                        $result = $this->db()->delete($table, array('id', $match[1]), 0);
                        if ($result) {
                            // log
                            \Index\Log\Model::add(0, 'eleave', 'Delete', '{LNG_Delete} {LNG_Shift} id : '.implode(', ', $match[1]), $login['id']);
                            // reload
                            $ret['location'] = 'reload';
                        } else {
                            $ret['alert'] = Language::get('Unable to delete the item');
                        }
                    } elseif ($action === 'published') {
                        // สถานะการเผยแพร่
                        $search = $this->db()->first($table, (int) $match[1][0]);
                        if ($search) {
                            $static = $search->static == 1 ? 0 : 1;
                            $this->db()->update($table, $search->id, array('static' => $static));
                            // คืนค่า
                            $ret['elem'] = 'static_'.$search->id;
                            $ret['title'] = Language::get('STATIC', '', $static);
                            $ret['class'] = 'icon-static'.$static;
                            // log
                            \Index\Log\Model::add(0, 'eleave', 'Save', $ret['title'].' id : '.$match[1][0], $login['id']);
                        } else {
                            $ret['alert'] = Language::get('Item not found');
                        }
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