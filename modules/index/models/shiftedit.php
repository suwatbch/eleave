<?php
/**
 * @filesource modules/index/models/shiftedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Shiftedit;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=index-shiftedit
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
     * @param int   $id    ID
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'id' => 0,
                'name' => '',
                'static' => '',
                // 'workweek' => '',
                'start_time' => '',
                'end_time' => '',
                'start_break_time' => '',
                'end_break_time' => '',
                'skipdate' => 0,
                'description' => '',
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            return static::createQuery()
                ->from('shift')
                ->where(array('id', $id))
                ->first();
        }
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (shiftedit.php)
     *
     * @param Request $request
     */

    public function submit(Request $request)
    {
        $ret = [];
        // ตรวจสอบ session, token, สิทธิ์การใช้งาน
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
                try {
                    // รับค่าและตรวจสอบข้อมูล
                    $save = array(
                        'name' => $request->post('name')->topic(),
                        'static' => $request->post('static')->toInt(),
                        'start_time' => $request->post('start_time')->topic(),
                        'end_time' => $request->post('end_time')->topic(),
                        'start_break_time' => $request->post('start_break_time')->topic(),
                        'end_break_time' => $request->post('end_break_time')->topic(),
                        'skipdate' => $request->post('skipdate')->toInt(),
                    );

                    // ตรวจสอบข้อมูลเพิ่มเติมตามต้องการ

                    // บันทึกข้อมูล
                    $id = $request->post('id')->toInt();
                    if ($id == 0) {
                        // เพิ่มใหม่
                        $id = $this->db()->insert($this->getTableName('shift'), $save);
                    } else {
                        // แก้ไข
                        $this->db()->update($this->getTableName('shift'), $id, $save);
                    }

                    // log
                    \Index\Log\Model::add($id, 'index', 'Save', '{LNG_Shift} ID : ' . $id, $login['id']);

                    // ส่งค่ากลับ
                    $ret['alert'] = Language::get('Saved successfully');
                    $ret['location'] = 'reload';

                    // เคลียร์
                    $request->removeToken();
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // ส่งค่ากลับเป็น JSON
        echo json_encode($ret);
    }
}