<?php
/**
 * @filesource modules/eleave/views/workdaymanagement.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Workdaymanagement;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-workdaymanagement
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
     * รายการประเภทการลา
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
            'model' => \Eleave\workdaymanagement\Model::toDataTable(),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('eleaveSetup_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => ['month', 'year'],
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/eleave/model/workdaymanagement/action',
            'actionCallback' => 'dataTableActionCallback',
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('topic', 'document_no'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'member_id' => array(
                    'class' => 'center',
                    'text' => '{LNG_member_id}'
                ),
                'yaer' => array(
                    'text' => '{LNG_yaer}',
                    'class' => 'center'
                ),
                'month' => array(
                    'text' => '{LNG_month}',
                    'class' => 'center'
                ),
                'published' => array(
                    'text' => ''
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'member_id' => array(
                    'class' => 'center'
                ),
                'yaer' => array(
                    'class' => 'center'
                ),
                'month' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'eleave-workdaymanagement', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                ),
                'delete' => array(
                    'class' => 'icon-delete button red',
                    'id' => ':id',
                    'text' => '{LNG_Delete}',
                    'data-confirm' => '{LNG_Are you sure you want to delete?}'
                )
            ),
            /* ปุ่มเพิ่ม */
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'eleave-workdaymanagement')),
                'title' => '{LNG_Add} {LNG_Leave type}'
            ),              
        ));
        // save cookie
        setcookie('eleaveSetup_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
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
        $item['num_days'] = $item['num_days'] == 0 ? '{LNG_Unlimited}' : $item['num_days'];
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
