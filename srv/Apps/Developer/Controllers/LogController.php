<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/12
 * Time: 15:13
 */

namespace Multiple\Developer\Controllers;


use Models\System\SystemApiCallLog;
use Models\System\SystemApiError;
use Models\User\UserInfo;
use Models\User\Users;
use Phalcon\Mvc\View;
use Services\Admin\DeveloperLog as AdminLog;
use Util\Pagination;

class LogController extends ControllerBase
{
    public function apiAction()
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $key = $this->request->get('key', 'string', '');//关键字
        $status = $this->request->get('status', 'int', '-1');//状态
        $client_type = $this->request->get('client_type', 'string', '0');//客户端类型
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $time_start = $this->request->get('time_start', 'int', '');//运行时间-开始
        $time_end = $this->request->get('time_end', 'int', '');//运行时间-结束
        $app_version = $this->request->get('app_version', 'string', '');//app版本号
        $api = $this->request->get('api', 'string', '');//api
        $code = $this->request->get('code', 'string', '');//code
        $ip = trim($this->request->get('ip', 'string', ''));//ip地址
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username="' . $key . '" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($code) {
            $params[0][] = ' code = "' . $code . '"';
        }
        if ($status != '-1') {
            $params[0][] = ' status = ' . $status;
        }
        if ($client_type) {
            $params[0][] = ' client_type = "' . $client_type . '"';
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . strtotime($end);
        }
        if ($time_start) {
            $params[0][] = ' time  >= ' . $time_start;
        }
        if ($time_end) {
            $params[0][] = ' time  <= ' . $time_end;
        }
        if ($app_version) {
            $params[0][] = ' app_version  = "' . $app_version . '"';
        }
        if ($api) {
            $params[0][] = ' api  = "' . $api . '"';
        }
        if ($ip) {
            $params[0][] = ' ip  = "' . $ip . '"';
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';

        //排序
        if ($sort) {
            if ($sort == 'created') {
                $params['order'] = " created desc";
            } else if ($sort == 'time') {
                $params['order'] = " time desc ,created desc";
            }
        }
        $count = SystemApiCallLog::dataCount($params[0]);
        $list = SystemApiCallLog::findList($params);
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('status', $status);
        $this->view->setVar('client_type', $client_type);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('time_start', $time_start);
        $this->view->setVar('time_end', $time_end);
        $this->view->setVar('app_version', $app_version);
        $this->view->setVar('api', $api);
        $this->view->setVar('code', $code);
        $this->view->setVar('ip', $ip);

        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);

        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }


        Pagination::instance($this->view)->showPage($page, $count, $limit);
        /*var_dump(SystemApiCallLog::findFirst()->toArray());exit;*/
    }

    //项目内部文件日志
    public function frameworkAction()
    {
        $tabs = [];
        $path = 'Cache';
        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." && $file != "..") {
                        if (is_dir(realpath($path . '/' . $file))) {
                            $tabs[] = $file;
                        }
                    }
                }
                closedir($dh);
            }
        }
        $tab = $this->request->get('tab', 'string');
        $tab = in_array($tab, $tabs) ? $tab : $tabs[0];

        $folders = AdminLog::init()->getFolder($path . '/' . $tab)['folders'];
        array_multisort(array_column($folders, 'path'), SORT_DESC, $folders);
        $this->view->setVar('tabs', $tabs);
        $this->view->setVar('folders', $folders);
        $this->view->setVar('tab', $tab);
    }

    //nginx日志
    public function nginxAction()
    {

    }

    //系统错误
    public function sysAction()
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $key = $this->request->get('key', 'string', '');//关键字
        $client_type = $this->request->get('client_type', 'string', 0);//客户端类型
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间


        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username="' . $key . '" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }

        if ($client_type) {
            $params[0][] = ' client_type = "' . $client_type . '"';
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . strtotime($end);
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SystemApiError::dataCount($params[0]);
        $list = SystemApiError::findList($params);
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('client_type', $client_type);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);


        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //安卓崩溃日志
    public function crashAndroidAction()
    {

    }

    //ios崩溃日志
    public function crashIosAction()
    {

    }

    public function fileDetailAction()
    {
        $this->view->disableLevel([
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true
        ]);
        $path = $this->request->get("path", 'string', '');
        if ($path) {
            echo(nl2br(file_get_contents($path)));
        }

    }
}