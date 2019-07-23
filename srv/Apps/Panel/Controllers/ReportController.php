<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/21
 * Time: 14:57
 */

namespace Multiple\Panel\Controllers;


use Models\Admin\Admins;
use Models\Group\Group;
use Models\Group\GroupReport;
use Models\Social\SocialReport;
use Models\User\UserInfo;
use Util\Pagination;

class ReportController extends ControllerBase
{
    //动态举报
    public function discussAction()#动态举报#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $discuss_id = $this->request->get('discuss_id', 'int', 0);//动态id
        $reporter = $this->request->get('reporter', 'int', 0);//举报人id

        $check_start = $this->request->get('check_start', 'string', '');//审核开始时间
        $check_end = $this->request->get('check_end', 'string', '');//审核结束时间

        $report_start = $this->request->get('report_start', 'string', '');//举报开始时间
        $report_end = $this->request->get('report_end', 'string', '');//举报结束时间
        $type = $this->request->get('type', 'int', 2);//类型 0-所有 1-已处理 2-待处理

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params[0][] = "type='discuss'";


        if ($check_start) {
            $params[0][] = ' check_time  >= ' . strtotime($check_start);
        }
        if ($check_end) {
            $params[0][] = ' check_time  <= ' . (strtotime($check_end) + 86400);
        }
        if ($report_start) {
            $params[0][] = ' created  >= ' . strtotime($report_start);
        }
        if ($report_end) {
            $params[0][] = ' created  <= ' . (strtotime($report_end) + 86400);
        }
        if ($discuss_id) {
            $params[0][] = ' item_id  = "' . $discuss_id . '"';
        }
        if ($reporter) {
            $params[0][] = ' reporter  = "' . $reporter . '"';
        }
        if ($type) {
            if ($type == 1) {
                $params[0][] = ' (status  =1 or status =2) ';
            } else if ($type == 2) {
                $params[0][] = ' status  =0 ';
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SocialReport::dataCount($params[0]);
        $list = SocialReport::findList($params);
        $this->view->setVar('list', $list);
        $this->view->setVar('check_start', $check_start);
        $this->view->setVar('check_end', $check_end);
        $this->view->setVar('report_start', $report_start);
        $this->view->setVar('report_end', $report_end);
        $this->view->setVar('discuss_id', $discuss_id);
        $this->view->setVar('reporter', $reporter);
        $this->view->setVar('type', $type);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $admin_ids = array_column($list, 'check_user');
            $user_ids = array_unique(array_merge($user_ids, array_column($list, 'reporter')));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
            if (array_unique($admin_ids)) {
                if ($admin_ids[0] != 0) {
                    $admins = Admins::getByColumnKeyList(['id in (' . implode(',', $admin_ids) . ')', 'id,name'], 'id');
                    $this->view->setVar('admins', $admins);
                }
            }

        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);

    }

    //用户举报
    public function userAction()#用户举报#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $user_id = $this->request->get('user_id', 'int', 0);//用户id
        $reporter = $this->request->get('reporter', 'int', 0);//举报人id

        $check_start = $this->request->get('check_start', 'string', '');//审核开始时间
        $check_end = $this->request->get('check_end', 'string', '');//审核结束时间

        $report_start = $this->request->get('report_start', 'string', '');//举报开始时间
        $report_end = $this->request->get('report_end', 'string', '');//举报结束时间
        $type = $this->request->get('type', 'int', 2);//类型 0-所有 1-已处理 2-待处理

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params[0][] = "type='user'";
        if ($check_start) {
            $params[0][] = ' check_time  >= ' . strtotime($check_start);
        }
        if ($check_end) {
            $params[0][] = ' check_time  <= ' . (strtotime($check_end) + 86400);
        }
        if ($report_start) {
            $params[0][] = ' created  >= ' . strtotime($report_start);
        }
        if ($report_end) {
            $params[0][] = ' created  <= ' . (strtotime($report_end) + 86400);
        }
        if ($user_id) {
            $params[0][] = ' item_id  = "' . $user_id . '"';
        }
        if ($reporter) {
            $params[0][] = ' reporter  = "' . $reporter . '"';
        }
        if ($type) {
            if ($type == 1) {
                $params[0][] = ' (status  =1 or status =2) ';
            } else if ($type == 2) {
                $params[0][] = ' status  =0 ';
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SocialReport::dataCount($params[0]);
        $list = SocialReport::findList($params);
        $this->view->setVar('list', $list);
        $this->view->setVar('check_start', $check_start);
        $this->view->setVar('check_end', $check_end);
        $this->view->setVar('report_start', $report_start);
        $this->view->setVar('report_end', $report_end);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('reporter', $reporter);
        $this->view->setVar('type', $type);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $admin_ids = array_column($list, 'check_user');
            $user_ids = array_unique(array_merge($user_ids, array_column($list, 'reporter')));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
            if (array_unique($admin_ids)) {
                if ($admin_ids[0] != 0) {
                    $admins = Admins::getByColumnKeyList(['id in (' . implode(',', $admin_ids) . ')', 'id,name'], 'id');
                    $this->view->setVar('admins', $admins);
                }
            }

        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);

    }

    //群举报
    public function groupAction()#群聊举报#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $user_id = $this->request->get('user_id', 'int', 0);//用户id
        $reporter = $this->request->get('reporter', 'int', 0);//举报人id

        $check_start = $this->request->get('check_start', 'string', '');//审核开始时间
        $check_end = $this->request->get('check_end', 'string', '');//审核结束时间

        $report_start = $this->request->get('report_start', 'string', '');//举报开始时间
        $report_end = $this->request->get('report_end', 'string', '');//举报结束时间
        $type = $this->request->get('type', 'int', 2);//类型 0-所有 1-已处理 2-待处理

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params[0][] = "type='user'";
        if ($check_start) {
            $params[0][] = ' check_time  >= ' . strtotime($check_start);
        }
        if ($check_end) {
            $params[0][] = ' check_time  <= ' . (strtotime($check_end) + 86400);
        }
        if ($report_start) {
            $params[0][] = ' created  >= ' . strtotime($report_start);
        }
        if ($report_end) {
            $params[0][] = ' created  <= ' . (strtotime($report_end) + 86400);
        }
        if ($user_id) {
            $params[0][] = ' item_id  = "' . $user_id . '"';
        }
        if ($reporter) {
            $params[0][] = ' reporter  = "' . $reporter . '"';
        }
        if ($type) {
            if ($type == 1) {
                $params[0][] = ' (status  =1 or status =2) ';
            } else if ($type == 2) {
                $params[0][] = ' status  =0 ';
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = GroupReport::dataCount($params[0]);
        $list = GroupReport::findList($params);
        $this->view->setVar('list', $list);
        $this->view->setVar('check_start', $check_start);
        $this->view->setVar('check_end', $check_end);
        $this->view->setVar('report_start', $report_start);
        $this->view->setVar('report_end', $report_end);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('reporter', $reporter);
        $this->view->setVar('type', $type);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $admin_ids = array_column($list, 'check_user');
            $user_ids = array_unique(array_merge($user_ids, array_column($list, 'reporter')));
            $gid_ids = array_unique(array_column($list, 'gid'));

            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');

            $groups = Group::getByColumnKeyList(['id in (' . implode(',', $gid_ids) . ')', 'columns' => 'name,id,default_name'], 'id');

            $this->view->setVar('users', $users);
            $this->view->setVar('groups', $groups);
            if (array_unique($admin_ids)) {
                if ($admin_ids[0] != 0) {
                    $admins = Admins::getByColumnKeyList(['id in (' . implode(',', $admin_ids) . ')', 'id,name'], 'id');
                    $this->view->setVar('admins', $admins);
                }
            }

        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //视频举报
    public function videoAction()#视频举报#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $user_id = $this->request->get('user_id', 'int', 0);//用户id
        $reporter = $this->request->get('reporter', 'int', 0);//举报人id

        $check_start = $this->request->get('check_start', 'string', '');//审核开始时间
        $check_end = $this->request->get('check_end', 'string', '');//审核结束时间
        $video_id = $this->request->get('video_id', 'int', 0);//视频id

        $report_start = $this->request->get('report_start', 'string', '');//举报开始时间
        $report_end = $this->request->get('report_end', 'string', '');//举报结束时间
        $type = $this->request->get('type', 'int', 0);//类型 0-所有 1-已处理 2-待处理

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params[0][] = "type='video'";
        if ($check_start) {
            $params[0][] = ' check_time  >= ' . strtotime($check_start);
        }
        if ($check_end) {
            $params[0][] = ' check_time  <= ' . (strtotime($check_end) + 86400);
        }
        if ($report_start) {
            $params[0][] = ' created  >= ' . strtotime($report_start);
        }
        if ($report_end) {
            $params[0][] = ' created  <= ' . (strtotime($report_end) + 86400);
        }
        if ($user_id) {
            $params[0][] = ' item_id  = "' . $user_id . '"';
        }
        if ($reporter) {
            $params[0][] = ' reporter  = "' . $reporter . '"';
        }
        if ($video_id) {
            $params[0][] = ' item_id  = "' . $video_id . '"';
        }
        if ($type) {
            if ($type == 1) {
                $params[0][] = ' (status  =1 or status =2) ';
            } else if ($type == 2) {
                $params[0][] = ' status  =0 ';
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SocialReport::dataCount($params[0]);
        $list = SocialReport::findList($params);
        $this->view->setVar('list', $list);
        $this->view->setVar('check_start', $check_start);
        $this->view->setVar('check_end', $check_end);
        $this->view->setVar('report_start', $report_start);
        $this->view->setVar('report_end', $report_end);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('reporter', $reporter);
        $this->view->setVar('type', $type);
        $this->view->setVar('video_id', $video_id);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $admin_ids = array_column($list, 'check_user');
            $user_ids = array_unique(array_merge($user_ids, array_column($list, 'reporter')));
            $gid_ids = array_unique(array_column($list, 'gid'));
            if (!empty($user_ids))
                $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            if (!empty($gid_ids))
                $groups = Group::getByColumnKeyList(['id in (' . implode(',', $gid_ids) . ')', 'columns' => 'name,id,default_name'], 'id');

            $this->view->setVar('users', $users);
            $this->view->setVar('groups', $groups);
            if (array_unique($admin_ids)) {
                if ($admin_ids[0] != 0) {
                    $admins = Admins::getByColumnKeyList(['id in (' . implode(',', $admin_ids) . ')', 'id,name'], 'id');
                    $this->view->setVar('admins', $admins);
                }
            }

        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }
}