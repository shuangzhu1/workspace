<?php

namespace Multiple\Panel\Controllers;

use Models\Admin\AdminGroup;
use Models\Admin\AdminGroupRight;
use Models\Admin\AdminLogs;
use Models\Admin\AdminMenus;
use Models\Admin\AdminMenusCat;
use Models\Admin\AdminRight;
use Models\Admin\Admins;
use Multiple\Panel\Plugins\AdminPrivilege;
use Util\Pagination;

class AdminController extends ControllerBase
{

    public function indexAction()#账号管理#
    {
        //非超级管理员/管理员 没有权限
        if (!in_array($this->admin['level'], [AdminPrivilege::$LEVEL_SUPER_MAN, AdminPrivilege::$LEVEL_ADMIN])) {
            $this->dispatcher->forward(['controller' => 'errors', 'action' => 'noPrivilege', 'params' => ['code' => '503', 'msg' => '对不起,您没有权限~!']]);
            return;
        }
        //超级管理员
        if ($this->admin['level'] == AdminPrivilege::$LEVEL_SUPER_MAN) {
            $list = Admins::findList(['status <>0', 'order' => '`group` asc']);
            $group = AdminGroup::findList();
        } else {
            //管理员
            $list = Admins::findList(['status <>0 and `group`=' . $this->admin['group'] . " and level<>1", 'order' => '`group` asc']);
            $group = AdminGroup::findList(["id=" . $this->admin['group']]);
        }
        if ($group) {
            $group = array_column($group, 'name', 'id');
        }
        $this->view->setVar('group', $group);
        $this->view->setVar('list', $list);

    }

    /**
     * menus settings
     */
    public function menusAction()#子菜单导航#
    {
        // echo $this->router->get;exit;
        AdminPrivilege::init()->syncMenu();
        $cid = intval($this->request->get('cid'));
        $this->view->setVar('data', null);
        $this->view->setVar('topCat', 0);
        $pid = $this->request->get("pid", 'int', 1);
        $this->view->setVar('pid', $pid);
        $this->view->setVar('cid', $cid);
        if ($cid) {
            $where = $cid ? 'cid = ' . $cid : null;
            $menus = AdminMenus::findList([$where, 'order' => 'sort asc,id asc']);
            $cat = AdminMenusCat::findOne(array('id=' . $cid . ' and parent_id > 0', 'order' => 'sort desc'));
            if ($cat) {
                $this->view->setVar('topCat', $cat['parent_id']);
                $this->view->setVar('data', $menus);
            }
        } else {
            $menus = AdminMenus::findList(['cid=0', 'order' => 'sort asc,id asc']);
            $this->view->setVar('data', $menus);

        }
    }

    /**
     * group list
     */
    public function groupAction()#用户分组#
    {
        $group = AdminGroup::findList();
        $this->view->setVar('groups', $group);
    }

    /**
     * permission
     */
    public function userPermAction()#用户权限#
    {
        $uid = $this->request->get('uid', 'int', 0);
        $permissions = AdminRight::findList(['uid=' . $uid . ' and right_type=1', '']);
        $menus = AdminMenus::findList();
        $users = Admins::findList('status=1 and level<>' . AdminPrivilege::$LEVEL_SUPER_MAN);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('userPermissions', $permissions);

        $userInfo = Admins::findOne('id=' . $uid);
        $pg=[];
        if ($userInfo) {
            $permissions_group = AdminGroupRight::findList(['group_id = ' . $userInfo['group'] . ' and right_type = 1', 'columns' => 'menu_id']);
            //$permissions_group = ($permissions_group ? $permissions_group->toArray() : []);
            foreach ($permissions_group as $item) {
                $pg[] = $item['menu_id'];//角色权限
            }
        }
        foreach ($pg as $key => $item) //如果组权限和附件权限有重复菜单，去掉组权限中改菜单
        {
            foreach ($permissions as $value) {
                if ($item == $value['menu_id'])
                    unset($pg[$key]);
            }
        }
        $this->view->setVar('userInfo', $userInfo);
        $this->view->setVar('pg', $pg);

        $this->view->setVar('users', $users);
        $this->view->setVar('menus', $menus);
        $this->view->setVar('gid', $userInfo['group']);
    }

    /**
     * permission
     */
    public function groupPermAction()#分组权限#
    {
        $gid = $this->request->get('gid', 'int', 0);

        $this->view->setVar('gid', $gid);
        $permissions = AdminGroupRight::findList('group_id=' . $gid);
        $menus = AdminMenus::findList([""]);
        $this->view->setVar('menus', $menus);
        $this->view->setVar('groupPermissions', $permissions);

        $groupInfo = AdminGroup::findOne('id=' . $gid);
        $this->view->setVar('groupInfo', $groupInfo);

        $groupList = AdminGroup::findList([""]);
        $this->view->setVar('groups', $groupList);
    }

    /**
     * menus settings
     */
    public function menuCatAction()#菜单管理#
    {
        //  echo $this->router->getRewriteUri();exit;

        // 加载菜单
        $menusCat = AdminMenusCat::getByColumnKeyList(['', 'order' => 'sort asc,id asc'], 'id');
        $this->view->setVar('menuCat', $menusCat);
        // the data in view layer has been initialized by Menu::init() in __construct()
    }

    //管理员日志
    public function logAction()#操作日志#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $type = $this->request->get('type', 'string', '');
        $key = $this->request->get('key', 'string', '');
        $admin_id = $this->request->get('admin', 'int', 0);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $this->view->setVar('type', $type);
        $this->view->setVar('admin_id', $admin_id);
        $this->view->setVar('key', $key);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($type) {
            $params[0][] = 'type="' . $type . '"';
        }
        if ($key) {
            $params[0][] = 'action like "%' . $type . '%"';
        }
        if ($admin_id) {
            $params[0][] = 'uid="' . $type . '"';
        }
        if ($start) {
            $params[0][] = 'created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = 'created  <= ' . strtotime($end);
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = AdminLogs::dataCount($params[0]);
        $list = AdminLogs::findList($params);
        $this->view->setVar('list', $list);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $admins = Admins::getColumn(['', 'columns' => 'id,name'], 'name', 'id');
        $this->view->setVar('admins', $admins);

    }

}