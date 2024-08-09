<?php
/**
 * @filesource modules/eleave/controllers/workdaymanagement.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Workdaymanagement;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

class Controller extends \Gcms\Controller
{
    public function render(Request $request)
    {
        // ตรวจสอบสิทธิ์การเข้าถึง
        if ($login = Login::isMember()) {
            // สร้าง Section
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            // หัวข้อ
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'
            ));
            // สร้าง div สำหรับเนื้อหา
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // ตาราง (เรียกใช้ View)
            $div->appendChild(\Eleave\Workdaymanagement\View::create()->render($request));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
