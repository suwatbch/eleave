<?php
/**
 * @filesource modules/eleave/controllers/manageshifts_export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Manageshifts_export;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

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
        // ตั้งค่า title ของหน้า
        $this->title = Language::trans('{LNG_List of} {LNG_Shifts}');
        
        // กำหนดเมนูที่เลือกอยู่
        $this->menu = 'eleave';
        
        // สามารถจัดการโมดูลได้
        if (Login::checkPermission(Login::isMember(), 'can_manage_eleave')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-verfied">{LNG_Settings}</span></li>');
            $ul->appendChild('<li><span>{LNG_E-Leave}</span></li>');
            $ul->appendChild('<li><span>{LNG_Shifts}</span></li>');
            $section->add('header', array('innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'));
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'settings', 'eleave'));
            $div = $section->add('div', array('class' => 'content_bg'));
            // ตาราง
            $div->appendChild(\Eleave\Manageshifts_export\View::create()->render($request));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
