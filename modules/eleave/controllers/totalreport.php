<?php
/**
 * @filesource modules/eleave/controllers/totalreport.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Totalreport;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
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
     * แสดงรายการขอลา (admin)
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สมาชิก
        $login = Login::isMember();
        // สามารถอนุมัติได้
        if (Login::checkPermission($login, 'can_config')) {
            // ค่าที่ส่งมา
            $params = array(
                'from' => $request->request('from')->date(),
                'to' => $request->request('to')->date(),
                'leave_id' => $request->request('leave_id')->toInt(),
                'member_id' => $request->request('member_id')->toInt(),
                'status' => $request->request('status')->toInt(),
                'leave_status' => Language::get('LEAVE_STATUS')
            );
            //เพิ่มทั้งหมด
            $add = array(-1 => Language::get('all items'));
            foreach ($add as $key => $value){
                $params['leave_status'] = array($key => $value) + $params['leave_status'];
            }
            $params['status'] = isset($params['leave_status'][$params['status']]) ? $params['status'] : -1;
            // แสดงผล
            $section = Html::create('section');
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // ตาราง
            $div->appendChild(\Eleave\Totalreport\View::create()->render($request, $params, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
