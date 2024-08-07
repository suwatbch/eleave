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
        // session, token, สามารถจัดการโมดูลได้, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
                try {
                    // รับค่าจากฟอร์ม
                    $start_time = $request->post('start_time')->toString();
                    $end_time = $request->post('end_time')->toString();
                    $start_break_time = $request->post('start_break_time')->toString();
                    $end_break_time = $request->post('end_break_time')->toString();
                    $skipdate = $request->post('skipdate')->toInt();

                    /// สร้างข้อความคำอธิบาย
                    $description = "$start_time - $end_time พัก $start_break_time - $end_break_time";
                    
                    // ตรวจสอบการข้ามวัน
                    if ($skipdate) {
                        // ถ้าข้ามวัน แสดงข้อความเพิ่มเติม (ตัวอย่าง)
                        $description .= " (ข้ามวัน)";
                    }

                    // ค่าที่ส่งมา
                    $save = array(
                    'id' => $request->post('id')->toInt(),
                    'name' => $request->post('name')->topic(),
                    'static' => $request->post('static')->toInt(),
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'start_break_time' => $start_break_time,
                    'end_break_time' => $end_break_time,
                    'skipdate' => $skipdate,
                    'description' => $description
                    // 'workweek' => $request->post('workweek')->topic()
                    );
                    
                    // ตรวจสอบรายการที่เลือก
                    $name = $request->post('name')->topic();
                    $existingShift = $this->db()->createQuery()
                    ->from('shift')
                    ->where(array('name', $name))
                    ->first();
                    if ($existingShift && $existingShift->id != $save['id']) {
                        $ret['alert'] = Language::get('This shift already has a name.');
                    } else {
                        // ตรวจสอบรายการที่เลือก
                    $id = $request->post('id')->toInt();
                    $index = self::get($id);
                    if (!$index) {
                        // ไม่พบ
                        $ret['alert'] = Language::get('Sorry, Item not found It may be deleted');
                    } else {
                        // ตรวจสอบค่าที่จำเป็น
                        if (empty($save['name'])) {
                            // ไม่ได้กรอก name
                            $ret['ret_name'] = 'Please fill in name';
                        }
                        if (empty($save['start_time'])) {
                            // ไม่ได้กรอก name
                            $ret['ret_start_time'] = 'Please fill in start time';
                        }
                        if (empty($save['end_time'])) {
                            // ไม่ได้กรอก name
                            $ret['ret_end_time'] = 'Please fill in end time';
                        }
                        if (empty($save['start_break_time'])) {
                            // ไม่ได้กรอก name
                            $ret['ret_start_break_time'] = 'Please fill in start break time';
                        }
                        if (empty($save['end_break_time'])) {
                            // ไม่ได้กรอก name
                            $ret['ret_end_break_time'] = 'Please fill in end break time';
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
                            \Index\Log\Model::add($index->id, 'shifts', 'Save', '{LNG_Manage shift} ID : '.$index->id, $login['id']);
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