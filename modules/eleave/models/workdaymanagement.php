<?php
/**
 * @filesource modules/eleave/models/workdaymanagement.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

 namespace Eleave\Workdaymanagement;

 use Gcms\Login;
 use Kotchasan\Http\Request;
 use Kotchasan\Language;
 
 class Model extends \Kotchasan\Model
 {
     public static function toDataTable()
     {
         return static::createQuery()
             ->select('S.id', 'S.member_id', 'U.name', 'S.year', 'S.month', 'S.days', 'S.days AS business_days')  // เพิ่มคอลัมน์ business_days
             ->from('shift_workdays S')
             ->join('user U', 'LEFT', array('U.id', 'S.member_id'));
     }
 
     public function action(Request $request)
     {
         $ret = [];
         if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
             if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
                 $action = $request->post('action')->toString();
                 if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                     $table = $this->getTableName('shift_workdays');
                     if ($action === 'delete') {
                         $this->db()->delete($table, array('id', $match[1]), 0);
                         \Index\Log\Model::add(0, 'eleave', 'Delete', '{LNG_Delete} {LNG_Leave type} ID : '.implode(', ', $match[1]), $login['id']);
                         $ret['location'] = 'reload';
                     } elseif ($action === 'published') {
                         $search = $this->db()->first($table, (int) $match[1][0]);
                         if ($search) {
                             $published = $search->published == 1 ? 0 : 1;
                             $this->db()->update($table, $search->id, array('published' => $published));
                             $ret['elem'] = 'published_'.$search->id;
                             $ret['title'] = Language::get('PUBLISHEDS', '', $published);
                             $ret['class'] = 'icon-published'.$published;
                             \Index\Log\Model::add(0, 'eleave', 'Save', $ret['title'].' ID : '.$match[1][0], $login['id']);
                         }
                     }
                 }
             }
         }
         if (empty($ret)) {
             $ret['alert'] = Language::get('Unable to complete the transaction');
         }
         echo json_encode($ret);
     }
 }
 
