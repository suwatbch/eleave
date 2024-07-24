<?php
/**
 * @filesource modules/index/model/manageshifts.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Manageshifts;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Database\Query;
use Kotchasan\DataTable;



/**
 * module=shift-setup
 *
 * @since 1.0
 */
class Model  extends \Kotchasan\Model 
{
    /**
     * Query ข้อมูลสำหรับแสดงในตาราง
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public function getAllShifts()
    {
        return static::createQuery()->from('app_shifts')->execute();
    }

    public static function saveShift($data)
    {
        // Validate and save shift data to database
        // $db = Sql::create();
        $model = new static();
        if (isset($data['id'])) {
            $model->db()->update($model->getTableName('app_shifts'), $data['id'], $data); // ปรับให้ใช้ id เป็นคีย์หลัก
        } else {
            $model->db()->insert($model->getTableName('app_shifts'), $data);
        }
    }

    public static function deleteShift($shiftId)
    {
        // Delete shift from database
        // $db = Sql::create();
        // $db->delete('app_shifts', array('id' => $shiftId));
        $model = new static();
        $model->db()->delete($model->getTableName('app_shifts'), $shiftId);
    }

}
    $model = new Model();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ดึงข้อมูลแบบฟอร์ม
        $shiftData = array(
            'name' => $_POST['name'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'start_break_time' => $_POST['start_break_time'],
            'end_break_time' => $_POST['end_break_time'],
            'status' => $_POST['status']
        );
        // Save shift data
        $model->saveShift($shiftData);

        header('Location: manageshifts_list.php');
        exit;

}