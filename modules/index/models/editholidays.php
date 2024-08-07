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
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     *
     * @param int   $id    id
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'ID' => 0
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            return static::createQuery()
                ->from('holidays')
                ->where(array('id', $id))
                ->first();
        }
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (editholidays.php)
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
                        'id' => $request->post('id')->toInt(),
                        'date' => $request->post('date')->date(),
                        'description' => $request->post('description')->textarea()
                    );
                    $date = $request->post('date')->toString();
                    // ตรวจสอบว่ามีวันหยุดที่มีวันที่เหมือนกันอยู่ในฐานข้อมูลหรือไม่
                    $existingHoliday = $this->db()->createQuery()
                        ->from('holidays') // ควรแก้ไขให้ตรงกับชื่อของตาราง
                        ->where(array('date', $date))
                        ->first();
                    if ($existingHoliday) {
                        $ret['alert'] = Language::get('This holiday already exists');
                    } else {
                        // ตรวจสอบรายการที่เลือก
                        $id = $request->post('id')->toInt();
                        $index = self::get($id);
                        if (!$index) {
                            // ไม่พบ
                            $ret['alert'] = Language::get('Sorry, Item not found It may be deleted');
                        } else {
                            // ตรวจสอบค่าที่จำเป็น
                            if (empty($save['date'])) {
                                $ret['ret_date'] = 'Please fill in';
                            }
                            if (empty($save['description'])) {
                                $ret['ret_description'] = 'Please fill in';
                            }
                            if (empty($ret)) {
                                if ($index->id == 0) {
                                    // ใหม่
                                    $this->db()->insert($this->getTableName('holidays'), $save);
                                } else {
                                    // แก้ไข
                                    $this->db()->update($this->getTableName('holidays'), $index->ID, $save);
                                }
                                // log
                                \Index\Log\Model::add($index->ID, 'holidays', 'Save', '{LNG_Holiday} id : '.$index->id, $login['id']);
                                // คืนค่า
                                $ret['alert'] = Language::get('Saved successfully');
                                $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'holidays'));
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