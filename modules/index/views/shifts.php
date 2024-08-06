<?php
/**
 * @filesource modules/index/view/shifts.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Shifts;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=index-shifts
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var array
     */
    private $publisheds;
    /**
     * จัดการกะการทำงาน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Index\Shifts\Model::toDataTable(),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('shift_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => 'id ASC',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            // 'hideColumns' => array('id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/models/shifts/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}'
                    ) 
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'sequence' => array(
                     'text' => '{LNG_Sequence}',
                     'class' => 'center'
                 ),
                'id' => array(
                    'text' => '{LNG_Id}',
                    'class' => 'center'
                ),
                'name' => array(
                    'text' => '{LNG_Shift name}',
                    'class' => 'center'
                ),
                'static' => array(
                    'text' => '{LNG_Status}',
                    'class' => 'center'
                ),
                'start_time' => array(
                    'text' => '{LNG_Start time}',
                    'class' => 'center'
                ),
                'end_time' => array(
                    'text' => '{LNG_End time}',
                    'class' => 'center'
                ),
                'start_break_time' => array(
                    'text' => '{LNG_Start break time}',
                    'class' => 'center'
                ),
                'end_break_time' => array(
                    'text' => '{LNG_End break time}',
                    'class' => 'center'
                ),
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'id' => array(
                    'class' => 'center'
                ),
                'name' => array(
                    'class' => 'center'
                ),
                'static' => array(
                    'class' => 'center'
                ), 
                'start_time' => array(
                    'class' => 'center'
                ),
                'end_time' => array(
                    'class' => 'center'
                ),
                'start_break_time' => array(
                    'class' => 'center'
                ),
                'end_break_time' => array(
                    'class' => 'center'
                ),
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'index-shiftedit', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            ),
            /* ปุ่มเพิ่ม */
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'index-shiftedit')),
                'title' => '{LNG_Add} {LNG_Manage shift}'
            )
        ));
        // save cookie
        setcookie('shift_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTMLๆๆๆ
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว.
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $item['static'] = $item['static'] == 1 ? '{LNG_Fixed}' : '{LNG_Rotating}';
        return $item;
    }
}