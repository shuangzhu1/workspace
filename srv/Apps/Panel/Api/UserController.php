<?php
/**
 * Created by PhpStorm.
 * User: yanue-mi
 * Date: 14-8-19
 * Time: 上午11:49
 */

namespace Multiple\Panel\Api;

use Components\User\UserManager;
use Components\Yunxin\ServerAPI;
use Models\Group\Group;
use Models\Group\GroupMember;
use Models\User\Message;
use Models\User\UserCoinLog;
use Models\User\UserInfo;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserThirdParty;
use OSS\OssClient;
use Services\Discuss\DiscussManager;
use Services\Im\ImManager;
use Services\Im\NotifyManager;
use Services\Im\SysMessage;
use Services\Site\CacheSetting;
use Services\Upload\OssManager;
use Services\User\UserStatus;
use Download\Csv;
use Models\Social\SocialDiscuss;
use Models\User\UserLoginLog;
use Models\User\UserPointGrade;
use Models\User\UserPointLog;
use Models\User\UserPointRules;
use Phalcon\Exception;
use Services\Admin\AdminLog;
use Upload\Upload;
use Util\Ajax;
use Util\ImgSize;
use Util\Pagination;
use Util\Time;
use Util\Validator;


class UserController extends ApiBase
{
    public function setPointRules()
    {
        // params
        $data = $this->request->get('data');
        if (!($data)) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // each do
        foreach ($data as $row) {
            $id = isset($row['id']) ? $row['id'] : null;
            unset($row['id']); // for update it
            $row['points'] = $row['quantity'];
            unset($row['quantity']);

            if ($id) {
                // update data
                UserPointRules::updateOne($row, ['id' => $id]);
            } else {
                $rule = new UserPointRules();
                $row['customer_id'] = CUR_APP_ID;
                $row['created'] = time();
                $rule->insertOne($row);
            }
        }

        // log
        $this->ajax->outRight('操作成功');
    }


    /*永久封号*/
    public function deleteUserAction()
    {
        $users = $this->request->getPost('data');
        if (!$users) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        // $ids = implode(',', $data);
        $data = ['status' => UserStatus::STATUS_DELETED, 'modify' => time()];
        //更新用户状态  更新动态状态  发送im消息
        foreach ($users as $item) {
            if ($this->db->execute("update users set status=" . $data['status'] . " where id ='" . $item . "'")) {
                AdminLog::init()->add('永久封号', AdminLog::TYPE_USER, $item, array('type' => "update", 'id' => $item));
                // $this->db->execute("update social_discuss set status=" . DiscussManager::STATUS_SHIELD . ' where user_id ="' . $item . '"');
                SysMessage::init()->initMsg(SysMessage::TYPE_USER_FREEZE, ['to_user_id' => $item]);
            }
        }
        $this->ajax->outRight('');
    }

    /*解封*/
    public function recoveryUserAction()
    {
        $users = $this->request->getPost('data');
        if (!$users) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        //  $ids = implode(',', $users);
        $data = ['status' => UserStatus::STATUS_NORMAL, 'modify' => time()];
        //更新用户状态
        foreach ($users as $item) {
            if ($this->db->query("update users set status=" . $data['status'] . ' where id ="' . $item . '"')) {
                AdminLog::init()->add('解封', AdminLog::TYPE_USER, $item, array('type' => "update", 'id' => $item));
            }
        }
        $this->ajax->outRight('');
    }

    /*禁用账号*/
    public function forbidUsersAction()
    {
        $users = $this->request->getPost('data');
        if (!$users) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        //  $ids = implode(',', $data);
        $data = ['status' => UserStatus::STATUS_LOCKED, 'modify' => time()];
        //更新用户状态   发送im消息
        foreach ($users as $item) {
            if ($this->db->execute("update users set status=" . $data['status'] . " where id ='" . $item . "'")) {
                AdminLog::init()->add('禁用账号', AdminLog::TYPE_USER, $item, array('type' => "update", 'id' => $item));
                SysMessage::init()->initMsg(SysMessage::TYPE_USER_LOCKED, ['to_user_id' => $item]);
            }
        }
        $this->ajax->outRight('');
    }

    /*解除禁用*/
    public function unForbidUsersAction()
    {
        $users = $this->request->getPost('data');
        if (!$users) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        //$ids = implode(',', $users);
        $data = ['status' => UserStatus::STATUS_NORMAL, 'modify' => time()];
        //更新用户状态
        foreach ($users as $item) {
            if ($this->db->query("update users set status=" . $data['status'] . ' where id ="' . $item . '"')) {
                AdminLog::init()->add('账号解除禁用', AdminLog::TYPE_USER, $item, array('type' => "update", 'id' => $item));
            }
        }
        $this->ajax->outRight('');
    }

    public function addGroupAction()
    {
        $exp_start = $this->request->getPost("exp_start", "string");
        $exp_end = $this->request->getPost("exp_end", "string");
        //  $discount = $this->request->getPost('discount', "float", '100');
        $name = $this->request->getPost('name', "striptags", "");
        $member_limit = 0;// $this->request->getPost('member_limit', "int", 0);
        $top_limit = $this->request->getPost('top_limit', "int", 0);
        //   $badge = $this->request->getPost("badge", "striptags", '');
        // $share_commission = $this->request->getPost("share_commission", "striptags", '');
        //  $diy_commission = $this->request->getPost("diy_commission", "striptags", '');

        /*  if (!$name || strlen($name) < 0) {
              $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "等级名称没有提供！");
          }
          if (!$discount || strlen($discount) < 0) {
              $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "折扣值没有提供！");
          }*/
        /*    if (!$badge || strlen($badge) < 0) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "等级徽章图标没有提供！");
            }*/
        if (!$exp_start && !$exp_end) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "等级经验值范围没有提供！");
        }

        if (empty($exp_start) && $exp_start != '0') {
            $exp_start = "";
        }
        if (empty($exp_end) && $exp_end != '0') {
            $exp_end = "";
        }

        $rowData = array(
            'name' => $name,
            //  'discount' => $discount,
            'amount_start' => $exp_start,
            'amount_end' => $exp_end,
            'group_member_count' => $member_limit,
            'top_discuss' => $top_limit
            //   'badge' => $badge,
            //  'share_commission' => $share_commission,
            //   'diy_commission' => $diy_commission
        );

        $id = UserManager::getInstance()->addGroup($rowData);

        if ($id <= 0) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, UserManager::getInstance()->getErrorMessage($id));
        }
        if (!$id) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '设置失败!');
        }

        // log
        $this->ajax->outRight($id);
    }

    public function delGroupAction()
    {
        $grade = $this->request->getPost("grade", 'int', 0);
        if (!$grade || $grade <= 0) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "需要指定要删除的等级");
        }
        $rs = UserManager::getInstance()->delGroup($grade);
        if ($rs <= 0) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, UserManager::getErrorMessage($rs));
        } else {
            $this->ajax->outRight();
        }
    }

    public function saveGroupsAction()
    {
        $data = $this->request->get('data');
        if (!($data && is_array($data))) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        $settingModel = new UserPointGrade();
        $this->db->begin();
        try {
            foreach ($data as $row) {
                $id = isset($row['id']) ? $row['id'] : null;
                unset($row['id']); // for update it
                //if name is not exists
                if (!$row['name']) continue;
                if (empty($row['exp_start']) && $row['exp_start'] != '0') {
                    $row['exp_start'] = '';
                }
                if (empty($row['exp_end']) && $row['exp_end'] != '0') {
                    $row['exp_end'] = '';
                }
                $rowData = array(
                    'name' => $row['name'],
                    'amount_start' => $row['exp_start'],
                    'amount_end' => $row['exp_end'],
                    //  'badge' => $row['badge'],
                    'group_member_count' => 0,//$row['member_limit'],
                    'top_discuss' => $row['top_limit'],
                );
                $grade = $settingModel->findOne('id=' . $id);
                if ($grade) {
                    if (!UserPointGrade::updateOne($rowData, ['id' => $id])) {
                        throw new Exception("更新用户等级失败！");
                    }
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "更新用户等级时操作失败！");
        }
        $this->ajax->outRight();
    }

    public function delPointLogAction()
    {
        $data = $this->request->get('data');
        if (!($data && is_array($data))) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // 删除回链
        $list = UserPointLog::findList('id in (' . implode(',', $data) . ')');

        foreach ($list as $item) {
            UserPointLog::remove(['id' => $item['id']]);
        }

        $this->ajax->outRight('');
    }

    public function delCoinLogAction()
    {
        $data = $this->request->get('data');
        if (!($data && is_array($data))) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // 删除回链
        $list = UserCoinLog::findList('id in (' . implode(',', $data) . ')');

        foreach ($list as $item) {
            UserCoinLog::updateOne(['status' => 0], ['id' => $item['id']]);
        }

        $this->ajax->outRight('');
    }

    /*登录日志*/
    public function loginLogAction()
    {
        $aoData = $this->request->get('aoData');
        $uid = $this->request->get('user_id', 'int', '');
        $total_count = UserLoginLog::dataCount("user_id=" . $uid);
        $aoData = json_decode($aoData, true);

        $data = array_column($aoData, 'value', 'name');

        $start = $data['iDisplayStart'];
        $limit = $data['iDisplayLength'];

        $aoData['iTotalRecords'] = $total_count;
        $aoData['iTotalDisplayRecords'] = $total_count;

        $params = ['user_id=' . $uid, 'columns' => 'login_time as time,client_type,os,app_version,phone_model,client_ip', 'offset' => $start, 'limit' => $limit];
        //排序了
        if ($data['iSortingCols']) {
            $params['order'] = 'login_time  ' . $data['sSortDir_0'];
        } else {
            $params['order'] = 'login_time desc';
        }
        $list = UserLoginLog::findList($params);
        $aoData['aaData'] = [];
        if ($list) {
            foreach ($list as $item) {
                $item['time'] = date('Y-m-d H:i', $item['time']);
                $aoData['aaData'][] = $item;
            }
        }
        echo json_encode($aoData, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /*动态列表*/
    public function discussAction()
    {
        $aoData = $this->request->get('aoData');
        $uid = $this->request->get('user_id', 'int', '');
        $total_count = SocialDiscuss::dataCount("user_id=" . $uid);
        $aoData = json_decode($aoData, true);

        $data = array_column($aoData, 'value', 'name');
        $start = $data['iDisplayStart'];
        $limit = $data['iDisplayLength'];
        $aoData['iTotalRecords'] = $total_count;
        $aoData['iTotalDisplayRecords'] = $total_count;
        $params = ['user_id=' . $uid, 'columns' => 'id,created,tags_name,content,media,media_type,status,like_cnt,fav_cnt,comment_cnt,forward_cnt,report_cnt,view_cnt,is_top,address', 'offset' => $start, 'limit' => $limit];
        if ($data['iSortingCols']) {
            $params['order'] = $data['mDataProp_' . $data['iSortCol_0']] . ' ' . $data['sSortDir_0'];
        }
        $list = SocialDiscuss::findList($params);
        $aoData['aaData'] = [];
        if ($list) {
            foreach ($list as $item) {
                $item['created'] = date('Y-m-d H:i', $item['created']);
                $aoData['aaData'][] = $item;
            }
        }
        echo json_encode($aoData, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /*取消认证*/
    public function cancelAuthAction()
    {
        $id = $this->request->get('id');
        if (!$id) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        $user_profile = UserProfile::findOne(['user_id=' . $id . ' and is_auth']);
        if (!$user_profile) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '用户不存在或者还没有认证');
        }
        $user_profile_data = ['is_auth' => 4, 'auth_type' => '', 'auth_desc' => '', 'website' => '', 'introduce' => '', 'industry' => '', 'company' => '', 'job' => ''];
        if (UserProfile::updateOne($user_profile_data, ['id' => $user_profile['id']])) {
            AdminLog::init()->add('取消认证', AdminLog::TYPE_USER, $id, array('type' => "update", 'id' => $id));
        }
        $this->ajax->outRight("取消成功");
    }

    public function editAuthAction()
    {
        $uid = $this->request->get("uid", 'int', 0);
        $website = $this->request->get("website", 'string', '');
        $introduce = $this->request->get("introduce", 'string', '');
        $auth_desc = $this->request->get("auth_desc", 'string', '');
        if ($website && !Validator::validateUrl($website)) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "无效的网址");
        }
        if (!$uid) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "无效的参数");
        }
        $data = ['auth_desc' => $auth_desc, 'introduce' => $introduce, 'website' => $website];
        $user_priofile = UserProfile::findOne('user_id=' . $uid);
        if (!$user_priofile) {
            $this->ajax->outRight(Ajax::CUSTOM_ERROR_MSG, "用户不存在");
        }
        UserProfile::updateOne($data, ['id' => $user_priofile['id']]);
        $this->ajax->outRight("编辑成功");
    }

    public function listAction()
    {
        $group = $this->request->get('group', 'int');
        //   $this->view->setVar('group', $group);
        $id = $this->request->get('id', 'int');
        // $this->view->setVar('id', $id);
        $nick = $this->request->get('name', 'striptags');
        $phone = $this->request->get('phone', 'int', 0);
        $status = $this->request->get('status', 'string', 1);
        //  $this->view->setVar('name', $nick);
        $start = $this->request->get('start', 'int');
        $end = $this->request->get('end', 'int');
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $type = $this->request->get('type', 'int', 1);
        $auth = $this->request->get('auth', 'int', 0);
        $order = $this->request->getPost('order', 'string', '');//order
        $sort = $this->request->getPost('sort', 'string', '');//sort
        $where = [];
        $order_column = 'u.created desc';//排序字段

        if ($group) {
            $where[] = ' u.grade=' . $group;
        }
        if ($id) {
            $where[] = ' u.id=' . $id;
        }
        if ($nick) {
            $where[] = ' u.username like "%' . $nick . '%"';
        }
        if ($type != 0) {
            $where[] = ' u.user_type=' . $type;
        }
        if ($phone) {
            $where[] = ' u.phone=' . $phone;
        }
        if ($start) {
            $where[] = ' u.created  >= ' . strtotime($start);
        }
        if ($end) {
            $where[] = ' u.created  <= ' . (strtotime($end) + 86400);
        }
        if ($auth) {
            $where[] = ' p.is_auth =1 and status=1';
        }
        if ($status != '-1') {
            $where[] = ' u.status = ' . $status;
        }
        if ($order && $sort) {
            if ($order !== 'charm') {
                $order_column = 'u.' . $order . " " . $sort;
            } else {
                $order_column = 'p.' . $order . " " . $sort;
            }
        }

        $where = $where ? implode(' and ', $where) : '';
        $sql = "select *  from users as u  left join user_profile as p on u.id=p.user_id " . ($where ? ' where ' . $where : '') . ' order by  ' . $order_column . '  limit ' . ($page - 1) * $limit . ',' . $limit;
        $list = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $count = $this->db->query("select count(1) as count from users as u  left join user_profile as p on u.id=p.user_id " . ($where ? ' where ' . $where : ''))->fetch(\PDO::FETCH_ASSOC);
        $count = $count['count'];

        if ($list) {
            $uids = array_column($list, 'user_id');
            $third_party = UserThirdParty::getColumn(['user_id in (' . implode(',', $uids) . ")", 'columns' => 'user_id,type'], 'type', 'user_id');
            foreach ($list as &$item) {
                if (isset($third_party[$item['user_id']])) {
                    $item['third_type'] = ($third_party[$item['user_id']]) == 1 ? 'QQ' : '微信';
                } else if ($item['phone']) {
                    $item['third_type'] = "手机";
                } else {
                    $item['third_type'] = "其他";
                }
            }
        }
        /*    $this->view->setVar('list', $list);
            $this->view->setVar('type', $type);
            $this->view->setVar('start', $start);
            $this->view->setVar('end', $end);
            $this->view->setVar('auth', $auth);*/
        $data = [];
        if ($list) {
            foreach ($list as $i) {
                $data[] = [$this->getFromOB('users/partial/user', array('item' => $i))];
            }
        } else {
            $data[] .= "<tr><td colspan='12'>暂无数据</td></tr>";
        }
        $bar = Pagination::getAjaxListPageBar($count, $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $count, 'bar' => $bar]);
    }

    //获取会话列表
    public function getConAction()
    {
        $uid = $this->request->get("uid", 'int', 0);
        $type = $this->request->get("chat_type", 'string', 'single');//聊天对象 single-单聊 group-群聊
        $key = $this->request->get("key", 'string', '');//关键字
        if (!$uid) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $redis = $this->di->get("redis");
        //单聊
        if ($type == 'single') {
            $t_list = $redis->hGetAll(CacheSetting::KEY_CONVERSATION_LIST . $uid);
            $list = [];
            $order_data = [];//排序字段
            $user_info = [];
            if ($t_list) {
                $where = "id in (" . implode(',', array_keys($t_list)) . ")";
                if ($key) {
                    if (preg_match('/^[1-9][0-9]{4,}$/', $key)) {
                        $where .= " and (id=" . $key . " or username like '%" . $key . "%')";
                    } else {
                        $where .= " and username like '%" . $key . "%'";
                    }

                }
                $user_info = Users::getByColumnKeyList([$where, 'columns' => 'avatar,username,id as uid'], 'uid');

                foreach ($t_list as $k => $item) {
                    if (in_array($k, [13, 14, 15, 16, 17])) {
                        if ($k == 13) {
                            $list[$k] = json_decode($item, true);
                            $list[$k]['user_info'] = ['uid' => $k, 'avatar' => 'http://avatorimg.klgwl.com/13/logo.png', 'username' => '恐龙君'];
                            $order_data[] = $list[$k]['send_time'];
                        } else {

                        }
                    } else {
                        if (!isset($user_info[$k])) {
                            continue;
                        }
                        $list[$k] = json_decode($item, true);
                        $list[$k]['uid'] = $k;
                        $order_data[] = $list[$k]['send_time'];
                        $list[$k]['user_info'] = ['uid' => $k, 'avatar' => $user_info[$k]['avatar'], 'username' => $user_info[$k]['username']];

                    }
                }
            }
            $data = '';
            if ($list) {
                array_multisort($list, SORT_DESC, $order_data);
                $data = [$this->getFromOB('users/partial/item-con-single', array('list' => $list, 'user_info' => $user_info))];
            } else {
                $data = "<li>暂无数据</td></tr></li>";
            }
        } //群聊
        else {
            //群列表
            $groups = GroupMember::getColumn(['user_id=' . $uid, 'columns' => 'gid'], 'gid');
            $list = [];
            if ($groups) {
                $order_data = [];//排序字段
                $where = 'id in (' . implode(',', $groups) . ')';
                if ($key) {
                    if (preg_match('/^[1-9][0-9]{3,}$/', $key)) {
                        $where .= " and (id=" . $key . " or name like '%" . $key . "%')";
                    } else {
                        $where .= " and name like '%" . $key . "%'";
                    }

                }
                $group_list = Group::getByColumnKeyList([$where, 'columns' => 'id,user_id,yx_gid,name,default_name,avatar,default_avatar,status'], 'id');
                foreach ($group_list as $k => $item) {
                    $tmp = $redis->hGet(CacheSetting::KEY_GROUP_CONVERSATION_LIST, $k);
                    if (!$tmp) {
                        continue;
                    }
                    $list[$k] = json_decode($tmp, true);
                    $list[$k]['group_info'] = ['gid' => $k, 'avatar' => $item['avatar'] ? $item['avatar'] : $item['default_avatar'], 'name' => $item['name'] ? $item['name'] : $item['default_name']];
                    $order_data[] = $list[$k]['send_time'];
                }
            }
            if ($list) {
                array_multisort($list, SORT_DESC, $order_data);
                $data = [$this->getFromOB('users/partial/item-con-group', array('list' => $list))];
            } else {
                $data = "<li>暂无数据</td></tr></li>";
            }

        }

        $this->ajax->outRight(['list' => $data]);
    }

    //获取会话历史消息
    public function getConMesAction()
    {
        $first_id = $this->request->get("first_id", 'int', 0);
        $last_id = $this->request->get("last_id", 'int', 0);
        $limit = $this->request->get("limit", 'int', 50);
        $start = $this->request->get("start", 'string', '');
        $end = $this->request->get("end", 'string', '');
        $key = $this->request->get("search_key", 'string', '');

        $mix_id = $this->request->get("mix_id", 'string', '');
        $chat_type = $this->request->get("chat_type", 'string', 'single');
        $gid = $this->request->get("gid", 'int', 0);

        if (!$mix_id && !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        //单聊
        if ($chat_type == 'single') {
            if ($key) {
                $count_filter = 'mix_id="' . $mix_id . '"' . " and media_type='text' and body like '%" . $key . "%'";
            } else {
                $count_filter = 'mix_id="' . $mix_id . '"' . " and media_type in('text','audio','video','picture','file','custom')";
            }
            $message_count = Message::dataCount($count_filter);

            $message_mix = Message::findOne(['mix_id="' . $mix_id . '"', 'columns' => 'from_uid,to_uid']);
            $user_info = Users::getByColumnKeyList(["id=" . $message_mix['from_uid'] . " or id=" . $message_mix['to_uid'], "columns" => "id as uid,username,avatar"], 'uid');
            if ($message_mix['from_uid'] == 13 || $message_mix['to_uid'] == 13) {
                $user_info[13] = ['username' => '恐龙君', 'avatar' => 'http://avatorimg.klgwl.com/13/logo.png', 'uid' => 13];
            }
            //下拉加载
            if ($first_id) {

                $data = ['list' => '', 'hide_tip' => 0, 'first_id' => 0, 'video_ids' => [], 'data_count' => $message_count];
                $msg = Message::findOne(["id=" . $first_id, 'columns' => 'send_time,id']);
                if (!$msg) {
                    $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
                }
                $where = "mix_id=$mix_id and send_time<=" . $msg['send_time'] . ' and id <>' . $first_id;
                if ($start) {
                    $where .= " and send_time>=" . strtotime($start) * 1000;
                }
                if ($end) {
                    $where .= " and send_time<=" . (strtotime($end) * 1000 + 60000);
                }
                if ($key) {
                    $where .= " and body like '%" . $key . "%' and media_type='text'";
                } else {
                    $where .= " and media_type in('text','audio','video','picture','file','custom')";
                }

                $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type,created,extend_json', $where, 'limit' => $limit, 'order' => 'send_time desc,id desc']);
                if ($message) {
                    $message = array_reverse($message);

                    $pre = "";
                    foreach ($message as $k => $m) {
                        if ($m['media_type'] == 'video') {
                            $data['video_ids'][] = $m['id'];
                        }
                        $data['list'][] = $this->getFromOB('users/partial/item-msg-single', ['key' => $key, 'from_uid' => $message_mix['from_uid'], 'to' => $message_mix['to_uid'], 'item' => ['pre' => $pre, 'info' => $m, 'user_info' => $user_info[$m['from_uid']]]]);
                        $pre = date("YmdHi", $m["send_time"] / 1000);
                        if (!$data['hide_tip'] && $pre == date('YmdHi', $msg['send_time'] / 1000)) {
                            $data['hide_tip'] = $msg['id'];
                        }
                    }
                    $data['first_id'] = $message[0]['id'];
                }

            } //上拉刷新
            else if ($last_id) {
                $msg = Message::findOne(["id=" . $last_id, 'columns' => 'send_time']);
                if (!$msg) {
                    $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
                }
                $where = "mix_id=$mix_id and send_time>=" . $msg['send_time'] . ' and id <>' . $last_id;
                if ($start) {
                    $where .= " and send_time>=" . strtotime($start) * 1000;
                }
                if ($end) {
                    $where .= " and send_time<=" . (strtotime($end) * 1000 + 60000);
                }
                if ($key) {
                    $where .= " and body like '%" . $key . "%' and media_type='text'";
                } else {
                    $where .= " and media_type in('text','audio','video','picture','file','custom')";
                }

                $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type,created,extend_json', $where, 'limit' => $limit, 'order' => 'send_time desc,id desc']);
                $data = ['list' => '', 'last_id' => 0, 'video_ids' => [], 'data_count' => $message_count];
                if ($message) {
                    $message = array_reverse($message);
                    $next = date("YmdHi", $msg['send_time'] / 1000);
                    foreach ($message as $k => $m) {
                        if ($m['media_type'] == 'video') {
                            $data['video_ids'][] = $m['id'];
                        }
                        $data['list'][] = $this->getFromOB('users/partial/item-msg-single', ['key' => $key, 'from_uid' => $message_mix['from_uid'], 'to' => $message_mix['to_uid'], 'item' => ['next' => $next, 'info' => $m, 'user_info' => $user_info[$m['from_uid']]]]);
                        $next = date("YmdHi", $m["send_time"] / 1000);
                    }
                    $data['last_id'] = $message[count($message) - 1]['id'];
                }
            } else {
                $where = "mix_id=$mix_id";
                if ($start) {
                    $where .= " and send_time>=" . strtotime($start) * 1000;
                }
                if ($end) {
                    $where .= " and send_time<=" . (strtotime($end) * 1000 + 60000);
                }
                if ($key) {
                    $where .= " and body like '%" . $key . "%' and media_type='text'";
                } else {
                    $where .= " and media_type in('text','audio','video','picture','file','custom')";
                }

                $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type,created,extend_json', $where, 'limit' => $limit, 'order' => 'send_time desc,id desc']);
                $data = ['list' => '', 'first_id' => 0, 'last_id' => 0, 'video_ids' => []];
                if ($message) {
                    $message = array_reverse($message);
                    $data = ['list' => '', 'first_id' => 0, 'last_id' => 0, 'video_ids' => [], 'data_count' => $message_count];
                    $pre = 0;
                    foreach ($message as $k => $m) {
                        if ($m['media_type'] == 'video') {
                            $data['video_ids'][] = $m['id'];
                        }
                        $data['list'][] = $this->getFromOB('users/partial/item-msg-single', ['key' => $key, 'from_uid' => $message_mix['from_uid'], 'to' => $message_mix['to_uid'], 'item' => ['pre' => $pre, 'info' => $m, 'user_info' => $user_info[$m['from_uid']]]]);
                        $pre = date("YmdHi", $m["send_time"] / 1000);
                    }
                    $data['last_id'] = $message[count($message) - 1]['id'];
                    $data['first_id'] = $message[0]['id'];
                }
            }
        } else {
            //群聊
            $group = Group::findOne(['id="' . $gid . '"', 'columns' => 'name,default_name,avatar,default_avatar,id']);
            if ($key) {
                $count_filter = 'gid=' . $gid . " and media_type ='text' and body like '%" . $key . "%'";
            } else {
                $count_filter = 'gid=' . $gid . " and media_type in('text','audio','video','picture','file','custom')";
            }
            $message_count = Message::dataCount($count_filter);
            //下拉加载
            if ($first_id) {
                $data = ['list' => '', 'hide_tip' => 0, 'first_id' => 0, 'video_ids' => [], 'data_count' => $message_count];
                $msg = Message::findOne(["id=" . $first_id, 'columns' => 'send_time,id']);
                if (!$msg) {
                    $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
                }
                $where = "gid=$gid and send_time<=" . $msg['send_time'] . ' and id <>' . $first_id;
                if ($start) {
                    $where .= " and send_time>=" . strtotime($start) * 1000;
                }
                if ($end) {
                    $where .= " and send_time<=" . (strtotime($end) * 1000 + 60000);
                }
                if ($key) {
                    $where .= " and body like '%" . $key . "%' and media_type='text'";
                } else {
                    $where .= " and media_type in('text','audio','video','picture','file','custom')";
                }


                $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type,created,extend_json', $where, 'limit' => $limit, 'order' => 'send_time desc,id desc']);
                if ($message) {
                    $message = array_reverse($message);
                    $uids = array_unique(array_column($message, 'from_uid'));
                    $user_info = Users::getByColumnKeyList(["id in(" . implode(',', $uids) . ')', "columns" => "id as uid,username,avatar"], 'uid');
                    $pre = "";
                    foreach ($message as $k => $m) {

                        if ($m['media_type'] == 'video') {
                            $data['video_ids'][] = $m['id'];
                        }
                        $data['list'][] = $this->getFromOB('users/partial/item-msg-single', ['key' => $key, 'from_uid' => $m['from_uid'], 'to' => 0, 'item' => ['pre' => $pre, 'info' => $m, 'user_info' => $user_info[$m['from_uid']]]]);
                        $pre = date("YmdHi", $m["send_time"] / 1000);
                        if (!$data['hide_tip'] && $pre == date('YmdHi', $msg['send_time'] / 1000)) {
                            $data['hide_tip'] = $msg['id'];
                        }
                    }
                    $data['first_id'] = $message[0]['id'];
                }

            } //上拉刷新
            else if ($last_id) {
                $msg = Message::findOne(["id=" . $last_id, 'columns' => 'send_time']);
                if (!$msg) {
                    $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
                }
                $where = "gid=$gid and send_time>=" . $msg['send_time'] . ' and id <>' . $last_id;
                if ($start) {
                    $where .= " and send_time>=" . strtotime($start) * 1000;
                }
                if ($end) {
                    $where .= " and send_time<=" . (strtotime($end) * 1000 + 60000);
                }
                if ($key) {
                    $where .= " and body like '%" . $key . "%' and media_type='text'";
                } else {
                    $where .= " and media_type in('text','audio','video','picture','file','custom')";
                }


                $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type,created,extend_json', $where, 'limit' => $limit, 'order' => 'send_time desc,id desc']);
                $uids = array_unique(array_column($message, 'from_uid'));
                $user_info = Users::getByColumnKeyList(["id in(" . implode(',', $uids) . ')', "columns" => "id as uid,username,avatar"], 'uid');
                $data = ['list' => '', 'last_id' => 0, 'video_ids' => [], 'data_count' => $message_count];
                if ($message) {
                    $message = array_reverse($message);
                    $next = date("YmdHi", $msg['send_time'] / 1000);
                    foreach ($message as $k => $m) {
                        if ($m['media_type'] == 'video') {
                            $data['video_ids'][] = $m['id'];
                        }
                        $data['list'][] = $this->getFromOB('users/partial/item-msg-single', ['key' => $key, 'from_uid' => $m['from_uid'], 'to' => 0, 'item' => ['next' => $next, 'info' => $m, 'user_info' => $user_info[$m['from_uid']]]]);
                        $next = date("YmdHi", $m["send_time"] / 1000);
                    }
                    $data['last_id'] = $message[count($message) - 1]['id'];
                }
            } else {
                $where = "gid=$gid";
                if ($start) {
                    $where .= " and send_time>=" . strtotime($start) * 1000;
                }
                if ($end) {
                    $where .= " and send_time<=" . (strtotime($end) * 1000 + 60000);
                }
                if ($key) {
                    $where .= " and body like '%" . $key . "%' and media_type='text'";
                } else {
                    $where .= " and media_type in('text','audio','video','picture','file','custom')";
                }

                $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type,created,extend_json', $where, 'limit' => $limit, 'order' => 'send_time desc,id desc']);
                $data = ['list' => '', 'first_id' => 0, 'last_id' => 0, 'video_ids' => [], 'data_count' => 0];
                if ($message) {
                    $message = array_reverse($message);
                    $uids = array_unique(array_column($message, 'from_uid'));
                    $user_info = Users::getByColumnKeyList(["id in(" . implode(',', $uids) . ')', "columns" => "id as uid,username,avatar"], 'uid');
                    $data = ['list' => '', 'first_id' => 0, 'last_id' => 0, 'video_ids' => [], 'data_count' => $message_count];
                    $pre = 0;
                    foreach ($message as $k => $m) {
                        if ($m['media_type'] == 'video') {
                            $data['video_ids'][] = $m['id'];
                        }
                        $data['list'][] = $this->getFromOB('users/partial/item-msg-single', ['key' => $key, 'from_uid' => $m['from_uid'], 'to' => 0, 'item' => ['pre' => $pre, 'info' => $m, 'user_info' => $user_info[$m['from_uid']]]]);
                        $pre = date("YmdHi", $m["send_time"] / 1000);
                    }
                    $data['last_id'] = $message[count($message) - 1]['id'];
                    $data['first_id'] = $message[0]['id'];
                }
            }
        }


        $this->ajax->outRight($data);
    }

    //编辑官方用户信息
    public function editProfileAction()
    {
        $data = $this->request->get('');
        $uid = $data['uid'];
        $avatar = trim($data['avatar']);
        $username = trim($data['username']);
        $sex = trim($data['sex']);
        if (!$avatar || !$username || !$uid || !$sex) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!Users::exist("id=" . $uid . " and user_type=" . UserStatus::USER_TYPE_OFFICIAL)) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }

        $update_info = ['avatar' => $avatar, 'username' => $username, 'sex' => $sex];

        try {
            $this->original_mysql->begin();
            if (!UserStatus::getInstance()->editInfo($uid, $update_info)) {
                throw new \Exception("编辑失败");
            }
            $this->original_mysql->commit();
            Ajax::init()->outRight();
        } catch (\Exception $e) {
            $this->original_mysql->rollback();
            var_dump($e->getMessage());
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '编辑失败');
        }
    }

    //发送警告消息
    public function sendWarningAction()
    {
        $msg = $this->request->get("msg", 'string', '');
        $uid = $this->request->get("uid", 'int', 0);
        if (!$msg) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ServerAPI::init()->sendMsg(ImManager::ACCOUNT_SYSTEM, 0, $uid, 0, ['msg' => $msg]);
        if ($res) {
            $message = new Message();
            $time = time();
            $data = [
                "from_uid" => ImManager::ACCOUNT_SYSTEM,
                "mix_id" => ImManager::init()->getMixId(ImManager::ACCOUNT_SYSTEM, $uid),
                'to_uid' => $uid,
                'body' => $msg,
                'type' => 1,
                'media_type' => strtolower(NotifyManager::msgType_TEXT),
                "created" => time(),
                'send_time' => substr((string)microtime(true) * 1000, 0, 13),
                "year" => date("Y", $time),
                "month" => date("m", $time),
                "day" => date("d", $time),
                "client_type" => NotifyManager::fromClientType_PC,
            ];
            $message_id = $message->insertOne($data);
        }
        $this->ajax->outRight("");
    }
}
