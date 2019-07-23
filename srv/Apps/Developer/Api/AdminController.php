<?php

namespace Multiple\Developer\Api;


use Models\Developer\AdminGroup;
use Models\Developer\AdminGroupRight;
use Models\Developer\AdminMenus;
use Models\Developer\AdminMenusCat;
use Models\Developer\AdminRight;
use Models\Developer\Admins;
use Services\Admin\DeveloperLog as AdminLog;
use Util\Ajax;


class AdminController extends ApiBase
{

    public function delAction()
    {
        $auth = $this->session->get('customer_auth');
        if (!$auth) {
            Ajax::outError(Ajax::ERROR_USER_HAS_NOT_LOGIN);
        }

        $id = intval($this->request->get('data'));
        if (!$id) {
            Ajax::outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        $item = Admins::findOne('id =' . $id);
        if (!$item) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (!Admins::updateOne(['status' => AdminLog::STATUS_DELETED], ['id' => $id])) {
            Ajax::outError(Ajax::ERROR_RUN_TIME_ERROR_OCCURRED);
        }

        Ajax::outRight('');
    }

    public function saveAction()
    {
        $auth = $this->session->get('customer_auth');
        if (!$auth) {
            Ajax::outError(Ajax::ERROR_USER_HAS_NOT_LOGIN);
        }

        $data = $this->request->get('data');
        $id = $this->request->get('id');
        if (!$data) {
            Ajax::outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // 密码判断
        if ($data['password'] != '') {
            $len = strlen($data['password']);
            if (!($len >= 6 && $len <= 16)) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "密码长度为6-16位,如不更改,请留空");
            }
            $data['password'] = sha1($data['password']);
        }
        // 组
        $group = $data['level'];
        $data['group_name'] = "游客";
        $data['`group`'] = $group;
        unset($data['level']);
        $data['status'] = $data['active'];
        unset($data['active']);

        $group_name = AdminGroup::findOne('id="' . $group . '"');
        if ($group_name) {
            $data['group_name'] = $group_name['name'];
        }
        if (is_numeric($id) && $id > 0) {
            $nav = Admins::findOne('id = "' . $id . '"');
            if (!$nav) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }

            if (!$data['password']) {
                unset($data['password']);
            }

            if (!Admins::updateOne($data, ['id' => $id])) {
                Ajax::outError(Ajax::ERROR_NOTHING_HAS_CHANGED);
            }
        } else {
            $admin = new Admins();

            if (!$data['password']) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "新建账号是密码为必填~!");
            }
            // create
            $data['created'] = time();
            if (!$admin_id = $admin->insertOne($data)) {
                Ajax::outError(Ajax::ERROR_NOTHING_HAS_CHANGED);
            }
            // 新增权限
            $group_right = AdminGroupRight::findList('group_id=' . $group);
            if ($group_right) {
                foreach ($group_right as $item) {
                    $user_right = new AdminRight();
                    $user_right->insertOne(array(
                        'uid' => $admin_id,
                        'menu_id' => $item['menu_id'],
                        'right_type' => $item['right_type'],
                    ));
                }
            }

        }

        Ajax::outRight('');
    }

    /**
     * update menus
     */
    public function setMenuAction()
    {
        $menuCats = $this->request->get('menus');
        if (!$menuCats) {
            Ajax::outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        foreach ($menuCats as $cat) {
            $id = isset($cat['id']) ? $cat['id'] : null;
            unset($cat['id']); // for update it
            if ($id) {
                // update data
                $menu = AdminMenus::findOne('id=' . $id);
                if ($menu) {
                    AdminMenus::updateOne($cat, ['id' => $id]);
                }
            } else {
                //if $menu has no id ,then add this
                if ($cat['title']) {
                    $menu = new AdminMenus();
                    $menu->insertOne($cat);
                }
            }
        }

        // log
        Ajax::outRight();
    }

    /**
     * mv news to new cat
     */
    public function mvMenuAction()
    {
        // get params
        $cid = intval(filter_input(INPUT_POST, 'cid', FILTER_SANITIZE_NUMBER_INT));
        $data = $this->request->get('data');
        if (!($data && $data)) {
            Ajax::outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        $where = ' id in ( ' . implode(',', $data) . ' ) ';
        $menus = AdminMenus::findList($where);

        if ($menus) {
            foreach ($menus as $menu) {
                AdminMenus::updateOne(array('cid' => $cid), ['id' => $menu['id']]);
            }
        }

        Ajax::outRight();
    }


    /**
     * update menus
     */
    public function setMenuCatAction()
    {
        // request params
        $menuCats = $this->request->get('menuCats');
        if (!$menuCats) {
            Ajax::outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // each do
        foreach ($menuCats as $cat) {
            $id = isset($cat['id']) ? $cat['id'] : null;
            unset($cat['id']); // for update it
            if ($id) {
                // update data
                $item = AdminMenusCat::findOne('id=' . $id);
                if ($item) {
                    AdminMenusCat::updateOne($cat, ['id' => $id]);
                }
            } else {
                //if $menu has no id ,then add this
                if ($cat['title']) {
                    $item = new AdminMenusCat();
                    $item->insertOne($cat);
                }
            }
        }
        // log
        Ajax::outRight();
    }

    /**
     * set groups
     */
    public function upGroupAction()
    {
        // request params
        $groups = $this->request->get('groups');
        if (!$groups) {
            Ajax::outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // each do
        foreach ($groups as $group) {
            $id = isset($group['id']) ? $group['id'] : null;
            $data = array('name' => $group['name'], 'desc' => $group['desc']);
            unset($group['id']); // for update it
            if ($id) {
                $item = AdminGroup::findOne('id=' . $id);
                if ($item) {
                    AdminGroup::updateOne($data, ['id' => $id]);
                }
            } else {
                //if $menu has no id ,then add this
                if ($group['name']) {
                    $item = new AdminGroup();
                    $item->insertOne($data);
                }
            }
        }

        // log
        Ajax::outRight();
    }

    /**
     * modify user permission
     */
    public function setUserPermAction()
    {
        $permissions = $this->request->get('permissions');
        $uid = $this->request->get('uid');
        $permissions = json_decode($permissions, true);

        if (!($permissions && $uid)) {
            Ajax::outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // update group permissions
        foreach ($permissions as $permission) {
            $menu = AdminMenus::findOne('id=' . $permission['menu_id']);
            if (!$menu) {
                continue;
            }
            $permission['module'] = $menu['module'];
            $permission['controller'] = $menu['controller'];
            $permission['action'] = $menu['action'];

            $id = isset($permission['perm_id']) ? $permission['perm_id'] : '';
            unset($permission['perm_id']); // for update it
            if ($id) {
                $item = AdminRight::findOne('id=' . $id);
                if ($item) {
                    AdminRight::updateOne($permission, ['id' => $id]);
                }
            } else {
                if ($permission['menu_id']) {
                    $permission['uid'] = $uid;
                    $item = new AdminRight();
                    $item->insertOne($permission);
                }
            }
        }

        // log
        Ajax::outRight('管理员权限更新成功');
    }

    /**
     * modify user permission
     */
    public function setGroupPermAction()
    {
        $permissions = $this->request->get('permissions');
        $gid = $this->request->get('gid');
        $permissions = json_decode($permissions, true);

        if (!($permissions && $gid)) {
            Ajax::outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // update group permissions
        foreach ($permissions as $permission) {
            $menu = AdminMenus::findOne('id=' . $permission['menu_id']);
            if (!$menu) {
                continue;
            }
            $permission['module'] = $menu['module'];
            $permission['controller'] = $menu['controller'];
            $permission['action'] = $menu['action'];

            $id = isset($permission['perm_id']) ? $permission['perm_id'] : '';
            unset($permission['perm_id']); // for update it
            if ($id) {
                $item = AdminGroupRight::findOne('id=' . $id);
                if ($item) {
                    AdminGroupRight::updateOne($permission, ['id' => $id]);
                }
            } else {
                //if $menu has no id ,then add this
                if ($permission['menu_id']) {
                    $permission['group_id'] = $gid;
                    $item = new AdminGroupRight();
                    $item->insertOne($permission);
                }
            }
        }

        // log
        Ajax::outRight('管理组权限更新成功');
    }

}