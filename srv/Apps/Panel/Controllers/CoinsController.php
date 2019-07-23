<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/16
 * Time: 9:13
 */

namespace Multiple\Panel\Controllers;


use Components\Rules\Coin\PointRule;
use Models\User\UserDragonCoin;
use Models\User\UserDragonCoinLog;
use Models\User\UserInfo;
use Models\User\Users;
use Services\Site\SiteKeyValManager;
use Util\Pagination;

class CoinsController extends ControllerBase
{
    //具有龙币的用户列表
    public function listAction()
    {
        $user_id = $this->request->get("user_id", 'int', 0);
    }

    //龙币设置
    public function settingAction()#龙币设置#
    {
        $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'coin_setting');
        $setting = $setting ? json_decode($setting, true) : [];
        $this->view->setVar('setting', $setting);
    }

    public function recordsAction()#龙币记录#
    {
        $user_id = $this->request->get('user_id', 'int', 0);
        $action = $this->request->get('action', 'int', 0);
        $username = $this->request->get('username', 'string', '');
        $start_time = $this->request->get('start_time', 'string', '');
        $end_time = $this->request->get('end_time', 'string', '');
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);

        $filter = [];
        if ($user_id > 0) {
            $filter[] = "user_id =" . $user_id;
        }
        if ($action > 0) {
            $filter[] = "type =" . $action;
        }
        if ($username != "") {

            $users = Users::getColumn(['username like "%' . $username . '%"', 'id'], 'id');
            if ($users) {
                $filter[] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($start_time != "") {
            $filter[] = "created >= '" . strtotime($start_time) . "'";
        }
        if ($end_time != "") {
            $filter[] = "created <= '" . (strtotime($end_time) + 86400) . "'";
        }

        if ($filter) {
            $where = " " . implode(' and ', $filter);
        } else {
            $where = "";
        }
        $list = UserDragonCoinLog::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc']);
        $count = UserDragonCoinLog::dataCount($where);

        /*   $list = $this->db->query("select log.*,ifnull(u.username,'用户不存在') as username  from user_coin_log as log left join users as u on log.user_id=u.id
           " . $where . " order by log.created desc limit " . (($page - 1) * $limit) . "," . $limit)->fetchAll();
           $count = $this->db->query("select count(*)  from user_coin_log as log left join users as u on log.user_id=u.id
           " . $where)->fetch();*/

        if ($list) {
            $uids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'username,user_id'], 'user_id');
            foreach ($list as &$item) {
                $item['username'] = $users[$item['user_id']]['username'];
            }
        }

        Pagination::instance($this->view)->showPage($page, $count, $limit, 5);
        $this->view->setVar('list', $list);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('act', $action);
        $this->view->setVar('username', $username);
        $this->view->setVar('start_time', $start_time);
        $this->view->setVar('end_time', $end_time);
        $this->view->setVar('limit', $limit);
    }

    public function userListAction()#龙币用户#
    {
        $user_id = $this->request->get('user_id', 'int', 0);
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序
        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($user_id > 0) {
            $params[0][] = "user_id =" . $user_id;
        }
        //排序
        if ($sort) {
            if ($sort == 'history') {
                $params['order'] = " history_count $sort_order";
            } else if ($sort == 'available') {
                $params['order'] = "available_count $sort_order, created desc";
            } else if ($sort == 'frozen') {
                $params['order'] = "frozen_count $sort_order, created desc";
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';

        $list = UserDragonCoin::findList($params);
        $count = UserDragonCoin::dataCount($params[0]);
        if ($list) {
            $uids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        Pagination::instance($this->view)->showPage($page, $count, $limit, 5);
        $this->view->setVar('list', $list);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);

    }
}