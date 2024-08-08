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
 * @autor Goragod Wiriya <admin@goragod.com>
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
        // สมาชิก
        if ($login = Login::isMember()) {
            // ตรวจสอบสิทธิ์ในการเข้าถึง (ถ้ามี)
            if (Login::checkPermission($login, 'can_view_report')) {
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('nav', array(
                    'class' => 'breadcrumbs'
                ));
              
                $div = $section->add('div', array(
                    'class' => 'content_bg'
                ));
                // menu
             
                $div->appendChild(\Eleave\Workdaymanagement\Controller::create()->render($request));
                // คืนค่า HTML
                return $section->render();
            } else {
                // ไม่มีสิทธิ์ในการเข้าถึง
                return \Index\Error\Controller::execute($this, $request->getUri());
            }
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
