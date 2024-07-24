<?php
/**
 * @filesource modules/Index/controllers/manageshifts.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Manageshifts;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Language;
// use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
// use Kotchasan\Http\Response;

/**
 * module=shifts-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * การจัดการกะการทำงาน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ตรวจสอบการเข้าระบบ
        if ($login = Login::isMember()) {
        // if ($login = Login::isMember()) {
        //     $view = new \Kotchasan\View();
        //     $view->setContents(array(
        //         '/{MAIN}/' => \Index\Manageshifts\View::create()->render($request)
        //     ));
        //     return $view->renderHTML();
        // }
        // ตั้งค่า title ของหน้า
        // $this->title = Language::trans('{LNG_List of} {LNG_Manage Shifts}');
        $this->title = Language::trans('{LNG_Manage shifts}');
        
        // เลือกเมนู
        $this->menu = 'settings';
        
        // สามารถจัดการโมดูลได้
        if (Login::checkPermission(Login::isMember(), 'can_manage_shifts')) {
            // แสดงผล
            $section = Html::create('section', array(
                'class' => 'content_bg',));
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'));
            // $ul = $breadcrumbs->add('ul');
            // $ul->appendChild('<li><span class="icon-verfied">{LNG_Settings}</span></li>');
            // $ul->appendChild('<li><span>{LNG_E-Leave}</span></li>');
            // $ul->appendChild('<li><span>{LNG_Shifts}</span></li>');
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-home">Home</span></li>');
            $ul->appendChild('<li><span>{LNG_Settings}</span></li>');
            $ul->appendChild('<li><span>{LNG_Manage shifts}</span></li>');

            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-calendar">'.$this->title.'</h2>'));
            
                // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'settings', 'eleave'));
            // ตาราง
            $div = $section->add('div', array('class' => 'content_bg'));
            $div->appendChild(\Index\Manageshifts\View::create()->render($request));
            
            // คืนค่า HTML
            return $section->render();
        }
    }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}