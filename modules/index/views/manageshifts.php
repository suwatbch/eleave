<?php
/**
 * @filesource modules/index/views/manageshifts.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Manageshifts;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=manageshifts
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มจัดการกะการทำงาน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ฟอร์ม
        $form = Html::create('form', array(
            'id' => 'setup_frm', // กำหนด ID ของฟอร์ม
            'class' => 'setup_frm', 
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/manageshifts/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Shift details}' // กำหนดชื่อเรื่องสำหรับ Fieldset
        ));
        // shift_name
        $fieldset->add('text', array(
            'id' => 'name', 
            'label' => '{LNG_Shift name}',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'maxlength' => 100,
            'value' => '',
            'required' => true
        ));
        // start_time
        $fieldset->add('time', array(
            'id' => 'start_time',
            'label' => '{LNG_Start time}',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'item',
            'value' => '',
            'required' => true
        ));
        // end_time
        $fieldset->add('time', array(
            'id' => 'end_time',
            'label' => '{LNG_End time}',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'item',
            'value' => '',
            'required' => true
        ));
        // start_break_time
        $fieldset->add('time', array(
            'id' => 'start_break_time',
            'label' => '{LNG_Break start}',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'item',
            'value' => ''
        ));
        // end_break_time
        $fieldset->add('time', array(
            'id' => 'end_break_time',
            'label' => '{LNG_Break end}',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'item',
            'value' => ''
        ));
        // shift_status
        $fieldset->add('select', array(
            'id' => 'status',
            'label' => '{LNG_Shift status}',
            'labelClass' => 'g-input icon-category',
            'itemClass' => 'item',
            'options' => array(
                1 => '{LNG_Fixed}',
                2 => '{LNG_Rotating}'
            ),
            'value' => 1,
            'required' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        $form->script('initManageshifts();');
        // คืนค่า HTML
        return $form->render();
    }
}