<?php

namespace Multiple\Developer\Controllers;

use Models\Developer\AdminGroup;
use Models\Developer\AdminGroupRight;
use Models\Developer\AdminLogs;
use Models\Developer\AdminMenus;
use Models\Developer\AdminMenusCat;
use Models\Developer\AdminRight;
use Models\Developer\Admins;
use Util\Pagination;

class AdminController extends ControllerBase
{

    public function indexAction()
    {
        $list = Admins::findList(['status <>0']);
        $group = AdminGroup::findList();
        if ($group) {
            $group = array_column($group, 'name', 'id');
        }
        $this->view->setVar('group', $group);
        $this->view->setVar('list', $list);
    }

    /**
     * menus settings
     */
    public function menusAction()
    {
        $cid = intval($this->request->get('cid'));
        $this->view->setVar('data', null);
        $this->view->setVar('topCat', 0);
        if ($cid) {
            $where = $cid ? 'cid = ' . $cid : null;
            $menus = AdminMenus::findList($where);
            $cat = AdminMenusCat::findOne(array('id=' . $cid . ' and parent_id > 0', 'order' => 'sort desc'));
            if ($cat) {
                $this->view->setVar('topCat', $cat['parent_id']);
                $this->view->setVar('data', $menus);
            }
        }
    }

    /**
     * group list
     */
    public function groupAction()
    {
        $group = AdminGroup::findList();
        $this->view->setVar('groups', $group);
    }

    /**
     * permission
     */
    public function userPermAction()
    {
        $uid = $this->request->get('uid', 'int', 0);
        $permissions = AdminRight::findList('uid=' . $uid);
        $menus = AdminMenus::findList();
        $users = Admins::findList('status=1');
        $this->view->setVar('uid', $uid);
        $this->view->setVar('userPermissions', $permissions);

        $userInfo = Admins::findOne('id=' . $uid);
        $this->view->setVar('userInfo', $userInfo);

        $this->view->setVar('users', $users );
        $this->view->setVar('menus', $menus);
    }

    /**
     * permission
     */
    public function groupPermAction()
    {
        $gid = $this->request->get('gid', 'int', 0);

        $this->view->setVar('gid', $gid);
        $permissions = AdminGroupRight::findList('group_id=' . $gid);
        $menus = AdminMenus::findList();
        $this->view->setVar('menus', $menus);
        $this->view->setVar('groupPermissions', $permissions);

        $groupInfo = AdminGroup::findOne('id=' . $gid);
        $this->view->setVar('groupInfo', $groupInfo);

        $groupList = AdminGroup::findList();
        $this->view->setVar('groups', $groupList);
    }

    /**
     * menus settings
     */
    public function menuCatAction()
    {
        // the data in view layer has been initialized by Menu::init() in __construct()
    }

    //管理员日志
    public function logAction()
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