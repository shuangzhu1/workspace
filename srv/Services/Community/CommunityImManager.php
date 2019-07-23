<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/4
 * Time: 15:58
 */

namespace Services\Community;


use Components\Yunxin\ServerAPI;
use Services\Im\ImManager;
use Services\Site\SiteKeyValManager;
use Services\User\UserStatus;
use Util\Debug;

class CommunityImManager
{
    const TYPE_COMMUNITY_GROUP_INVITE = "community_group_invite"; //邀请加入社群
    const TYPE_COMMUNITY_APPLY = "community_apply"; //社区申请
    const TYPE_COMMUNITY_APPLY_FAIL = "community_apply_fail"; //社区审核不通过
    const TYPE_COMMUNITY_APPLY_SUCCESS = "community_apply_success"; //社区审核通过
    const TYPE_COMMUNITY_GROUP_DISSOLVE = "community_group_dissolve"; //社群被解散
    const TYPE_COMMUNITY_MANAGER_REVOKE = "community_manager_revoke"; //撤销社群管理员
    const TYPE_COMMUNITY_MANAGER_ASSIGN = "community_manager_assign"; //任命社群管理员
    const TYPE_COMMUNITY_GROUP_JOIN_APPLY = "community_group_join_apply"; //申请加入社群
    const TYPE_COMMUNITY_GROUP_CREATE_APPLY = "community_group_create_apply"; //申请创建社群
    const TYPE_COMMUNITY_GROUP_CREATE_SUCCESS = "community_group_create_success"; //申请创建社群审核成功
    const TYPE_COMMUNITY_GROUP_CREATE_FAIL = "community_group_create_fail"; //申请创建社群审核失败

    const TYPE_COMMUNITY_NEWS = "comm_news"; //社区新闻


    const ACCOUNT_TYPE_COMMUNITY = 6;//社区消息

    private static $instance = null;
    private $baseUrl = "https://api.netease.im/nimserver/msg/";

    public function __construct()
    {
        $this->baseUrl = "https://api.netease.im/nimserver/msg/";
    }

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    //编译模板
    public function compileTemple(array $data, $type)
    {
        $res = "";
        $template = SiteKeyValManager::init()->getOneByKey(SiteKeyValManager::KEY_PAGE_IM_TPL, $type);
        if ($template) {
            $res = $template['val'];

            foreach ($data as $key => $val) {
                $res = str_replace('#' . $key . '#', $val, $res);
            }
        }

        return $res;
    }

    public function initMsg($type, $data, $opt = [])
    {
        $res = false;
        switch ($type) {
            case self::TYPE_COMMUNITY_GROUP_INVITE:
                //邀请加入社群
                $to_user_id = $data['to_user_id'];//发送给谁
                $uid = $data['user_id'];//用户id
                $community_id = $data['community_id'];//社区id
                $group_id = $data['group_id'];//社群id
                $community = $data['community_name'];//社区名称
                $group = $data['group_name'];//社群名称


                $user_info = UserStatus::getInstance()->getCacheUserInfo($uid, false, $to_user_id, true);
                //个人备注
                $user_name = $user_info['username'];
                $msg = self::compileTemple(array('username' => $user_name, 'community' => $community, 'group' => $group), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'uid' => $uid,
                    'avatar' => $user_info['avatar'],
                    'username' => $user_name,
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                    'gid' => $group_id,
                    'group_name' => $group,
                );
                $res = self::sendSystemMessage($uid, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_APPLY:
                //社区申请
                $to_user_id = $data['to_user_id'];//发送给谁
                $community = $data['community_name'];//社区名称


                $msg = self::compileTemple(array('community' => $community), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_name' => $community,
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                Debug::log(var_export($res, true), 'im');
                break;
            case self::TYPE_COMMUNITY_APPLY_FAIL:
                //社区申请审核失败
                $to_user_id = $data['to_user_id'];//发送给谁
                $community = $data['community_name'];//社区名称


                $msg = self::compileTemple(array('community' => $community), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_name' => $community,
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_APPLY_SUCCESS:
                //社区申请审核通过
                $to_user_id = $data['to_user_id'];//发送给谁
                $community_id = $data['community_id'];//社区id
                $community = $data['community_name'];//社区名称


                $msg = self::compileTemple(array('community' => $community), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_GROUP_DISSOLVE:
                //社群被解散
                $to_user_id = $data['to_user_id'];//发送给谁
                $community_id = $data['community_id'];//社区id
                $group_id = $data['group_id'];//社群id
                $community = $data['community_name'];//社区名称
                $group = $data['group_name'];//社群名称


                $msg = self::compileTemple(array('community' => $community, 'group' => $group), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                    'gid' => $group_id,
                    'group_name' => $group,
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_MANAGER_REVOKE:
                //撤销管理员身份
                $to_user_id = $data['to_user_id'];//发送给谁
                $community_id = $data['community_id'];//社区id
                $community = $data['community_name'];//社区名称


                $msg = self::compileTemple(array('community' => $community), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_MANAGER_ASSIGN:
                //任命管理员身份
                $to_user_id = $data['to_user_id'];//发送给谁
                $community_id = $data['community_id'];//社区id
                $community = $data['community_name'];//社区名称


                $msg = self::compileTemple(array('community' => $community), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_GROUP_JOIN_APPLY:
                //加入社群申请
                $to_user_id = $data['to_user_id'];//发送给谁
                $community_id = $data['community_id'];//社区id
                $group_id = $data['group_id'];//社群id
                $community = $data['community_name'];//社区名称
                $group = $data['group_name'];//社群名称
                $apply_id = $data['apply_id'];//申请id
                $username = $data['username'];//用户名
                $uid = $data['uid'];//用户id
                $avatar = $data['avatar'];//用户头像


                $msg = self::compileTemple(array('community' => $community, 'username' => $username, 'group' => $group_id), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'uid' => $uid,
                    'avatar' => $avatar,
                    'username' => $username,
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                    'gid' => $group_id,
                    'group_name' => $group,
                    'apply_id' => $apply_id
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_GROUP_CREATE_APPLY:
                //创建社群申请
                $to_user_id = $data['to_user_id'];//发送给谁
                $community_id = $data['comm_id'];//社区id
                $community = $data['comm_name'];//社区名称
                $group = $data['group_name'];//社群名称
                $apply_id = $data['apply_id'];//申请id
                $username = $data['username'];//申请人昵称
                $uid = $data['user_id'];//申请人id


                $msg = self::compileTemple(array('community' => $community, 'group' => $group, 'username' => $username), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                    'group_name' => $group,
                    'apply_id' => $apply_id,
                    'uid' => $uid,
                    'username' => $username
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_GROUP_CREATE_SUCCESS:
                //创建社群申请 审核通过
                $to_user_id = $data['to_user_id'];//发送给谁
                $community_id = $data['comm_id'];//社区id
                $community = $data['comm_name'];//社区名称
                $group_id = $data['group_id'];//社群id
                $group = $data['group_name'];//社群名称


                $msg = self::compileTemple(array('community' => $community, 'group' => $group), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                    'group_name' => $group,
                    'gid' => $group_id,
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_GROUP_CREATE_FAIL:
                //创建社群申请 审核失败
                $to_user_id = $data['to_user_id'];//发送给谁
                $community_id = $data['comm_id'];//社区id
                $community = $data['comm_name'];//社区名称
                $group = $data['group_name'];//社群名称
                $reason = $data['reason'];//原因


                $msg = self::compileTemple(array('community' => $community, 'group' => $group, 'reason' => $reason), $type);
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'comm_id' => $community_id,
                    'comm_name' => $community,
                    'group_name' => $group,
                );
                $res = self::sendSystemMessage(0, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_COMMUNITY_NEWS:
                //社区新闻
                $from = $data['from'];//发送消息的人用户id
                $news_id = $data['news_id'];//社区id
                $group_id = $data['group_id'];//社群id
                $title = $data['title'];//社区名称
                $content = $data['content'];//社群名称
                $media = $data['media'];//新闻封面
                $yx_gid = $data['yx_gid'];//云信群id


                $msg = "";
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'news_id' => $news_id,
                    'title' => $title,
                    'group_id' => $group_id,
                    'content' => $content,
                    'media' => $media
                );
                $res = self::sendSystemMessage($from, '', $yx_gid, $im_data, $type);
                break;
        }
        return $res;
    }

    /**
     * http 消息发送系统消息(扩展)接口
     *
     * @param $from //发送消息的人UID
     * @param $to //接收消息的人的UID
     * @param $gid //如果gid 不为空则认为是群聊
     * @param string $body //文本消息正文
     * @param string $ext //扩展类型 json字符串
     * @param int $msg_type //云信消息类型
     * @param string $push_content //云信推行笑死内容
     * @param array $option //云信推行笑死内容
     * @return bool
     */
    protected function sendSystemMessage($from, $to, $gid = "", $body = "", $ext, $msg_type = 100, $push_content = "", $option = [])
    {
        $ext_type = json_encode(['extend_type' => $ext]);
        //群系统消息
        if ($gid > 0) {
            if ($option) {
                ServerAPI::init()->sendMsg($from, 1, $gid, $msg_type, $body, $ext_type, $push_content, $option);
            } else {
                ServerAPI::init()->sendMsg($from, 1, $gid, $msg_type, $body, $ext_type, $push_content);
            }
        } else {
            $async = false; //同步发送
            $from = ImManager::ACCOUNT_COMMUNITY;
            // $account_type = self::ACCOUNT_TYPE_COMMUNITY;
            //  Debug::log('from:' . $from . 'to:' . $to . 'body:' . $new_body . 'ext_type:' . $ext_type . "pushcontent:" . $push_content, 'im');
            if ($async) {
                if ($option) {
                    $res = ServerAPI::init()->asyncSendMsg($from, 0, $to, $msg_type, $body, $ext_type, $push_content, $option);
                } else {
                    $res = ServerAPI::init()->asyncSendMsg($from, 0, $to, $msg_type, $body, $ext_type, $push_content);
                }
            } else {
                if ($option) {
                    $res = ServerAPI::init()->sendMsg($from, 0, $to, $msg_type, $body, $ext_type, $push_content, $option);
                } else {
                    $res = ServerAPI::init()->sendMsg($from, 0, $to, $msg_type, $body, $ext_type, $push_content);
                }
            }
            if ($res && $res['code'] == 200) {
            } else {
                Debug::log('send fail:' . var_export($res, true), 'im');
                return false;
            }

        }
        return true;
    }
}