<?php

/**
 * @filesource modules/index/views/holidays.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Holidays;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Html;

/**
 * module=index-holidays
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
     * รายการวันหยุด
     *
     * @param Request $request
     * @param array $params
     * @return string
     */
    public function render(Request $request, $params)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        $uri = $request->createUriWithGlobals(WEB_URL . 'index.php');

        $year = $request->request('year')->toInt();
        if ($year == 0) {
            $addNew = array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'index-editholidays', 'id' => ':id', 'year' => $params['year']))
            );
        } else {
            $addNew = array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'index-editholidays', 'id' => ':id'))
            );
        }
        
        $table = new DataTable(array(
            'uri' => $uri,
            'model' => \Index\Holidays\Model::toDataTable($params),
            'perPage' => $request->cookie('eleaveSetup_perPage', 30)->toInt(),
            // เรียงลำดับ
            'sort' => 'holidays',
            'onRow' => array($this, 'onRow'),
            'hideColumns' => array('id'),
            //   ลบ
            'action' => 'index.php/index/model/holidays/action',
            'actionCallback' => 'dataTableActionCallback',
            'searchColumns' => array('year', 'holidays', 'description'),
            'filters' => array(
                array(
                    'name' => 'year',
                    'text' => '{LNG_Year}',
                    'options' => $params['years'],
                    'value' =>  $params['year']
                )
            ),
            'headers' => array(
                'sequence' => array(
                    'text' => '{LNG_Sequence}',
                    'class' => 'center'
                ),
                'id' => array(
                    'text' => '{LNG_id}'
                ),
                'holidays' => array(
                    'text' => '{LNG_Holidays}',
                    'class' => 'center'
                ),
                'description' => array(
                    'text' => '{LNG_Description}',
                    'class' => 'left'
                ),
                'published' => array(
                    'text' => ''
                )
            ),
            'cols' => array(
                'sequence' => array(
                    'class' => 'center'
                ),
                'id' => array(
                    'class' => 'center'
                ),
                'holidays' => array(
                    'class' => 'center'
                ),
                'description' => array(
                    'class' => 'left'
                ),
                'published' => array(
                    'class' => 'center'
                )
            ),
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'index-editholidays', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                ),
                'delete' => array(
                    'class' => 'icon-delete button red',
                    'id' => ':id',
                    'text' => '{LNG_Delete}'
                )
            ),
            'addNew' => $addNew,
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
        $item['sequence'] = $o + 1; // เพิ่มคอลัมน์ลำดับ
        $item['num_days'] = $item['num_days'] == 0 ? '{LNG_Unlimited}' : $item['num_days'];
        $item['published'] = '<a id=published_' . $item['id'] . ' class="icon-published' . $item['published'] . '" title="' . $this->publisheds[$item['published']] . '"></a>';
        return $item;
    }
}
