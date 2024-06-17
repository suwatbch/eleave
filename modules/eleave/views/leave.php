<?php
/**
 * @filesource modules/eleave/views/leave.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Leave;

use Kotchasan\Html;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=eleave-leave
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @param int $id
     * @return array
     */
    public static function getleave_id($id)
    {
        $periodM = Language::get('LEAVE_PERIOD');
        $period = $periodM;
        if ($id == 3 || $id == 7 || $id == 8) {
            unset($period[1]); 
            unset($period[2]);
        }
        return $period;
    }

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
        // ไม่สามารถแก้ไขได้
        $notEdit = !empty($index->status);
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/eleave/model/leave/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            // 'title' => '{LNG_Details of} {LNG_Request for leave} '.($index->id > 0 ? self::toStatus((array) $index, true) : '')
            'title' => '{LNG_Details of} {LNG_Request for leave} '.''  // แก้ไขเพิ่มเติม
        ));
        // leave_id
        $fieldset->add('select', array(
            'id' => 'leave_id',
            'labelClass' => 'g-input icon-verfied',
            'itemClass' => 'item',
            'label' => '{LNG_Leave type}',
            'options' => \Eleave\Leavetype\Model::init()->toSelect(),
            'disabled' => $notEdit,
            'value' => isset($index->leave_id) ? $index->leave_id : 0
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
                'options' => array('' => '{LNG_Please select}') + $category->toSelect($k),
                'disabled' => Language::get('CATEGORIES', '', $k) !== '',
                'value' => isset($index->{$k}) ? $index->{$k} : ''
            ));
        }
        // detail
        $fieldset->add('textarea', array(
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Detail}/{LNG_Reasons for leave}',
            'rows' => 5,
            'disabled' => $notEdit,
            'value' => isset($index->detail) ? $index->detail : ''
        ));
        // break time
        // $fieldset->add('select', array(
        //     'id' => 'break_id',
        //     'labelClass' => 'g-input icon-clock',
        //     'itemClass' => 'item',
        //     'label' => '{LNG_Break time}',
        //     'options' => \Eleave\Leavetype\Model::getbreak()->selectbreak(),
        //     'disabled' => $notEdit,
        //     'value' => isset($index->break_id) ? $index->break_id : 0
        // ));
        $groups = $fieldset->add('groups');
        // start_date
        $groups->add('date', array(
            'id' => 'start_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width25',
            'label' => '{LNG_Start date}',
            'comment' => '{LNG_Full day no need to choose a time}',
            'disabled' => $notEdit,
            'value' => isset($index->start_date) ? $index->start_date : date('Y-m-d')
        ));
        $leave_period = Language::get('LEAVE_PERIOD');
        // start_period
        $groups->add('select', array(
            'id' => 'start_period',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width25',
            'label' => '&nbsp;',
            'options' => $leave_period,
            'disabled' => $notEdit,
            'value' => isset($index->start_period) ? $index->start_period : 0
        ));
        $leave_hour = Language::get('LEAVE_HOUR');
        $leave_minutes = Language::get('LEAVE_MINUTES');
        $pAdd = array(-1 => Language::get('Select'));
        foreach ($pAdd as $key => $value){
            $leave_hour = array($key => $value) + $leave_hour;
            $leave_minutes = array($key => $value) + $leave_minutes;
        }
        $groups->add('select', array(
            'id' => 'start_hour',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width25',
            'label' => '&nbsp;',
            'options' => $leave_hour,
            'disabled' => $notEdit,
            'value' => isset($index->start_hour) ? $index->start_hour : -1
        ));
        $groups->add('select', array(
            'id' => 'start_minutes',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width25',
            'label' => '&nbsp;',
            'options' => $leave_minutes,
            'disabled' => $notEdit,
            'value' => isset($index->start_minutes) ? $index->start_minutes : -1
        ));
        $groups = $fieldset->add('groups');
        // end_date
        $groups->add('date', array(
            'id' => 'end_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width25',
            'label' => '{LNG_End date}',
            'comment' => '{LNG_Full day no need to choose a time}',
            'disabled' => $notEdit,
            'value' => isset($index->end_date) ? $index->end_date : date('Y-m-d')
        ));
        unset($leave_period[2]);
        // end_period
        $groups->add('select', array(
            'id' => 'end_period',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width25',
            'label' => '&nbsp;',
            'options' => $leave_period,
            'disabled' => $notEdit,
            'value' => isset($index->end_period) ? $index->end_period : 0
        ));
        $groups->add('select', array(
            'id' => 'end_hour',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width25',
            'label' => '&nbsp;',
            'options' => $leave_hour,
            'disabled' => $notEdit,
            'value' => isset($index->end_hour) ? $index->end_hour : -1
        ));
        $groups->add('select', array(
            'id' => 'end_minutes',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width25',
            'label' => '&nbsp;',
            'options' => $leave_minutes,
            'disabled' => $notEdit,
            'value' => isset($index->end_minutes) ? $index->end_minutes : -1
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
                'capture' => true,
                'dataPreview' => 'filePreview',
                'multiple' => true
            ));
        }
        if ($index->id > 0) {
            $fieldset->appendChild('<div class="item">'.\Download\Index\Controller::init($index->id, 'eleave', self::$cfg->eleave_file_typies, ($canEdit ? $login['id'] : 0)).'</div>');
        }
        // communication
        $fieldset->add('textarea', array(
            'id' => 'communication',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Communication}',
            'comment' => '{LNG_Contact information during leave}',
            'rows' => 3,
            'disabled' => $notEdit,
            'value' => isset($index->communication) ? $index->communication : ''
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        if (!$notEdit) {
            // submit
            $fieldset->add('submit', array(
                'class' => 'button ok large icon-save',
                'value' => '{LNG_Save}'
            ));
            // id
            $fieldset->add('hidden', array(
                'id' => 'id',
                'value' => $index->id
            ));
            \Gcms\Controller::$view->setContentsAfter(array(
                '/:type/' => implode(', ', self::$cfg->eleave_file_typies),
                '/:size/' => Text::formatFileSize(self::$cfg->eleave_upload_size)
            ));
        } else {
            // status
            $fieldset->add('select', array(
                'id' => 'status',
                'labelClass' => 'g-input icon-star0',
                'itemClass' => 'item',
                'label' => '{LNG_Status}',  
                'options' => Language::get('LEAVE_STATUS'),
                'value' => $index->status,
                'disabled' => true
            ));
            // reason
            $fieldset->add('text', array(
                'id' => 'reason',
                'labelClass' => 'g-input icon-comments',
                'itemClass' => 'item',
                'label' => '{LNG_Reason}',
                'maxlength' => 255,
                'value' => $index->reason,
                'disabled' => true
            ));
        }
        // Javascript
        $form->script('initEleaveLeave();');
        // คืนค่า HTML
        return $form->render();
    }
}
