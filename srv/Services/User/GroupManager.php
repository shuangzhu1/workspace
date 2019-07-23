<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/5
 * Time: 15:46
 */

namespace Services\User;


use Models\BaseModel;
use Models\Group\GroupRmHistoryMsgLog;
use Models\Statistics\StatisticsGroupWeek;
use Models\User\Message;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\Vip\VipPrivileges;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\User\UserStatus;
use Components\Yunxin\ServerAPI;
use Models\Group\Group;
use Models\Group\GroupAnnouncement;
use Models\Group\GroupMember;
use Models\Group\GroupReport;
use Models\Social\SocialReport;
use Models\User\UserContactMember;
use Models\User\UserPointGrade;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Services\Im\SysMessage;
use Services\Social\SocialManager;
use Util\Ajax;
use Util\Debug;

class GroupManager extends Plugin
{
    private static $instance = null;

    const GROUP_STATUS_LOCKED = 0;//群被封
    const GROUP_STATUS_NORMAL = 1;//群正常
    const GROUP_STATUS_DISSOLVE = 2;//群已解散

    const GROUP_ANNOUNCEMENT_NORMAL = 1;//正常
    const GROUP_ANNOUNCEMENT_DELETED = 0;//群主已删除

    const GROUP_MEMBER_NORMAL = 1;//普通群成员
    const GROUP_MEMBER_ADMIN = 2;//管理员
    const GROUP_MEMBER_CREATOR = 3;//群主


    //邀请模式
    const GROUP_INVITE_MODE_ADMIN = 0;//管理员
    const GROUP_INVITE_MODE_ALL = 1;//全部群成员
    const GROUP_INVITE_MODE_NONE = 2;//不允许任何人邀请

    //加入群聊模式
    const GROUP_JOIN_MODE_ALL = 0;//全部
    const GROUP_JOIN_MODE_AUTH = 1;//需要验证
    const GROUP_JOIN_MODE_NONE = 2;//不允许加入

    //被邀请入群模式
    const GROUP_BEINVITE_MODE_AGREE = 0;//需要同意
    const GROUP_BEINVITE_MODE_ALL = 1;//不需要同意

    //加入方式
    const GROUP_JOIN_TYPE_CREATE = 0;//创群加入
    const GROUP_JOIN_TYPE_INVITE = 1;//被人邀请
    const GROUP_JOIN_TYPE_JOIN = 2;//主动加入-扫码
    const GROUP_JOIN_TYPE_APPLY = 3;//主动申请加入
    const GROUP_JOIN_TYPE_LINK = 4;//邀请链接加入

    public $ajax = null;
    static $status = [
        self::GROUP_STATUS_LOCKED => '系统已封杀',
        self::GROUP_STATUS_NORMAL => '正常',
        self::GROUP_STATUS_DISSOLVE => '群主已解散',
    ];

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->ajax = new Ajax();
    }

    /**添加群聊
     * @param $uid
     * @param $to_uid
     * @param $avatar
     * @param $join_mode -加入群聊限制 0-不需要验证 1-需要验证 2-不允许主动加入
     * @param $invite_mode -邀请人限制 0-管理员 1-所有人
     * @return bool|int
     */
    public function addGroup($uid, $to_uid, $avatar, $join_mode = 0, $invite_mode = 1)
    {
        //  Debug::log("start:" . microtime(true), 'debug');
        $owner = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'username,grade,is_vip']);
        // $grade = UserPointGrade::findOne(['grade=' . $owner['grade'], 'columns' => 'group_member_count']);
        //已经创建的群聊个数
        $group_count = Group::dataCount("user_id=" . $uid . " and status=" . self::GROUP_STATUS_NORMAL);

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
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "创建群聊已达上限");
        }
//        if ($grade) {
//            if (substr_count($to_uid, ',') + 2 > $grade['group_member_count']) {
//                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, Ajax::getCustomMsg(Ajax::GROUP_CREATE, $grade['group_member_count']));
//            }
//        }
        if (substr_count($to_uid, ',') + 2 > $group_member_limit) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, Ajax::getCustomMsg(Ajax::GROUP_CREATE, $group_member_limit));
        }


        try {
            $this->db->begin();
            $group_data = array(
                'default_name' => '',
                'name' => "",
                "default_avatar" => $avatar,
                "avatar" => '',
                "user_id" => $uid,
                "created" => time(),
                "last_avatar_user_count" => 0,
                // "member_limit" => $grade['group_member_count'],
                "member_limit" => $group_member_limit,
                "join_mode" => $join_mode,
                "invite_mode" => $invite_mode
            );
            $group = new Group();
            if ($id = $group->insertOne($group_data)) {
                $name = $this->batchAddMember($uid, $uid . "," . $to_uid, $id, self::GROUP_JOIN_TYPE_CREATE, $uid, 1);
                if (!$name) {
                    throw  new \Exception("云信-创建群聊失败 添加成员失败");
                }
                //    $start2 = microtime(true);
                //    Debug::log("start2:" . microtime(true), 'debug');

                $yx_gid = 0;//云信gid
                $yx = ServerAPI::init()->createGroup($name, $uid, explode(',', $to_uid), $avatar, $owner['username'], $invite_mode, $join_mode);

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

                $this->db->commit();
                // $end = microtime(true);

                // Debug::log("use total time:" . ($end - $start), 'debug');

                // 重新获取数据
                return array('gid' => $id, 'group_name' => $name, "group_avatar" => $avatar, 'yx_gid' => $yx_gid, 'join_mode' => $join_mode, "invite_mode" => $invite_mode);
            } else {
                throw  new \Exception("创建群聊失败");
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log('创建群聊失败' . $e->getMessage(), 'error');
            return false;
        }


    }

    //批量添加群成员
    /**
     * @param $owner
     * @param $to_uid
     * @param $gid
     * @param int $join_type
     * @param int $invitor
     * @param int $is_create
     * @return bool|string
     */
    public function batchAddMember($owner, $to_uid, $gid, $join_type = 0, $invitor = 0, $is_create = 1)
    {
        if (!is_array($to_uid)) {
            $to_uid = array_filter(array_unique(explode(',', $to_uid)));
        }
        $name_str = ''; //成员名称集合
        $members = Users::getByColumnKeyList(['id in (' . implode(',', $to_uid) . ')', 'columns' => 'username,id'], 'id');
        $order_columns = [];//排序字段
        foreach ($members as $item) {
            $order_columns[] = array_search($item['id'], $to_uid);
        }
        $batch_data = [];
        $time = time();
        $redis = $this->di->get('redis');
        foreach ($members as $m) {
            $tmp_data = [$gid, $m['id'], $owner == $m['id'] ? self::GROUP_MEMBER_CREATOR : self::GROUP_MEMBER_NORMAL, $m['username'], $time, $join_type, $invitor];
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
            $name_str .= ',' . $m['username'];
        }

        if (GroupMember::insertBatch(['gid', 'user_id', 'member_type', 'default_nick', 'created', 'join_type', 'invitor', 'is_mute'], $batch_data)) {
            return mb_substr($name_str, 1, 20, 'utf-8');
        }
        return false;
    }

    /**添加成员
     ** @param $owner -群主
     * @param $to_uid -添加的成员
     * @param $gid -群id
     * @param $inviter -邀请者
     * @param $is_create -是否是创建群聊
     * @return bool
     */
    public function addMember($owner, $to_uid, $gid, $inviter = 0, $is_create = false)
    {

        // $to_uid = explode(',', $to_uid);
        if (!$to_uid) {
            return false;
        }
        $user_ids = GroupMember::getColumn(['user_id in (' . $to_uid . ') and gid=' . $gid, 'columns' => 'user_id'], 'user_id');
        $to_uid = explode(',', $to_uid);
        $to_uid = array_diff($to_uid, $user_ids);
        $name_str = ''; //成员名称集合
        if ($to_uid) {
            $members = Users::getByColumnKeyList(['id in (' . implode(',', $to_uid) . ')', 'columns' => 'username,id'], 'id');
            $order_columns = [];//排序字段
            foreach ($members as $item) {
                $order_columns[] = array_search($item['id'], $to_uid);
            }
            array_multisort($order_columns, SORT_ASC, $members);
            foreach ($members as $m) {
                $new_data = new GroupMember();
                $new_data->insertOne(['gid' => $gid, 'user_id' => $m['id'], 'member_type' => $owner == $m['id'] ? self::GROUP_MEMBER_CREATOR : self::GROUP_MEMBER_NORMAL, 'default_nick' => $m['username'], 'created' => time()]);
                $name_str .= ',' . $m['username'];
            }
        }
        if ($is_create) {
            return mb_substr($name_str, 1, 20, 'utf-8');
        } else {
            return true;
        }
    }

    /**扫码加入群聊
     * @param $uid -扫码人
     * @param $invitor -邀请者
     * @param $gid -群id
     * @param string $avatar -app端生成的群头像
     * @return bool
     */
    public function joinGroup($uid, $invitor = 0, $gid, $avatar = '')
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid,join_mode,invite_mode');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //邀请者不是群成员
        if (!$invitor_info = $this->memberExists($gid, $invitor, 'default_nick,nick,member_type')) {
            $this->ajax->outError(Ajax::ERROR_INVITOR_NOT_GROUP_MEMBER);
        }
        //已经是群成员
        if ($this->memberExists($gid, $uid)) {
            $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_EXIST);
        }
        //不允许加入 扫群主二维码可以
        if ($group['join_mode'] == self::GROUP_JOIN_MODE_NONE && $group['user_id'] != $invitor) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_NOT_JOIN);
        }
        Debug::log("data:" . var_export($invitor_info, true), 'debug');
        //仅允许管理员邀请
        if ($group['invite_mode'] == self::GROUP_INVITE_MODE_ADMIN) {
            if (($invitor_info['member_type'] != GroupManager::GROUP_MEMBER_CREATOR && $invitor_info['member_type'] != GroupManager::GROUP_MEMBER_ADMIN)) {
                $this->ajax->outError(Ajax::ERROR_MEMBER_NOT_JOIN);
            }
        }

        $member_count = GroupMember::dataCount('gid=' . $gid);
        //群聊成员数已达上限
        if ($group['member_limit'] <= $member_count) {
            $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_LIMIT);
        }
        try {
            $this->db->begin();
            $user = Users::findOne(['id=' . $uid, 'columns' => 'username']);
            if (!$user) {
                $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
            }

            $data = [
                'gid' => $gid,
                'user_id' => $uid,
                'yx_gid' => $group['yx_gid'],
                'member_type' => self::GROUP_MEMBER_NORMAL,
                'default_nick' => $user['username'],
                'created' => time(),
                'join_type' => self::GROUP_JOIN_TYPE_JOIN,
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

            //云信接口调用
            $res = ServerAPI::init()->addIntoGroup($group['yx_gid'], $group['user_id'], [$uid], 0, $user['username'] . '加入群聊', json_encode(['extend_type' => 'join_group', "username" => $invitor_info['nick'] ? $invitor_info['nick'] : $invitor_info['default_nick']], JSON_UNESCAPED_UNICODE));
            if (!$res || $res['code'] !== 200) {
                throw new \Exception('加入群聊失败:云信错误' . ($res ? $res['desc'] : ''));
            }
            //更新群头像
            if ($avatar) {
                $this->db->query("update `group` set default_avatar='" . $avatar . "' where id=" . $gid)->execute();
                $res2 = ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], ['icon' => $avatar]);
                if (!$res2 || $res2['code'] !== 200) {
                    throw new \Exception('云信群头像更新失败' . ($res2 ? $res2['desc'] : ''));
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Debug::log('加入群聊失败：' . var_export($e->getMessage(), true), 'error');
            $this->db->rollback();
            return false;
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
     * @return bool
     */
    public function edit($uid, $gid, $avatar = '', $name = '', $join_mode = -1, $invite_mode = -1, $beinvite_mode = -1)
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$operator = $this->memberExists($gid, $uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_INVITOR_NOT_GROUP_MEMBER);
        }
        //不是群主且不是群管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != self::GROUP_MEMBER_ADMIN) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        $data = [];
        $yunxin_data = [];
        if ($avatar) {
            $data['avatar'] = $avatar;
            $yunxin_data['icon'] = $avatar;
        }
        if ($name) {
            $data['name'] = $name;
            $yunxin_data['tname'] = $name;
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
        try {
            if ($yunxin_data) {
                $res = ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], $yunxin_data);
                if (!$res || $res['code'] !== 200) {
                    throw new \Exception('更新群聊数据:云信错误' . ($res ? $res['desc'] : ''));
                }
            }
            Group::updateOne($data, 'id=' . $gid);
            return true;
        } catch (\Exception $e) {
            Debug::log('设置群聊数据失败：' . $e->getMessage(), 'error');
            return false;
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
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid,is_mute');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$operator = $this->memberExists($gid, $uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        //不是群主也不是群管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != self::GROUP_MEMBER_ADMIN) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        try {
            //禁言、取消禁言单个用户
            if ($to_uid) {
                if ($uid == $to_uid) {
                    $this->ajax->outError(Ajax::INVALID_PARAM);
                }
                //操作的对象不是群成员
                if (!$member = $this->memberExists($gid, $to_uid, 'is_mute,member_type')) {
                    $this->ajax->outError(Ajax::ERROR_HANDLE_NOT_GROUP_MEMBER);
                }
                //管理员不能禁言其他管理员和群主
                if ($operator['member_type'] == self::GROUP_MEMBER_ADMIN) {
                    if ($member['member_type'] == self::GROUP_MEMBER_ADMIN || $member['member_type'] == self::GROUP_MEMBER_CREATOR) {
                        $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
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

    /**解散群聊
     * @param $uid -解散人uid
     * @param $gid -被解散的群id
     * @return bool
     */
    public function dissolveGroup($uid, $gid)
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群主
        if ($group['user_id'] != $uid) {
            $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN);
        }
        try {
            $this->db->begin();
            $res = $this->db->query('update `group` set status = ' . self::GROUP_STATUS_DISSOLVE . ' where id = ' . $gid)->execute();
            if (!$res) {
                throw new \Exception('解散群聊失败');
            }

            //云信接口调用
            $res = ServerAPI::init()->removeGroup($group['yx_gid'], $group['user_id']);
            if (!$res || $res['code'] !== 200) {
                throw new \Exception('删除群聊失败:云信错误' . ($res ? $res['desc'] : ''));
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Debug::log('删除群聊失败：' . $e->getMessage(), 'error');
            $this->db->rollback();
            return false;
        }


    }

    /**退出群聊
     * @param $uid -退出用户id
     * @param $gid -群id
     * @param $avatar -群头像
     * @return bool
     */
    public function leaveGroup($uid, $gid, $avatar = '')
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //群主
        if ($group['user_id'] == $uid) {
            $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_ADMIN);
        }
        //不是群成员
        if (!$this->memberExists($gid, $uid)) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
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
            if ($avatar) {
                $this->db->query("update `group` set default_avatar='" . $avatar . "' where id=" . $gid)->execute();
                $res2 = ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], ['icon' => $avatar]);
                if (!$res2 || $res2['code'] !== 200) {
                    throw new \Exception('云信群头像更新失败' . ($res2 ? $res2['desc'] : ''));
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

    /**邀请好友加入群聊
     * @param $uid -邀请人
     * @param $to_uid -被邀请人
     * @param $gid -群id
     * @param $avatar -群头像
     * @return bool
     */
    public function inviteGroup($uid, $to_uid, $gid, $avatar = '')
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid,invite_mode,join_mode');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$member = $this->memberExists($gid, $uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        //只有群主能邀请
        if ($group['invite_mode'] == self::GROUP_INVITE_MODE_NONE) {
            if ($group['user_id'] != $uid) {
                $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN);
            }
        } //仅管理员能邀请
        else if ($group['invite_mode'] == self::GROUP_INVITE_MODE_ADMIN) {
            //既不是管理员 又不是群主
            if ($group['user_id'] != $uid && $member['member_type'] != self::GROUP_MEMBER_ADMIN) {
                $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN);
            }
        }
        //不允许加入 仅群主可以
        if ($group['join_mode'] == self::GROUP_JOIN_MODE_NONE) {
            if ($group['user_id'] != $uid && $member['member_type'] != self::GROUP_MEMBER_ADMIN) {
                $this->ajax->outError(Ajax::ERROR_MEMBER_NOT_JOIN);
            }
        }
        $add_member_count = substr_count($to_uid, ',');
        //云信一次最多支持拉200个用户
        if ($add_member_count > 199) {
            $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_LIMIT_200);
        }
        $count = GroupMember::dataCount('gid = ' . $gid);
        if ($add_member_count + $count >= $group['member_limit']) {
            if ($group['member_limit'] == $count) {
                $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_LIMIT);
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, Ajax::getCustomMsg(Ajax::GROUP_INVITE, ($group['member_limit'] - $count)));
            }
        }
        try {
            $this->db->begin();
            $exist_uids = GroupMember::getColumn(['gid = ' . $gid . ' and user_id in(' . $to_uid . ')', 'columns' => 'user_id'], 'user_id');
            if ($exist_uids) {
                $to_uid = explode(',', $to_uid);
                $to_uid_arr = array_diff($to_uid, $exist_uids);
                $to_uid = $to_uid_arr ? implode(',', $to_uid_arr) : '';
            }
            if ($to_uid) {
                $name_str = $this->batchAddMember($uid, $to_uid, $gid, self::GROUP_JOIN_TYPE_INVITE, $uid, 0);
                //   $users = Users::getColumn(['id in(' . $to_uid . ')', 'columns' => 'id,username'], 'username', 'id');
                /* $name_str = ''; //成员名称集合
                 foreach ($users as $k => $item) {
                     $new_data = new GroupMember();
                     $new_data->insertOne(['gid' => $gid, 'user_id' => $k, 'member_type' => self::GROUP_MEMBER_NORMAL, 'default_nick' => $item, 'created' => time()]);
                     $name_str .= ',' . $item;
                 }*/

                $inviter = GroupMember::findOne(['gid = ' . $gid . ' and user_id = ' . $uid, 'columns' => 'nick,default_nick']);
                //云信接口调用

                //*云信限制150字符以下*/
                $msg = ($inviter['nick'] ? $inviter['nick'] : $inviter['default_nick']);
                /* $msg_lenth = mb_strlen($msg, 'utf - 8');
                 if (mb_strlen($name_str, 'utf - 8') > (150 - $msg_lenth - 6)) {
                     $msg .= '邀请' . mb_substr($name_str, 1, 144 - $msg_lenth - 2) . ' ..' . '加入群聊';
                 } else {
                     $msg .= '邀请' . mb_substr($name_str, 1) . '加入群聊';
                 }*/
                $res = ServerAPI::init()->addIntoGroup($group['yx_gid'], $group['user_id'], explode(',', $to_uid), 0, $msg, json_encode(['extend_type' => 'invite_group', "username" => $msg, 'uid' => $uid], JSON_UNESCAPED_UNICODE));
                if (!$res || $res['code'] !== 200) {
                    if ($res['code'] == 801) {
                        throw new \Exception('邀请好友加入群聊失败:群人数达到上限');
                    }
                    throw new \Exception('邀请好友加入群聊失败:云信错误' . ($res ? $res['desc'] : ''));
                }
            }
            if ($avatar) {
                $this->db->query("update `group` set default_avatar='" . $avatar . "' where id=" . $gid)->execute();
                $res2 = ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], ['icon' => $avatar]);
                if (!$res2 || $res2['code'] !== 200) {
                    throw new \Exception('云信群头像更新失败' . ($res2 ? $res2['desc'] : ''));
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

    /**群主转让
     * @param $uid -操作人uid
     * @param $to_uid -新群主
     * @param $gid -群号
     * @return bool
     */
    public function changeOwner($uid, $to_uid, $gid)
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid,transfer_record');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群主
        if ($group['user_id'] != $uid) {
            $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN);
        }
        //转让的对象不是群成员
        if (!$this->memberExists($gid, $to_uid)) {
            $this->ajax->outError(Ajax::ERROR_NOT_GROUP_MEMBER);
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
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        try {
            $this->db->begin();
            #转让记录#
            $transfer_record = $group['transfer_record'];
            $transfer_record = $transfer_record ? $transfer_record . "," . $uid . ":" . time() : $uid . ":" . time();

            $res = $this->db->query("update `group` set user_id=" . $to_uid . ',transfer_record="' . $transfer_record . '" where id = ' . $gid)->execute();
            //更换群主
            $res2 = $this->db->query("update `group_member` set member_type=" . self::GROUP_MEMBER_CREATOR . ' where gid = ' . $gid . ' and user_id = ' . $to_uid)->execute();
            $res3 = $this->db->query("update `group_member` set member_type=" . self::GROUP_MEMBER_NORMAL . ' where gid = ' . $gid . ' and user_id = ' . $uid)->execute();

            if (!$res || !$res2 || !$res3) {
                throw new \Exception("转让失败");
            }
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
     * @param $avatar -群头像
     * @return bool
     */
    public function kickMember($uid, $to_uid, $gid, $avatar = '')
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (!$operator = $this->memberExists($gid, $uid, 'member_type,nick,default_nick')) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        //不是群主 也不是管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != self::GROUP_MEMBER_ADMIN) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        //踢人者是群管理员
        if ($operator['member_type'] == self::GROUP_MEMBER_ADMIN) {
            //剔除的成员里有管理员或群主
            if (GroupMember::dataCount("gid=" . $gid . " and user_id in (" . $to_uid . ") and (member_type=" . self::GROUP_MEMBER_CREATOR . " or member_type=" . self::GROUP_MEMBER_ADMIN . ")") > 0) {
                $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
            }
        } else {
            //群主 云信限制群主踢人必须先取消管理员身份 所以先找出管理员
            $admins = GroupMember::getColumn(["gid=" . $gid . " and user_id in (" . $to_uid . ") and member_type=" . self::GROUP_MEMBER_ADMIN, 'columns' => 'user_id'], 'user_id');
        }

        try {

            $this->db->begin();
            $res = $this->db->execute("delete from  `group_member`  where gid=" . $gid . ' and user_id in(' . $to_uid . ')');
            if (!$res) {
                throw new \Exception("删除失败");
            }
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
            //更新群头像
            if ($avatar) {
                $this->db->query("update `group` set default_avatar='" . $avatar . "' where id=" . $gid)->execute();
                $res2 = ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], ['icon' => $avatar]);
                if (!$res2 || $res2['code'] !== 200) {
                    throw new \Exception('云信群头像更新失败' . ($res2 ? $res2['desc'] : ''));
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /**修改群名片
     * @param $uid -操作人
     * @param $to_uid -被修改人
     * @param $gid -群id
     * @param $nick -名片
     * @return bool
     */
    public function updateNick($uid, $to_uid, $gid, $nick)
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //操作的对象不是群成员
        if (!$member = $this->memberExists($gid, $to_uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_HANDLE_NOT_GROUP_MEMBER);
        }
        //群主改成员 名片
        if ($uid != $to_uid) {
            if (!$operator = $this->memberExists($gid, $uid, 'member_type')) {
                $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
            }
            //操作者是群管理员
            if ($operator['member_type'] == self::GROUP_MEMBER_ADMIN) {
                //修改管理员或者群主 权限不足
                if ($member['member_type'] == self::GROUP_MEMBER_ADMIN || $member['member_type'] == self::GROUP_MEMBER_CREATOR) {
                    $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
                }
            } //操作者是普通成员
            elseif ($operator['member_type'] == self::GROUP_MEMBER_NORMAL) {
                $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
            } else {
                //群主
            }

        }

        try {
            $this->db->begin();
            //$res = $this->db->query("update `group_member` set nick='" . $nick . "'  where gid=" . $gid . ' and user_id = ' . $to_uid);
            $res = GroupMember::updateOne(['nick' => $nick], ['gid' => $gid, 'user_id' => $to_uid]);
            if (!$res) {
                throw new \Exception("修改失败");
            }
            $res = ServerAPI::init()->updateGroupNick($group['yx_gid'], $group['user_id'], $to_uid, $nick);
            if (!$res || $res['code'] !== 200) {
                throw new \Exception('修改成员昵称失败:云信错误' . ($res ? $res['desc'] : ''));
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /** 发布/修改群公告
     * @param $uid -发布人
     * @param $gid -群id
     * @param $content -公告内容
     * @return bool
     */
    public function setAnnouncement($uid, $gid, $content, $an_id)
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid,if (name <> "",name,default_name) as name');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$operator = $this->memberExists($gid, $uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        //不是群主也不是群管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != self::GROUP_MEMBER_ADMIN) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        try {
            // $this->db->begin();
            //编辑群公告
            if ($an_id) {
                $announcement = GroupAnnouncement::findOne('id = ' . $an_id . ' and gid = ' . $gid . ' and status = ' . self::GROUP_ANNOUNCEMENT_NORMAL);
                if (!$announcement) {
                    Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
                }
                $data = ["content" => $content, "user_id" => $uid, "modify" => time()];
                $res = GroupAnnouncement::updateOne($data, ['id' => $an_id]);
                $modify_time = $data['modify'];
            } //添加群公告
            else {
                $announcement = new GroupAnnouncement();
                $data = ["content" => $content, "user_id" => $uid, "created" => time(), 'modify' => 0, 'gid' => $gid];
                $res = $announcement->insertOne($data);
                $an_id = $res;
                $modify_time = $data['created'];
            }
            if (!$res) {
                throw new \Exception("操作失败");
            }
            /*  $res = ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], ['announcement' => $content]);
              if (!$res || $res['code'] !== 200) {
                  throw new \Exception('发布群公告:云信错误' . ($res ? $res['desc'] : ''));
              }*/
            //  $this->db->commit();
            //发送系统通知
            $member = GroupMember::findOne(['gid = ' . $gid . ' and user_id = ' . $uid, 'columns' => 'if (nick = "",default_nick,nick) as nick']);
            $member_info = UserStatus::getInstance()->getCacheUserInfo($uid);
            $im_data = [
                'gid' => $gid,
                'yx_gid' => $group['yx_gid'],
                'an_id' => $an_id,
                'group_name' => $group['name'],
                'modify_time' => $modify_time,
                'username' => $member['nick'],
                'avatar' => $member_info['avatar'],
                'content' => $data['content']
            ];
            SysMessage::init()->initMsg(SysMessage::TYPE_GROUP_ANNOUNCEMENT, $im_data);
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /**删除群公告
     * @param $gid
     * @param $uid
     * @param $an_ids
     * @return bool
     */
    public function removeAnnouncementList($uid, $gid, $an_ids)
    {
        $group = $this->groupExists($gid, 'member_limit,user_id,yx_gid');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (!$member = $this->memberExists($gid, $uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_HANDLE_NOT_GROUP_MEMBER);
        }
        //不是群主也不是群管理员
        if ($group['user_id'] != $uid && $member['member_type'] != self::GROUP_MEMBER_ADMIN) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        $an_ids = array_filter(explode(',', $an_ids));
        if ($an_ids) {
            $res = $this->original_mysql->execute("update group_announcement set modify=" . time() . ", status=" . self::GROUP_ANNOUNCEMENT_DELETED . ' where gid = ' . $gid . ' and id in (' . implode(', ', $an_ids) . ')');
            if ($res) {
                return true;
            }
            return false;
        }
    }

    /**获取群公告列表
     * @param $gid
     * @return \Phalcon\Mvc\ResultsetInterface
     */
    public function announcementList($gid)
    {
        $list = $this->original_mysql->query("select id as an_id,content,created,modify,if(modify=0,created,modify) as order_time from group_announcement where " . "gid=" . $gid . " and status=" . self::GROUP_ANNOUNCEMENT_NORMAL . " order by order_time desc")->fetchAll(\PDO::FETCH_ASSOC);
        //  GroupAnnouncement::findList(['gid = ' . $gid . ' and status = ' . self::GROUP_ANNOUNCEMENT_NORMAL, 'columns' => 'id as an_id,content,created,modify,if (modify = 0,created,modify) as order_time', 'order' => 'order_time desc']);
        if ($list) {
            foreach ($list as &$item) {
                unset($item['order_time']);
            }
        }
        return $list;
    }

    /**群公告详情
     * @param $gid
     * @param $an_id
     * @return string|static
     */
    public function announcementDetail($gid, $an_id)
    {
        $detail = GroupAnnouncement::findOne(['gid = ' . $gid . ' and id = ' . $an_id . ' and status = ' . self::GROUP_ANNOUNCEMENT_NORMAL, 'columns' => 'id as an_id,content,created,user_id,modify']);
        if ($detail) {
            $group_member = GroupMember::findOne(['gid = ' . $gid . ' and user_id = ' . $detail['user_id'], 'columns' => 'user_id,if (nick <> "",nick,default_nick) as username']);
            $user_info = UserStatus::getInstance()->getCacheUserInfo($detail['user_id']);
            $detail['username'] = $group_member && $group_member['username'] ? $group_member['username'] : $user_info['username'];
            $detail['avatar'] = $user_info['avatar'];
            $detail['uid'] = $detail['user_id'];
            $group = Group::findOne(['id = ' . $gid, 'columns' => 'user_id']);
            $detail['owner_uid'] = $group['user_id'];
        }
        return $detail ? $detail : '';
    }

    /**
     * 获取群组列表
     * @param $uid -用户id
     * @return array
     */
    public function getGroupList($uid)
    {
        $res = ['data_count' => 0, 'data_list' => []];
        $where = 'user_id = ' . $uid;

        // 查找所有群
        $gids_data = GroupMember::getByColumnKeyList([$where, 'columns' => 'gid,created', 'order' => 'created desc'], 'gid');
        if (!$gids_data) {
            return $res;
        }
        $all_gids = array_column($gids_data, 'gid');
        $join_time = array_column($gids_data, 'created', 'gid');
        $order_data = []; //排序字段
        $groups = Group::findList(['id in(' . implode(', ', $all_gids) . ') and status = 1', 'columns' => 'yx_gid,id,name,default_name,introduce,avatar,default_avatar,user_id,created,is_mute,invite_mode,join_mode,beinvite_mode,comm_id']);
        if ($groups) {
            $gids = array_column($groups, 'id');

            //   $last_group_message = Message::getColumn(['gid in(' . implode(', ', $gids) . ') and gid <> 0', 'columns' => 'gid,max(send_time) as send_time', 'order' => 'send_time desc', 'group' => 'gid'], 'gid');
            $group_member_count = GroupMember::getByColumnKeyList(['gid in(' . implode(', ', $gids) . ')', 'columns' => 'count(*) as member_count,gid', 'group' => 'gid'], 'gid');
            //   $last_group_message_count = count($last_group_message);
            $redis = $this->di->get("redis");

            foreach ($groups as $item) {
                //   $k = array_search($item['id'], $last_group_message);
                $k = $redis->hGet(CacheSetting::KEY_GROUP_ACTIVE, $item['id']);
                $temp['gid'] = $item['id'];
                $temp['yx_gid'] = $item['yx_gid'];
                $temp['name'] = $item['name'];
                $temp['default_name'] = $item['default_name'];
                $temp['introduce'] = $item['introduce'];
                $temp['avatar'] = $item['avatar'];
                $temp['default_avatar'] = $item['default_avatar'];
                $temp['admin'] = $item['user_id'];
                $temp['created'] = $item['created'];
                $temp['is_mute'] = $item['is_mute'];
                $temp['invite_mode'] = $item['invite_mode'];
                $temp['comm_id'] = $item['comm_id'];
                $temp['join_mode'] = $item['join_mode'];
                $temp['join_time'] = $join_time[$item['id']];
                $temp['member_count'] = $group_member_count[$item['id']]['member_count'];
                $res['data_count'] += 1;
                $res['data_list'][] = $temp;
                //$order_data[] = $join_time[$item['id']];
                if ($k) {
                    $order_data[] = $k;
                } else {
                    $order_data[] = $join_time[$item['id']];
                }
            }
            array_multisort($order_data, SORT_DESC, $res['data_list']);
        }
        return $res;
    }

    /**单个群成员信息
     * @param $uid
     * @param $to_uid
     * @param $gid
     * @return mixed
     */
    public function singleMember($uid, $to_uid, $gid)
    {
        $group = $this->groupExists($gid, 'user_id');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$this->memberExists($gid, $uid)) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        $params = ['gid = ' . $gid . " and user_id=" . $to_uid, 'columns' => 'user_id  as uid,member_type,default_nick,nick,created,is_mute,join_type,invitor'];
        $res = GroupMember::findOne($params);
        //数据不存在
        if (!$res) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $contact = UserPersonalSetting::findOne(['owner_id = ' . $uid . ' and user_id=' . $to_uid, 'columns' => 'mark,user_id']);
        $avatar = Users::findOne(['id=' . $to_uid, 'columns' => 'id,avatar,username']);

        $res['avatar'] = $avatar['avatar'];
        $res['contact_mark'] = '';
        //添加了备注
        if (isset($contact) && $contact['mark']) {
            $temp['contact_mark'] = $contact['mark'];
        }
        //群主
        if ($uid == $group['user_id']) {
            $avatar = Users::findOne(['id=' . $res['invitor'], 'columns' => 'avatar,username']);
            $contact = UserPersonalSetting::findOne(['owner_id = ' . $uid . ' and user_id=' . $res['invitor'], 'columns' => 'mark']);
            $res['invitor_name'] = $avatar['username'];
            if ($contact) {
                $res['invitor_name'] = $contact['mark'];
            }
        } else {
            $res['invitor_name'] = '';
        }
        return $res;
        // $this->ajax->outRight($res);
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
        $res = ['data_count' => 0, 'data_list' => []];
        $group = $this->groupExists($gid, 'member_limit,user_id');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$group_member = $this->memberExists($gid, $uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
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
            if ($group_member['member_type'] != self::GROUP_MEMBER_NORMAL) {
                $uids = array_unique(array_merge($uids, array_column($members, 'invitor')));
            }
            $uids_str = implode(',', $uids);

            $contact = UserPersonalSetting::getByColumnKeyList(['owner_id = ' . $uid . ' and user_id in(' . $uids_str . ')', 'columns' => 'mark,user_id'], 'user_id');
            $avatars = Users::getByColumnKeyList(['id in(' . $uids_str . ')', 'columns' => 'id,avatar,username'], 'id');
            foreach ($members as $item) {
                $temp = $item;
                $temp['user_avatar'] = $avatars[$item['user_id']]['avatar'];
                $temp['contact_mark'] = '';
                //添加了备注
                if (isset($contact[$item['user_id']])) {
                    $temp['contact_mark'] = $contact[$item['user_id']]['mark'];
                }
                //群主
                if ($group_member['member_type'] != self::GROUP_MEMBER_NORMAL) {
                    $temp['invitor_name'] = $avatars[$item['invitor']]['username'];
                    if (isset($contact[$item['invitor']]) && $contact[$item['invitor']]['mark']) {
                        $temp['invitor_name'] = $contact[$item['invitor']]['mark'];
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

    /**群详情
     * @param $uid
     * @param $gid
     * @param  $yx_gid --云信gid
     * @return array|bool
     */
    public function groupInfo($uid, $gid, $yx_gid = 0)
    {
        $params = ['status = ' . self::GROUP_STATUS_NORMAL, 'columns' => 'id as gid,yx_gid,default_name,name,introduce,default_avatar,avatar,user_id as admin,created,member_limit,is_mute,invite_mode,join_mode,beinvite_mode,comm_id'];
        if ($gid) {
            $params[0] .= ' and id=' . $gid;
        } else {
            $params[0] .= ' and yx_gid=' . $yx_gid;
        }

        $group = Group::findOne($params);
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
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
        $announcement = GroupAnnouncement::dataCount(['gid = ' . $group['gid'] . ' and status = ' . self::GROUP_ANNOUNCEMENT_NORMAL]);
        $group['announcement'] = $announcement;

        $member = GroupMember::findOne(['gid = ' . $group['gid'] . ' and user_id = ' . $uid, 'columns' => 'member_type,default_nick,nick,is_mute,created as join_time']);

        $group['member_info'] = (object)[];
        if ($member) {
            $group['member_info'] = $member;
        }

        return $group;
    }

    /**编辑群名称
     * @param $uid -编辑人用户id
     * @param $gid -群id
     * @param $name -q群名称
     * @return bool
     */
    public function editGroupName($uid, $gid, $name)
    {
        $group = $this->groupExists($gid, 'user_id,yx_gid');
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$operator = $this->memberExists($gid, $uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        //不是群主也不是群管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != self::GROUP_MEMBER_ADMIN) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        try {
            $this->db->begin();
            $res = $this->db->query('update `group` set  `name` = "' . $name . '" , `modify` = ' . time() . " where id=" . $gid)->execute();
            if (!$res) {
                throw new \Exception('更新群名称失败');
            }
            //云信接口
            $yx = ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], ['tname' => $name]);
            if (!$yx || $yx['code'] !== 200) {
                throw new \Exception('更新群名称:云信错误' . ($yx ? $yx['desc'] : ''));
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log($e->getMessage(), 'error');
            return false;
        }

    }

    /**举报
     * @param $uid -举报人id
     * @param $to_uid -被举报人id
     * @param $gid -群id
     * @param $reason_id -原因id
     * @param $images -证据
     * @return bool
     */
    public function report($uid, $to_uid, $gid, $reason_id, $images)
    {
        /*已提交举报申请 后台还没审核*/
        $data = SocialReport::findOne(['reporter = ' . $uid . ' and user_id = ' . $to_uid . ' and type = "group" and item_id = ' . $gid . ' and status = 0', 'columns' => 'id']);
        if ($data) {
            $this->ajax->outError(Ajax::ERROR_REPORT_HAS_SENT);
        }
        $reason = SocialManager::init()->getReportReason(SocialManager::REPORT_REASON_TYPE_GROUP, $reason_id);
        $report = new GroupReport();
        $data = [
            'user_id' => $to_uid,
            'gid' => $gid,
            'reporter' => $uid,
            'type' => $to_uid ? 1 : 0,//0-群 1-群成员
            'created' => time(),
            'reason_id' => $reason ? $reason['id'] : 0,
            'reason_content' => $reason ? $reason['content'] : 0,
            'imgs' => $images,
        ];
        if (!$report->insertOne($data)) {
            return false;
        }

        //todo 发im消息
        return true;
    }


    /**群是否存在/群部分数据
     * @param $gid
     * @param string $columns
     * @return array|bool
     */
    public function groupExists($gid, $columns = '')
    {
        $params = ['id = ' . $gid . ' and status = ' . self::GROUP_STATUS_NORMAL];
        if ($columns) {
            if (strpos('member_limit', $columns) !== false && strpos('user_id', $columns) === false)//获取群主id
                $columns .= ',user_id';
            $params['columns'] = $columns;
        }
        $res = Group::findOne($params);
        /*if( $res )
        {
            if( $columns )
            {
                //获取群主等级
                $grade = Users::findFirst(['id = ' . $res->user_id,'columns' => 'grade'])->grade;
                $member_limit = UserPointGrade::findFirst('grade = ' . $grade)->group_member_count;
                $res->member_limit = $member_limit;//替换群主建群人数上限为实时数据
                return $res->toArray();
            }else
            {
                return true;
            }
        }else
        {
            return false;
        }*/
        if ($columns) {
            //获取群主等级
            /* $grade = Users::findOne(['id = ' . $res['user_id'], 'columns' => 'grade']);
             $member_limit = UserPointGrade::findOne(['grade = ' . $grade['grade'], 'columns' => 'group_member_count']);
             $res['member_limit'] = $member_limit['group_member_count'];//替换群主建群人数上限为实时数据*/
            return $res ? $res : false;
        } else {
            return $res ? true : false;
        }
    }

    /**群成员是否存在
     * @param $gid
     * @param $uid
     * @param $columns
     * @return bool
     */
    public function memberExists($gid, $uid, $columns = '')
    {
        $res = GroupMember::findOne(['gid = ' . $gid . ' and user_id = ' . $uid, 'columns' => $columns ? $columns : 'id'], false, true);
        return $res ? ($columns ? $res : true) : false;
    }

    /**群升级
     * @param $gid
     * @param $uid
     * @return bool
     */
    public function upgrade($gid, $uid)
    {
        $group = Group::findOne(["id=" . $gid, 'columns' => 'member_limit,user_id']);
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //不是群成员
        if (!$operator = $this->memberExists($gid, $uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        //不是群主也不是群管理员
        if ($group['user_id'] != $uid && $operator['member_type'] != self::GROUP_MEMBER_ADMIN) {
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH);
        }
        //获取群主等级
        $user_info = UserInfo::findOne(['user_id = ' . $uid, 'columns' => 'grade,is_vip']);
        // $member_limit = UserPointGrade::findOne(['grade = ' . $grade['grade'], 'columns' => 'group_member_count']);

        $normal_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
        //群聊人数限制
        $group_member_limit = $normal_setting ? $normal_setting['group_member_count'] : 200;

        if ($user_info['is_vip']) {
            $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'group_member_count']);
            $group_member_limit = $vip_privileges ? $vip_privileges['group_member_count'] : $group_member_limit;
        }

        if ($group_member_limit <= $group['member_limit']) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "群已达最高级别,你目前只能创建" . $group_member_limit . "人群");
        }
        if (Group::updateOne(['member_limit' => intval($group_member_limit), 'modify' => time()], ['id' => $gid])) {
            return true;
        }
        return false;
    }

    /**添加管理员
     * @param $uid
     * @param $to_uid
     * @param $gid
     * @return bool
     */
    public function addManager($uid, $to_uid, $gid)
    {
        $group = Group::findOne(["id=" . $gid, 'columns' => 'user_id,yx_gid']);
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS, "群聊不存在");
        }
        if ($uid != $group['user_id']) {
            $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN, "你不是群主");
        }
        if (!$member = $this->memberExists($gid, $to_uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_HANDLE_NOT_GROUP_MEMBER, "对方不是群成员");
        }
        //已经是群管理员
        if ($member['member_type'] == self::GROUP_MEMBER_ADMIN) {
            return true;
        }
        try {
            //云信接口
            $yx = ServerAPI::init()->addGroupManager($group['yx_gid'], $uid, [$to_uid]);
            if (!$yx || $yx['code'] !== 200) {
                throw new \Exception('添加管理员失败:' . ($yx ? $yx['desc'] : ''));
            }
            GroupMember::updateOne(['member_type' => self::GROUP_MEMBER_ADMIN], 'gid=' . $gid . " and user_id=" . $to_uid);
            return true;
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /**移除管理员
     * @param $uid
     * @param $to_uid
     * @param $gid
     * @return bool
     */
    public function removeManager($uid, $to_uid, $gid)
    {
        $group = Group::findOne(["id=" . $gid, 'columns' => 'user_id,yx_gid']);
        //数据不存在
        if (!$group) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS, "群聊不存在");
        }
        if ($uid != $group['user_id']) {
            $this->ajax->outError(Ajax::ERROR_GROUP_MEMBER_NOT_ADMIN, "你不是群主");
        }

        if (!$member = $this->memberExists($gid, $to_uid, 'member_type')) {
            $this->ajax->outError(Ajax::ERROR_HANDLE_NOT_GROUP_MEMBER, "对方不是群成员");
        }
        //还不是群管理员
        if ($member['member_type'] != self::GROUP_MEMBER_ADMIN) {
            return true;
        }
        try {
            //云信接口
            $yx = ServerAPI::init()->removeGroupManager($group['yx_gid'], $uid, [$to_uid]);
            if (!$yx || $yx['code'] !== 200) {
                throw new \Exception('移除管理员失败:' . ($yx ? $yx['desc'] : ''));
            }
            GroupMember::updateOne(['member_type' => self::GROUP_MEMBER_NORMAL], 'gid=' . $gid . " and user_id=" . $to_uid);
            return true;
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 删除群聊天记录
     * @param $uid
     * @param $gid
     * @return bool
     */
    public function rmHistoryMsg($uid, $gid)
    {
        $allowUserType = [
            self::GROUP_MEMBER_ADMIN,
            self::GROUP_MEMBER_CREATOR
        ];
        if (!$group = Group::findOne(['id=' . $gid, 'columns' => 'user_id,yx_gid'])) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS, '群不存在');

        }
        $user = GroupMember::findOne(['gid = ' . $gid . ' and user_id = ' . $uid, 'columns' => 'id,member_type,yx_gid']);//只有群或管理员才能操作
        if (!$user || !in_array($user['member_type'], $allowUserType))
            $this->ajax->outError(Ajax::ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH, '无操作权限');

        try {
            $res = SysMessage::init()->initMsg(SysMessage::TYPE_GROUP_RM_HISTORY_MSG, ['gid' => $gid, 'yx_gid' => $group['yx_gid']]);
            if (!$res) {
                throw new \Exception('推送删除记录消息失败:');
            }
            //云信更新自定义字段
            ServerAPI::init()->updateGroup($group['yx_gid'], $group['user_id'], ['custom' => json_encode(["last_clear_time" => time()])]);

            GroupRmHistoryMsgLog::insertOne(['uid' => $uid, 'gid' => $gid, 'created' => time()]);

            return true;
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            return false;
        }

    }
}
