<?php
/**
 * @filesource modules/eleave/views/manageshifts_export.php
 *
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Manageshifts_export;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-manageshifts_export
 */
class View extends \Gcms\View
{
    /**
     * @var array
     */
    private $publisheds;
    /**
     * Render the shift management table
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        // URL for the table
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // DataTable
        $table = new DataTable(array(
            'uri' => $uri,
            'model' => \Eleave\Manageshifts_export\Model::toDataTable(),
            'perPage' => $request->cookie('eleaveSetup_perPage', 30)->toInt(),
            'sort' => 'id ASC',
            'onRow' => array($this, 'onRow'),
            'hideColumns' => array('id'),
            'action' => 'index.php/eleave/model/manageshifts_export/action',
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
            'searchColumns' => array('shift_name', 'shift_type'),
            'headers' => array(
                'shift_name' => array(
                    'text' => '{LNG_Shift Name}'
                ),
                'shift_type' => array(
                    'text' => '{LNG_Shift Type}',
                    'class' => 'center'
                ),
                'start_time' => array(
                    'text' => '{LNG_Start Time}',
                    'class' => 'center'
                ),
                'end_time' => array(
                    'text' => '{LNG_End Time}',
                    'class' => 'center'
                ),
                'break_start' => array(
                    'text' => '{LNG_Break Start}',
                    'class' => 'center'
                ),
                'break_end' => array(
                    'text' => '{LNG_Break End}',
                    'class' => 'center'
                ),
                'workdays' => array(
                    'text' => '{LNG_Work Days}',
                    'class' => 'center'
                ),
                'published' => array(
                    'text' => ''
                )
            ),
            'cols' => array(
                'shift_type' => array(
                    'class' => 'center'
                ),
                'start_time' => array(
                    'class' => 'center'
                ),
                'end_time' => array(
                    'class' => 'center'
                ),
                'break_start' => array(
                    'class' => 'center'
                ),
                'break_end' => array(
                    'class' => 'center'
                ),
                'workdays' => array(
                    'class' => 'center'
                ),
                'published' => array(
                    'class' => 'center'
                )
            ),
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'eleave-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            ),
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'eleave-write')),
                'title' => '{LNG_Add} {LNG_Shift}'
            )
        ));
        // save cookie
        setcookie('eleaveSetup_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        // return HTML
        return $table->render();
    }

    /**
     * Format each row in the DataTable.
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
