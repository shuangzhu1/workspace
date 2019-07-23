<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/6
 * Time: 14:17
 */

namespace Multiple\Panel\Controllers;


use Models\Admin\Admins;
use Models\User\UserFeedback;
use Models\User\Users;
use Services\Site\SiteKeyValManager;
use Util\Pagination;

class SystemController extends ControllerBase
{
    //用户反馈
    public function feedbackAction()#用户反馈#
    {
        $key = $this->request->get('key', 'string', '');
        $status = $this->request->get('status', 'int', -1);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间


        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 15);
        $params = [];
        $params[] = '';
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username="' . $key . '" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($status != -1) {
            $params[0][] = ' check_status = ' . $status;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $list = UserFeedback::findList($params);
        $admins = [];
        if ($list) {
            $admin_ids = array_unique(array_filter(array_column($list, 'check_user')));
            if ($admin_ids) {
                $admins = Admins::getColumn(['id in (' . implode(',', $admin_ids) . ')', 'columns' => 'name,id'], 'name', 'id');
            }
        }

        $this->view->setVar('admins', $admins);
        $count = UserFeedback::dataCount($params[0]);
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('status', $status);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //图片鉴黄
    public function imgCheckAction()#图片鉴黄#
    {

    }

    //图片鉴黄设置
    public function imgCheckSettingAction()#图片鉴黄设置#
    {
        $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "img_check");

        $this->view->setVar('setting', json_decode($setting,true));
    }
}