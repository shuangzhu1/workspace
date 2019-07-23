<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/3
 * Time: 16:56
 */

namespace Services\Community;


use Components\Kafka\Producer;
use Components\Yunxin\ServerAPI;
use Models\Community\Community;
use Models\Community\CommunityApply;
use Models\Community\CommunityAttention;
use Models\Community\CommunityGroup;
use Models\Community\CommunityGroupApply;
use Models\Community\CommunityGroupMember;
use Models\Community\CommunityNews;
use Models\Community\CommunityProfile;
use Models\Group\GroupAnnouncement;
use Services\Community\CommunityManager;
use Models\Group\Group;
use Models\Group\GroupInviteLog;
use Models\Group\GroupJoinLog;
use Models\Group\GroupMember;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\Users;
use Models\Vip\VipPrivileges;
use Phalcon\Mvc\User\Plugin;
use Services\Kafka\TopicDefine;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\User\GroupManager;
use Util\Ajax;
use Util\Debug;

class CommunityGroupManager extends Plugin
{
    private static $instance = null;
    protected static $ajax = null;

    const check_status_waiting = 0;//待审核
    const check_status_success = 1;//审核通过
    const check_status_fail = 2;//审核失败

    const beyond_limit = 4;//超过了多少用户需要认证

    /**
     * @param bool $is_cli
     * @return  CommunityGroupManager
     */
    public static function getInstance($is_cli = false)
    {
        if (!self::$instance) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    public function __construct($is_cli = false)
    {
        if (!$is_cli) {
            self::$ajax = new Ajax();
        }
    }
    //
    /**检测社区名是否被使用
     * @param $uid
     * @param $comm_id
     * @param $name
     * @return bool
     */
    public function checkName($uid, $comm_id, $name)
    {
        if (CommunityGroup::exist("name='" . $name . "' and comm_id=" . $comm_id)) {
            return false;
        }
        if (CommunityGroupApply::exist("name='" . $name . "' and comm_id=" . $comm_id . " and status=" . self::check_status_waiting)) {
            return false;
        }
        return true;
    }

    /**
     * 群聊列表
     * @param $uid --用户id
     * @param $comm_id --社区id
     * @param int $page --第几页
     * @param int $limit --每页显示的数据量
     * @return array
     */
    public function groupList($uid, $comm_id, $page = 1, $limit = 20)
    {
        $data = ['data_list' => []];
        $community = Community::findOne(["id=" . $comm_id . " and status=" . CommunityManager::status_normal, 'columns' => 'user_id']);
        if (!$community) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }

        $attention = CommunityAttention::findOne(['comm_id=' . $comm_id . " and user_id=" . $uid, 'columns' => 'role']);
        //还不是社区成员 //只能看见公开群
        if (!$attention) {
            $list = Group::findList(['comm_id=' . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL . " and is_private=0", 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc', 'columns' => 'user_id as uid,id as gid,yx_gid,name,avatar,comm_id,is_private']);
            foreach ($list as &$item) {
                $item['is_member'] = 0;
            }
        } else {
            //区主能够看到所有社群
            if ($attention['role'] == CommunityManager::role_owner) {
                $list = Group::findList(['comm_id=' . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc', 'columns' => 'user_id as uid,id as gid,yx_gid,name,avatar,comm_id,is_private']);
            } else {
                //公开群个数
                $public_group_count = Group::dataCount('comm_id=' . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL . " and is_private=0");

                $offset = ($page - 1) * $limit;
                $private_count = 0;
                $private_offset = 0;
                $list = [];
                if ($offset < $public_group_count) {
                    //全部取公开群信息
                    if ($public_group_count < $offset + $limit) {
                        $private_count = $offset + $limit - $public_group_count;
                    }
                    $list = Group::findList(['comm_id=' . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL . " and is_private=0", 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc', 'columns' => 'user_id as uid,id as gid,yx_gid,name,avatar,comm_id,is_private']);

                } else {
                    $private_offset = $offset - $public_group_count;
                    $private_count = $limit;
                }
                if ($private_count) {
                    $ids = Group::getColumn(['comm_id=' . $comm_id . " and status=" . GroupManager::GROUP_STATUS_NORMAL . " and is_private=1", 'columns' => 'id'], 'id');
                    if ($ids) {
                        $gids = GroupMember::getColumn(["user_id=" . $uid . " and gid in(" . implode(',', $ids) . ")", 'offset' => $private_offset, 'limit' => $private_count, 'order' => 'created desc', 'columns' => 'gid'], 'gid');
                        if ($gids) {
                            $private_list = Group::findList(["id in (" . implode(',', $gids) . ")", 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc', 'columns' => 'user_id as uid,id as gid,yx_gid,name,avatar,comm_id,is_private']);
                            $private_list && $list = array_merge($list, $private_list);
                        }
                    }
                }
            }
            if ($list) {
                $gids = array_column($list, 'gid');
                //已加入的群
                $joined_group = CommunityGroupMember::getColumn(['gid in (' . (implode(',', $gids)) . ') and user_id=' . $uid, 'columns' => 'gid'], 'gid', 'gid');
                foreach ($list as &$item) {
                    $item['is_member'] = ($joined_group && isset($joined_group[$item['gid']])) ? 1 : 0;
                }
            }
        }
        $data['data_list'] = $list;
        return $data;
    }

    //创建群聊申请
    public function createGroupApply($uid, $to_uid, $comm_id, $avatar, $name, $is_private = false, $introduce = '')
    {
        $community = Community::findOne(['id=' . $comm_id . " and status=" . CommunityManager::status_normal, 'columns' => 'user_id,name']);
        if (!$community) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }

        //每天限制只能提交一次
        if (CommunityGroupApply::exist("user_id=" . $uid . " and comm_id=" . $comm_id . " and ymd=" . date('Ymd'))) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_APPLY_LIMIT);
        }
        //普通社区成员最多只能创建一个社群
        if (CommunityGroup::exist("user_id='" . $uid . "' and comm_id=" . $comm_id)) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_GROUP_LIMIT);
        }

        //已经在审核中
        if (CommunityGroupApply::exist("user_id=" . $uid . " and comm_id=" . $comm_id . " and status=" . self::check_status_waiting)) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_CHECKING);
        }

        //普通成员提交的建群申请  相同名字的社群已经存在
        if (CommunityGroupApply::exist("name='" . $name . "' and comm_id=" . $comm_id . " and status=" . self::check_status_waiting)) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS);
        }

        //相同名字的社群已经存在
        if (CommunityGroup::exist("name='" . $name . "' and comm_id=" . $comm_id)) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS);
        }

        $owner = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'username,is_vip']);
        //已经创建的群聊个数
        $group_count = Group::dataCount("user_id=" . $uid . " and status=" . GroupManager::GROUP_STATUS_NORMAL);

        $normal_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
        //群聊个数限制
        $add_group_limit = $normal_setting ? $normal_setting['add_group_count'] : 30;
        //群聊人数限制
        $group_member_limit = $normal_setting ? $normal_setting['group_member_count'] : 200;

        if ($owner['is_vip']) {
            $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'add_group_count,group_member_count']);
            $add_group_limit = $vip_privileges ? $vip_privileges['add_group_count'] : $add_group_limit;
            $group_member_limit = $vip_privileges ? $vip_privileges['group_member_count'] : $group_member_limit;
        }

        if ($group_count >= $add_group_limit) {
            self::$ajax->outError(Ajax::CUSTOM_ERROR_MSG, "创建群聊已达上限");
        }
        if (substr_count($to_uid, ',') + 2 > $group_member_limit) {
            self::$ajax->outError(Ajax::CUSTOM_ERROR_MSG, Ajax::getCustomMsg(Ajax::GROUP_CREATE, $group_member_limit));
        }

        $apply_data = array(
            "user_id" => $uid,
            "created" => time(),
            "comm_id" => $comm_id,
            'name' => $name,
            'ymd' => date('Ymd'),
            'detail' => json_encode([
                'member_limit' => $group_member_limit,
                "is_private" => $is_private,
                'comm_owner' => $community['user_id'],
                'introduce' => $introduce,
                'to_uid' => $to_uid,
                'name' => $name,
                'avatar' => $avatar
            ]));
        //申请成功 推送消息给区长和管理员
        if ($apply_id = CommunityGroupApply::insertOne($apply_data)) {
            $admins = \Models\Community\CommunityManager::getColumn(["comm_id=" . $comm_id, 'columns' => 'user_id'], 'user_id');
            $admins[] = $community['user_id'];
            $user = Users::findOne(['id=' . $uid, 'columns' => 'username']);
            foreach ($admins as $a) {
                CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_GROUP_CREATE_APPLY,
                    [
                        'to_user_id' => $a,
                        'user_id' => $uid,
                        'comm_id' => $comm_id,
                        'comm_name' => $community['name'],
                        'group_name' => $name,
                        'apply_id' => $apply_id,
                        'username' => $user['username']
                    ]);
            }
            self::$ajax->outRight("申请成功", Ajax::SUCCESS_SUBMIT);
        }
        self::$ajax->outError(Ajax::FAIL_SUBMIT);
    }

    // 异步处理普通成员创建群聊审核结果
    public function checkGroupCreateApplyAsync($data)
    {
        $apply = CommunityGroupApply::findOne(['id=' . $data['apply_id']]);
        $group_detail = json_decode($apply['detail'], true);
        $community = Community::findOne(['id=' . $apply['comm_id'], 'columns' => 'user_id,name']);

        $uid = $apply['user_id'];//创建群聊的用户id
        $comm_id = $apply['comm_id'];//社群id
        $name = $group_detail['name'];//群名称
        $avatar = $group_detail['avatar'];//群头像
        $introduce = $group_detail['introduce'];//群简介
        $is_private = $group_detail['is_private'];//群是否私有


        //申请的用户已经取消了关注社区
        if (!CommunityAttention::exist("user_id=" . $uid . " and comm_id=" . $comm_id)) {
            return true;
        }
        //审核失败
        if (!$data['is_success']) {
            //申请已被处理
            if ($apply['status'] != self::check_status_waiting) {
                return false;
            }
            if (CommunityGroupApply::updateOne(["status" => self::check_status_fail, 'modify' => time(), 'executor' => $data['executor']], 'id=' . $data['apply_id'])) {
                //推送消息给申请人
                CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_GROUP_CREATE_FAIL, [
                        'to_user_id' => $apply['to_user_id'],
                        'comm_id' => $apply['comm_id'],
                        'comm_name' => $community['name'],
                        'group_name' => $group_detail['name'],
                        'reason' => '',
                    ]

                );
                return true;
            }
            return false;
        } else {
            CommunityGroupApply::updateOne(["status" => self::check_status_success, 'modify' => time(), 'executor' => $data['executor']], 'id=' . $data['apply_id']);

            //审核通过

            $owner = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'username,is_vip']);
            //已经创建的群聊个数
            $group_count = Group::dataCount("user_id=" . $uid . " and status=" . GroupManager::GROUP_STATUS_NORMAL);

            $normal_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
            //群聊个数限制
            $add_group_limit = $normal_setting ? $normal_setting['add_group_count'] : 30;

            if ($owner['is_vip']) {
                $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'add_group_count,group_member_count']);
                $add_group_limit = $vip_privileges ? $vip_privileges['add_group_count'] : $add_group_limit;
            }
            if ($group_count >= $add_group_limit) {
                //推送消息给申请人
                CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_GROUP_CREATE_FAIL, [
                    'to_user_id' => $apply['to_user_id'],
                    'comm_id' => $apply['comm_id'],
                    'comm_name' => $data['comm_name'],
                    'group_name' => $group_detail['name'],
                    'reason' => '创建群聊已达上限',
                ]);
                return true;
            }

            try {
                $this->db->begin();
                $this->original_mysql->begin();

                $group_data = array(
                    'default_name' => '',
                    'name' => $group_detail['name'],
                    "default_avatar" => '',
                    "avatar" => $avatar,
                    "user_id" => $uid,
                    "created" => time(),
                    "last_avatar_user_count" => 0,
                    // "member_limit" => $grade['group_member_count'],
                    "member_limit" => $group_detail['member_limit'],
                    "join_mode" => GroupManager::GROUP_JOIN_MODE_ALL,
                    "invite_mode" => GroupManager::GROUP_INVITE_MODE_ALL,
                    "is_private" => $is_private,
                    'introduce' => $introduce,
                    'comm_id' => $comm_id,
                    'comm_owner' => $group_detail['comm_owner'],
                    'custom' => json_encode(['comm_id' => $comm_id, 'comm_owner' => $group_detail['comm_owner']])
                );
                $to_uid = $group_detail['to_uid'];
                $to_user_ids = CommunityAttention::getColumn(['user_id in (' . $to_uid . ')' . ' and comm_id=' . $comm_id], 'user_id');
                $to_uid = implode(',', $to_user_ids);
                $group = new Group();
                if ($id = $group->insertOne($group_data)) {
                    CommunityGroup::insertOne(['gid' => $id, 'user_id' => $uid, 'created' => time(), 'comm_id' => $comm_id, 'is_private' => $group_detail['is_private'], 'name' => $group_detail['name']]);
                    $res = $this->batchAddMember($comm_id, $uid, $uid . "," . $to_uid, $id, 0, GroupManager::GROUP_JOIN_TYPE_CREATE, $uid, 1);
                    if (!$res) {
                        throw  new \Exception("云信-创建群聊失败 添加成员失败");
                    }
                    //    $start2 = microtime(true);
                    //    Debug::log("start2:" . microtime(true), 'debug');

                    $yx_gid = 0;//云信gid
                    $yx = ServerAPI::init()->createGroup($name, $uid, explode(',', $to_uid), $avatar, $owner['username'], 1, 0, '', '', 0, $group_data['custom']);

                    // $end = microtime(true);
                    //  Debug::log("use time:" . ($end - $start2), 'debug');


                    if ($yx && $yx['code'] == 200) {
                        $yx_gid = $yx['tid'];
                    } else {
                        throw  new \Exception("云信-创建群聊失败" . ($yx ? $yx['desc'] : ''));
                    }
                    $data = ['default_name' => $name, 'yx_gid' => $yx_gid];
                    $group->updateOne($data, 'id=' . $id);
                    GroupMember::updateOne(["yx_gid" => $yx_gid], "gid=" . $id);

                    CommunityProfile::updateOne(['group_cnt' => 'group_cnt+1'], 'comm_id=' . $comm_id);


                    //推送消息给申请人
                    CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_GROUP_CREATE_SUCCESS, [
                            'to_user_id' => $apply['to_user_id'],
                            'comm_id' => $apply['comm_id'],
                            'comm_name' => $community['name'],
                            'group_name' => $name,
                            'group_id' => $id,
                        ]
                    );

                    $this->db->commit();
                    $this->original_mysql->commit();
                    return true;
                } else {
                    throw  new \Exception("创建群聊失败");
                }
            } catch (\Exception $e) {
                $this->db->rollback();
                $this->original_mysql->rollback();

                Debug::log('创建群聊失败' . $e->getMessage(), 'error');
                return false;
            }

        }

    }

    //
    /**创建社群申请审核
     * @param $uid
     * @param $apply_id
     * @param $is_success
     */
    public function checkGroupCreateApply($uid, $apply_id, $is_success)
    {
        $apply = CommunityGroupApply::findOne(['id=' . $apply_id]);
        if (!$apply) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //申请已被处理
        if ($apply['status'] != self::check_status_waiting) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_APPLY_HAS_BEEN_HANDLE);
        }
        $community = Community::findOne(['id=' . $apply['comm_id'], 'columns' => 'user_id,name']);
        if ($community['user_id'] != $uid) {
            if (!\Models\Community\CommunityManager::exist("user_id=" . $uid . " and comm_id=" . $apply['comm_id'])) {
                self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
            }
        }
        Producer::getInstance($this->di->get("config")->kafka->host)->setTopic(TopicDefine::TOPIC_COMMUNITY_GROUP_CREATE)->produce(
            ['is_success' => $is_success, 'executor' => $uid, 'apply_id' => $apply_id]
        );
        self::$ajax->outRight("提交成功", Ajax::SUCCESS_SUBMIT);

    }

    //创建群聊
    /**
     * @param $uid
     * @param $to_uid
     * @param $comm_id
     * @param $avatar
     * @param $name
     * @param bool $is_private
     * @param bool $is_apply --是否申请创建群聊
     * @param string $introduce --群简介
     * @return array|bool
     * @throws \Exception
     */
    public function createGroup($uid, $to_uid, $comm_id, $avatar, $name, $is_private = false, $is_apply = false, $introduce = '')
    {
        $attention = CommunityAttention::findOne(['user_id=' . $uid . " and comm_id=" . $comm_id, 'columns' => 'role']);
        if (!$attention) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        //普通社区成员申请创建群聊
        if ($is_apply) {
            $this->createGroupApply($uid, $to_uid, $comm_id, $avatar, $name, $is_private, $introduce);
        } else {
            if (($attention['role'] != CommunityManager::role_owner && $attention['role'] != CommunityManager::role_admin)) {
                self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
            }
        }
        $community = Community::findOne(['id=' . $comm_id . " and status=" . CommunityManager::status_normal, 'columns' => 'user_id']);
        if (!$community) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //普通成员提交的建群申请 相同名字的社群已经存在
        if (CommunityGroupApply::exist("name='" . $name . "' and comm_id=" . $comm_id . " and status=" . self::check_status_waiting)) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS);
        }
        //相同名字的社群已经存在
        if (CommunityGroup::exist("name='" . $name . "' and comm_id=" . $comm_id)) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS);
        }

        //获取该用户在该社区创建的社群个数  默认一个管理员建一个 todo 后台控制个数
//        $community_group_cnt = CommunityGroup::dataCount("comm_id=" . $comm_id . " and user_id=" . $uid);
//        if ($community_group_cnt > 0) {
//            self::$ajax->outError(Ajax::ERROR_COMMUNITY_GROUP_LIMIT);
//        }

        $owner = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'username,is_vip']);
        //已经创建的群聊个数
        $group_count = Group::dataCount("user_id=" . $uid . " and status=" . GroupManager::GROUP_STATUS_NORMAL);

        $normal_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
        //群聊个数限制
        $add_group_limit = $normal_setting ? $normal_setting['add_group_count'] : 30;
        //群聊人数限制
        $group_member_limit = $normal_setting ? $normal_setting['group_member_count'] : 200;

        if ($owner['is_vip']) {
            $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'add_group_count,group_member_count']);
            $add_group_limit = $vip_privileges ? $vip_privileges['add_group_count'] : $add_group_limit;
            $group_member_limit = $vip_privileges ? $vip_privileges['group_member_count'] : $group_member_limit;
        }

        if ($group_count >= $add_group_limit) {
            self::$ajax->outError(Ajax::CUSTOM_ERROR_MSG, "创建群聊已达上限");
        }
        if (substr_count($to_uid, ',') + 2 > $group_member_limit) {
            self::$ajax->outError(Ajax::CUSTOM_ERROR_MSG, Ajax::getCustomMsg(Ajax::GROUP_CREATE, $group_member_limit));
        }
        try {
            $this->db->begin();
            $this->original_mysql->begin();

            $group_data = array(
                'default_name' => '',
                'name' => $name,
                "default_avatar" => '',
                "avatar" => $avatar,
                "user_id" => $uid,
                "created" => time(),
                "last_avatar_user_count" => 0,
                // "member_limit" => $grade['group_member_count'],
                "member_limit" => $group_member_limit,
                "join_mode" => GroupManager::GROUP_JOIN_MODE_ALL,
                "invite_mode" => GroupManager::GROUP_INVITE_MODE_ALL,
                "is_private" => $is_private,
                'comm_id' => $comm_id,
                'comm_owner' => $community['user_id'],
                'introduce' => $introduce,
                'custom' => json_encode(['comm_id' => $comm_id, 'comm_owner' => $community['user_id']])
            );
            $group = new Group();
            if ($id = $group->insertOne($group_data)) {
                CommunityGroup::insertOne(['gid' => $id, 'user_id' => $uid, 'created' => time(), 'comm_id' => $comm_id, 'is_private' => $is_private, 'name' => $name]);
                $res = $this->batchAddMember($comm_id, $uid, $uid . "," . $to_uid, $id, 0, GroupManager::GROUP_JOIN_TYPE_CREATE, $uid, 1);
                if (!$res) {
                    throw  new \Exception("云信-创建群聊失败 添加成员失败");
                }
                //    $start2 = microtime(true);
                //    Debug::log("start2:" . microtime(true), 'debug');

                $yx_gid = 0;//云信gid
                $yx = ServerAPI::init()->createGroup($name, $uid, explode(',', $to_uid), $avatar, $owner['username'], 1, 0, '', '', 0, $group_data['custom']);

                // $end = microtime(true);
                //  Debug::log("use time:" . ($end - $start2), 'debug');


                if ($yx && $yx['code'] == 200) {
                    $yx_gid = $yx['tid'];
                } else {
                    throw  new \Exception("云信-创建群聊失败" . ($yx ? $yx['desc'] : ''));
                }
                $data = ['default_name' => $name, 'yx_gid' => $yx_gid];
                $group->updateOne($data, 'id=' . $id);
                GroupMember::updateOne(["yx_gid" => $yx_gid], "gid=" . $id);
                CommunityProfile::updateOne(['group_cnt' => 'group_cnt+1'], 'comm_id=' . $comm_id);
                $this->db->commit();
                $this->original_mysql->commit();

                // $end = microtime(true);

                // Debug::log("use total time:" . ($end - $start), 'debug');

                // 重新获取数据
                return array('gid' => $id, 'group_name' => $name, "group_avatar" => $avatar, 'yx_gid' => $yx_gid, 'join_mode' => GroupManager::GROUP_JOIN_MODE_ALL, "invite_mode" => GroupManager::GROUP_INVITE_MODE_ALL);
            } else {
                throw  new \Exception("创建群聊失败");
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();

            Debug::log('创建群聊失败' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**邀请好友加入群聊
     * @param $uid -邀请人
     * @param $to_uid -被邀请人
     * @param $gid -群id
     * @return bool
     */
    public function inviteGroup($uid, $to_uid, $gid)
    {
        $groupManager = GroupManager::init();
        $group = $groupManager->groupExists($gid, 'name,comm_id,member_limit,user_id,yx_gid,invite_mode,join_mode,is_private');
        //数据不存在
        if (!$group || !$group['comm_id']) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$member = $groupManager->memberExists($gid, $uid, 'member_type')) {
            self::$ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        //只有群主能邀请
        if ($group['invite_mode'] == GroupManager::GROUP_INVITE_MODE_NONE || $group['is_private'] == 1) {
            if ($group['user_id'] != $uid) {
                self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN);
            }
        } //仅管理员能邀请
        else if ($group['invite_mode'] == GroupManager::GROUP_INVITE_MODE_ADMIN) {
            //既不是管理员 又不是群主
            if ($group['user_id'] != $uid && $member['member_type'] != GroupManager::GROUP_MEMBER_ADMIN) {
                self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN);
            }
        }
        //不允许加入 仅群主可以
        if ($group['join_mode'] == GroupManager::GROUP_JOIN_MODE_NONE) {
            if ($group['user_id'] != $uid && $member['member_type'] != GroupManager::GROUP_MEMBER_ADMIN) {
                self::$ajax->outError(Ajax::ERROR_MEMBER_NOT_JOIN);
            }
        }
        $add_member_count = substr_count($to_uid, ',') + 1;
        //云信一次最多支持拉200个用户
        if ($add_member_count > 200) {
            self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_LIMIT_200);
        }
        $community = Community::findOne(['id=' . $group['comm_id'], 'columns' => 'name']);

        $count = GroupMember::dataCount('gid = ' . $gid);
        if ($add_member_count + $count >= $group['member_limit']) {
            if ($group['member_limit'] == $count) {
                self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_LIMIT);
            } else {
                self::$ajax->outError(Ajax::CUSTOM_ERROR_MSG, Ajax::getCustomMsg(Ajax::GROUP_INVITE, ($group['member_limit'] - $count)));
            }
        }
        try {
            $exist_uids = GroupMember::getColumn(['gid = ' . $gid . ' and user_id in(' . $to_uid . ')', 'columns' => 'user_id'], 'user_id');
            if ($exist_uids) {
                $to_uid = explode(',', $to_uid);
                $to_uid_arr = array_diff($to_uid, $exist_uids);
                $to_uid = $to_uid_arr ? implode(',', $to_uid_arr) : '';
            }
            $this->db->begin();

            if ($to_uid) {
                $no_need_agree = [];//不需要同意的用户id
                //社群成员数超过了指定个 需要对方同意
                if ($count >= self::beyond_limit) {
                    $this->addBatchInviteLog($uid, $to_uid, $gid, $group['comm_id'], $community['name'], $group['name']);
                } elseif (($add_member_count + $count) <= self::beyond_limit) {
                    $this->batchAddMember($group['comm_id'], $uid, $to_uid, $gid, $group['yx_gid'], GroupManager::GROUP_JOIN_TYPE_INVITE, $uid, 0);
                    $no_need_agree = explode(',', $to_uid);
                } else {
                    $to_uid = explode(',', $to_uid);
                    //不需要同意的成员
                    $no_need_agree_uid = array_slice($to_uid, 0, self::beyond_limit - $count);
                    //需要同意的成员
                    $need_agree_uid = array_slice($to_uid, self::beyond_limit - $count);
                    $this->batchAddMember($group['comm_id'], $uid, implode(',', $no_need_agree_uid), $gid, $group['yx_gid'], GroupManager::GROUP_JOIN_TYPE_INVITE, $uid, 0);
                    $this->addBatchInviteLog($uid, implode(',', $need_agree_uid), $gid, $group['comm_id'], $community['name'], $group['name']);
                    $no_need_agree = $no_need_agree_uid;
                }
                if ($no_need_agree) {
                    $inviter = GroupMember::findOne(['gid = ' . $gid . ' and user_id = ' . $uid, 'columns' => 'nick,default_nick']);
                    //云信接口调用
                    //*云信限制150字符以下*/
                    $msg = ($inviter['nick'] ? $inviter['nick'] : $inviter['default_nick']);
                    $res = ServerAPI::init()->addIntoGroup($group['yx_gid'], $group['user_id'], $no_need_agree, 0, $msg, json_encode(['extend_type' => 'invite_group', "username" => $msg, 'uid' => $uid], JSON_UNESCAPED_UNICODE));
                    if (!$res || $res['code'] !== 200) {
                        if ($res['code'] == 801) {
                            throw new \Exception('邀请好友加入群聊失败:群人数达到上限');
                        }
                        throw new \Exception('邀请好友加入群聊失败:云信错误' . ($res ? $res['desc'] : ''));
                    }
                }

            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            $this->db->rollback();
            return false;
        }
    }

    /**批量添加群成员
     * @param $comm_id
     * @param $owner
     * @param $to_uid
     * @param $gid
     * @param $yx_gid
     * @param int $join_type
     * @param int $invitor
     * @param int $is_create
     * @return bool|string
     */
    public function batchAddMember($comm_id, $owner, $to_uid, $gid, $yx_gid, $join_type = 0, $invitor = 0, $is_create = 1)
    {
        if (!is_array($to_uid)) {
            $to_uid = array_filter(array_unique(explode(',', $to_uid)));
        }
        $members = Users::getByColumnKeyList(['id in (' . implode(',', $to_uid) . ')', 'columns' => 'username,id'], 'id');

        $batch_data = [];//群成员数据集合
        $batch_data2 = [];//社群群成员数据集合
        $time = time();
        $redis = $this->di->get('redis');
        foreach ($members as $m) {
            $tmp_data = [$gid, $yx_gid, $m['id'], $owner == $m['id'] ? GroupManager::GROUP_MEMBER_CREATOR : GroupManager::GROUP_MEMBER_NORMAL, $m['username'], $time, $join_type, $invitor];
            $batch_data2[] = [$gid, $m['id'], $comm_id];
            //非创建群聊  禁言处理 之前被禁言退出的 再次进来标记为禁言
            if (!$is_create) {
                if ($redis->hExists(CacheSetting::KEY_GROUP_MEMBER_MUTE . $gid, $m['id'])) {
                    $tmp_data[] = 1;
                } else {
                    $tmp_data[] = 0;
                }
            } else {
                $tmp_data[] = 0;
            }
            $batch_data[] = $tmp_data;
        }
        if (!GroupMember::insertBatch(['gid', 'yx_gid', 'user_id', 'member_type', 'default_nick', 'created', 'join_type', 'invitor', 'is_mute'], $batch_data)) {
            return false;
        }
        if (!CommunityGroupMember::insertBatch(['gid', 'user_id', 'comm_id'], $batch_data2)) {
            return false;
        }
        return true;
    }

    //发送社群群聊邀请
    public function addBatchInviteLog($uid, $to_uid, $gid, $comm_id, $comm_name, $group_name)
    {
        if (!is_array($to_uid)) {
            $to_uid = array_filter(array_unique(explode(',', $to_uid)));
        }
        $batch_data = [];
        $time = time();
        foreach ($to_uid as $u) {
            $batch_data[] = [$gid, $uid, $u, $time];
        }
        if (!GroupInviteLog::insertBatch(['gid', 'user_id', 'to_user_id', 'created'], $batch_data)) {
            return false;
        }
        foreach ($to_uid as $item) {
            //发送消息
            CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_GROUP_INVITE, ['user_id' => $uid, 'to_user_id' => $item, 'community_id' => $comm_id, 'community_name' => $comm_name, 'group_id' => $gid, 'group_name' => $group_name]);
        }

        return true;
    }

    /**加入群聊
     * @param $uid -进群人用户id
     * @param $invitor -邀请者
     * @param $gid -群id
     * @param $join_type -加入方式 1-被人邀请 2-主动申请
     * @return bool
     */
    public function joinGroup($uid, $invitor = 0, $gid, $join_type)
    {
        $groupManager = GroupManager::init();
        $group = $groupManager->groupExists($gid, 'name,member_limit,user_id,yx_gid,join_mode,invite_mode,is_private,comm_id,comm_owner');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //已经是群成员
        if ($groupManager->memberExists($gid, $uid)) {
            self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_EXIST);
        }
        if ($invitor) {
            //邀请者不是群成员
            if (!$invitor_info = $groupManager->memberExists($gid, $invitor, 'default_nick,nick,member_type')) {
                self::$ajax->outError(Ajax::ERROR_INVITOR_NOT_GROUP_MEMBER);
            }
        } else {
            //私密群 禁止加入
            if ($group['is_private']) {
                self::$ajax->outError(Ajax::ERROR_COMMUNITY_PRIVATE_GROUP_NOT_JOIN);
            }


            //申请加入的不是区主
            if ($group['comm_owner'] != $uid) {
                //主动加群 自动关注社区
                $apply_id = GroupJoinLog::insertOne(['user_id' => $uid, 'gid' => $gid, 'created' => time()]);
                $admins = GroupMember::getColumn(['gid=' . $gid . " and (member_type=" . $groupManager::GROUP_MEMBER_CREATOR . " or member_type=" . $groupManager::GROUP_MEMBER_ADMIN . ")", 'columns' => 'user_id'], 'user_id');
                $community = Community::findOne(["id=" . $group['comm_id'], 'columns' => 'name']);

                if (!CommunityAttention::exist("comm_id=" . $group['comm_id'] . " and user_id=" . $uid)) {
                    if (CommunityAttention::insertOne(["user_id" => $uid, 'comm_id' => $group['comm_id'], 'created' => time(), 'role' => CommunityManager::role_normal])) {
                        CommunityProfile::updateOne(["attention_cnt" => 'attention_cnt+1'], 'comm_id=' . $group['comm_id']);
                    }
                }

                $user = Users::findOne(['id=' . $uid, 'columns' => 'username,avatar']);
                foreach ($admins as $a) {
                    CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_GROUP_JOIN_APPLY,
                        ['to_user_id' => $a, 'community_id' => $group['comm_id'], 'username' => $user['username'], 'uid' => $uid, 'avatar' => $user['avatar'], 'community_name' => $community['name'], 'group_name' => $group['name'], 'group_id' => $gid, 'apply_id' => $apply_id
                        ]);
                }
                return true;
            }

        }

        //不允许加入 扫群主二维码可以
        if ($group['join_mode'] == $groupManager::GROUP_JOIN_MODE_NONE && $group['user_id'] != $invitor) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_NOT_JOIN);
        }
        //仅允许管理员邀请
        if ($group['invite_mode'] == $groupManager::GROUP_INVITE_MODE_ADMIN) {
            if ($invitor) {
                if (($invitor_info['member_type'] != GroupManager::GROUP_MEMBER_CREATOR && $invitor_info['member_type'] != GroupManager::GROUP_MEMBER_ADMIN)) {
                    self::$ajax->outError(Ajax::ERROR_MEMBER_NOT_JOIN);
                }
            } else {
                self::$ajax->outError(Ajax::ERROR_MEMBER_NOT_JOIN);
            }
        }
        $member_count = GroupMember::dataCount('gid=' . $gid);
        //群聊成员数已达上限
        if ($group['member_limit'] <= $member_count) {
            self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_LIMIT);
        }
        try {
            $this->db->begin();
            $user = Users::findOne(['id=' . $uid, 'columns' => 'username']);
            if (!$user) {
                self::$ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
            }

            $data = [
                'gid' => $gid,
                'user_id' => $uid,
                'yx_gid' => $group['yx_gid'],
                'member_type' => $groupManager::GROUP_MEMBER_NORMAL,
                'default_nick' => $user['username'],
                'created' => time(),
                'join_type' => $join_type,
                'invitor' => $invitor
            ];
            $redis = $this->di->get('redis');
            if ($redis->hExists(CacheSetting::KEY_GROUP_MEMBER_MUTE . $gid, $uid)) {
                $data['is_mute'] = 1;
            }
            $member = new GroupMember();
            if (!$member->insertOne($data)
            ) {
                $message = [];
                foreach ($member->getMessages() as $msg) {
                    $message[] = (string)$msg;
                }
                throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
            }

            //社群成员录入
            CommunityGroupMember::insertOne(['gid' => $gid, 'user_id' => $uid, 'comm_id' => $group['comm_id']]);

            //云信接口调用
            $res = ServerAPI::init()->addIntoGroup($group['yx_gid'], $group['user_id'], [$uid], 0, $user['username'] . '加入群聊', json_encode(['extend_type' => 'join_group', 'join_type' => $join_type, "username" => $invitor_info['nick'] ? $invitor_info['nick'] : $invitor_info['default_nick']], JSON_UNESCAPED_UNICODE));
            if (!$res || $res['code'] !== 200) {
                throw new \Exception('加入群聊失败:云信错误' . ($res ? $res['desc'] : ''));
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Debug::log('加入群聊失败：' . var_export($e->getMessage(), true), 'error');
            $this->db->rollback();
            return false;
        }
    }

    /**退出群聊
     * @param $uid -退出用户id
     * @param $gid -群id
     * @return bool
     */
    public function leaveGroup($uid, $gid)
    {
        $groupManager = GroupManager::init();
        $group = $groupManager->groupExists($gid, 'member_limit,user_id,yx_gid');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //群主
        if ($group['user_id'] == $uid) {
            self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_ADMIN);
        }
        //不是群成员
        if (!$groupManager->memberExists($gid, $uid)) {
            self::$ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        try {
            $this->db->begin();
            $res = $this->db->query('delete from `group_member`  where gid = ' . $gid . ' and user_id = ' . $uid)->execute();
            if (!$res) {
                throw new \Exception('退出群聊失败');
            }
            //云信接口调用
            $res = ServerAPI::init()->leaveGroup($group['yx_gid'], $uid);
            if (!$res || $res['code'] !== 200) {
                throw new \Exception('退出群聊失败:云信错误' . ($res ? $res['desc'] : ''));
            }
            //社群数据删除
            CommunityGroupMember::remove("user_id=" . $uid . " and gid=" . $gid);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            $this->db->rollback();
            return false;
        }
    }

    /**同意加入群
     * @param $uid
     * @param $gid
     * @param $to_uid
     * @param $pass --是否同意
     * @param $apply_id --审核id
     * @return bool
     */
    public function agreeJoin($uid, $gid, $to_uid, $pass = 1, $apply_id)
    {
        $groupManager = GroupManager::init();
        $group = $groupManager->groupExists($gid, 'member_limit,user_id,yx_gid,comm_id');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$operator = $groupManager->memberExists($gid, $uid, 'member_type')) {
            self::$ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        //不是群主也不是管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != $groupManager::GROUP_MEMBER_ADMIN) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        //已经是群成员
        if ($groupManager->memberExists($gid, $to_uid)) {
            self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_EXIST);
        }
        $member_count = GroupMember::dataCount('gid=' . $gid);
        //群聊成员数已达上限
        if ($group['member_limit'] <= $member_count) {
            self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_LIMIT);
        }
        //审核通过
        if ($pass) {
            try {
                $this->db->begin();
                $user = Users::findOne(['id=' . $to_uid, 'columns' => 'username']);
                if (!$user) {
                    self::$ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
                }

                $data = [
                    'gid' => $gid,
                    'user_id' => $to_uid,
                    'yx_gid' => $group['yx_gid'],
                    'member_type' => $groupManager::GROUP_MEMBER_NORMAL,
                    'default_nick' => $user['username'],
                    'created' => time(),
                    'join_type' => $groupManager::GROUP_JOIN_TYPE_JOIN,
                ];
                $redis = $this->di->get('redis');
                if ($redis->hExists(CacheSetting::KEY_GROUP_MEMBER_MUTE . $gid, $to_uid)) {
                    $data['is_mute'] = 1;
                }
                $member = new GroupMember();
                if (!$member->insertOne($data)
                ) {
                    $message = [];
                    foreach ($member->getMessages() as $msg) {
                        $message[] = (string)$msg;
                    }
                    throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
                }

                //社群成员录入
                CommunityGroupMember::insertOne(['gid' => $gid, 'user_id' => $to_uid, 'comm_id' => $group['comm_id']]);
                GroupJoinLog::updateOne(['user_id' => $to_uid, 'gid' => $gid, 'check_user_id' => $uid, 'modify' => time(), 'status' => self::check_status_success], 'id=' . $apply_id);
                //云信接口调用
                $res = ServerAPI::init()->addIntoGroup($group['yx_gid'], $group['user_id'], [$to_uid], 0, $user['username'] . '加入群聊', json_encode(['extend_type' => 'join_group', "username" => ''], JSON_UNESCAPED_UNICODE));
                if (!$res || $res['code'] !== 200) {
                    throw new \Exception('同意加入群聊失败:云信错误' . ($res ? $res['desc'] : ''));
                }
                //todo 给管理员发通知消息

                $this->db->commit();
                return true;
            } catch (\Exception $e) {
                Debug::log('同意加入群聊失败：' . var_export($e->getMessage(), true), 'error');
                $this->db->rollback();
                return false;
            }
        } else {
            GroupJoinLog::updateOne(['user_id' => $to_uid, 'gid' => $gid, 'check_user_id' => $uid, 'modify' => time(), 'status' => self::check_status_fail], 'id=' . $apply_id);
            //todo 给管理员发通知消息

            return true;
        }
    }

    /**
     * @param $uid
     * @param $gid
     * @param string $avatar
     * @param string $name
     * @param int $join_mode //加入群聊限制 0-不需要验证 1-需要验证 2-不允许加入
     * @param int $invite_mode //邀请人限制 0-管理员 1-所有人
     * @param int $beinvite_mode //被邀请人权限 0-需要同意 1-不需要同意
     * @param int $is_private //是否私有
     * @return bool
     */
    public function edit($uid, $gid, $avatar = '', $name = '', $join_mode = -1, $invite_mode = -1, $beinvite_mode = -1, $is_private)
    {
        $groupManager = GroupManager::init();
        $group = $groupManager->groupExists($gid, 'member_limit,user_id,yx_gid');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$operator = $groupManager->memberExists($gid, $uid, 'member_type')) {
            self::$ajax->outError(Ajax::ERROR_INVITOR_NOT_GROUP_MEMBER);
        }
        //不是群主且不是群管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != $groupManager::GROUP_MEMBER_ADMIN) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        $data = [];
        $yunxin_data = [];
        $community_group = [];
        if ($name) {
            //相同名字的社群已经存在
            if (CommunityGroup::exist("name='" . $name . "' and gid=" . $gid)) {
                self::$ajax->outError(Ajax::ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS);
            }
            $data['name'] = $name;
            $yunxin_data['tname'] = $name;
            $community_group['name'] = $name;
        }
        if ($avatar) {
            $data['avatar'] = $avatar;
            $yunxin_data['icon'] = $avatar;
        }

        if ($join_mode != -1) {
            $data['join_mode'] = $join_mode;
            $yunxin_data['joinmode'] = $join_mode;

        }
        if ($beinvite_mode != -1) {
            $data['beinvite_mode'] = $beinvite_mode;
            $yunxin_data['beinvitemode'] = $beinvite_mode;

        }
        if ($invite_mode != -1) {
            $data['invite_mode'] = $invite_mode;
            //云信不支持 不允许任何人邀请加入群聊
            if ($invite_mode != 2) {
                $yunxin_data['invitemode'] = $invite_mode;
            }
        }
        if ($is_private != -1) {
            $data['is_private'] = $is_private;
            $community_group['is_private'] = $is_private;
        }
        try {
            if ($yunxin_data) {
                $res = ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], $yunxin_data);
                if (!$res || $res['code'] !== 200) {
                    throw new \Exception('更新群聊数据:云信错误' . ($res ? $res['desc'] : ''));
                }
            }
            if ($community_group) {
                CommunityGroup::updateOne($community_group, 'gid=' . $gid);
            }
            Group::updateOne($data, 'id=' . $gid);
            return true;
        } catch (\Exception $e) {
            Debug::log('设置群聊数据失败：' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**解散群聊
     * @param $uid -解散人uid
     * @param $gid -被解散的群id
     * @return bool
     */
    public function dissolveGroup($uid, $gid)
    {
        $groupManager = GroupManager::init();
        $group = $groupManager->groupExists($gid, 'member_limit,user_id,yx_gid,comm_id,comm_owner');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群主并且也不是区长
        if ($group['user_id'] != $uid && $group['comm_owner'] != $uid) {
            self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN);
        }
        try {
            $this->db->begin();
            $this->original_mysql->begin();
            //更新群状态
            Group::updateOne(['status' => $groupManager::GROUP_STATUS_DISSOLVE], 'id=' . $gid);
            //删除社区群
            CommunityGroup::remove('gid=' . $gid);
            //删除社区群成员
            CommunityGroupMember::remove("gid=" . $gid);

            CommunityProfile::updateOne(['group_cnt' => 'group_cnt-1'], 'comm_id=' . $group['comm_id'] . " and group_cnt>0");


            //异步推送消息
            Producer::getInstance($this->di->get("config")->kafka->host)->setTopic(TopicDefine::TOPIC_COMMUNITY_GROUP_DISSOLVE)->produce(['gid' => $gid]);

            //云信接口调用
            $res = ServerAPI::init()->removeGroup($group['yx_gid'], $group['user_id']);
            if (!$res || $res['code'] !== 200) {
                throw new \Exception('删除群聊失败:云信错误' . ($res ? $res['desc'] : ''));
            }
            $this->db->commit();
            $this->original_mysql->commit();
            return true;
        } catch (\Exception $e) {
            Debug::log('删除群聊失败：' . $e->getMessage(), 'error');
            $this->db->rollback();
            $this->original_mysql->rollback();
            return false;
        }
    }

    /**群主转让
     * @param $uid -操作人uid
     * @param $to_uid -新群主
     * @param $gid -群号
     * @return bool
     */
    public function changeOwner($uid, $to_uid, $gid)
    {
        $groupManager = GroupManager::init();
        $group = $groupManager->groupExists($gid, 'member_limit,user_id,yx_gid,transfer_record,comm_owner');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群主并且不是区长
        if ($group['user_id'] != $uid && $group['comm_owner'] != $uid) {
            self::$ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN);
        }
        $uid = $group['user_id'];//避免操作者是区主的身份

        //转让的对象不是群成员
        if (!$groupManager->memberExists($gid, $to_uid)) {
            self::$ajax->outError(Ajax::ERROR_NOT_GROUP_MEMBER);
        }
        $user_info = UserInfo::findOne(["user_id=" . $to_uid, 'columns' => 'grade,is_vip']);
        // $grade = UserPointGrade::findOne(['grade = ' . $user_info['grade'], 'columns' => 'group_member_count']);


        $normal_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
        //群聊人数限制
        $group_member_limit = $normal_setting ? $normal_setting['group_member_count'] : 200;

        if ($user_info['is_vip']) {
            $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'group_member_count']);
            $group_member_limit = $vip_privileges ? $vip_privileges['group_member_count'] : $group_member_limit;
        }


        //被转让的用户 所能创建群成员的数量限制小于被转让群的最大成员限制
        if ($group_member_limit < $group['member_limit']) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        try {
            $this->db->begin();
            #转让记录#
            $transfer_record = $group['transfer_record'];
            $transfer_record = $transfer_record ? $transfer_record . "," . $uid . ":" . time() : $uid . ":" . time();
            //更换群主
            Group::updateOne(['user_id' => $to_uid, 'transfer_record' => $transfer_record], 'id=' . $gid);
            CommunityGroup::updateOne(['user_id' => $to_uid], 'gid=' . $gid);
            GroupMember::updateOne(['member_type' => $groupManager::GROUP_MEMBER_CREATOR], 'gid=' . $gid . " and user_id=" . $to_uid);
            GroupMember::updateOne(['member_type' => $groupManager::GROUP_MEMBER_NORMAL], 'gid=' . $gid . " and user_id=" . $uid);


            $res = ServerAPI::init()->changeGroupOwner($group['yx_gid'], $uid, $to_uid);
            if (!$res || $res['code'] !== 200) {
                throw new \Exception('群主转让失败:云信错误' . ($res ? $res['desc'] : ''));
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /**群主踢人
     * @param $uid -操作人uid
     * @param $to_uid -被踢人uid
     * @param $gid -群id
     * @return bool
     */
    public function kickMember($uid, $to_uid, $gid)
    {
        $groupManager = GroupManager::init();

        $group = $groupManager->groupExists($gid, 'member_limit,user_id,yx_gid,comm_id,comm_owner');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (!$operator = $groupManager->memberExists($gid, $uid, 'member_type,nick,default_nick')) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        //不是群主 也不是管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != $groupManager::GROUP_MEMBER_ADMIN) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        //踢人者是群管理员
        if ($operator['member_type'] == $groupManager::GROUP_MEMBER_ADMIN) {
            //剔除的成员里有管理员或群主
            if (GroupMember::dataCount("gid=" . $gid . " and user_id in (" . $to_uid . ") and (member_type=" . $groupManager::GROUP_MEMBER_CREATOR . " or member_type=" . $groupManager::GROUP_MEMBER_ADMIN . ")") > 0) {
                self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
            }
        } else {
            //群主 云信限制群主踢人必须先取消管理员身份 所以先找出管理员
            $admins = GroupMember::getColumn(["gid=" . $gid . " and user_id in (" . $to_uid . ") and member_type=" . $groupManager::GROUP_MEMBER_ADMIN, 'columns' => 'user_id'], 'user_id');
        }
        //操作人是区长 不用做任何处理 按照正常逻辑
        if ($uid = $group['comm_owner']) {

        } else {
            //社区管理员列表
            $community_admins = \Models\Community\CommunityManager::getColumn(['comm_id=' . $group['comm_id'], 'columns' => 'user_id'], 'user_id');
            $community_admins[] = $group['comm_owner'];
            $to_uid_arr = explode(',', $to_uid);
            //剔除的人有社区管理员或区长
            if (array_intersect($to_uid_arr, $community_admins)) {
                self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
            }

        }


        try {
            $this->db->begin();
            $this->original_mysql->begin();
            //群成员删除
            GroupMember::remove("gid=" . $gid . " and user_id in (" . $to_uid . ")");
            //社群成员删除
            CommunityGroupMember::remove("gid=" . $gid . " and user_id in (" . $to_uid . ")");
            //先移除管理员
            if (!empty($admins)) {
                $yx = ServerAPI::init()->removeGroupManager($group['yx_gid'], $uid, $admins);
                if (!$yx || $yx['code'] !== 200) {
                    throw new \Exception('移除管理员失败:' . ($yx ? $yx['desc'] : ''));
                }
            }
            $arr = explode(",", $to_uid);
            $msg = $operator['nick'] ? $operator['nick'] : $operator['default_nick'];
            foreach ($arr as $v) {
                $res = ServerAPI::init()->kickFromGroup($group['yx_gid'], $uid, $v, json_encode(['extend_type' => 'kick_group', "username" => $msg, 'uid' => $uid], JSON_UNESCAPED_UNICODE));
                if (!$res || $res['code'] !== 200) {
                    throw new \Exception('删除成员失败:云信错误,yx_gid:' . $group['yx_gid'] . ",uid:" . $uid . ',v:' . $v . ($res ? $res['desc'] : ''));
                }
            }
            $this->db->commit();
            $this->original_mysql->commit();

            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /**获取社群列表
     * @param $uid
     * @param $to_uid
     * @param $comm_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function myGroup($uid, $to_uid, $comm_id, $page = 1, $limit = 20)
    {
        $data = ['data_list' => [], 'data_count' => 0];
        $where = "user_id=" . $to_uid;
        if ($comm_id) {
            $where .= " and comm_id=" . $comm_id;
        }
        $list = CommunityGroup::getColumn([$where, 'limit' => $limit, 'offset' => ($page - 1) * $limit, 'columns' => 'gid'], 'gid');
        if ($list) {
            $list = Group::findList(['id in (' . implode(',', $list) . ')', 'columns' => 'user_id as uid,id as gid,yx_gid,name,avatar,comm_id,is_private']);
            $data['data_list'] = $list;
        }
        $data['data_count'] = CommunityGroup::count($where);
        return $data;
    }

    /**获取群成员列表
     * @param $uid -用户id
     * @param $gid -群id
     * @param int $page -第几页
     * @param int $limit -每页显示的数量
     * @return array
     */
    public function groupMember($uid, $gid, $page = 0, $limit = 20)
    {
        $groupManager = GroupManager::init();

        $res = ['data_count' => 0, 'data_list' => []];
        $group = $groupManager->groupExists($gid, 'member_limit,user_id,comm_id,comm_owner');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }


        //不是群成员
        if (!$group_member = $groupManager->memberExists($gid, $uid, 'member_type')) {
            //self::$ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
            $community_attention = CommunityAttention::findOne(['comm_id=' . $group['comm_id'] . " and user_id=" . $uid, 'columns' => 'role']);
            if (!$community_attention || $community_attention['role'] == CommunityManager::role_normal) {
                self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
            }
        }

        $params = ['gid = ' . $gid, 'columns' => 'user_id,member_type,default_nick,nick,created,is_mute,join_type,invitor', 'order' => 'member_type desc,created asc'];
        if ($page > 0) {
            $params['limit'] = $limit;
            $params['offset'] = ($page - 1) * $limit;
        }
        $members = GroupMember::findList($params);
        if ($members) {
            $uids = array_column($members, 'user_id');
            //群主或者管理员
            if ($group_member && $group_member['member_type'] != $groupManager::GROUP_MEMBER_NORMAL) {
                $uids = array_unique(array_merge($uids, array_column($members, 'invitor')));
            }
            $uids_str = implode(',', $uids);

            $personalSetting = UserPersonalSetting::getByColumnKeyList(['owner_id = ' . $uid . ' and user_id in(' . $uids_str . ')', 'columns' => 'mark,user_id'], 'user_id');
            $avatars = Users::getByColumnKeyList(['id in(' . $uids_str . ')', 'columns' => 'id,avatar,username'], 'id');
            $contact = UserContactMember::getByColumnKeyList(['owner_id = ' . $uid . ' and user_id in(' . $uids_str . ')', 'columns' => 'user_id'], 'user_id');
            $community_admins = \Models\Community\CommunityManager::getColumn(['comm_id=' . $group['comm_id'], 'columns' => 'user_id'], 'user_id');

            foreach ($members as $item) {
                $temp = $item;
                $temp['user_avatar'] = $avatars[$item['user_id']]['avatar'];
                $temp['contact_mark'] = '';
                $temp['is_contact'] = 0;
                $temp['comm_role'] = CommunityManager::role_normal;

                if ($group['comm_owner'] == $item['user_id']) {
                    $temp['comm_role'] = CommunityManager::role_owner;
                } else {
                    if ($community_admins && in_array($item['user_id'], $community_admins)) {
                        $temp['comm_role'] = CommunityManager::role_admin;
                    }
                }
                //添加了备注
                if (isset($personalSetting[$item['user_id']])) {
                    $temp['contact_mark'] = $personalSetting[$item['user_id']]['mark'];
                }
                if (isset($contact[$item['user_id']])) {
                    $temp['is_contact'] = 1;
                }
                //群主
                if ($group_member['member_type'] != $groupManager::GROUP_MEMBER_NORMAL) {
                    $temp['invitor_name'] = $avatars[$item['invitor']]['username'];
                    if (isset($personalSetting[$item['invitor']]) && $personalSetting[$item['invitor']]['mark']) {
                        $temp['invitor_name'] = $personalSetting[$item['invitor']]['mark'];
                    }
                } else {
                    $temp['invitor_name'] = '';
                }

                $res['data_list'][] = $temp;
            }
        }
        $res['data_count'] = GroupMember::dataCount('gid = ' . $gid);
        return $res;
    }

    //社群新闻消息推送
    public function pushNews($comm_id, $news_id)
    {
        $news = CommunityNews::findOne(['comm_id=' . $comm_id . " and id=" . $news_id, 'columns' => 'title,content,media']);
        if (!$news) {
            return true;
        }
        $group_ids = Group::getByColumnKeyList(['comm_id=' . $comm_id, 'columns' => 'id,user_id,yx_gid'], 'id');
        if ($group_ids) {
            foreach ($group_ids as $item) {
                (CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_NEWS,
                    [
                        'from' => $item['user_id'],
                        'news_id' => $news_id,
                        'group_id' => $item['id'],
                        'title' => $news['title'],
                        'content' => $news['content'],
                        'media' => $news['media'],
                        'yx_gid' => $item['yx_gid']
                    ]));
            }
        }
    }

    /**群成员禁言/集体禁言
     * @param $uid
     * @param $to_uid
     * @param $gid
     * @param int $type
     * @return bool
     */
    public function mute($uid, $to_uid, $gid, $type = 1)
    {
        $groupManager = GroupManager::init();
        $group = $groupManager->groupExists($gid, 'member_limit,user_id,yx_gid,is_mute,comm_id,comm_owner');
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$operator = $groupManager->memberExists($gid, $uid, 'member_type')) {
            self::$ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        //不是群主也不是群管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != $groupManager::GROUP_MEMBER_ADMIN) {
            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        try {
            //禁言、取消禁言单个用户
            if ($to_uid) {
                if ($uid == $to_uid) {
                    self::$ajax->outError(Ajax::INVALID_PARAM);
                }
                //操作的对象不是群成员
                if (!$member = $groupManager->memberExists($gid, $to_uid, 'is_mute,member_type')) {
                    self::$ajax->outError(Ajax::ERROR_HANDLE_NOT_GROUP_MEMBER);
                }
                //管理员不能禁言其他管理员和群主
                if ($operator['member_type'] == $groupManager::GROUP_MEMBER_ADMIN) {
                    if ($member['member_type'] == $groupManager::GROUP_MEMBER_ADMIN || $member['member_type'] == $groupManager::GROUP_MEMBER_CREATOR) {
                        self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
                    }
                }
                //禁言区主
                if ($group['comm_owner'] == $to_uid) {
                    self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
                } else {
                    //禁言社区管理员
                    if (\Models\Community\CommunityManager::exist('comm_id=' . $group['comm_id'] . " and user_id=" . $to_uid)) {
                        //操作者是区主
                        if ($uid == $group['comm_owner']) {
                        } else {
                            self::$ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
                        }
                    }
                }

                if ($member['is_mute'] != $type) {
                    $redis = $this->di->get("redis");
                    if ($type == 1) {
                        $redis->hSet(CacheSetting::KEY_GROUP_MEMBER_MUTE . $gid, $to_uid, 1);
                    } else {
                        $redis->hDel(CacheSetting::KEY_GROUP_MEMBER_MUTE . $gid, $to_uid);
                    }
                    $res = ServerAPI::init()->muteTlist($group['yx_gid'], $group['user_id'], $to_uid, $type);
                    if (!$res || $res['code'] !== 200) {
                        throw new \Exception('群成员禁言失败:云信错误' . ($res ? $res['desc'] : ''));
                    }
                    GroupMember::updateOne(['is_mute' => $type], 'gid=' . $gid . " and user_id=" . $to_uid);
                }
            } else {
                //全体禁言/取消禁言
                if ($group['is_mute'] != $type) {
                    $res = ServerAPI::init()->muteTlistAll($group['yx_gid'], $group['user_id'], $type == 1 ? 'true' : 'false');
                    if (!$res || $res['code'] !== 200) {
                        throw new \Exception('群全体禁言操作失败:云信错误' . ($res ? $res['desc'] : ''));
                    }
                    Group::updateOne(['is_mute' => $type], 'id=' . $gid);
                }
            }
            return true;
        } catch (\Exception $e) {
            Debug::log('禁言操作失败:' . $e->getMessage(), 'error');
            return false;
        }
    }

    //社群解散发消息
    public function sendGroupDissolveMsg($gid)
    {
        $group = Group::findOne(['id=' . $gid, 'columns' => 'name,comm_id']);
        if (!$group) {
            return;
        }
        $community = Community::findOne(['id=' . $group['comm_id'], 'columns' => 'name']);
        if (!$community) {
            return;
        }
        $members = GroupMember::getColumn(['gid=' . $gid, 'columns' => 'user_id'], 'user_id');
        foreach ($members as $m) {
            CommunityImManager::init()->initMsg(CommunityImManager::TYPE_COMMUNITY_GROUP_DISSOLVE,
                ['to_user_id' => $m, 'community_id' => $group['comm_id'], 'community_name' => $community['name'], 'group_id' => $gid, 'group_name' => $group['name']]);
        }
    }

    /**社群详情
     * @param $uid
     * @param $gid
     * @param  $yx_gid --云信gid
     * @return array|bool
     */
    public function groupInfo($uid, $gid, $yx_gid = 0)
    {
        $groupManager = GroupManager::init();
        $params = ['status = ' . $groupManager::GROUP_STATUS_NORMAL, 'columns' => 'id as gid,yx_gid,default_name,name,introduce,default_avatar,avatar,user_id as admin,created,member_limit,is_mute,invite_mode,join_mode,beinvite_mode,comm_id,comm_owner'];
        if ($gid) {
            $params[0] .= ' and id=' . $gid;
        } else {
            $params[0] .= ' and yx_gid=' . $yx_gid;
        }

        $group = Group::findOne($params);
        //数据不存在
        if (!$group) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        /* $member = GroupMember::findOne(['gid = ' . $group['gid'] . ' and user_id = ' . $uid, 'columns' => 'nick,default_nick,push,created']);
         if (!$member) {
             $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
         }*/

        $user_info = UserInfo::findOne(['user_id = ' . $group['admin'], 'columns' => 'grade,is_vip']);
        $normal_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
        //群聊人数限制
        $group_member_limit = $normal_setting ? $normal_setting['group_member_count'] : 200;
        if ($user_info['is_vip']) {
            $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'group_member_count']);
            $group_member_limit = $vip_privileges ? $vip_privileges['group_member_count'] : $group_member_limit;
        }
        $group['top_member_limit'] = $group_member_limit;


        $group['member_count'] = GroupMember::dataCount('gid =' . $group['gid']);

        $avatar = Users::findOne(['id = ' . $uid, 'columns' => 'avatar']);
        $member['user_avatar'] = $avatar['avatar'];
        $group = array_merge($group, $member);
        $announcement = GroupAnnouncement::dataCount(['gid = ' . $group['gid'] . ' and status = ' . $groupManager::GROUP_ANNOUNCEMENT_NORMAL]);
        $group['announcement'] = $announcement;

        $member = GroupMember::findOne(['gid = ' . $group['gid'] . ' and user_id = ' . $uid, 'columns' => 'member_type,default_nick,nick,is_mute,created as join_time']);

        $group['member_info'] = (object)[];
        if ($member) {
            $group['member_info'] = $member;
            $community_role = CommunityAttention::findOne(['comm_id=' . $group['comm_id'] . " and user_id=" . $uid, 'columns' => 'role']);
            if ($community_role) {
                $group['member_info']['comm_role'] = intval($community_role['role']);
            } else {
                $group['member_info']['comm_role'] = -1;
            }
        }

        return $group;
    }

}