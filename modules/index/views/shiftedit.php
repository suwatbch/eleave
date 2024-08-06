<?php
/**
 * @filesource modules/index/views/shiftedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Shiftedit;

use Kotchasan\Html;

/**
 * module=index-shiftedit
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มสร้าง/แก้ไข จัดการกะการทำงาน
     *
     * @param object $index
     *
     * @return string
     */
    public function render($index)
    {
        // ตรวจสอบและกำหนดค่าตัวแปร $login
        $login = $_SESSION['login'] ?? null; // ตัวอย่างการดึงค่าจากเซสชั่น

        if (!$login) {
            // หากผู้ใช้ยังไม่ล็อกอิน หรือไม่มีข้อมูลใน $login
            return 'Error: You must be logged in to edit shifts';
        }

        //Form
        $form = Html::create('form', array(
            'id' => 'setup_frm', // กำหนด ID ของฟอร์ม
            'class' => 'setup_frm', 
            'autocomplete' => 'off',
            'action' => 'index.php/index/models/shiftedit/save',
            'onsubmit' => 'return doFormSubmit();', // ปรับให้ฟังก์ชัน doFormSubmit() ทำงานก่อนส่งฟอร์ม
            'method' => 'post',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Shift details}' // กำหนดชื่อเรื่องสำหรับ Fieldset
        ));
        // shift name
        $fieldset->add('text', array(
            'id' => 'name', 
            'label' => '{LNG_Shift name}<em>*</em>',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'maxlength' => 4,
            'value' => isset($index->name) ? $index->name : '',
            'required' => true
        ));
        // static
        $fieldset->add('select', array(
            'id' => 'static',
            'labelClass' => 'g-input icon-file',
            'label' => '{LNG_Status}<em>*</em>',
            'itemClass' => 'item',
            'options' => array(0 => 'Rotating', 1 => 'Fixed'),
            'value' => isset($index->static) ? $index->static : '0',
        ));
        
        // // ประกาศฟังค์ชั่น genTimes
        $starttime = empty($index->start_time) ?'': date("Y-m-d").''.$index->start_time;
        $times = \Gcms\Functions::genTimes($index->$starttime);

        // กลุ่มสำหรับเวลาเริ่มและเวลาสิ้นสุด
        $work_time_group = $fieldset->add('groups');

        // เวลาเริ่ม
        $work_time_group->add('select', array(
            'id' => 'start_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_Start time}<em>*</em>',
            'options' => $times,
            'value' => isset($index->start_time) ? $index->start_time : '',
            'required' => true
        ));

        // เวลาสิ้นสุด
        $work_time_group->add('select', array(
            'id' => 'end_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_End time}<em>*</em>',
            'options' => $times,
            'value' => isset($index->end_time) ? $index->end_time : ' ',
            'required' => true
        ));

        // กลุ่มสำหรับเวลาเริ่มต้นพักและเวลาพักสิ้นสุด
        $break_time_group = $fieldset->add('groups');

        // เวลาเริ่มต้นพัก
        $break_time_group->add('select', array(
            'id' => 'start_break_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_Start break time}<em>*</em>',
            'options' => $times,
            'value' => isset($index->start_break_time) ? $index->start_break_time : '',
            'required' => true
        ));

        // เวลาพักสิ้นสุด
        $break_time_group->add('select', array(
            'id' => 'end_break_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_End break time}<em>*</em>',
            'options' => $times,
            'value' => isset($index->end_break_time) ? $index->end_break_time : '',
            'required' => true
        ));
        
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button ok large icon-save',
            'value' => '{LNG_Save}'
        ));
        // description
        $fieldset->add('hidden', array( 
            'id' => 'description',
            'name' => 'description',
            'value' => $index->description
        ));

        $fieldset->add('hidden', array(
            'id' => 'id',
            'name' => 'id',
            'value' => $index->id
        ));
        
        $fieldset->add('hidden', array(
            'id' => 'skipdate',
            'name' => 'skipdate',
            'value' => $index->skipdate
        ));

        // คืนค่า HTML
        return $form->render();
    }
}