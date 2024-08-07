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
      *
      * @return string
      */
      public function render(Request $request)
      {
          $this->publisheds = Language::get('PUBLISHEDS');
          $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
          $years = array();
          $currentYear = (int)date('Y');
          for ($i = $currentYear - 10; $i <= $currentYear + 10; $i++) {
              $years[$i] = $i;
          }
          $year = $request->request('year', $currentYear)->toInt();
          $filters = array(
              array(
                  'name' => 'year',
                  'text' => '{LNG_Year}',
                  'options' => $years,
                  'value' => $year
              )
          );
          $table = new DataTable(array(
              'uri' => $uri,
              'model' => \Index\Holidays\Model::toDataTable($year),
              'perPage' => $request->cookie('eleaveSetup_perPage', 30)->toInt(),
            // เรียงลำดับ
              'sort' => 'date',
              'onRow' => array($this, 'onRow'),
              'hideColumns' => array('id'),
            //   ลบ
              'action' => 'index.php/index/model/holidays/action',
              'actionCallback' => 'dataTableActionCallback',
              'searchColumns' => array('ID', 'date', 'description'),
              'headers' => array(
                  'sequence' => array(
                      'text' => '{LNG_Sequence}',
                      'class' => 'center'
                  ),
                  'id' => array(
                      'text' => '{LNG_id}'
                  ),
                  'date' => array(
                      'text' => '{LNG_date}',
                      'class' => 'center'
                  ),
                  'description' => array(
                      'text' => '{LNG_description}',
                      'class' => 'center'
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
                  'date' => array(
                      'class' => 'center'
                  ),
                  'description' => array(
                      'class' => 'center'
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
                      'text' => '{LNG_Delete}',
                      'data-confirm' => '{LNG_Are you sure?}'
                  )
              ),
              'addNew' => array(
                  'class' => 'float_button icon-new',
                //   'href' => $uri->createBackUri(array('module' => 'index-editholidays')),
                    'href' => $uri->createBackUri(array('module' => 'index-editholidays', 'id' => ':id')),
                  'title' => '{LNG_Add} {LNG_Leave type}'
              ),
              'filters' => $filters,
              
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
         $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
         return $item;
     }
 }
 