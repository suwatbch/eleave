<?php
/**
 * @filesource modules/index/views/editholidays.php
 */

namespace Index\Editholidays;

use Kotchasan\Html;

/**
 * module=index-editholidays
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
            'action' => 'index.php/index/model/editholidays/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of}{LNG_Holiday}'
        ));
        // date
        $fieldset->add('date', array(
            'id' => 'date',
            'name' => 'date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Date}',
            'value' => isset($index->date) ? $index->date : ''
        ));
        // description
        $fieldset->add('textarea', array(
            'id' => 'description',
            'name' => 'description',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Description}',
            'rows' => 5,
            'value' => isset($index->description) ? $index->description : ''
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
            'id' => 'id',
            'name' => 'id',
            'value' => $index->id
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
