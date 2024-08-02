<?php
/**
 * @filesource modules/eleave/models/email.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Email;

use Kotchasan\Language;

/**
 * ส่งอีเมลและ LINE ไปยังผู้ที่เกี่ยวข้อง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ส่งอีเมลและ LINE แจ้งการทำรายการ
     *
     * @param array $order
     *
     * @return string
     */
    public static function send($order)
    {
        $lines = [];
        $emails = [];
        $name = '';
        $mailto = '';
        $line_uid = '';
        if (self::$cfg->demo_mode) {
            // โหมดตัวอย่าง ส่งหาผู้ทำรายการและแอดมินเท่านั้น
            $where = array(
                array('id', array($order['member_id'], 1))
            );
        } else {
            // ส่งหาผู้ทำรายการและผู้ที่เกี่ยวข้อง
            $where = array(
                // ผู้ทำรายการ
                array('id', $order['member_id'])
            );
            // ผู้อนุมัตื
            $passapprove1 = true;
            $passapprove2 = true;
            if (empty($order['member_id_m1']) || $order['member_id_m1'] == 0) { $passapprove1 = false; $order['member_id_m1'] = 0;}
            if (empty($order['member_id_m2']) || $order['member_id_m2'] == 0) { $passapprove2 = false; $order['member_id_m2'] = 0;}
            if ($passapprove1 && $order['status'] == 0 && $order['status_m1'] == 0 || ($order['status'] == 3 && $order['status_m1'] == 3)){
                $where[] = array('id', $order['member_id_m1']);
            }
            if ($passapprove2 && $order['status'] == 0 && $order['status_m1'] == 1 && $order['status_m2'] == 0){
                $where[] = array('id', $order['member_id_m2']);
            }
        }
        // ตรวจสอบรายชื่อผู้รับ
        $query = static::createQuery()
            ->select('id', 'username', 'name', 'line_uid', 'email')
            ->from('user')
            ->where(array('active', 1))
            ->andWhere($where, 'OR')
            ->cacheOn();

        $sendmailTo = false;
        $sendmailApprove = false;
        foreach ($query->execute() as $item) {
            if ($item->id == $order['member_id']) {
                // ผู้ทำรายการ
                $name = $item->name;
                $mailto = $item->email;
                $line_uid = $item->line_uid;
                $order['name'] = $item->name;
                if (!empty($item->email)){
                    $sendmailTo = true;
                }
            } else {
                // เจ้าหน้าที่
                if (!empty($item->email)){
                    $sendmailApprove = true;
                    $emails[] = $item->name.'<'.$item->email.'>';
                    if ($item->line_uid != '') {
                        $lines[] = $item->line_uid;
                    }
                }
            }
        }
        // ข้อความอีเมล
        $msg = Language::trans(\Eleave\View\View::create()->render($order, true));
        // ข้อความสำหรับผู้ทำรายการ
        $user_msg = str_replace('%MODULE%', 'leave', $msg);
        // ข้อความสำหรับผู้อนุมัติ
        $admin_msg = str_replace('%MODULE%', 'approve', $msg);
        $ret = [];
        if (!empty(self::$cfg->line_api_key)) {
            // LINE Notify
            $err = \Gcms\Line::send($admin_msg);
            if ($err != '') {
                $ret[] = $err;
            }
        }
        // LINE ส่วนตัว
        if (!empty($lines)) {
            $err = \Gcms\Line::sendTo($lines, $admin_msg);
            if ($err != '') {
                $ret[] = $err;
            }
        }
        if (!empty($line_uid)) {
            $err = \Gcms\Line::sendTo($line_uid, $user_msg);
            if ($err != '') {
                $ret[] = $err;
            }
        }
        if (self::$cfg->noreply_email != '' && ($sendmailTo || $sendmailApprove)) {
            // email subject
            $Leavestatus = \Eleave\Leave\Model::getleaveofstatic($order['leave_id']);
            $Leavename = $Leavestatus->topic;
            if (isset($order['statusOld'])) {
                if ($order['status'] == 4) {
                    $subject = '['.self::$cfg->web_title.'] '.Language::get('Request for approval').$Leavename.Language::get('of').' '.$name.' '.Language::get('LEAVE_STATUS', '', 1).Language::get('LEAVE_STATUS', '', 3);
                } else {
                    $subject = '['.self::$cfg->web_title.'] '.Language::get('Request for approval').$Leavename.Language::get('of').' '.$name.' '.Language::get('LEAVE_STATUS', '', 2).Language::get('LEAVE_STATUS', '', 3);
                }
            
            } else {
                $subject = '['.self::$cfg->web_title.'] '.Language::get('Request for approval').$Leavename.Language::get('of').' '.$name.' '.Language::get('LEAVE_STATUS', '', $order['status']);
            }
            
            // ส่งอีเมลไปยังผู้ทำรายการเสมอ
            if ($sendmailTo) {
                $err = \Kotchasan\Email::send($name.'<'.$mailto.'>', self::$cfg->noreply_email, $subject, $user_msg);
                if ($err->error()) {
                    // คืนค่า error
                    $ret[] = strip_tags($err->getErrorMessage());
                }
            }
            if ($sendmailApprove) {
                foreach ($emails as $item) {
                    // ส่งอีเมล
                    $err = \Kotchasan\Email::send($item, self::$cfg->noreply_email, $subject, $admin_msg);
                    if ($err->error()) {
                        // คืนค่า error
                        $ret[] = strip_tags($err->getErrorMessage());
                    }
                }
            }
        }
        if (isset($err)) {
            // ส่งอีเมลสำเร็จ หรือ error การส่งเมล
            return empty($ret) ? Language::get('Your message was sent successfully') : implode("\n", array_unique($ret));
        } else {
            // ไม่มีอีเมลต้องส่ง
            return Language::get('Saved successfully');
        }
    }
}
