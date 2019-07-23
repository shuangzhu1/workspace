<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/6
 * Time: 11:56
 */

namespace Multiple\Panel\Controllers;


use Models\User\UserInfo;
use Models\User\UserInviter;
use Models\User\UserWelfare;
use Services\Site\SiteKeyValManager;
use Util\Pagination;

class WelfareController extends ControllerBase
{
    public function listAction()#大使列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $uid = $this->request->get('user_id', 'int', 0);
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序
        $start = $this->request->get("start", 'string', '');
        $end = $this->request->get("end", 'string', '');

        $order = 'created desc';
        $where = "";
        if ($uid) {
            $where .= "and user_id=" . $uid . " ";
        }
        if ($start) {
            $where .= "and created>=" . strtotime($start) * 1000 . " ";
        }
        if ($end) {
            $where .= "and created<=" . (strtotime($end) * 1000 + 86400000) . " ";
        }
        //排序
        if ($sort) {
            if ($sort == 'total_val') {
                $order = " total_val $sort_order";
            } else if ($sort == 'current_val') {
                $order = "current_val $sort_order, created desc";
            } else if ($sort == 'changed_val') {
                $order = "total_val-current_val $sort_order, created desc";
            }
        }

        $where = $where ? substr($where, 3) : '';
        $list = UserWelfare::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => $order]);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id,avatar'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $count = UserWelfare::dataCount($where);
        $this->view->setVar('list', $list);
        $this->view->setVar('user_id', $uid);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        Pagination::instance($this->view)->showPage($page, $count, $limit);

    }

    public function inviteRecordAction()#邀请记录#
    {
        $status = $this->request->get("status", 'int', -1);
        $inviter = $this->request->get("inviter", 'int', 0);//邀请者
        $invitee = $this->request->get("invitee", 'int', 0);//被邀请者

        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $start = $this->request->get("start", 'string', '');
        $end = $this->request->get("end", 'string', '');
        $order = 'created desc';
        $where = "";
        if ($inviter) {
            $where .= "and inviter=" . $inviter . " ";
        }
        if ($invitee) {
            $where .= "and user_id=" . $invitee . " ";
        }
        if ($start) {
            $where .= "and created>=" . strtotime($start) * 1000 . " ";
        }
        if ($end) {
            $where .= "and created<=" . (strtotime($end) * 1000 + 86400000) . " ";
        }
        $where = $where ? substr($where, 3) : '';
        $list = UserInviter::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => $order]);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $user_ids = array_unique(array_merge($user_ids, array_unique(array_column($list, 'inviter'))));

            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id,phone'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $count = UserInviter::dataCount($where);
        $this->view->setVar('list', $list);
        $this->view->setVar('inviter', $inviter);
        $this->view->setVar('invitee', $invitee);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    public function settingAction()#基础配置#
    {
        $setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "welfare_setting", true);

        $this->view->setVar('setting', $setting);
    }

    public function groupAction()#等级配置#
    {

    }
}