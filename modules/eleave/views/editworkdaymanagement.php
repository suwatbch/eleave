<?php
/**
 * @filesource modules/eleave/views/editworkdaymanagement.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Editworkdaymanagement;

use Kotchasan\Html;

/**
 * module=index-editholidays
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มสร้าง/แก้ไข วันหยุด
     *
     * @param object $index
     *
     * @return string
     */
    public function render($index)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/eleave/model/editworkdaymanagement/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of}{LNG_Workday Management}'
        ));
        // member_id
        $fieldset->add('text', array(
            'id' => 'member_id',
            'name' => 'member_id',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'item',
            'label' => '{LNG_Member_id}',
            'placeholder' => '{LNG_Member_id}',
            'maxlength' => 150,
            'value' => isset($index->member_id) ? $index->member_id : ''
        ));
        // year
        $fieldset->add('text', array(
            'id' => 'year',
            'name' => 'year',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'item',
            'label' => '{LNG_Year}',
            'placeholder' => '{LNG_Year}',
            'value' => isset($index->year) ? $index->year : ''
        ));
        // month
        $fieldset->add('select', array(
            'id' => 'month',
            'name' => 'month',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Month}',
            'options' => array(
                '' => '{LNG_Select month}',
                '01' => 'January',
                '02' => 'February',
                '03' => 'March',
                '04' => 'April',
                '05' => 'May',
                '06' => 'June',
                '07' => 'July',
                '08' => 'August',
                '09' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December'
            ),
            'value' => isset($index->month) ? $index->month : ''
        ));
        
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button ok large icon-save',
            'value' => '{LNG_Save}'
        ));
        // hidden id field
        if (isset($index->ID)) {
            $form->add('hidden', array(
                'id' => 'ID',
                'name' => 'ID',
                'value' => $index->ID
            ));
        }
        // คืนค่า HTML
        return $form->render();
    }
}
