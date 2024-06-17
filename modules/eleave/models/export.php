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
        $where = [];
        if ($params['status'] == -1) {
            $where = array(
                array('F.status','<=', 2)
            );
        } else {
            $where = array(
                array('F.status', $params['status'])
            );
        }
        if (!empty($params['department'])) {
            $where[] = array('F.department', $params['department']);
        }
        if (!empty($params['member_id'])) {
            $where[] = array('F.member_id', $params['member_id']);
        }
        if (!empty($params['leave_id'])) {
            $where[] = array('F.leave_id', $params['leave_id']);
        }
        if (!empty($params['from']) || !empty($params['to'])) {
            if (empty($params['to'])) {
                $sql = "(F.`start_date`>='$params[from]')";
                $sql .= " OR ('$params[from]' BETWEEN F.`start_date` AND F.`end_date`)";
            } elseif (empty($params['from'])) {
                $sql = "(F.`start_date`<='$params[to]')";
                $sql .= " OR ('$params[to]' BETWEEN F.`start_date` AND F.`end_date`)";
            } else {
                $sql = "(F.`start_date`>='$params[from]' AND F.`start_date`<='$params[to]')";
                $sql .= " OR ('$params[from]' BETWEEN F.`start_date` AND F.`end_date` AND '$params[to]' BETWEEN F.`start_date` AND F.`end_date`)";
            }
            $where[] = Sql::create($sql);
        }
        return \Kotchasan\Model::createQuery()
            ->select('F.id', 
                    'F.create_date', 
                    'U.username', 
                    'U.name', 
                    'L.topic',
                    'F.leave_id', 
                    'F.start_date',
                    'F.days', 
                    'F.start_period', 
                    'F.end_date', 
                    'F.end_period', 
                    'F.member_id', 
                    'F.communication', 
                    'C.topic as department',
                    'F.detail',
                    'F.status',
                    'F.reason')
            ->from('leave_items F')
            ->join('user U', 'LEFT', array('U.id', 'F.member_id'))
            ->join('leave L', 'LEFT', array('L.id', 'F.leave_id'))
            ->join('category C', 'LEFT', array('C.category_id', 'F.department'))
            ->where($where)
            ->order($params['sort'])
            //->order('id','DESC')
            ->cacheOn()
            ->execute();
    }
}
