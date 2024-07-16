<?php
/**
 * @filesource modules/eleave/models/manageshifts_export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Manageshifts_export;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;


/**
 * module=eleave-manageshifts_export
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query data for DataTable
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('id', 'shift_name', 'shift_type', 'start_time', 'end_time', 'break_start', 'break_end', 'workdays')
            ->from('shifts');
    }

    /**
     * Handle actions from the DataTable
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, permission check, and not demo mode
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
                // get POST values
                $action = $request->post('action')->toString();
                // get IDs
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    $table = $this->getTableName('shifts');
                    if ($action === 'delete') {
                        // delete
                        $this->db()->delete($table, array('id', $match[1]), 0);
                        // log
                        \Index\Log\Model::add(0, 'eleave', 'Delete', '{LNG_Delete} {LNG_Shift} ID: '.implode(', ', $match[1]), $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'published') {
                        // update publish status
                        $search = $this->db()->first($table, (int)$match[1][0]);
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $this->db()->update($table, $search->id, array('published' => $published));
                            // return values
                            $ret['elem'] = 'published_'.$search->id;
                            $ret['title'] = Language::get('PUBLISHEDS', '', $published);
                            $ret['class'] = 'icon-published'.$published;
                            // log
                            \Index\Log\Model::add(0, 'eleave', 'Save', $ret['title'].' ID: '.$match[1][0], $login['id']);
                        }
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // return JSON
        echo json_encode($ret);
    }

    /**
     * Save shift data
     *
     * @param array $data
     * @return bool
     */
    public static function saveShift($data)
    {
        return static::createQuery()
            ->insert('shifts', $data) !== false;
    }

    /**
     * Update shift data
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateShift($id, $data)
    {
        return static::createQuery()
            ->update('shifts')
            ->set($data)
            ->where(array('id', $id))
            ->execute() !== false;
    }

    /**
     * Delete shift data
     *
     * @param int $id
     * @return bool
     */
    public static function deleteShift($id)
    {
        return static::createQuery()
            ->delete('shifts', array('id', $id)) !== false;
    }
}
