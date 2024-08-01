<?php
/**
 * @filesource modules/eleave/views/approve.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Approve;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=eleave-approve
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แบบฟอร์มขอลา
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        // แอดมินสามารถแก้ไขได้
        $notEdit = Login::isAdmin() ? false : true;
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/eleave/model/approve/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $name = \Eleave\Approve\Model::get($index->id);
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of request for leave} '.$name->name
        ));
        $fieldset->add('hidden', array(
            'id' => 'member_id',
            'value' => $index->member_id
        ));
        $fieldset->add('hidden', array(
            'id' => 'shift_id',
            'value' => $index->shift_id
        ));
        // leave_id
        $fieldset->add('select', array(
            'id' => 'leave_id',
            'labelClass' => 'g-input icon-verfied',
            'itemClass' => 'item',
            'label' => '{LNG_Leave type}',
            'options' => \Eleave\Leavetype\Model::init()->toSelect(),
            'disabled' => $notEdit,
            'value' => $index->leave_id
        ));
        $fieldset->add('div', array(
            'id' => 'leave_detail',
            'class' => 'subitem message margin-bottom'
        ));
        $category = \Eleave\Category\Model::init();
        foreach ($category->items() as $k => $label) {
            $fieldset->add('select', array(
                'id' => $k,
                'labelClass' => 'g-input icon-valid',
                'itemClass' => 'item',
                'label' => $label,
                'options' => $category->toSelect($k),
                'disabled' => $notEdit,
                'value' => $index->{$k}
            ));
        }
        // รูปแบบการลา start_period
        $leave_period = Language::get('LEAVE_PERIOD');
        $fieldset->add('select', array(
            'id' => 'start_period',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'item',
            'label' => '{LNG_Leave type}',
            'options' => $leave_period,
            'disabled' => $notEdit,
            'value' => $index->start_period
        ));
        $groups = $fieldset->add('groups');
        // start_date
        $groups->add('date', array(
            'id' => 'start_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width50',
            'label' => '{LNG_Start date}',
            'disabled' => $notEdit,
            'value' => $index->start_date
        ));
        // เก็บข้อมูลวันที่เก่าซ่อนไว้
        $fieldset->add('hidden', array(
            'id' => 'last_start_date',
            'value' => $index->last_start_date
        ));
        // อัปเดตตัวแปร $time_ent ด้วยค่าใหม่
        $leave_time = \Eleave\Leave\Model::getTime0fShift($index->shift_id,$index->member_id);
        $time_stt = $leave_time;
        $time_ent = $leave_time;
        if (count($leave_time) != 48) {
            array_pop($time_stt);
            array_shift($time_ent);
        }

        // เพิ่ม "00:00" 
        $adddata = array("00:00" => "");
        foreach ($adddata as $key => $value){
            $time_stt = array($key => $value) +$time_stt;
            $time_ent = array($key => $value) +$time_ent;
        }
        // เวลาเริ่มต้น
        $groups->add('select', array(
            'id' => 'start_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width25',
            'label' => '{LNG_Start time}',
            'options' => $time_stt,
            'disabled' => $notEdit,
            'value' => $index->start_time
        ));
        // เวลาสิ้นสุด
        $groups->add('select', array(
            'id' => 'end_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width25',
            'label' => '{LNG_End time}',
            'options' => $time_ent,
            'disabled' => $notEdit,
            'value' => $index->end_time
        ));
        $groups = $fieldset->add('groups');
        // end_date
        $fieldset->add('date', array(
            'id' => 'end_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_End date}',
            'disabled' => $notEdit,
            'value' => $index->end_date
        ));
        // แจ้งเตือนข้อมูลลา
        $fieldset->add('text', array(
            'id' => 'textalert',
            'labelClass' => 'g-input icon-email',
            'itemClass' => 'item',
            'label' => '{LNG_Total number of leave this time}',
            'comment' => '<em>{LNG_Check the accuracy of leave}</em>',
            'disabled' => true,
            'value' => '<em>'.$index->textalert.'</em>'
        ));
        // id กะหมุนเวียน
        $fieldset->add('hidden', array(
            'id' => 'cal_shift_id',
            'value' => $index->cal_shift_id
        ));
        // สนานะหลังจากคำนวณ
        $fieldset->add('hidden', array(
            'id' => 'cal_status',
            'value' => $index->cal_status
        ));
        // เก็บวันที่คำนวณได้
        $fieldset->add('hidden', array(
            'id' => 'cal_days',
            'value' => $index->cal_days
        ));
        // เก็บเวลาที่คำนวณได้
        $fieldset->add('hidden', array(
            'id' => 'cal_times',
            'value' => $index->cal_times
        ));
        if (!$notEdit) {
            // file eleave
            $fieldset->add('file', array(
                'id' => 'eleave',
                'name' => 'eleave[]',
                'labelClass' => 'g-input icon-upload',
                'itemClass' => 'item',
                'label' => '{LNG_Attached file}',
                'comment' => '{LNG_Upload :type files} {LNG_no larger than :size} ({LNG_Can select multiple files})',
                'accept' => self::$cfg->eleave_file_typies,
                'dataPreview' => 'filePreview',
                'multiple' => true
            ));
        }
        $fieldset->appendChild('<div class="item">'.\Download\Index\Controller::init($index->id, 'eleave', self::$cfg->eleave_file_typies, $login['id']).'</div>');
        // detail
        $fieldset->add('textarea', array(
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Detail}/{LNG_Reasons for leave}',
            'rows' => 5,
            'disabled' => $notEdit,
            'value' => $index->detail
        ));
        // communication
        $fieldset->add('textarea', array(
            'id' => 'communication',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'item',
            'label' => '{LNG_Communication}',
            'rows' => 3,
            'disabled' => $notEdit,
            'value' => $index->communication
        ));
        $statustemp = Language::get('LEAVE_STATUS');
        $status = [];
        $isDisabled = false;
        if ($index->status == 4) { $statuskey = [4]; $isDisabled = true; }
        else if ($index->status == 3) { $statuskey = [1, 2, 3]; }
        else { $statuskey = [0, 1, 2]; }
        foreach ($statustemp as $key => $value) {
            if (in_array($key, $statuskey)) {
                $status[$key] = $value;
            }
        }
        $indexstatus = $index->status;
        if ($index->status != 0 && $index->status != 3 && $index->status != 4) {
            $status = array_splice($status,1,2);
            $indexstatus -= 1;
        }
        // status
        $fieldset->add('select', array(
            'id' => 'status',
            'labelClass' => 'g-input icon-star0',
            'itemClass' => 'item',
            'label' => '{LNG_Status}',
            'options' => $status ,
            'value' => $indexstatus,
            'disabled' => $isDisabled
        ));
        // reason
        $fieldset->add('text', array(
            'id' => 'reason',
            'labelClass' => 'g-input icon-comments',
            'itemClass' => 'item',
            'label' => '{LNG_Reason}',
            'maxlength' => 255,
            'value' => $index->reason,
            'disabled' => $isDisabled
        ));
        if ($index->status != 4) {
            $fieldset = $form->add('fieldset', array(
                'class' => 'submit'
            ));
            // submit
            $fieldset->add('submit', array(
                'class' => 'button ok large icon-save',
                'value' => '{LNG_Save}'
            ));
        }
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/:type/' => implode(', ', self::$cfg->eleave_file_typies),
            '/:size/' => Text::formatFileSize(self::$cfg->eleave_upload_size)
        ));
        // Javascript
        $form->script('initEleaveLeave();');
        // คืนค่า HTML
        return $form->render();
    }
}
