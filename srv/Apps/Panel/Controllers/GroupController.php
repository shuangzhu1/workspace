<?php

namespace Multiple\Panel\Controllers;

use Models\Group\Group;
use Models\Group\GroupMember;
use Models\Group\GroupReport;
use Models\Social\SocialDiscuss;
use Models\Social\SocialReport;
use Models\User\UserAttention;
use Models\User\UserInfo;
use Models\User\Users;
use Phalcon\Mvc\Model;
use Phalcon\Tag;
use Services\Admin\AdminLog;
use Util\Pagination;

class GroupController extends ControllerBase
{
    public function listAction()#群聊列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $status = $this->request->get('status', 'int', 1);
        $key = $this->request->get('key', 'string', '');
        $name = $this->request->get('name', 'string', '');
        $start = $this->request->get('start', 'string', '');
        $end = $this->request->get('end', 'string', '');
        $gid = $this->request->get('gid', 'int', 0);
        $yx_gid = $this->request->get('yx_gid', 'int', 0);

        $params = [];
        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params[0][] = " comm_id=0";
        if ($status != '-1') {
            $params[0][] = " status=" . $status;
        }
        if ($name) {
            $params[0][] = " (name like '%" . $name . "%' or default_name like '%" . $name . "%')";
        }
        if ($gid) {
            $params[0][] = " (id =$gid)";
        }
        if ($yx_gid) {
            $params[0][] = " (yx_gid =$yx_gid)";
        }
        if ($start) {
            $params[0][] = " created>=" . strtotime($start);
        }
        if ($end) {
            $params[0][] = " created<=" . (strtotime($end) + 86400);
        }
        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username="' . $key . '" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $list = Group::findList($params);
        $count = Group::dataCount($params[0]);
        // $ret = ProductGroupManager::init()->get_list(array('with_count' => 1), $page, $limit);
        // $count = $ret['count'];
        // $list = $ret['list'];
        if ($list) {
            $gids = array_column($list, 'id');
            $admins = array_unique(array_column($list, 'user_id'));
            //群成员数
            $group_member_count = GroupMember::getByColumnKeyList(["gid in (" . implode(',', $gids) . ')', 'columns' => 'count(1) as count,gid', 'group' => 'gid'], 'gid');
            //管理员信息
            $admin_info = GroupMember::getByColumnKeyList(['user_id in (' . implode(',', $admins) . ')', 'columns' => 'nick,default_nick,user_id'], 'user_id');
            foreach ($list as &$item) {
                $item['admin_info'] = $admin_info[$item['user_id']];
                $item['member_count'] = $group_member_count[$item['id']]['count'];
            }
        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);

        $this->view->setVar('list', $list);
        $this->view->setVar('status', $status);
        $this->view->setVar('key', $key);
        $this->view->setVar('name', $name);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('gid', $gid);
        $this->view->setVar('yx_gid', $yx_gid);
    }

    //加入的群聊列表
    public function joinListAction() #加入的群聊列表#
    {
        $uid = $this->request->get("user_id", 'int', 0);
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $key = $this->request->get("key", 'string', '');
        $status = $this->request->get('status', 'int', -1);
        $start = $this->request->get('start', 'string', '');
        $end = $this->request->get('end', 'string', '');
        if (!$uid) {
            $this->err("404", '无效的参数');
            return;
        }


        $count = 0;
        $list = [];
        $gids = [];
        $admins = [];
        if ($key) {
            if (is_int($key)) {
                $where = "gm.user_id=" . $uid . " and (g.name like '%$key%' or g.default_name like '%$key%' or g.id=$key) ";
            } else {
                $where = "gm.user_id=" . $uid . " and (g.name like '%$key%' or g.default_name like '%$key%') ";
            }
            if ($status != '-1') {
                $where .= " and g.status=" . $status;
            }
            if ($start) {
                $where .= " and g.created>=" . strtotime($start);
            }
            if ($end) {
                $where .= " and g.created<=" . (strtotime($end) + 86400);
            }

            $count = $this->db->query("select g.id,gm.user_id,g.status,g.created from `group` as g left join group_member as gm on g.id=gm.gid where $where group by g.id,gm.user_id")->fetchAll(\PDO::FETCH_ASSOC);
            $count = count($count);
            if ($count > 0) {
                $list = $this->db->query("select g.*,gm.default_nick,gm.nick from `group` as g left join group_member as gm on g.id=gm.gid where $where group by g.id,gm.user_id  order by g.created desc limit " . ($page - 1) * $limit . ',' . $limit)->fetchAll(\PDO::FETCH_ASSOC);
            }
            $gids = array_column($list, 'id');
            $admins = array_unique(array_column($list, 'user_id'));
        } else {
            $params = ['user_id=' . $uid];

            $params['offset'] = ($page - 1) * $limit;
            $params['limit'] = $limit;
            $params['columns'] = 'gid,default_nick,nick,user_id';
            if ($status != '-1') {
                $params[0] .= " and status=" . $status;
            }
            if ($start) {
                $params[0] .= " and created>=" . strtotime($start);
            }
            if ($end) {
                $params[0] .= " and created<=" . (strtotime($end) + 86400);
            }

            $member_list = GroupMember::getByColumnKeyList($params, 'gid');

            $count = GroupMember::dataCount($params[0]);
            if ($member_list) {
                $gids = array_column($member_list, 'gid');

                $params = [];
                $params[0] = 'id in (' . implode(',', $gids) . ')';
                $params['order'] = 'created desc';
                $params['columns'] = '';
                $list = Group::findList($params);
                foreach ($list as &$item) {
                    $item['default_nick'] = $member_list[$item['id']]['default_ick'];
                    $item['nick'] = $member_list[$item['id']]['nick'];
                    $admins[] = $item['user_id'];
                }
            }
        }

        if ($list) {
            //群成员数
            $group_member_count = GroupMember::getByColumnKeyList(["gid in (" . implode(',', $gids) . ')', 'columns' => 'count(1) as count,gid', 'group' => 'gid'], 'gid');
            //管理员信息
            $admin_info = GroupMember::getByColumnKeyList(['user_id in (' . implode(',', $admins) . ')', 'columns' => 'nick,default_nick,user_id'], 'user_id');
            foreach ($list as &$item) {
                $item['admin_info'] = $admin_info[$item['user_id']];
                $item['member_count'] = $group_member_count[$item['id']]['count'];
            }
        }
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('status', $status);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    /*
        群组详情
    */
    public function detailAction()#群聊详情#
    {
        $group_id = $this->dispatcher->getParam(0);//群组id
        $group = Group::findOne("id=" . $group_id);
        if (!$group) {
            $this->err(404, '数据不存在');
        }
        $group['report_cnt'] = GroupReport::dataCount('gid=' . $group_id . ' and user_id=0');
        $logs = AdminLog::init()->getLogs(AdminLog::TYPE_GROUP, $group_id);
        $user_info = UserInfo::findOne(['user_id=' . $group['user_id']]);
        $user_info['group_cnt'] = Group::dataCount('user_id=' . $group['user_id']);
        $user_info['follower_cnt'] = UserAttention::dataCount('user_id=' . $group['user_id']);
        $user_info['attention_cnt'] = UserAttention::dataCount('owner_id=' . $group['user_id']);
        $user_info['report_cnt'] = SocialReport::dataCount('user_id=' . $group['user_id']);
        $group['member_count'] = GroupMember::dataCount('gid=' . $group_id);
        $this->view->setVar('logs', $logs);
        $this->view->setVar('user_info', $user_info);
        $this->view->setVar('item', $group);
        $bar = Pagination::getAjaxPageBar($group['member_count'], 1, 10, 5);
        $this->view->setVar('bar', $bar);
    }

} 

