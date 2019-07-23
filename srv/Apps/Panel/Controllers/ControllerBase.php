<?php

namespace Multiple\Panel\Controllers;

use Components\Auth\Authentication;
use Components\Curl\CurlManager;
use Components\Passport\Identify;
use Models\Admin\AdminGroupRight;
use Models\Admin\AdminMenus;
use Models\Admin\AdminMenusCat;
use Models\Admin\AdminRight;
use Models\Admin\Admins;
use Models\Customers;
use Multiple\Panel\Plugins\AdminPrivilege;
use Multiple\Panel\Plugins\PanelMenu;
use Phalcon\Mvc\Controller;
use Phalcon\Tag;
use Util\Ajax;

/**
 * Class ControllerBase
 * @package Multiple\Panel\Controllers
 * @property \Util\Uri $uri
 */
class ControllerBase extends Controller
{
    /**
     * @var \Models\Customers
     */
    protected $host_key = null;
    protected $admin = null;


    public function initialize()
    {
        $auth = $this->session->get('customer_auth');
        $this->admin = $admin = $this->session->get('admin');

        $this->view->setVar('auth', $auth);
        $this->view->setVar('admin', $admin);

        global $global_redis;
        //$global_redis = $this->di->get('redis');

        if (empty($auth)) {
            $this->session->set("current_request_url", $this->uri->fullUrl());
            $this->response->redirect($this->uri->baseUrl('/srv/account/login'))->send();
            return;
        }

        if (!$this->admin) {
            $this->response->redirect($this->uri->baseUrl('/srv/account/login'))->send();
            return;
        }

        if (!$this->setMenuPermission()) {
            $this->dispatcher->forward(['controller' => 'errors', 'action' => 'noPrivilege', 'params' => ['code' => '503', 'msg' => '对不起,您没有权限~!']]);
            return;
        }

    }

    private function setMenuPermission()
    {
        // 当前请求路径
        $module = $this->dispatcher->getModuleName();
        $controller = $this->dispatcher->getControllerName();
        $action = $this->dispatcher->getActionName();
        //取出所有1,2级菜单
        $menusCat = AdminMenusCat::findList(['is_hide<>1', 'order' => 'sort asc,id asc']);
        //管理组或附件权限需要
        $menusCats = [];
        if ($menusCat) {
            foreach ($menusCat as $cat) {
                $menusCats[$cat['id']] = $cat;
            }
        }
        //以菜单id为键格式化数据
        foreach ($menusCat as $k => $v) {
            $menusCat[$v['id']] = $v;
            unset($menusCat[$k]);
        }
        $user_right = [];
        //非超级管理员
        if ($this->admin['level'] != AdminPrivilege::$LEVEL_SUPER_MAN) {
            //用户附加权限
            $user_right = AdminRight::findList(array('uid=' . $this->admin['id'] . ' and right_type=1 and module="panel"'));
            //用户组权限
            $admin_group = Admins::findOne(['id="' . $this->admin['id'] . '"']);
            if ($admin_group['group']) {
                $group_right = AdminGroupRight::findList(array('group_id=' . $admin_group['group'] . ' and right_type=1 and module="panel"'));
                $group_right && $user_right = array_merge($user_right, $group_right);
            }
            $user_right_tmp = [];
            foreach ($user_right as $k => $v) {
                $user_right_tmp[$v['id']] = $v;
                unset($user_right[$k]);
            }
            $user_right = $user_right_tmp;
        }

        //$user_right 用户总权限
        if ($user_right || $this->admin['level'] == AdminPrivilege::$LEVEL_SUPER_MAN) {
            $user_own_right = array_unique(array_column($user_right, 'menu_id'));
            if ($this->admin['level'] == AdminPrivilege::$LEVEL_SUPER_MAN) {
                $menus_third = AdminMenus::findList(array('is_hide=0 and module="panel"', 'order' => 'sort asc'));
                $mens_second = AdminMenusCat::findList(array('id in (' . implode(',', array_column($menus_third, 'cid')) . ') and is_hide = 0', 'order' => 'sort asc'));
            } else {
                $menus_third = AdminMenus::findList(array('id in (' . implode(',', $user_own_right) . ') and is_hide=0', 'order' => 'sort asc'));
                $mens_second = AdminMenusCat::findList(array('id in (' . implode(',', array_column($menus_third, 'cid')) . ') and is_hide = 0', 'order' => 'sort asc'));
            }
            $menus_third_tmp = [];
            foreach ($menus_third as $k => $v) {
                $menus_third_tmp[$v['cid']][] = $v;
            }
            $menus_third = $menus_third_tmp;
            $menus_first_ids = [];
            foreach ($mens_second as $k => $v) {
                $menus_first_ids[] = $v['parent_id'];
            }
            $menus_first = AdminMenusCat::findList(array('id in (' . implode(',', array_unique($menus_first_ids)) . ') and is_hide = 0', 'order' => 'sort asc'));
            $menus_first_tmp = [];
            foreach ($menus_first as $k => $v) {
                $menus_first_tmp[$v['id']] = $v;
                unset($menus_first[$k]);
            }
            $menus_first = $menus_first_tmp;
            //2级菜单整合到1级
            foreach ($mens_second as $k => $v) {
                $menus_first[$v['parent_id']]['list'][$v['id']] = $v;
            }
            //3级整合到2级
            foreach ($menus_first as $k => $v) {
                foreach ($menus_third as $kk => $vv) {
                    if (array_key_exists($kk, $v['list'])) {
                        $menus_first[$k]['list'][$kk]['list'] = $vv;
                    }
                }
            }


        }


        $curMenu = Authentication::getCurMenuInfo($module, $controller, $action);
        if ($curMenu) {
            $thrOwnMenus = [];
            $thrMenus = Authentication::getCurMenus($curMenu['cid']);
            if ($thrMenus) {
                foreach ($thrMenus as $_menu) {
                    if (($user_own_right && in_array($_menu['id'], $user_own_right))) {
                        $thrOwnMenus[$_menu['id']] = $_menu;
                    }
                }
            }
            if ($this->admin['level'] != AdminPrivilege::$LEVEL_SUPER_MAN && !Authentication::getCurrentPermission($this->admin['id'], $curMenu['id'])) {
                //  $this->err('503', '对不起,您没有权限~!');
                return false;
            }
            Tag::setTitle("恐龙谷 - " . $curMenu['title']);


            /*   $this->view->setVar('curMenuCatId', $curMenuCat['id']);
               $this->view->setVar('topMenuCatId', $topMenuCat['id']);*/


        }
        $this->view->setVar('menuCats', $menusCats);
        $this->view->setVar('curMenu', $curMenu);
        $this->view->setVar('menus_tree', $menus_first);
        return true;
    }

    public function checkPermission()
    {
        //PanelMenu::init()->getCurMenuKey();

        return true;
    }

    /**
     * render custom err page
     *
     * usage:
     * return $this->err();
     *
     * @param string $code
     * @param string $msg
     * @return mixed
     */
    protected function err($code = "404", $msg = '404 page no found')
    {
        $this->dispatcher->forward(['controller' => 'errors', 'action' => 'noPrivilege', 'params' => ['code' => $code, 'msg' => $msg]]);
        /*  Tag::setTitle('运行时错误');
          $this->view->setViewsDir(MODULE_PATH . '/Views');
          $this->response->setHeader('content-type', 'text/html;charset=utf-8');
          $this->response->setStatusCode($code, $msg);

          $this->view->setVar('msg', $msg);

          return $this->view->pick('base/error');*/
    }

    /**
     * @param string $path url中path信息 前面不带‘/’
     * @param array $data
     * @param string $domain 为空取默认值：http://service.klgwl.com/,
     * @return mixed
     */
    public function postApi($path = '', $data = [], $domain = '')
    {
        $apiDomain = $this->config->api_domain;
        if (empty($domain)) {
            $url = $apiDomain['service'] . $path;

        }else{
            $url = $domain . $path;
        }
        $data['random'] = rand(1000, 1000000);
        $data['sign_type'] = 'MD5';
        $data['time_stamp'] = time();
        $data['sign'] = Identify::init()->buildRequestMysign($data);

        $result = CurlManager::init()->CURL_POST($url, $data);
        if ($result['curl_is_success'] != 1) {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, $result);
        } else {
            if (json_decode($result['data'])->code == 200 || json_decode($result['data'])->code == 0) {
                return json_decode($result['data'], true)['data'];
            } else {
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, json_decode($result['data'], true)['data']);
            }

        }
    }
}
