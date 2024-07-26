<?php
/**
 * @filesource modules/eleave/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Home;

use Gcms\Login;
use Kotchasan\Database\Sql;

/**
 * โมเดลสำหรับอ่านข้อมูลแสดงในหน้า  Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * คืนค่าจำนวนการลาแยกแต่ละสถานะ
     * ถ้า login เป็นผู้อนุมัติคืนค่ารายการที่เกี่ยวข้อง
     * ถ้าไม่ใช่คืนค่าประวัติของตัวเอง
     *
     * @param array $login
     * @param array $fiscalyear
     *
     * @return object
     */
    public static function get($login, $fiscalyear)
    {
        $setsionlogin = $_SESSION['login'];
        $qs = [];
        if (Login::checkPermission($login, 'can_approve_eleave')) {
            $status = 0;
            if ($setsionlogin['status'] != 1) {
                $where = array(
                    array('status', $status),
                    array('start_date', '>=', $fiscalyear['from']),
                    array('start_date', '<=', $fiscalyear['to'])
                );
                $sql = "(`member_id_m1`='$setsionlogin[id]' AND `status_m1`='$status')";
                $sql .= " OR (`member_id_m2`='$setsionlogin[id]' AND `status_m2`='$status' AND `status_m1`>'$status' AND `status`='$status')";
                $where[] = Sql::create($sql);
                $qs[] = static::createQuery()
                ->select('0 type', 'status', 'SQL(COUNT(id) AS count)')
                ->from('leave_items')
                ->where($where);
            } else {
                $qs[] = static::createQuery()
                ->select('0 type', 'status', 'SQL(COUNT(id) AS count)')
                ->from('leave_items')
                ->where(array(
                    array('status', $status),
                    array('start_date', '>=', $fiscalyear['from']),
                    array('start_date', '<=', $fiscalyear['to'])
                ));
            }
        }
        $qs[] = static::createQuery()
            ->select('1 type', 'status', 'SQL(COUNT(id) AS count)')
            ->from('leave_items')
            ->where(array(
                array('member_id', $login['id']),
                array('start_date', '>=', $fiscalyear['from']),
                array('start_date', '<=', $fiscalyear['to'])
            ))
            ->groupBy('status');
        $query = static::createQuery()
            ->select()
            ->unionAll($qs)
            ->cacheOn();
        $result = [];
        foreach ($query->execute() as $item) {
            $result[$item->type][$item->status] = $item->count;
        }
        return $result;
    }
}
