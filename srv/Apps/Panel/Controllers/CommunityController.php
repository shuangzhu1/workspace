<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/11
 * Time: 16:27
 */

namespace Multiple\Panel\Controllers;


use Models\Community\Community;
use Models\Community\CommunityApply;
use Models\Community\CommunityDiscuss;
use Models\Community\CommunityNews;
use Models\Community\CommunityProfile;
use Models\Group\Group;
use Models\Group\GroupMember;
use Models\User\UserInfo;
use Models\User\Users;
use Util\Pagination;

class CommunityController extends ControllerBase
{
    public function listAction()#社区列表#
    {
        $user_id = $this->request->get("user_id", 'int', 0);

        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("p", 'int', 1);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($user_id) {
            $params[0][] = ' user_id  = ' . $user_id;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = Community::dataCount($params[0]);
        $list = Community::findList($params);

        if ($list) {
            $user_ids = array_unique(array_column($list, 'user_id'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
            $comm_ids = array_column($list, 'id');
            $profile = CommunityProfile::getByColumnKeyList(['comm_id in (' . implode(',', $comm_ids) . ')', 'columns' => 'attention_cnt,discuss_cnt,group_cnt,comm_id,cover,brief'], 'comm_id');
            foreach ($list as $k => $item) {
                $list[$k] = array_merge($list[$k], $profile[$item['id']]);
            }
        }


        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('user_id', $user_id);
    }

    public function applyListAction()#申请列表#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("p", 'int', 1);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $status = $this->request->get("status", 'int', 1);
        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($user_id) {
            $params[0][] = ' user_id  = ' . $user_id;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($status >= 0) {
            $params[0][] = ' status  = ' . $status;
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = CommunityApply::dataCount($params[0]);
        $list = CommunityApply::findList($params);

        if ($list) {
            $user_ids = array_unique(array_column($list, 'user_id'));
            $user_ids = array_unique(array_merge($user_ids, array_column($list, 'code_owner')));

            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('status', $status);

    }

    public function groupListAction()#社群列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $status = $this->request->get('status', 'int', 1);
        $key = $this->request->get('key', 'string', '');
        $name = $this->request->get('name', 'string', '');
        $start = $this->request->get('start', 'string', '');
        $comm_id = $this->request->get('comm_id', 'int', 0);

        $end = $this->request->get('end', 'string', '');
        $gid = $this->request->get('gid', 'int', 0);
        $yx_gid = $this->request->get('yx_gid', 'int', 0);

        $params = [];
        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($comm_id) {
            $params[0][] = " comm_id=" . $comm_id;
        } else {
            $params[0][] = " comm_id>0";
        }
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
            $comm_ids = array_unique(array_column($list, 'comm_id'));
            //群成员数
            $group_member_count = GroupMember::getByColumnKeyList(["gid in (" . implode(',', $gids) . ')', 'columns' => 'count(1) as count,gid', 'group' => 'gid'], 'gid');
            //管理员信息
            $admin_info = GroupMember::getByColumnKeyList(['user_id in (' . implode(',', $admins) . ')', 'columns' => 'nick,default_nick,user_id'], 'user_id');
            $community_info = Community::getByColumnKeyList(['id in (' . implode(',', $comm_ids) . ')', 'columns' => 'id,name'], 'id');

            foreach ($list as &$item) {
                $item['admin_info'] = $admin_info[$item['user_id']];
                $item['member_count'] = $group_member_count[$item['id']]['count'];
                $item['comm_name'] = $community_info[$item['comm_id']]['name'];

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
        $this->view->setVar('comm_id', $comm_id);
    }

    public function discussAction()#动态列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $key = $this->request->get('key', 'string', '');//关键字
        $status = $this->request->get('status', 'int', '-1');//状态
        $media_type = $this->request->get('media_type', 'int', 0);//媒体类型
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序
        $comm_id = $this->request->get('comm_id', 'int', 0);//社区id


        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($comm_id) {
            $params[0][] = ' comm_id = ' . $comm_id;
        }
        if ($status != -1) {
            $params[0][] = ' status = ' . $status;
        }
        if ($media_type) {
            $params[0][] = ' media_type = ' . $media_type;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }


        $params['columns'] = "*";

        $columns = 'id,created';
        //排序
        if ($sort) {

            if ($sort == 'created') {
                $params['order'] = " created $sort_order";
            } else if ($sort == 'fav') {
                $params['order'] = "fav_cnt $sort_order, created desc";
                $columns .= ",fav_cnt";
            } else if ($sort == 'comment') {
                $params['order'] = "comment_cnt $sort_order, created desc";
                $columns .= ",comment_cnt";
            } else if ($sort == 'like') {
                $params['order'] = "like_cnt $sort_order, created desc";
                $columns .= ",like_cnt";
            } else if ($sort == 'report') {
                $params['order'] = "report_cnt $sort_order, created desc";
                $columns .= ",report_cnt";

            } else if ($sort == 'package') {
                if ($sort_order == 'desc') {
                    $params[0][] = ' package_id<>"" ';
                } else {
                    $params[0][] = ' package_id="" ';
                }
                $params['order'] = "created desc";

            } else if ($sort == 'forward') {
                $params['order'] = "forward_cnt $sort_order, created desc";
                $columns .= ",forward_cnt";

            } else if ($sort == 'view') {
                $params['order'] = "view_cnt $sort_order, created desc";
                $columns .= ",view_cnt";
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = CommunityDiscuss::dataCount($params[0]);
        $list = [];
        $ids = (CommunityDiscuss::getColumn([$params[0], 'columns' => $columns, 'order' => $params['order'], 'limit' => $params['limit'], 'offset' => $params['offset']], 'id'));
        if ($ids) {
            $list = CommunityDiscuss::findList(['id in (' . implode(',', $ids) . ')', 'order' => $params['order']]);
        }

        $this->view->setVar('key', $key);
        $this->view->setVar('status', $status);
        $this->view->setVar('media_type', $media_type);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('comm_id', $comm_id);

        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('list', $list);

        $this->view->setVar('where', base64_encode($params[0]));
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    public function newsAction()#新闻列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $key = $this->request->get('key', 'string', '');//关键字
        $status = $this->request->get('status', 'int', '-1');//状态
        $media_type = $this->request->get('media_type', 'int', 0);//媒体类型
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序
        $comm_id = $this->request->get('comm_id', 'int', 0);//社区id


        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($comm_id) {
            $params[0][] = ' comm_id = ' . $comm_id;
        }
        if ($status != -1) {
            $params[0][] = ' status = ' . $status;
        }
        if ($media_type) {
            $params[0][] = ' media_type = ' . $media_type;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }


        $params['columns'] = "*";

        $columns = 'id,created';
        //排序
        if ($sort) {

            if ($sort == 'created') {
                $params['order'] = " created $sort_order";
            } else if ($sort == 'fav') {
                $params['order'] = "fav_cnt $sort_order, created desc";
                $columns .= ",fav_cnt";
            } else if ($sort == 'comment') {
                $params['order'] = "comment_cnt $sort_order, created desc";
                $columns .= ",comment_cnt";
            } else if ($sort == 'like') {
                $params['order'] = "like_cnt $sort_order, created desc";
                $columns .= ",like_cnt";
            } else if ($sort == 'report') {
                $params['order'] = "report_cnt $sort_order, created desc";
                $columns .= ",report_cnt";

            } else if ($sort == 'package') {
                if ($sort_order == 'desc') {
                    $params[0][] = ' package_id<>"" ';
                } else {
                    $params[0][] = ' package_id="" ';
                }
                $params['order'] = "created desc";

            } else if ($sort == 'forward') {
                $params['order'] = "forward_cnt $sort_order, created desc";
                $columns .= ",forward_cnt";

            } else if ($sort == 'view') {
                $params['order'] = "view_cnt $sort_order, created desc";
                $columns .= ",view_cnt";
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = CommunityNews::dataCount($params[0]);
        $list = [];
        $ids = (CommunityNews::getColumn([$params[0], 'columns' => $columns, 'order' => $params['order'], 'limit' => $params['limit'], 'offset' => $params['offset']], 'id'));
        if ($ids) {
            $list = CommunityNews::findList(['id in (' . implode(',', $ids) . ')', 'order' => $params['order']]);
        }

        $this->view->setVar('key', $key);
        $this->view->setVar('status', $status);
        $this->view->setVar('media_type', $media_type);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('comm_id', $comm_id);

        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('list', $list);

        $this->view->setVar('where', base64_encode($params[0]));
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }
}