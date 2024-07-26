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
         // URL สำหรับส่งให้ตาราง
         $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
 
         // สร้าง dropdown สำหรับเลือกปี
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
 
         // ตาราง
         $table = new DataTable(array(
             /* Uri */
             'uri' => $uri,
             /* Model */
             'model' => \Index\Holidays\Model::toDataTable($year),
             /* รายการต่อหน้า */
             'perPage' => $request->cookie('eleaveSetup_perPage', 30)->toInt(),
             /* เรียงลำดับ */
             'sort' => 'date',
             /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
             'onRow' => array($this, 'onRow'),
             /* คอลัมน์ที่ไม่ต้องแสดงผล */
             'hideColumns' => array('ID'),
             /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
             'action' => 'index.php/index/model/holidays/action',
             'actionCallback' => 'dataTableActionCallback',
             /* คอลัมน์ที่สามารถค้นหาได้ */
             'searchColumns' => array('id', 'date', 'description'),
             /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
             'headers' => array(
                 'sequence' => array(
                     'text' => '{LNG_Sequence}',
                     'class' => 'center'
                 ),
                 'ID' => array(
                     'text' => '{LNG_ID}'
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
             /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
             'cols' => array(
                 'sequence' => array(
                     'class' => 'center'
                 ),
                 'ID' => array(
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
             /* ปุ่มแสดงในแต่ละแถว */
             'buttons' => array(
                 'edit' => array(
                     'class' => 'icon-edit button green',
                     'href' => $uri->createBackUri(array('module' => 'index-editholidays', 'ID' => ':ID')),
                     'text' => '{LNG_Edit}'
                 ),
                 'delete' => array(
                     'class' => 'icon-delete button red',
                     'ID' => ':ID',
                     'text' => '{LNG_Delete}',
                     'data-confirm' => '{LNG_Are you sure you want to delete?}'
                 )
             ),
             /* ปุ่มเพิ่ม */
             'addNew' => array(
                 'class' => 'float_button icon-new',
                 'href' => $uri->createBackUri(array('module' => 'index-editholidays')),
                 'title' => '{LNG_Add} {LNG_Leave type}'
             ),
             /* ฟิลเตอร์ */
             'filters' => $filters
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
 