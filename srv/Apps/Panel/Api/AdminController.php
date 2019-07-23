<?php

namespace Multiple\Panel\Api;


use Models\Admin\AdminGroup;
use Models\Admin\AdminGroupRight;
use Models\Admin\AdminMenus;
use Models\Admin\AdminMenusCat;
use Models\Admin\AdminRight;
use Models\Admin\Admins;
use Models\User\UserInfo;
use Models\User\Users;
use Services\Admin\AdminLog;
use Services\User\UserStatus;
use Util\Ajax;


class AdminController extends ApiBase
{

    public function delAction()
    {
        $auth = $this->session->get('customer_auth');
        if (!$auth) {
            $this->ajax->outError(Ajax::ERROR_USER_HAS_NOT_LOGIN);
        }

        $id = intval($this->request->get('data'));
        if (!$id) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        $item = Admins::findOne('id =' . $id);
        if (!$item) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (!Admins::updateOne(['status' => AdminLog::STATUS_DELETED], ['id' => $id])) {
            $this->ajax->outError(Ajax::ERROR_RUN_TIME_ERROR_OCCURRED);
        }
        AdminLog::init()->add('删除后台账号', AdminLog::TYPE_ADMIN, $id, array('type' => "del", 'id' => $id));

        $this->ajax->outRight('');
    }

    public function saveAction()
    {
        $auth = $this->session->get('customer_auth');
        if (!$auth) {
            $this->ajax->outError(Ajax::ERROR_USER_HAS_NOT_LOGIN);
        }

        $data = $this->request->get('data');
        $id = $this->request->get('id');
        if (!$data) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // 密码判断
        if ($data['password'] != '') {
            $len = strlen($data['password']);
            if (!($len >= 6 && $len <= 16)) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "密码长度为6-16位,如不更改,请留空");
            }
            $data['password'] = sha1($data['password']);
        }
        // 组
        $group = $data['group'];
        $data['group_name'] = "游客";

        $data['`group`'] = $group;
        unset($data['group']);
        $data['status'] = $data['active'];
        unset($data['active']);

        $group_name = AdminGroup::findOne('id=' . $group);
        if ($group_name) {
            $data['group_name'] = $group_name['name'];
        }


        if (is_numeric($id) && $id > 0) {
            $nav = Admins::findOne('id = ' . $id);
            if (!$nav) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }

            if (!$data['password']) {
                unset($data['password']);
            }
            //修改自己的资料
            if ($this->admin['id'] == $id) {
                unset($data['level']);
                unset($data['group']);
                unset($data['active']);
                unset($data['account']);
            }
            if (!Admins::updateOne($data, ['id' => $id])) {
                $this->ajax->outError(Ajax::ERROR_NOTHING_HAS_CHANGED);
            }


            AdminLog::init()->add('更新后台账号', AdminLog::TYPE_ADMIN, $id, array('type' => "update", 'id' => $id, 'data' => $data));
        } else {
            $admin = new Admins();

            if (!$data['password']) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "新建账号是密码为必填~!");
            }

            // create
            $data['created'] = time();
            if (!$admin_id = $admin->insertOne($data)) {
                $this->ajax->outError(Ajax::ERROR_NOTHING_HAS_CHANGED);
            }

            AdminLog::init()->add('添加后台账号', AdminLog::TYPE_ADMIN, $admin_id, array('type' => "del", 'id' => $admin_id, 'data' => $data));

        }

        $this->ajax->outRight('');
    }

    /**
     * update menus
     */
    public function setMenuAction()
    {
        $menuCats = $this->request->get('menus');
        if (!$menuCats) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
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
        AdminLog::init()->add('添加后台菜单', AdminLog::TYPE_MENU, 0, array('type' => "add", 'id' => 0, 'data' => $menuCats));

        // log
        $this->ajax->outRight();
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
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        $where = ' id in ( ' . implode(',', $data) . ' ) ';
        $menus = AdminMenus::findList($where);

        if ($menus) {
            foreach ($menus as $menu) {
                AdminMenus::updateOne(array('cid' => $cid), 'id=' . $menu['id']);
            }
        }
        AdminLog::init()->add('移动后台菜单', AdminLog::TYPE_MENU, $cid, array('type' => "update", 'id' => $cid, 'data' => $data));

        $this->ajax->outRight();
    }


    /**
     * update menus
     */
    public function setMenuCatAction()
    {
        // request params
        $menuCats = $this->request->get('menuCats');
        if (!$menuCats) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
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
        AdminLog::init()->add('设置后台菜单', AdminLog::TYPE_MENU, 0, array('type' => "add", 'id' => 0, 'data' => $menuCats));

        // log
        $this->ajax->outRight();
    }

    /**
     * set groups
     */
    public function upGroupAction()
    {
        // request params
        $groups = $this->request->get('groups');
        if (!$groups) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // each do
        foreach ($groups as $group) {
            $id = isset($group['id']) ? $group['id'] : null;
            $data = array('name' => $group['name'], '`desc`' => $group['desc']);
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
        AdminLog::init()->add('更新管理员分组', AdminLog::TYPE_ADMIN, 0, array('type' => "update", 'id' => 0, 'data' => $groups));

        // log
        $this->ajax->outRight();
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
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
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
            $permission['mvc_uri'] = $menu['mvc_uri'];
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
        AdminLog::init()->add('更新管理员权限', AdminLog::TYPE_ADMIN, $uid, array('type' => "update", 'id' => $uid, 'data' => $permissions));

        // log
        $this->ajax->outRight('管理员权限更新成功');
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
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
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
            $permission['mvc_uri'] = $menu['mvc_uri'];
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
        AdminLog::init()->add('更新管理员分组权限', AdminLog::TYPE_ADMIN, $gid, array('type' => "update", 'id' => $gid, 'data' => $permissions));

        // log
        $this->ajax->outRight('管理组权限更新成功');
    }

    //app关联用户【单个】
    public function addAppUidAction()
    {
        $app_uid = $this->request->getPost("app_uid", 'int', 0);
        if (!$app_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!Users::exist('id=' . $app_uid)) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "app用户不存在");
        }
        if ($admins = Admins::findOne(['LOCATE("' . $app_uid . ',",concat(app_uid,","))>0', 'columns' => 'id,app_uid'])) {
            if ($admins['id'] == $this->admin['id']) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "你已经关联了该app用户");
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "该app用户已被其他管理员关联了");
            }
        }
        $admins = Admins::findOne(['id=' . $this->admin['id']]);
        $app_uid = $admins['app_uid'] ? $app_uid . ',' . $admins['app_uid'] : $app_uid;
        if (Admins::updateOne(['app_uid' => $app_uid], ['id' => $this->admin['id']])) {
            $admins['app_uid'] = $app_uid;
            $this->session->set('admin', $admins);
            AdminLog::init()->add('关联app账号', AdminLog::TYPE_ADMIN, $app_uid, array('type' => "update", 'id' => $app_uid));

            $this->ajax->outRight("");
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "添加失败");
        }
    }

//app关联用户【批量】
    public function addAppUidsAction()
    {
        $app_uid = $this->request->getPost("app_uid");
        if (!$app_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $uids = '';
        foreach ($app_uid as $u) {
            if (!Users::exist('id=' . $u)) {
                continue;
            }
            if ($admins = Admins::findOne(['LOCATE("' . $app_uid . ',",concat(app_uid,","))>0', 'columns' => 'id,app_uid'])) {
                continue;
            }
            $uids .= $u . ",";
        }
        if ($uids) {
            $uids = substr($uids, 0, -1);
            $admins = Admins::findOne(['id=' . $this->admin['id']]);
            $tmp_app_uid = $admins['app_uid'] ? $uids . ',' . $admins['app_uid'] : $uids;
            if (Admins::updateOne(['app_uid' => $tmp_app_uid], ['id' => $this->admin['id']])) {
                $admins['app_uid'] = $tmp_app_uid;
                $this->session->set('admin', $admins);
                AdminLog::init()->add('关联app账号', AdminLog::TYPE_ADMIN, 0, array('type' => "update", 'id' => $uids));
            }
        }

        $this->ajax->outRight("");
    }

    public function removeAppUidAction()
    {
        $app_uid = $this->request->getPost("app_uid", 'int', 0);
        if (!$app_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $admins = Admins::findOne(['id=' . $this->admin['id']]);
        if (!$admins['app_uid']) {
            $this->ajax->outRight("");
        }

        $admins['app_uid'] = str_replace($app_uid . ',', '', $admins['app_uid']);
        $admins['app_uid'] = str_replace($app_uid, '', $admins['app_uid']);
        if (substr($admins['app_uid'], -1, 1) == ',') {
            $admins['app_uid'] = substr($admins['app_uid'], 0, -1);
        }

        if (Admins::updateOne(['app_uid' => $admins['app_uid']], ['id' => $this->admin['id']])) {
            $this->session->set('admin', $admins);
            AdminLog::init()->add('取消关联app账号', AdminLog::TYPE_ADMIN, $app_uid, array('type' => "update", 'id' => $app_uid));

            $this->ajax->outRight("");
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "删除失败");
        }
    }


    //获取机器人列表
    public function getUserAction()
    {
        $limit = $this->request->get('limit', 'int', 10); //每页显示的数量
        $page = $this->request->get('page', 'int', 1); //第几页
        $key = $this->request->get('key', 'string', ''); //关键字

        $uids = Admins::findOne(['app_uid<>""', 'columns' => 'group_concat(app_uid,",") as uids']);
        $used_uids = $uids ? array_filter(array_unique(explode(',', $uids['uids']))) : [];
        $where = 'user_type=' . UserStatus::USER_TYPE_ROBOT . " and (user_id<71041 or user_id>71078)";
        if ($key) {
            if (strlen($key) == 11 && preg_match('/^1[\d]{10}$/', $key)) {
                $where .= ' and  (phone=' . $key . ' or username like "%' . $key . '%") ';
            } else if (strlen($key) >= 5 && preg_match('/^[1-9][\d]{4,}$/', $key)) {
                $where .= ' and  (user_id=' . $key . ' or username like "%' . $key . '%") ';
            } else {
                $where .= ' and username like "%' . $key . '%"';
            }
        }
        $count = UserInfo::dataCount($where); //总记录数
        $res = UserInfo::findList(array($where, 'columns' => 'user_id as id,avatar,username,sex,phone,created', 'order' => 'id asc', 'offset' => ($limit * ($page - 1)), "limit" => $limit));
        $user_arr = array();
        $result = array();
        foreach ($res as $item) {
            $user_arr[] = $item['id'];
            $item['created'] = date('Y年m月d日', $item['created']);
            if (in_array($item['id'], $used_uids)) {
                $item['enable'] = 0;
            } else {
                $item['enable'] = 1;
            }
            $result[$item['id']] = $item;
        }
        $pageBar = $this->getPageBar($page, $limit, $count, 4);
        $data = "";
        if ($res) {
            foreach ($result as $r) {
                $data .= $this->getFromOB('admin/partial/robot_item', array('item' => $r));
            }
        }

        $this->ajax->outRight(array('limit' => $limit, 'page' => $page, 'pageBar' => $pageBar, 'data' => $data, 'count' => $count, 'res' => $result));
    }

    /**
     * @param $page --当前第几页
     * @param int $limit 每页显示的数据
     * @param $count --总的数据量
     * @param int $page_size --页面显示几个导航框(1,2,3)
     * @return string
     *
     *
     */
    public function getPageBar($page, $limit = 10, $count, $page_size = 6)
    {
        $bar = "";
        if ($count == 0) {
            return "";
        }
        $total_page = ceil($count / $limit);
        if ($page > 1) {
            $bar .= "<a href='javascript:;' data-page='1'>首页</a>";
            $bar .= "<a href='javascript:;' data-page='" . ($page - 1) . "'>上一页</a>";
        }
        if ($total_page <= $page_size) {
            for ($i = 1; $i <= $total_page; $i++) {
                if ($page == $i) {
                    $bar .= "<a href='javascript:;' class='curr' data-page='" . $i . "'>" . $i . "</a>";
                } else {
                    $bar .= "<a href='javascript:;' data-page='" . $i . "'>" . $i . "</a>";
                }

            }
        } else {
            if ($page < $page_size) {
                for ($i = 1; $i <= $page_size; $i++) {
                    if ($page == $i) {
                        $bar .= "<a href='javascript:;' class='curr' data-page='" . $i . "'>" . $i . "</a>";
                    } else {
                        $bar .= "<a href='javascript:;' data-page='" . $i . "'>" . $i . "</a>";
                    }
                }
            } else if ($page >= $page_size && $page < $total_page) {
                for ($i = $page - $page_size + 2; $i <= $page + 1; $i++) {
                    if ($page == $i) {
                        $bar .= "<a href='javascript:;' class='curr' data-page='" . $i . "'>" . $i . "</a>";
                    } else {
                        $bar .= "<a href='javascript:;' data-page='" . $i . "'>" . $i . "</a>";
                    }
                }
            } else if ($page == $total_page) {

                for ($i = $page - $page_size + 1; $i <= $page; $i++) {
                    if ($page == $i) {
                        $bar .= "<a href='javascript:;' class='curr' data-page='" . $i . "'>" . $i . "</a>";
                    } else {
                        $bar .= "<a href='javascript:;' data-page='" . $i . "'>" . $i . "</a>";
                    }
                }

            }

        }
        if ($total_page > 1 && $page != $total_page) {
            $bar .= "<a href='javascript::' data-page='" . ($page + 1) . "'>下一页</a>";

            $bar .= "<a href='javascript:;' data-page='" . ($total_page) . "' >尾页</a>";

        }
        $bar .= "<a href='javascript:;'>共" . $total_page . "页</a>";
        return $bar;
    }
}