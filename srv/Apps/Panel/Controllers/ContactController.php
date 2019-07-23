<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/4
 * Time: 11:15
 */

namespace Multiple\Panel\Controllers;


use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\Users;
use Util\Pagination;

class ContactController extends ControllerBase
{


    //关注列表
    public function followersAction()#关注列表#
    {
        $uid = $this->request->get("user_id", 'int', 0);
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $key = $this->request->get("key", 'string', '');
        if (!$uid) {
            $this->err("404", '无效的参数');
            return;
        }
        $params = ['owner_id=' . $uid];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0] .= ' and user_id in (' . implode(',', $users) . ')';
            } else {
                $params[0] .= " and 1=0";
            }
        }
        $count = UserAttention::dataCount($params[0]);
        $list = UserAttention::findList($params);

        if ($count > 0) {
            $uids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id,username,true_name,avatar,sex'], 'user_id');
            $person_settings = UserPersonalSetting::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ') and owner_id=' . $uid], 'user_id');
            $contact_members = UserContactMember::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ') and owner_id=' . $uid], 'user_id');
            foreach ($list as &$item) {
                $item['user_info'] = $users[$item['user_id']];
                if (isset($person_settings[$item['user_id']])) {
                    $item['personal_setting'] = $person_settings[$item['user_id']];
                } else {
                    $item['personal_setting'] = [];
                }
                if (isset($contact_members[$item['user_id']])) {
                    $item['contact_member'] = $contact_members[$item['user_id']];
                } else {
                    $item['contact_member'] = [];
                }
            }
        }
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('uid', $uid);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //粉丝列表
    public function fansAction()#粉丝列表#
    {
        $uid = $this->request->get("user_id", 'int', 0);
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $key = $this->request->get("key", 'string', '');
        if (!$uid) {
            $this->err("404", '无效的参数');
            return;
        }
        $params = ['user_id=' . $uid];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0] .= ' and owner_id in (' . implode(',', $users) . ')';
            } else {
                $params[0] .= " and 1=0";
            }
        }
        $count = UserAttention::dataCount($params[0]);
        $list = UserAttention::findList($params);

        if ($count > 0) {
            $uids = array_column($list, 'owner_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id,username,true_name,avatar,sex'], 'user_id');
            $person_settings = UserPersonalSetting::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ') and owner_id=' . $uid], 'user_id');
            $contact_members = UserContactMember::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ') and owner_id=' . $uid], 'user_id');
            foreach ($list as &$item) {
                $item['user_info'] = $users[$item['owner_id']];
                if (isset($person_settings[$item['owner_id']])) {
                    $item['personal_setting'] = $person_settings[$item['owner_id']];
                } else {
                    $item['personal_setting'] = [];
                }
                if (isset($contact_members[$item['owner_id']])) {
                    $item['contact_member'] = $contact_members[$item['owner_id']];
                } else {
                    $item['contact_member'] = [];
                }
            }
        }
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('uid', $uid);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //黑名单列表
    public function blacklistAction()#黑名单列表#
    {
        $uid = $this->request->get("user_id", 'int', 0);
        $to_user_id = $this->request->get("to_user_id", 'int', 0);

        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $key = $this->request->get("key", 'string', '');
        if (!$uid && !$to_user_id) {
            $this->err("404", '无效的参数');
            return;
        }
        if ($uid) {
            $params = ['owner_id=' . $uid];
        } else {
            $params = ['user_id=' . $to_user_id];
        }
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0] .= $uid ? ' and user_id in (' . implode(',', $users) . ')' : ' and owner_id in (' . implode(',', $users) . ')';
            } else {
                $params[0] .= " and 1=0";
            }
        }
        $count = UserBlacklist::dataCount($params[0]);
        $list = UserBlacklist::findList($params);

        if ($count > 0) {
            if ($uid) {
                $uids = array_column($list, 'user_id');
            } else {
                $uids = array_column($list, 'owner_id');
            }

            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id,username,true_name,avatar,sex'], 'user_id');
            foreach ($list as &$item) {
                $item['user_info'] = $uid ? $users[$item['user_id']] : $users[$item['owner_id']];
            }
        }
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('to_uid', $to_user_id);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //好友列表
    public function friendsAction()#好友列表#
    {
        $uid = $this->request->get("user_id", 'int', 0);
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $key = $this->request->get("key", 'string', '');
        if (!$uid) {
            $this->err("404", '无效的参数');
            return;
        }
        $params = ['owner_id=' . $uid];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($key) {
            $params[0] .= ' and (default_mark like "%' . $key . '%" or mark like "%' . $key . '%" or user_id="' . $key . '")';
        }
        $count = UserContactMember::dataCount($params[0]);
        $list = UserContactMember::findList($params);

        if ($count > 0) {
            $uids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id,username,true_name,avatar,sex'], 'user_id');
            $person_settings = UserPersonalSetting::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ') and owner_id=' . $uid], 'user_id');
            foreach ($list as &$item) {
                $item['user_info'] = $users[$item['user_id']];
                if (isset($person_settings[$item['user_id']])) {
                    $item['personal_setting'] = $person_settings[$item['user_id']];
                } else {
                    $item['personal_setting'] = [];
                }
            }
        }
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('uid', $uid);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }
}