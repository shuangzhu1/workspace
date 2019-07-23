<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/29
 * Time: 16:18
 */

namespace Multiple\Panel\Controllers;


use Models\Agent\Agent;
use Models\Agent\AgentApply;
use Models\Shop\Shop;
use Models\User\UserInfo;
use Services\Site\SiteKeyValManager;
use Util\Pagination;

class AgentController extends ControllerBase
{
    public function listAction()#合伙人列表#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        $true_name = $this->request->get("true_name", 'string', '');
        $code = $this->request->get("code", 'string', '');

        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("p", 'int', 1);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间

        $params[] = [];
        $params['order'] = 'is_partner desc,created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($user_id) {
            $params[0][] = ' user_id  = ' . $user_id;
        }
        if ($code) {
            $params[0][] = ' code  = "' . $code . '"';
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($true_name) {
            $params[0][] = ' true_name like "%' . $true_name . '%"';
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = Agent::dataCount($params[0]);
        $list = Agent::findList($params);

        if ($list) {
            $user_ids = array_unique(array_column($list, 'user_id'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);

            $shop_list = Agent::getByColumnKeyList(['parent_merchant in (' . implode(',', $user_ids) . ')', 'group' => 'parent_merchant', 'columns' => 'count(1) as count,parent_merchant'], 'parent_merchant');
            $agent_list = Agent::getByColumnKeyList(['parent_partner in (' . implode(',', $user_ids) . ')', 'group' => 'parent_partner', 'columns' => 'count(1) as count,parent_partner'], 'parent_partner');
            foreach ($list as &$item) {
                $item['invite_shop_count'] = isset($shop_list[$item['user_id']]) ? $shop_list[$item['user_id']]['count'] : 0;
                $item['invite_agent_count'] = isset($agent_list[$item['user_id']]) ? $agent_list[$item['user_id']]['count'] : 0;
            }
        }


        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('true_name', $true_name);
        $this->view->setVar('code', $code);
    }

    public function applyDetailAction()#合伙人详情#
    {
        $apply_id = $this->request->get("apply_id", 'int', 0);
        if (!$apply_id) {
            $this->err(404, "data not exists");
            return;
        }
        $apply = AgentApply::findOne(['id=' . $apply_id]);
        if ($apply) {
            $info = UserInfo::findOne(['user_id=' . $apply['user_id'], 'columns' => 'username,avatar,phone,true_name']);
            $apply['user_info'] = $info;
        } else {
            $this->err(404, "data not exists");
            return;
        }
        $this->view->setVar('item', $apply);
    }

    public function detailAction()#合伙人详情#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        if (!$user_id) {
            $this->err(404, "data not exists");
            return;
        }
        $apply = Agent::findOne(['user_id=' . $user_id]);
        if ($apply) {
            $info = UserInfo::findOne(['user_id=' . $apply['user_id'], 'columns' => 'username,avatar,phone,true_name']);
            $apply['user_info'] = $info;
        } else {
            $this->err(404, "data not exists");
            return;
        }
        $this->view->setVar('item', $apply);
    }

    public function orderAction()#合伙人申请#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("p", 'int', 1);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $status = $this->request->get("status", 'int', 1);
        $code = $this->request->get("code", 'string', '');
        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($user_id) {
            $params[0][] = ' user_id  = ' . $user_id;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($status >= 0) {
            $params[0][] = ' status  = ' . $status;
        }
        if ($code) {
            $params[0][] = ' code  = "' . $code . '"';
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = AgentApply::dataCount($params[0]);
        $list = AgentApply::findList($params);

        if ($list) {
            $user_ids = array_unique(array_column($list, 'user_id'));
            $user_ids = array_unique(array_merge($user_ids, array_column($list, 'code_owner')));

            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('status', $status);
        $this->view->setVar('code', $code);

        $total = AgentApply::findOne(['columns' => 'sum(money) as total', 'is_paid=1']);
        $paid_user = AgentApply::dataCount(['is_paid=1']);

        $this->view->setVar('total_money', $total ? $total['total'] : 0);
        $this->view->setVar('paid_user_count', $paid_user);

    }

    public function settingAction()#基础设置#
    {
        $price = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
        $setting = $price['agent'];

        $this->view->setVar('setting', $setting);
    }
}