<?php
/**
 * @filesource modules/index/controllers/totalreport.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Workdaymanagement;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Http\Uri;
use Kotchasan\Language;

/**
 * module=totalreport
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายงาน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Total Report');
        // เลือกเมนู
        $this->menu = 'Workdaymanagement';
        // สมาชิก
        if ($login = Login::isMember()) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-menus">{LNG_Report}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-report">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // menu
            // $div->appendChild(\Index\Tabmenus\View::render($request, 'report', 'report'));
            $div->appendChild(\Eleave\Workdaymanagement\Controller::create()->render($request));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
