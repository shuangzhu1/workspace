<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/2
 * Time: 16:37
 */

namespace Services\Community;


use Models\Community\Community;
use Models\Community\CommunityApply;
use Models\Community\CommunityApplyCheckLog;
use Models\Community\CommunityAttention;
use Models\Community\CommunityGroup;
use Models\Community\CommunityGroupMember;
use Models\Community\CommunityInfo;
use Models\Community\CommunityNews;
use Models\Community\CommunityProfile;
use Models\Group\Group;
use Models\Group\GroupMember;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\UserProfile;
use Models\Vip\VipPrivileges;
use Phalcon\Mvc\User\Plugin;
use Services\Site\SiteKeyValManager;
use Services\User\GroupManager;
use Services\User\UserStatus;
use Util\Ajax;
use Util\Debug;

class CommunityManager extends Plugin
{
    private static $instance = null;
    protected static $ajax = null;

    const status_normal = 1;//正常
    const status_deleted = 0;//被平台删除


    const check_status_checking = 0;//待审核
    const check_status_success = 1;//审核通过
    const check_status_fail = 2;//审核失败

    const role_normal = 0;//普通用户
    const role_admin = 1;//社区管理员
    const role_owner = 2;//社区区主


    const  type_personal = 1;//个人
    const  type_company = 2;//企业


    /**
     * @param bool $is_cli
     * @return  CommunityManager
     */
    public static function getInstance($is_cli = false)
    {
        if (!self::$instance) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    protected function __construct($is_cli)
    {
        self::$ajax = new Ajax();
    }

    //
    /**检测社区名是否被使用
     * @param $uid
     * @param $name
     * @return bool
     */
    public function checkName($uid, $name)
    {
        if (CommunityApply::exist("name='" . $name . "'")) {
            return false;
        }
        return true;
    }
    //社区申请
    /**
     * @param $uid --用户id
     * @param $name --社区名字
     * @param $brief --社区简介
     * @param $cover --社区封面
     * @param $extra_desc --审核资料-社区描述
     * @param $extra_img --审核资料-图片
     * @param $type --申请类型【1-个人 2-企业】
     */
    public function apply($uid, $name, $brief, $cover, $extra_desc, $extra_img, $type)
    {
        //一个用户只能创建一个社区
        if (Community::exist("user_id=" . $uid . " and status=" . self::status_normal)) {
            self::$ajax->outError(Ajax::ERROR_OWN_ONE_COMMUNITY);
        } else {
            //已经提交了申请，且正在审核中
            if (CommunityApply::exist('user_id=' . $uid . " and status=" . self::check_status_checking)) {
                self::$ajax->outError(Ajax::ERROR_SUBMIT_REPEAT);
            }
        }
        //检测实名认证
        $user = UserProfile::findOne(["user_id=" . $uid, 'columns' => 'is_auth']);
        if (!$user || $user['is_auth'] != 1) {
            self::$ajax->outError(Ajax::ERROR_NO_AUTH);
        }
        //相同名字的社区已经存在
        if (CommunityApply::exist("name='" . $name . "' and user_id<>$uid and (status=" . self::check_status_checking . ' or status=' . self::check_status_success . ')')) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_NAME_UNIQUE);
        }
        if (CommunityApply::exist("user_id=" . $uid)) {
            $res = CommunityApply::updateOne(['type' => $type, 'name' => $name, 'brief' => $brief, 'cover' => $cover, 'extra_desc' => $extra_desc, 'extra_img' => $extra_img, 'modify' => time(), 'status' => self::check_status_checking], 'user_id=' . $uid);
        } else {
            $res = CommunityApply::insertOne(['type' => $type, 'user_id' => $uid, 'name' => $name, 'brief' => $brief, 'cover' => $cover, 'extra_desc' => $extra_desc, 'extra_img' => $extra_img, 'created' => time(), 'status' => self::check_status_checking]);
        }
        if ($res) {
            CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_APPLY, ['to_user_id' => $uid, 'community_name' => $name]);
            self::$ajax->outRight("提交成功");
        } else {
            self::$ajax->outError(Ajax::FAIL_SUBMIT);
        }
    }

    //审核
    /** 社区审核
     * @param $apply_id --申请id
     * @param $is_success --是否审核通过
     * @param $check_user --审核人id
     * @param string $reason --审核失败原因
     * @return bool
     */
    public function check($apply_id, $is_success, $check_user, $reason = '')
    {
        if ($apply = CommunityApply::findOne(['id=' . $apply_id . " and status=" . self::check_status_checking, 'columns' => 'type,id,user_id,name,brief,cover,extra_desc,extra_img'])) {
            try {
                $this->original_mysql->begin();
                if ($is_success) {
                    $status = self::check_status_success;
                    $id = Community::insertOne(['type' => $apply['type'], 'user_id' => $apply['user_id'], 'name' => $apply['name'], 'status' => self::status_normal, 'created' => time()]);
                    CommunityProfile::insertOne(['comm_id' => $id, 'created' => time(), 'cover' => $apply['cover'], 'brief' => $apply['brief']]);
                    //区主 自动关注
                    $this->attention($apply['user_id'], $id, true);
                } else {
                    $status = self::check_status_fail;
                }
                CommunityApply::updateOne(['status' => $status, 'modify' => time()], 'id=' . $apply_id);
                CommunityApplyCheckLog::insertOne(['status' => $status, 'check_user' => $check_user, 'check_reason' => $reason, 'apply_id' => $apply['id'], 'check_time' => time(), 'detail' => base64_encode(json_encode($apply, JSON_UNESCAPED_UNICODE))]);

                if ($is_success) {
                    CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_APPLY_SUCCESS, ['to_user_id' => $apply['user_id'], 'community_id' => $id, 'community_name' => $apply['name']]);
                } else {
                    CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_APPLY_FAIL, ['to_user_id' => $apply['user_id'], 'community_name' => $apply['name']]);
                }
                $this->original_mysql->commit();


                return true;
            } catch (\Exception $e) {
                $this->original_mysql->rollback();
                return false;
            }
        }
        return false;
    }

    public function communityExist($id)
    {
        return Community::exist("id=" . $id . " and status=" . self::status_normal);
    }

    /**关注社区
     * @param $uid
     * @param $is_check --是否审核后的自动关注
     * @param $comm_id
     */
    public function attention($uid, $comm_id, $is_check = false)
    {
        if (!$is_check) {
            if (!$this->communityExist($comm_id)) {
                self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            //已经关注过
            if (CommunityAttention::exist("user_id=" . $uid . " and comm_id=" . $comm_id)) {
                self::$ajax->outError(Ajax::ERROR_HAS_ATTENTION);
            }
            if (CommunityAttention::insertOne(["user_id" => $uid, 'comm_id' => $comm_id, 'created' => time(), 'role' => self::role_normal])) {
                CommunityProfile::updateOne(["attention_cnt" => 'attention_cnt+1'], 'comm_id=' . $comm_id);
            }
            //todo 关注区主

            self::$ajax->outRight("关注成功");
        } else {
            if (CommunityAttention::insertOne(["user_id" => $uid, 'comm_id' => $comm_id, 'created' => time(), 'role' => self::role_owner])) {
                CommunityProfile::updateOne(["attention_cnt" => 'attention_cnt+1'], 'comm_id=' . $comm_id);
            }
        }


    }

    /**取消社区关注
     * @param $uid
     * @param $comm_id
     */
    public function unAttention($uid, $comm_id)
    {
        $attention = CommunityAttention::findOne(['comm_id=' . $comm_id . " and user_id=" . $uid, 'columns' => 'role']);
        if ($attention) {
            if ($attention['role'] == self::role_owner || $attention['role'] == self::role_admin) {
                self::$ajax->outError(Ajax::ERROR_COMMUNITY_OWNER_ADMIN_UNSUBSCRIBE);
            }
            //社群成员无法取消关注
            if (CommunityGroupMember::exist("comm_id=" . $comm_id . " and user_id=" . $uid)) {
                self::$ajax->outError(Ajax::ERROR_COMMUNITY_GROUP_MEMBER_UNSUBSCRIBE);
            }
            if (CommunityAttention::remove("user_id=" . $uid . " and comm_id=" . $comm_id, true)) {
                CommunityProfile::updateOne(["attention_cnt" => 'attention_cnt-1'], 'comm_id=' . $comm_id);
            }
            self::$ajax->outRight("取消关注成功");
        }

    }


    /**我加入的/创建的社区/
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function myCommunity($uid, $page = 1, $limit = 20)
    {
        $data = ['data_list' => []];
        $offset = ($page - 1) * $limit;
        $ids = CommunityAttention::getColumn(['user_id=' . $uid, 'offset' => $offset, 'limit' => $limit, 'columns' => 'comm_id', 'order' => 'role desc,created desc'], 'comm_id');
        if ($ids) {
            $order_columns = [];
            $community = Community::getByColumnKeyList(['id in (' . implode(',', $ids) . ') and status=' . self::status_normal, 'columns' => 'name,user_id as uid,id'], 'id');
            $community_profile = CommunityProfile::getByColumnKeyList(['comm_id in (' . implode(',', $ids) . ')', 'columns' => 'comm_id,attention_cnt,discuss_cnt,cover,brief'], 'comm_id');
            $res = [];
            if ($community) {
                foreach ($community as $k => $item) {
                    $tmp = array_merge($item, $community_profile[$k]);
                    unset($tmp['id']);
                    $res[] = $tmp;
                    $order_columns[] = array_search($k, $ids);
                }
            }
            array_multisort($order_columns, SORT_ASC, $res);
            $data['data_list'] = $res;
        }
        return $data;
    }

    /**我的社区申请/
     * @param $uid
     * @return array
     */
    public function myApply($uid)
    {
        $apply = CommunityApply::findOne(['user_id=' . $uid, 'columns' => 'id,name,brief,cover,extra_desc,extra_img,status,type,created', 'order' => 'created desc']);

        if ($apply) {
            $apply['check_reason'] = '';
            if ($apply['status'] == self::check_status_success) {
                $community = Community::findOne(['user_id=' . $uid, 'columns' => 'id']);
                $apply['com_id'] = $community['id'];
            } else {
                $apply['com_id'] = "0";
                //审核失败
                if ($apply['status'] == self::check_status_fail) {
                    $check_log = CommunityApplyCheckLog::findOne(['apply_id=' . $apply['id'], 'columns' => 'check_reason']);
                    $apply['check_reason'] = $check_log ? $check_log['check_reason'] : '';
                }
            }
            unset($apply['id']);
        }
        $res = $apply ? $apply : (object)[];
        return $res;
    }

    /**
     * 获取推荐社区
     * @param $uid --用户id
     * @param int $limit --推荐的条数
     * @return array
     */
    public function recommendCommunity($uid, $limit = 4)
    {
        $data = ['data_list' => []];
        $where = "status=" . self::status_normal;
        $my_attention = CommunityAttention::getColumn(["user_id=" . $uid, 'columns' => 'comm_id'], 'comm_id');
        if ($my_attention) {
            $where .= " and id not in (" . implode(',', $my_attention) . ")";
        }
        $community = Community::findList([$where, 'columns' => 'name,user_id as uid,id', 'order' => 'rand()', 'limit' => $limit]);
        if ($community) {
            $ids = array_column($community, 'id');
            $community_profile = CommunityProfile::getByColumnKeyList(['comm_id in (' . implode(',', $ids) . ')', 'columns' => 'comm_id,attention_cnt,discuss_cnt,cover,brief'], 'comm_id');
            $res = [];
            foreach ($community as $item) {
                $tmp = array_merge($item, $community_profile[$item['id']]);
                unset($tmp['id']);
                $res[] = $tmp;
            }
            $data['data_list'] = $res;
        }
        return $data;
    }

    /**
     * 获取推荐新闻
     * @param $uid --用户id
     * @param int $limit --推荐的条数
     * @return array
     */
    public function recommendNews($uid, $limit = 4)
    {
        $data = ['data_list' => []];
        $where = "status=" . CommunityNewsManager::status_normal;
        $news = CommunityNews::findList([$where, 'columns' => 'id as news_id,title,media_type,media', 'order' => 'rand()', 'limit' => $limit]);
        if ($news) {
            $data['data_list'] = $news;
        }
        return $data;
    }

    /**
     * 搜索
     * @param $uid -- 用户id
     * @param $key --搜索关键字
     * @param int $page --第几页
     * @param int $limit --每页显示的数据个数
     * @return array
     */
    public function search($uid, $key, $page = 1, $limit = 20)
    {
        $data = ['data_list' => [], 'data_count' => 0];
        $where = 'status=' . self::status_normal . ' and name like "%' . $key . '%"';
        $community = Community::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'columns' => 'name,user_id as uid,id']);
        $data['data_count'] = Community::dataCount($where);
        if ($community) {
            $ids = array_column($community, 'id');
            $attention = CommunityAttention::getColumn(["comm_id in (" . implode(',', $ids) . ") and user_id=$uid", 'columns' => 'role,comm_id'], 'role', 'comm_id');
            $community_profile = CommunityProfile::getByColumnKeyList(['comm_id in (' . implode(',', $ids) . ')', 'columns' => 'comm_id,attention_cnt,discuss_cnt,cover,brief'], 'comm_id');
            $res = [];
            foreach ($community as $item) {
                $tmp = array_merge($item, $community_profile[$item['id']]);
                if (isset($attention[$item['id']])) {
                    $tmp['is_attention'] = 1;
                    $tmp['attention_role'] = intval($attention[$item['id']]);
                } else {
                    $tmp['is_attention'] = 0;
                    $tmp['attention_role'] = -1;
                }
                unset($tmp['id']);
                $res[] = $tmp;
            }
            $data['data_list'] = $res;
        }
        return $data;
    }

    /**
     * 社区详情
     * @param $uid
     * @param $comm_id
     * @return mixed
     */
    public function detail($uid, $comm_id)
    {
        $community = CommunityInfo::findOne(['comm_id=' . $comm_id . " and status=" . self::status_normal, 'columns' => 'user_id as uid,name,brief,cover,attention_cnt,discuss_cnt,group_cnt,discuss_level,news_level,push_group']);
        if (!$community) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $attention = CommunityAttention::findOne(['user_id=' . $uid . " and comm_id=" . $comm_id, 'columns' => 'role']);
        if ($attention) {
            $community['is_attention'] = 1;
            $community['attention_role'] = intval($attention['role']);
        } else {
            $community['is_attention'] = 0;
            $community['attention_role'] = -1;
        }

        $group_limit = 2;//展示的群聊数
        //区主
        if ($community['uid'] == $uid) {
            $list = Group::findList(['comm_id=' . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL, 'limit' => $group_limit, 'order' => 'created desc', 'columns' => 'user_id as uid,id as gid,is_private,comm_id,name,avatar,introduce']);
            $community['group_list'] = $list;
        } else {
            //先找两个公开群放上面
            $list = Group::findList(['comm_id=' . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL . " and is_private=0", 'limit' => $group_limit, 'order' => 'created desc', 'columns' => 'user_id as uid,id as gid,is_private,comm_id,name,avatar,introduce']);
            //不够找已加入的私聊群
            if (count($list) < $group_limit) {
                $group_ids = Group::getColumn(['comm_id=' . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL . " and is_private=1", 'columns' => 'id', 'order' => 'created desc'], 'id');
                if ($group_ids) {
                    $private_gids = GroupMember::getColumn(['id in (' . implode(',', $group_ids) . ') and user_id=' . $uid, 'limit' => $group_limit - count($list), 'order' => 'created desc', 'columns' => 'gid'], 'gid');
                    if ($private_gids) {
                        $list2 = Group::findList(['id in (' . implode(',', $private_gids) . ')', 'order' => 'created desc', 'columns' => 'user_id as uid,id as gid,is_private,comm_id,name,avatar,introduce']);
                        $list = array_merge($list, $list2);
                    }
                }
            }
            $community['group_list'] = $list;
        }
        if ($community['group_list']) {
            $gids = array_column($community['group_list'], 'gid');
            $joined_group = CommunityGroupMember::getColumn(['gid in (' . implode(',', $gids) . ') and user_id=' . $uid, 'columns' => 'gid'], 'gid');
            foreach ($community['group_list'] as $k => $g) {
                if ($joined_group && in_array($g['gid'], $joined_group)) {
                    $community['group_list'][$k]['is_member'] = 1;
                } else {
                    $community['group_list'][$k]['is_member'] = 0;
                }
            }
        }

        //其他成员

        return $community;
    }

    //设置社区管理员
    public function setManager($uid, $to_uid, $comm_id)
    {
        if ($uid == $to_uid) {
            self::$ajax->outError(Ajax::ERROR_TARGET_CAN_NOT_YOURSELF);
        }
        $community = Community::findOne(["id=" . $comm_id . " and status=" . self::status_normal, 'columns' => 'user_id,name']);
        if (!$community) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //非区主没有该权限
        if ($uid != $community['user_id']) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        $attention = CommunityAttention::findOne(['comm_id=' . $comm_id . " and user_id=" . $to_uid, 'columns' => 'role']);
        //对方还不是社区成员
        if (!$attention) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_NOT_MEMBER);
        }
        //对方已经是管理员
        if ($attention['role'] == self::role_admin) {
            self::$ajax->outRight("设置成功", Ajax::SUCCESS_SUBMIT);
            //  self::$ajax->outError(Ajax::ERROR_COMMUNITY_IS_MANAGER);
        }
        $admin = ["comm_id" => $comm_id, 'user_id' => $to_uid, 'created' => time()];
        try {
            $this->original_mysql->begin();
            //设置管理员
            \Models\Community\CommunityManager::insertOne($admin);
            //变更身份
            CommunityAttention::updateOne(['role' => self::role_admin], 'comm_id=' . $comm_id . " and user_id=" . $to_uid);
            $this->original_mysql->commit();

            CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_MANAGER_ASSIGN, ['to_user_id' => $to_uid, 'community_id' => $comm_id, 'community_name' => $community['name']]);

            self::$ajax->outRight("设置成功", Ajax::SUCCESS_SUBMIT);
        } catch (\Exception $e) {
            $this->original_mysql->rollback();
            self::$ajax->outError(Ajax::FAIL_SUBMIT);
        }

    }

    //删除社区管理员
    /**
     * @param $uid --操作者用户id
     * @param $to_uid --被删除社区管理员的用户id
     * @param $comm_id --社区id
     */
    public function removeManager($uid, $to_uid, $comm_id)
    {
        if ($uid == $to_uid) {
            self::$ajax->outError(Ajax::ERROR_TARGET_CAN_NOT_YOURSELF);
        }
        $community = Community::findOne(["id=" . $comm_id . " and status=" . self::status_normal, 'columns' => 'user_id']);
        if (!$community) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //非区主没有该权限
        if ($uid != $community['user_id']) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        $attention = CommunityAttention::findOne(['comm_id=' . $comm_id . " and user_id=" . $to_uid, 'columns' => 'role']);
        //对方还不是社区成员
        if (!$attention) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_NOT_MEMBER);
        }
        //对方不是管理员
        if ($attention['role'] == self::role_normal) {
            self::$ajax->outRight("设置成功", Ajax::SUCCESS_SUBMIT);
        }
        //该管理员还拥有群
        if (Group::findOne(["comm_id=" . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL . " and user_id=" . $to_uid, 'columns' => '1'])) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_MANAGER_HAS_GROUP_NO_TRANSFER);
        }

        try {
            //移除管理员
            \Models\Community\CommunityManager::remove("comm_id=" . $comm_id . " and user_id=" . $to_uid);
            //变更身份
            CommunityAttention::updateOne(['role' => self::role_normal], 'comm_id=' . $comm_id . " and user_id=" . $to_uid);

            $this->original_mysql->commit();

            CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_MANAGER_REVOKE, ['to_user_id' => $to_uid, 'community_id' => $comm_id, 'community_name' => $community['name']]);

            self::$ajax->outRight("设置成功", Ajax::SUCCESS_SUBMIT);
        } catch (\Exception $e) {
            $this->original_mysql->rollback();
            self::$ajax->outError(Ajax::FAIL_SUBMIT);
        }
    }

    /**获取社区管理员列表
     * @param $uid
     * @param $comm_id
     * @return array
     */
    public function managerList($uid, $comm_id)
    {
        $data = ['data_list' => []];
        $community = Community::findOne(["id=" . $comm_id . " and status=" . self::status_normal, 'columns' => 'user_id']);
        if (!$community) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //非区主没有该权限
        if ($uid != $community['user_id']) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        $list = \Models\Community\CommunityManager::getColumn(["comm_id=" . $comm_id, 'columns' => 'user_id'], 'user_id');
        if ($list) {
            $data['data_list'] = UserInfo::findList(['user_id in (' . implode(',', $list) . ')', 'columns' => 'username,user_id as uid,avatar']);
        }
        return $data;
    }

    /** -设置
     * @param $uid
     * @param $comm_id
     * @param $data
     */
    public function setting($uid, $comm_id, $data)
    {
        $community = Community::findOne(["id=" . $comm_id . " and status=" . self::status_normal, 'columns' => 'user_id']);
        if (!$community) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $attention = CommunityAttention::findOne(['comm_id=' . $comm_id . " and user_id=" . $uid, 'columns' => 'role']);
        //对方还不是社区成员
        if (!$attention) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        if ($attention['role'] != self::role_owner && $attention['role'] != self::role_admin) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        if (CommunityProfile::updateOne($data, "comm_id=" . $comm_id)) {
            self::$ajax->outRight("设置成功", Ajax::SUCCESS_SUBMIT);
        }
        self::$ajax->outError(Ajax::FAIL_SUBMIT);
    }

    /**
     * 获取社区关注者列表
     * @param $uid
     * @param $comm_id
     * @param $key
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function followersAction($uid, $comm_id, $key = '', $page = 1, $limit = 20)
    {
        $data = ['data_list' => []];
        $where = "comm_id=" . $comm_id;
        if ($key) {
            $query = "select ca.user_id as uid from community_attention as ca left join users as u on ca.user_id=u.id where u.username like '%" . $key . "%' and ca.comm_id=$comm_id order by ca.created desc limit " . ($page - 1) * $limit . "," . $limit;
            $list = $this->original_mysql->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $list = CommunityAttention::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc', 'columns' => 'user_id as uid']);
        }
        if ($list) {
            $uids = array_column($list, 'uid');
            $uids = implode(',', $uids);
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth'], 'uid');
            $user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');

            foreach ($list as $item) {
                $item = $users[$item['uid']];
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($users[$item['uid']]['username']);
                $data['data_list'][] = $item;
            }
        }

        return $data;
    }

    public function friends($uid, $comm_id, $key = '', $page = 1, $limit = 20)
    {
        $data = ['data_list' => []];
        $where = "comm_id=" . $comm_id;
        $contact_where = 'owner_id=' . $uid;
        if ($key) {
            $contact_where = " and (default_mark like '%" . $key . "%' or mark like '%" . $key . "%')";
        }
        $followers = CommunityAttention::getColumn([$where, 'columns' => 'user_id'], 'user_id');
        if ($followers) {
            $contact_where .= " and user_id in(" . implode(',', $followers) . ")";
            if ($page > 1) {
                $list = UserContactMember::findList([$contact_where, 'columns' => 'created,user_id as uid,default_mark,mark', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
            } else {
                $list = UserContactMember::findList([$contact_where, 'columns' => 'created,user_id as uid,default_mark,mark']);
            }
            if ($list) {
                $uids = array_column($list, 'uid');
                $uids = implode(',', $uids);

                $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth'], 'uid');
                $user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
                foreach ($list as $item) {
                    $item = $users[$item['uid']];
                    $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($users[$item['uid']]['username']);
                    $data['data_list'][] = $item;
                }
            }
        }
        return $data;
    }

}