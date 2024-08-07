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
            'id' => 'editworkdaymanagement_frm',
            'class' => 'editworkdaymanagement_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/eleave/model/editworkdaymanagement/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_editworkdaymanagement}'
        ));
        // date
        $fieldset->add('member_id', array(
            'id' => 'member_id',
            'name' => 'member_id',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_member_id}',
            'value' => isset($index->member_id) ? $index->member_id : ''
        ));
        // yaer
        $fieldset->add('textarea', array(
            'id' => 'yaer',
            'name' => 'yaer',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_yaer}',
            'value' => isset($index->yaer) ? $index->yaer : ''
        ));
         // month
         $fieldset->add('textarea', array(
            'id' => 'month',
            'name' => 'month',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_month}',
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
        $form->add('hidden', array(
            'id' => 'ID',
            'name' => 'ID',
            'value' => $index->ID
        ));
        // คืนค่า HTML
        return $form->render();
    }
}