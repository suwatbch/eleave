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

class View extends \Gcms\View
{
    private $publisheds;

    public function render(Request $request)
    {
        $this->publisheds = Language::get('PUBLISHEDS');

        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');

        $table = new DataTable(array(
            'uri' => $uri,
            'model' => \Eleave\Workdaymanagement\Model::toDataTable(),
            'perPage' => $request->cookie('eleaveSetup_perPage', 30)->toInt(),
            'sort' => ['month', 'year'],
            'onRow' => array($this, 'onRow'),
            'hideColumns' => array('id', 'days'),
            'action' => 'index.php/eleave/model/workdaymanagement/action',
            'actionCallback' => 'dataTableActionCallback',
            'searchColumns' => array('member_id', 'name', 'year', 'month'),
            'headers' => array(
                'member_id' => array(
                    'class' => 'center',
                    'text' => '{LNG_member_id}'
                ),
                'name' => array(
                    'text' => '{LNG_Name}',
                    'class' => 'center'
                ),
                'month' => array(
                    'text' => '{LNG_month}',
                    'class' => 'center'
                ),
                'business_days' => array(  //คอลัมน์ business_days
                    'text' => '{LNG_Business Days}',
                    'class' => 'center'
                ),
                'published' => array(
                    'text' => ''
                )
            ),
            'cols' => array(
                'member_id' => array(
                    'class' => 'center'
                ),
                'name' => array(
                    'class' => 'center'
                ),
                'month' => array(
                    'class' => 'center'
                ),
                'business_days' => array(  //คอลัมน์ business_days
                    'class' => 'center'
                )
            ),
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'eleave-editworkdaymanagement', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                ),
                'delete' => array(
                    'class' => 'icon-delete button red',
                    'id' => ':id',
                    'text' => '{LNG_Delete}',
                    'data-confirm' => '{LNG_Are you sure you want to delete?}'
                )
            ),
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'eleave-editworkdaymanagement')),
                'title' => '{LNG_Add} {LNG_Workday}'
            ),
        ));

        setcookie('eleaveSetup_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);

        return $table->render();
    }

    public function onRow($item, $o, $prop)
    {
        // แปลง days เป็นรูปแบบจำนวนวัน
        $daysArray = json_decode($item['days'], true); // แปลง JSON เป็น array
        $numberOfBusinessDays = is_array($daysArray) ? count($daysArray) : 0;
        $item['business_days'] = $numberOfBusinessDays . ' วัน';

        // แปลง published icon
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
