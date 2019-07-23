<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/10
 * Time: 10:59
 */

namespace Multiple\Panel\Controllers;


use Models\User\UserInfo;
use Models\Vip\VipOrder;
use Models\Vip\VipPrivileges;
use Services\Site\SiteKeyValManager;
use Util\Pagination;

class VipController extends ControllerBase
{
    public function settingAction()#配置信息#
    {
        $config = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "vip_privilege");
        $normal_config = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
        $package_setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "square_package_setting");
        $package_setting = $package_setting ? json_decode($package_setting, true) : [];

        $config = $config ? json_decode($config, true) : [];
        $normal_config = $normal_config ? json_decode($normal_config, true) : [];

        $this->view->setVar('setting', $config);
        $this->view->setVar('normal_setting', $normal_config);
        $this->view->setVar('package_setting', $package_setting);

    }

    public function listAction()#会员列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $uid = $this->request->get("uid", 'int', 0);
        $type = $this->request->get("type", 'int', 1);//0-已过期会员 1-有效会员
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间


        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($uid) {
            $params[0][] = ' user_id= ' . $uid;
        }
        if ($type == 1) {
            $params[0][] = ' enable=1 ';
        } else if ($type == 0) {
            $params[0][] = ' enable=0 ';
        }

        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = VipPrivileges::dataCount($params[0]);
        $list = VipPrivileges::findList([$params[0], 'order' => $params['order']]);

        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);

            $vipOrderIds = VipOrder::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ")", 'group' => 'user_id', 'columns' => 'max(id) as id,user_id'], 'user_id');
            $order = VipOrder::getByColumnKeyList(['id in (' . implode(',', array_column($vipOrderIds, 'id')) . ")", 'columns' => 'month,money,privileges,user_id'], 'user_id');
            $this->view->setVar('order', $order);
        }

        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('type', $type);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('uid', $uid);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    public function payHistoryAction()#付费记录#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $uid = $this->request->get("uid", 'int', 0);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $month = $this->request->get('month', 'string', '');//


        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($uid) {
            $params[0][] = ' user_id= ' . $uid;
        }

        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($month) {
            $params[0][] = ' month  = ' . $month;
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = VipOrder::dataCount($params[0]);
        $list = VipOrder::findList([$params[0], 'order' => $params['order']]);

        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('month', $month);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

}