<?php
/**
 * @filesource modules/index/models/Editholidays.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Editholidays;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=Index-Editholidays
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
                'id' => 0
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            return static::createQuery()
                ->from('holidays')
                ->where(array('ID','date' , 'description'))
                ->first();
        }
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (write.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, สามารถจัดการโมดูลได้, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'ID' => $request->post('ID')->ID(),
                        'date' => $request->post('date')->textarea(),
                        'description' => $request->post('description')->toInt()
                    );
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('ID')->toInt());
                    if (!$index) {
                        // ไม่พบ
                        $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                    } else {
                        if ($save['ID'] == '') {
                            // ไม่ได้กรอก ID
                            $ret['ret_ID'] = 'Please fill in';
                        }
                        if ($save['date'] == '') {
                            // ไม่ได้กรอก description
                            $ret['ret_description'] = 'Please fill in';
                        }
                        if (empty($ret)) {
                            if ($index->id == 0) {
                                // ใหม่
                                $index->id = $this->db()->insert($this->getTableName('holidays'), $save);
                            } else {
                                // แก้ไข
                                $this->db()->update($this->getTableName('holidays'), $index->id, $save);
                            }
                            // log
                            \Index\Log\Model::add($index->id, 'holidays', 'Save', '{LNG_holidays} ID : '.$index->id, $login['id']);
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'holidays'));
                            // เคลียร์
                            $request->removeToken();
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
