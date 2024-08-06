<?php
/**
 * @filesource modules/index/controllers/shiftedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Shiftedit;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=index-shiftedit
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข ประเภทการลา
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ตรวจสอบรายการที่เลือก
        $index = \Index\Shiftedit\Model::get($request->request('id')->toInt());
        // ข้อความ title bar
        $title = '{LNG_'.(empty($index->id) ? 'Add' : 'Edit').'}';
        $this->title = Language::trans($title.' {LNG_Shift}');
        // เลือกเมนู
        $this->menu = 'shift';
        // สามารถจัดการโมดูลได้
        if ($index && Login::checkPermission(Login::isMember(), 'can_manage_shift')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-verfied">{LNG_Settings}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=index-shifts}">{LNG_Manage shift}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));

            // แสดงฟอร์ม
            $div->appendChild(\Index\Shiftedit\View::create()->render($index));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}