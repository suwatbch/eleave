<?php
/**
 * @filesource modules/index/controllers/editholidays.php
 */

namespace Index\Editholidays;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=index-editholidays
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข วันหยุด
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ตรวจสอบรายการที่เลือก
        $index = \Index\Editholidays\Model::get($request->request('id')->toInt());
        // ข้อความ title bar
        $title = '{LNG_'.(empty($index->id) ? 'Add' : 'Edit').'}';
        $this->title = Language::trans($title.'{LNG_Holiday}');
        // เลือกเมนู
        $this->menu = 'eleave';

        // สามารถจัดการโมดูลได้
        if ($index && Login::checkPermission(Login::isMember(), 'can_manage_eleave')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            // $ul->appendChild('<li><span class="icon-verfied">{LNG_E-Leave}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=index-holidays}">{LNG_Holiday}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Index\Editholidays\View::create()->render($index));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
