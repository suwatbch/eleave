<?php
/**
 * @filesource modules/index/views/register.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Register;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=register
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ลงทะเบียนสมาชิกใหม่
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // หมวดหมู่
        $category = \Index\Category\Model::init(false);
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/register/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_User}'
        ));
        $groups = $fieldset->add('groups');
        // username
        $groups->add('text', array(
            'id' => 'register_username',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-user',
            'label' => '{LNG_Username}<em>*</em>',
            'comment' => '{LNG_Used for login}',
            'maxlength' => 8,
            'validator' => array('keyup,change', 'checkUsername')
        ));
        // name
        $groups->add('text', array(
            'id' => 'register_name',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'width50',
            'label' => '{LNG_Name}<em>*</em>',
            'placeholder' => '{LNG_Please fill in} {LNG_Name}'
        ));
        $groups = $fieldset->add('groups');
        // password
        $groups->add('password', array(
            'id' => 'register_password',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-password',
            'label' => '{LNG_Password}<em>*</em>',
            'comment' => '{LNG_Passwords must be at least four characters}',
            'maxlength' => 50,
            'showpassword' => true,
            'validator' => array('keyup,change', 'checkPassword')
        ));
        // repassword
        $groups->add('password', array(
            'id' => 'register_repassword',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-password',
            'label' => '{LNG_Confirm password}<em>*</em>',
            'comment' => '{LNG_Enter your password again}',
            'maxlength' => 50,
            'showpassword' => true,
            'validator' => array('keyup,change', 'checkPassword')
        ));
        $groups = $fieldset->add('groups');
        // กะการทำงาน
        $groups->add('select', array(
            'id' => 'register_shift_id',
            'labelClass' => 'g-input icon-verfied',
            'itemClass' => 'width50',
            'label' => 'กะทำงาน<em>*</em>',
            'options' => \Eleave\Leavetype\Model::getshifttype()->toshifttype(),
            'value' => 0
        ));
        // email
        $groups->add('text', array(
            'id' => 'register_email',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-email',
            'label' => '{LNG_Email}',
            'validator' => array('keyup,change', 'checkUsername', 'index.php/index/model/checker/username')
        ));
        $groups = $fieldset->add('groups');
        // ผู้อนุมัติ M1
        $groups->add('text', array(
            'id' => 'register_m1',
            'labelClass' => 'g-input icon-register',
            'itemClass' => 'width50',
            'maxlength' => 8,
            'label' => 'อนุมัติคนที่หนึ่ง<em>*</em>',
            'placeholder' => 'กรอกรหัสพนักงาน 8 หลัก'
        ));
        // ผู้อนุมัติ M2
        $groups->add('text', array(
            'id' => 'register_m2',
            'labelClass' => 'g-input icon-users',
            'itemClass' => 'width50',
            'maxlength' => 8,
            'label' => 'อนุมัติคนที่สอง',
            'placeholder' => 'กรอกรหัสพนักงาน 8 หลัก'
        ));
        // หมวดหมู่
        $a = 0;
        foreach ($category->items() as $k => $label) {
            if (in_array($k, self::$cfg->categories_multiple)) {
                $fieldset->add('checkboxgroups', array(
                    'id' => 'register_'.$k,
                    'itemClass' => 'item',
                    'label' => $category->name($k).'<em>*</em>',
                    'labelClass' => 'g-input icon-group',
                    'options' => $category->toSelect($k)
                ));
            } else {
                if ($a % 2 == 0) {
                    $groups = $fieldset->add('groups');
                }
                $a++;
                $groups->add('text', array(
                    'id' => 'register_'.$k,
                    'labelClass' => 'g-input icon-menus',
                    'itemClass' => 'width50',
                    'label' => $label.'<em>*</em>',
                    'datalist' => $category->toSelect($k),
                    'text' => true
                ));
            }
        }
        if ($a % 2 == 0) {
            $groups = $fieldset->add('groups');
        }
        // status
        $groups->add('select', array(
            'id' => 'register_status',
            'itemClass' => 'width50',
            'label' => '{LNG_Member status}<em>*</em>',
            'labelClass' => 'g-input icon-star0',
            'options' => self::$cfg->member_status,
            'value' => 0
        ));
        // permission
        $fieldset->add('checkboxgroups', array(
            'id' => 'register_permission',
            'itemClass' => 'item',
            'label' => '{LNG_Permission}',
            'labelClass' => 'g-input icon-list',
            'options' => \Gcms\Controller::getPermissions(),
            'value' => \Gcms\Controller::initModule([], 'newRegister')
        ));
        
        $quota_leave1 = 0;
        $quota_leave2 = 0;
        $quota_leave3 = 0;
        $quota_leave5 = 0;
        $quota_leave7 = 0;
        $quota_leave8 = 0;
        $leaveOption = \Index\register\Model::getAllLeave();
        foreach ($leaveOption as $item) {
            // ลาป่วย
            if ($item->id == 1) {
                $quota_leave1 = $item->num_days;
            } else if ($item->id == 2) {
                $quota_leave2 = $item->num_days;
            } else if ($item->id == 3) {
                $quota_leave3 = $item->num_days;
            } else if ($item->id == 5) {
                $quota_leave5 = $item->num_days;
            } else if ($item->id == 7) {
                $quota_leave7 = $item->num_days;
            } else if ($item->id == 8) {
                $quota_leave8 = $item->num_days;
            }
        }
        $groups = $fieldset->add('groups');
        $groups->add('text', array(
            'id' => 'register_quota_leave1',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width30',
            'label' => 'ลาป่วย<em>*</em>',
            'value' => isset($quota_leave1) ? $quota_leave1 : 0
        ));
        $groups->add('text', array(
            'id' => 'register_quota_leave2',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width30',
            'label' => 'ลากิจ<em>*</em>',
            'value' => isset($quota_leave2) ? $quota_leave2 : 0
        ));
        $groups->add('text', array(
            'id' => 'register_quota_leave3',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width30',
            'label' => 'ลาคลอดบุตร<em>*</em>',
            'value' => isset($quota_leave3) ? $quota_leave3 : 0
        ));
        $groups = $fieldset->add('groups');
        $groups->add('text', array(
            'id' => 'register_quota_leave5',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width30',
            'label' => 'ลาเข้ารับการตรวจเลือกทหาร<em>*</em>',
            'value' => isset($quota_leave5) ? $quota_leave5 : 0
        ));
        $groups->add('text', array(
            'id' => 'register_quota_leave7',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width30',
            'label' => 'ลาบวช<em>*</em>',
            'value' => isset($quota_leave7) ? $quota_leave7 : 0
        ));
        $groups->add('text', array(
            'id' => 'register_quota_leave8',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width30',
            'label' => 'ลาพักร้อน<em>*</em>',
            'value' => isset($quota_leave8) ? $quota_leave8 : 0
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-register',
            'value' => '{LNG_Register}'
        ));
        $fieldset->add('hidden', array(
            'id' => 'register_id',
            'value' => 0
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
