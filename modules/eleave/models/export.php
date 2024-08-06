<?php
/**
 * @filesource modules/eleave/models/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Export;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * export.php?module=eleave-export&typ=csv|print
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ส่งออกข้อมูล CSV
     *
     * @param array $params
     *
     * @return array
     */
    public static function csv($params)
    {
        // $where = [];
        // if ($params['status'] == -1) {
        //     $where = array(
        //         array('F.status','<=', 2)
        //     );
        // } else {
        //     $statusIn = $params['status'] == 1 ? [1,3] : [4];
        //     $where = array(
        //         array('F.status', 'IN', $statusIn)
        //     );
        //     if ($params['status'] == 1){
        //         $where[] = array('F.isexport', 0);
        //     } else if ($params['status'] == 4){
        //         $where[] = array('F.iscancel', 0);
        //         $where[] = array('F.cancel_date', '!=', NULL);
        //     }
        // }
        
        // if (!empty($params['department'])) {
        //     $where[] = array('F.department', $params['department']);
        // }
        // if (!empty($params['member_id'])) {
        //     $where[] = array('F.member_id', $params['member_id']);
        // }
        // if (!empty($params['leave_id'])) {
        //     $where[] = array('F.leave_id', $params['leave_id']);
        // }
        // if (!empty($params['from']) || !empty($params['to'])) {
        //     if (empty($params['to'])) {
        //         $sql = "(F.`start_date`>='$params[from]')";
        //         $sql .= " OR ('$params[from]' BETWEEN F.`start_date` AND F.`end_date`)";
        //     } elseif (empty($params['from'])) {
        //         $sql = "(F.`start_date`<='$params[to]')";
        //         $sql .= " OR ('$params[to]' BETWEEN F.`start_date` AND F.`end_date`)";
        //     } else {
        //         $sql = "(F.`start_date`>='$params[from]' AND F.`start_date`<='$params[to]')";
        //         $sql .= " OR ('$params[from]' BETWEEN F.`start_date` AND F.`end_date` AND '$params[to]' BETWEEN F.`start_date` AND F.`end_date`)";
        //     }
        //     $where[] = Sql::create($sql);
        // }
        // return \Kotchasan\Model::createQuery()
        //     ->select('F.id', 
        //             'F.create_date', 
        //             'U.username', 
        //             'U.name', 
        //             'L.topic',
        //             'F.leave_id', 
        //             'F.start_date',
        //             'F.start_time',
        //             'F.start_period',
        //             'F.days', 
        //             'F.times', 
        //             'F.end_date', 
        //             'F.end_time', 
        //             'F.end_period', 
        //             'F.member_id', 
        //             'F.communication', 
        //             'C.topic as department',
        //             'F.detail',
        //             'F.status',
        //             'F.reason')
        //     ->from('leave_items F')
        //     ->join('user U', 'LEFT', array('U.id', 'F.member_id'))
        //     ->join('leave L', 'LEFT', array('L.id', 'F.leave_id'))
        //     ->join('category C', 'LEFT', array('C.category_id', 'F.department'))
        //     ->where($where)
        //     ->order($params['sort'])
        //     ->cacheOn()
        //     ->execute();

        $qs = [];
        // อนุมัติ รออนุมัติยกเลิก
        $qs[] = static::createQuery()
                ->select('F.id', 
                        'F.create_date', 
                        'U.username', 
                        'U.name', 
                        'L.topic',
                        'F.leave_id', 
                        'F.start_date',
                        'F.start_time',
                        'F.start_period',
                        'F.days', 
                        'F.times', 
                        'F.end_date', 
                        'F.end_time', 
                        'F.end_period', 
                        'F.member_id', 
                        'F.communication', 
                        'C.topic as department',
                        'F.detail',
                        'F.status',
                        'F.reason',
                        'F.cancel_date')
                ->from('leave_items F')
                ->join('user U', 'LEFT', array('U.id', 'F.member_id'))
                ->join('leave L', 'LEFT', array('L.id', 'F.leave_id'))
                ->join('category C', 'LEFT', array('C.category_id', 'F.department'))
                ->where(array(
                    array('F.start_date', '>=', $params['from']),
                    array('F.start_date', '<=', $params['to']),
                    array('F.status', 'IN', [1,3]),
                    array('F.isexport', 0),
                    array('F.iscancel', 0)
                ))
                ->order($params['sort']);
        // ยกเลิก
        $qs[] = static::createQuery()
                ->select('F.id', 
                        'F.create_date', 
                        'U.username', 
                        'U.name', 
                        'L.topic',
                        'F.leave_id', 
                        'F.start_date',
                        'F.start_time',
                        'F.start_period',
                        'F.days', 
                        'F.times', 
                        'F.end_date', 
                        'F.end_time', 
                        'F.end_period', 
                        'F.member_id', 
                        'F.communication', 
                        'C.topic as department',
                        'F.detail',
                        'F.status',
                        'F.reason',
                        'F.cancel_date')
                ->from('leave_items F')
                ->join('user U', 'LEFT', array('U.id', 'F.member_id'))
                ->join('leave L', 'LEFT', array('L.id', 'F.leave_id'))
                ->join('category C', 'LEFT', array('C.category_id', 'F.department'))
                ->where(array(
                    array('F.cancel_date', '>=', $params['from']),
                    array('F.cancel_date', '<=', $params['to']),
                    array('F.cancel_date', '!=', NULL),
                    array('F.status', 4),
                    array('F.isexport', 1),
                    array('F.iscancel', 0)
                ))
                ->order($params['sort']);
        return static::createQuery()
                ->select()
                ->unionAll($qs)
                ->order($params['sort'])
                ->cacheOn()
                ->execute();
    }
}
