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
        //Form
        $form = Html::create('form', array(
            'id' => 'setup_frm', // กำหนด ID ของฟอร์ม
            'class' => 'setup_frm', 
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/shiftedit/submit',
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
            'maxlength' => 2,
            'value' => isset($index->name) ? $index->name : '',
            'required' => true
        ));
        // static
        $fieldset->add('select', array(
            'id' => 'static',
            'labelClass' => 'g-input icon-file',
            'label' => '{LNG_Static}<em>*</em>',
            'itemClass' => 'item',
            'options' => array(0 => 'Rotating', 1 => 'Fixed'),
            'value' => isset($index->static) ? $index->static : '0',
        ));

        // ฟิลด์วันทำงาน
        // $fieldset->add('checkboxgroups', array(
        //     'id' => 'workweek',
        //     'name' => 'workweek',
        //     'label' => '{LNG_Workweek}',
        //     'labelClass' => 'g-input icon-calendar',
        //     'itemClass' => 'item',
        //     'options' => array(
        //         'monday' => 'Monday',
        //         'tuesday' => 'Tuesday',
        //         'wednesday' => 'Wednesday',
        //         'thursday' => 'Thursday',
        //         'friday' => 'Friday',
        //         'saturday' => 'Saturday',
        //         'sunday' => 'Sunday'
        //     ),
        //     'value' => isset($index->workweek) ? explode(',', $index->workweek) : array(),
        //     'style' => 'display:none;' // ซ่อนฟิลด์นี้ในเบื้องต้น
        // ));
        
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
            'value' => ''
        ));

        $fieldset->add('hidden', array(
            'id' => 'id',
            'name' => 'id',
            'value' => $index->id
        ));
    
        $fieldset->add('hidden', array(
            'id' => 'skipdate',
            'value' => ''
        ));
         // workweek
         $fieldset->add('hidden', array(
            'id' => 'workweek',
            'value' => ''
        ));

        // Javascript สำหรับอัพเดท description field
        // $form->script('
        //     document.getElementById("setup_frm").addEventListener("submit", function() {
        //         var startTime = document.getElementById("start_time").value;
        //         var endTime = document.getElementById("end_time").value;
        //         var startBreakTime = document.getElementById("start_break_time").value;
        //         var endBreakTime = document.getElementById("end_break_time").value;
        //         var description = startTime + " - " + endTime + " พัก " + startBreakTime + " - " + endBreakTime;
        //         document.getElementById("description").value = description;
        //     });
        // ');

        // คืนค่า HTML
        return $form->render();
    }
}