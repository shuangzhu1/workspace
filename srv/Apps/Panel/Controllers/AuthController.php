<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/28
 * Time: 17:25
 */

namespace Multiple\Panel\Controllers;


use Models\Admin\Admins;
use Models\User\UserAuthApply;
use Models\User\Users;
use Services\Admin\AdminLog;
use Util\Pagination;

class AuthController extends ControllerBase
{
    //认证申请列表
    public function listAction()#认证申请列表#
    {
        $key = $this->request->get('key', 'string', '');
        $status = $this->request->get('status', 'int', 2);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $id_card = $this->request->get('id_card', 'string', '');//身份证号


        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $params = [];
        $params[0] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '" or true_name like "' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            } else {
                $params[0][] = '1=0';
            }

        }
        if ($status != 0) {
            $params[0][] = ' status = ' . $status;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($id_card) {
            $params[0][] = ' id_card  = "' . $id_card . '" ';
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $list = UserAuthApply::findList($params);
        $admins = [];
        if ($list) {
            $admin_ids = array_unique(array_filter(array_column($list, 'check_user')));
            if ($admin_ids) {
                $admins = Admins::getColumn(['id in (' . implode(',', $admin_ids) . ')', 'columns' => 'name,id'], 'name', 'id');
            }
        }

        $this->view->setVar('admins', $admins);
        $count = UserAuthApply::dataCount($params[0]);
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('status', $status);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('id_card', $id_card);
        Pagination::instance($this->view)->showPage($page, $count, $limit);

    }

    //认证详情
    public function detailAction()#认证详情#
    {
        $apply_id = $this->dispatcher->getParam(0);//动态id
        $data = UserAuthApply::findOne("id=" . $apply_id);
        if (!$data) {
            $this->err(404, '数据不存在');
        }
        $this->view->setVar('item', $data);
        $logs = AdminLog::init()->getLogs(AdminLog::TYPE_AUTH, $apply_id);
        $this->view->setVar('logs', $logs);
    }
}