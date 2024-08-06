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
                // 'name' => '',
                // 'static' => 0,
                // 'workweek' => '',
                // 'start_time' => '',
                // 'end_time' => '',
                // 'start_break_time' => '',
                // 'end_break_time' => '',
                // 'skipdate' => 0,
                // 'description' => '',
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
        // session, token, สามารถจัดการโมดูลได้, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_shift')) {
                try {
                    $save = array(
                    'id' => $request->post('id')->toInt(),
                    'name' => $request->post('name')->topic(),
                    'static' => $request->post('static')->toInt(),
                    'start_time' => $request->post('start_time')->time(),
                    'end_time' => $request->post('end_time')->time(),
                    'start_break_time' => $request->post('start_break_time')->time(),
                    'end_break_time' => $request->post('end_break_time')->time(),
                    'skipdate' => $request->post('skipdate')->toInt(),
                    'description' => $request->post('description')->topic(),
                    // 'workweek' => $request->post('workweek')->topic()
                    );
                    
                    // สร้าง description จากข้อมูล start_time, end_time, start_break_time, end_break_time
                    $save['description'] = $save['start_time'].' - '.$save['end_time'].' พัก '.$save['start_break_time'].' - '.$save['end_break_time'];

                    // ตรวจสอบรายการที่เลือก
                    $id = $request->post('id')->toInt();
                    if ($id > 0) {
                        $index = self::get($id);
                    if (!$index) {
                        // ไม่พบ
                        $ret['alert'] = Language::get('Sorry, Item not found It may be deleted');
                    } else {
                        if ($save['name'] == '') {
                            // ไม่ได้กรอก name
                            $ret['ret_name'] = 'Please fill in';
                        }
                        if (empty($ret)) {
                            if ($index->id == 0) {
                                // ใหม่
                                $this->db()->insert($this->getTableName('shift'), $save);
                            } else {
                                // แก้ไข
                                $this->db()->update($this->getTableName('shift'), $index->id, $save);
                            }
                            // log
                            \Index\Log\Model::add($index->id, 'index', 'Save', '{LNG_Manage shift} ID : '.$index->id, $login['id']);
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'index-shifts'));
                            // เคลียร์
                            $request->removeToken();
                        }
                    }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
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