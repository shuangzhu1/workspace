<?php

namespace Multiple\Developer\Controllers;

use Multiple\Developer\Plugins\Authentication;
use Models\Developer\AdminGroupRight;
use Models\Developer\AdminMenus;
use Models\Developer\AdminMenusCat;
use Models\Developer\AdminRight;
use Models\Developer\Admins;
use Phalcon\Mvc\Controller;
use Phalcon\Tag;

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


    public function onConstruct()
    {
        $auth = $this->session->get('customer_auth');
        $this->admin = $admin = $this->session->get('admin');

        $this->view->setVar('auth', $auth);
        $this->view->setVar('admin', $admin);

        if (empty($auth)) {
            $this->session->set("current_request_url", $this->uri->fullUrl());
            $this->response->redirect($this->uri->baseUrl('/account/login'))->send();
            return;
        }

        if (!$this->admin) {
            $this->response->redirect($this->uri->baseUrl('/account/login'))->send();
            return;
        }
        if (!$this->setMenuPermission()) {
            return;
        }
        global $global_redis;
        $global_redis = $this->di->get('redis');
    }

    private function setMenuPermission()
    {
        // 检查权限
        $module = $this->dispatcher->getModuleName();
        $controller = $this->dispatcher->getControllerName();
        $action = $this->dispatcher->getActionName();
        // 加载菜单
        $menusCat = AdminMenusCat::findList();

        $menusCats = [];
        if ($menusCat) {
            foreach ($menusCat as $cat) {
                $menusCats[$cat['id']] = $cat;
            }
        }

        $topMenuCatAll = [];
        $secMenuCatAll = [];
        $user_own_right = [];

        //超级管理员
        if ($this->admin['level'] == 1) {

            $secCat = AdminMenus::findList(['', 'group' => 'cid', 'columns' => 'cid']);

            if ($secCat) {
                $secCat = array_column($secCat, 'cid');

                foreach ($menusCats as $k => $menu) {
                    if (in_array($k, $secCat)) {
                        $topMenuCatAll[$menu['parent_id']] = $menusCats[$menu['parent_id']];
                        $secMenuCatAll[$k] = $menusCats[$k];
                    }
                }
            }

        } else {
            // 过滤用户不具有的权限
            $user_right = AdminRight::findList(array('uid=' . $this->admin['id'] . ' and right_type=1', 'columns' => 'menu_id'));
            $admin_group = Admins::findOne(['id="' . $this->admin['id'] . '"']);

            if ($admin_group['group']) {
                $group_right = AdminGroupRight::findList(array('group_id=' . $admin_group['group'] . ' and right_type=1', 'columns' => 'menu_id'));
                $group_right && $user_right = array_merge($user_right, $group_right);
            }
            if ($user_right) {
                $user_own_right = array_unique(array_column($user_right, 'menu_id'));
                $secCat = AdminMenus::findList(array('id in (' . implode(',', $user_own_right) . ')', 'group' => 'cid', 'columns' => 'cid'));

                if ($secCat) {
                    $secCat = array_column($secCat, 'cid');

                    foreach ($menusCats as $k => $menu) {
                        if (in_array($k, $secCat)) {
                            $topMenuCatAll[$menu['parent_id']] = $menusCats[$menu['parent_id']];
                            $secMenuCatAll[$k] = $menusCats[$k];
                        }
                    }
                }
            }
        }

        $this->view->setVar('menuCats', $menusCats);
        $this->view->setVar('topMenuCatAll', $topMenuCatAll);
        $this->view->setVar('secMenuCatAll', $secMenuCatAll);

        $this->view->setVar('module', $this->dispatcher->getModuleName());
        $this->view->setVar('controller', $this->dispatcher->getControllerName());
        $this->view->setVar('action', $this->dispatcher->getActionName());

        $curMenu = Authentication::getCurMenuInfo($module, $controller, $action);
        $this->view->setVar('curMenu', []);
        $this->view->setVar('curMenus', []);
        $this->view->setVar('curMenus', []);
        $this->view->setVar('curMenuCatId', 0);
        $this->view->setVar('topMenuCatId', 0);
        if ($curMenu) {
            $thrOwnMenus = [];
            $thrMenus = Authentication::getCurMenus($curMenu['cid']);
            if ($thrMenus) {
                foreach ($thrMenus as $_menu) {
                    if (($user_own_right && in_array($_menu['id'], $user_own_right)) || $this->admin['level'] == 1) {
                        $thrOwnMenus[$_menu['id']] = $_menu;
                    }
                }
            }
            //非超级管理员
            if ($this->admin['level'] != 1) {
                if (!Authentication::getCurrentPermission($this->admin['id'], $curMenu['id'])) {
                    $this->err('503', '对不起,您没有权限~!');
                    return false;
                }
            }


            $curMenuCat = $menusCats[$curMenu['cid']];
            $topMenuCat = $menusCats[$curMenuCat['parent_id']];

            Tag::setTitle("恐龙谷 - " . $curMenu['title']);

            $this->view->setVar('curMenu', $curMenu);
            $this->view->setVar('curMenuCat', $curMenuCat);
            $this->view->setVar('topMenuCat', $topMenuCat);
            $this->view->setVar('curMenuCatId', $curMenuCat['id']);
            $this->view->setVar('topMenuCatId', $topMenuCat['id']);
            $this->view->setVar('curMenus', $thrOwnMenus);
        }
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
        Tag::setTitle('运行时错误');
        $this->view->setViewsDir(MODULE_PATH . '/Views');
        $this->response->setHeader('content-type', 'text/html;charset=utf-8');
        $this->response->setStatusCode($code, $msg);

        $this->view->setVar('msg', $msg);

        return $this->view->pick('base/error');
    }
}
