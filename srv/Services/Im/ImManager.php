<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/8
 * Time: 10:06
 */

namespace Services\Im;


use Models\Group\GroupMember;
use Models\User\UserPersonalSetting;
use Services\User\UserStatus;
use Components\Yunxin\ServerAPI;
use Models\Group\Group;
use Models\System\SystemMessage;
use Models\User\UserContactMember;
use Models\User\Users;
use Services\Discuss\DiscussManager;
use Services\Site\SiteKeyValManager;
use Services\Social\SocialManager;
use Services\User\GroupManager;
use Util\Debug;
use Util\FilterUtil;

class ImManager
{
    const MSG_TYPE_TEXT = "chat"; //文本消息
    const MSG_TYPE_AUDIO = "audio"; //音频消息
    const MSG_TYPE_PIC = "picture"; //图片消息

    //云信固定账号

    const ACCOUNT_SYSTEM = 13;//系统消息  //token:c2d50ee8abd4ce6b16ce04a70b556311
    const ACCOUNT_DYNAMICS = 14;//动态通知
    const ACCOUNT_NEW_FRIEND = 15;//新的朋友
    const ACCOUNT_WALLET = 16;//用户钱包
    const ACCOUNT_HOT_NEWS = 17;//热点资讯
    const ACCOUNT_PACKAGE = 18;//红包君
    const ACCOUNT_COMMUNITY = 19;//社区通知 //token:c3c360809594299060de9406711b76ff


    //系统消息类型
    const ACCOUNT_TYPE_SYSTEM = 3;//系统消息
    const ACCOUNT_TYPE_DYNAMICS = 4;//动态通知
    const ACCOUNT_TYPE_NEW_FRIEND = 5;//新的朋友

    // 系统消息

    const TYPE_REGISTER = "register"; //注册
    const TYPE_REPORT_FROM = "report_from"; //举报属实->举报人
    const TYPE_REPORT_TO = "report_to"; //举报属实->被举报人
    const TYPE_REPORT_FAIL = "report_fail"; //举报未通过->举报人

    const TYPE_AUTH_SUCCESS = "auth_success"; //认证通过
    const TYPE_AUTH_FAIL = "auth_fail"; //认证失败
    const TYPE_USER = "user"; //用户名片
    const TYPE_INVITE_UPLOAD_PHOTOS = "invite_upload_photos"; //邀请上传照片墙
    const TYPE_WELCOME_BACK = "welcome_back";//欢迎再次使用恐龙谷
    const TYPE_PAIDQA_ANSWER = "paidqa_answer";//视频问答--问题被回答通知
    const TYPE_PAIDQA_NEW_QUESTION = "new_question";//视频问答--新问题  to恐龙君
    const TYPE_NEW_RED_BAG = "newredbag";//新红包
    const TYPE_PAIDQA_CHAT_NEW_QUESTION = "chat_new_question";//视频问答--新问题推送聊天消息
    const TYPE_PAIDQA_CHAT_ANSWER = "chat_answer";//视频问答--问题被回答推送聊天消息

    //app定义
    const TYPE_SYSTEM_GIF = "system_klgGif";//系统自定义gif
    const TYPE_DYNAMIC = "dynamic";//动态分享
    const TYPE_RENT = "rent";//租人业务
    const TYPE_RENT_CHAT = "orderChatMesssage";//租人业务订单消息
    const TYPE_THIRD_SHARE_LINK = "thirdShareLink";//第三方分享
    const TYPE_THIRD_LOCATION_IMAGE = "shareTypeLocationImage";//第三方分享
    const TYPE_THIRD_IMAGE = "shareTypeImage";//第三方分享
    const TYPE_ACTIVITY_SHARE = "ActivityShare";//活动分享
    const TYPE_VIDEO = "video";//视频


    const TYPE_SHOP_SHIELD = "shop_shield";//店铺被封
    const TYPE_SHOP_CHECK_SUCCESS = "shop_check_success";//店铺审核通过
    const TYPE_SHOP_CHECK_FAIL = "shop_check_fail";//店铺审核失败
    const TYPE_SHOP_DOWN = "shop_down";//店铺被下架
    const TYPE_GOOD_SHIELD = "goods_shield";//商品被平台删除
    const TYPE_AGENT_CHECK_SUCCESS = "agent_check_success";//合伙人审核通过
    const TYPE_AGENT_CHECK_FAIL = "agent_check_fail";//合伙人审核失败

    //vip相关
    const TYPE_VIP_DEADLINE_SOON = "vip_deadline_soon";//vip即将到期
    const TYPE_VIP_DEADLINE_HAS_ARRIVED = "vip_deadline_arrived";//vip已过期


    //动态
    const TYPE_COMMENT = "comment"; //评论
    const TYPE_MENTION = "mention"; //@功能
    const TYPE_LIKE = "like"; //赞
    const TYPE_REPLY = "reply"; //回复
    const TYPE_FORWARD = "forward"; //站内转发/站内分享
    const TYPE_REWARD = "discuss_reward"; //打赏动态
    const TYPE_REWARD_VIDEO = "video_reward"; //打赏视频

    const TYPE_DISCUSS_RECOMMEND = "discuss_recommend"; //动态推荐

    //新朋友
    const TYPE_ATTENTION = "attention"; //新增关注
    const TYPE_ADD_CONTACT = "add_contact"; //请求添加为联系人

    //群
    const TYPE_JOIN_GROUP = "join_group"; //加入群/公开群
    const TYPE_OUT_GROUP = "out_group"; //退出群/公开群
    const TYPE_INVITE_GROUP = "invite_group"; //邀请加入群/公开群
    const TYPE_UPDATE_GROUP_NAME = "update_group_name"; //修改群/公开群名称
    const TYPE_KICK_GROUP = "kick_group"; //被踢出群

    // 分享转发类型
    const TYPE_GROUP_TRANSFER = 'transfer'; //  分享转发
    const TRANSFER_TYPE_GROUP = 'group'; // 群名片
    const TRANSFER_TYPE_USER = 'user'; // 个人名片
    const TRANSFER_TYPE_DISCUSS = 'discuss'; // 动态
    const TRANSFER_TYPE_NEWS = 'news'; // 资讯
    const TRANSFER_TYPE_ACTIVITY = 'activity'; // 活动
    const TRANSFER_TYPE_LOCATION = 'location'; // 定位

    //云信消息类型
    const YUNXIN_MSG_TYPE_TEXT = 0;//表示文本消息,
    const YUNXIN_MSG_TYPE_PIC = 1;//图片,
    const YUNXIN_MSG_TYPE_AUDIO = 2;//语音,
    const YUNXIN_MSG_TYPE_VIDEO = 3;//视频,
    const YUNXIN_MSG_TYPE_LOCATION = 4;//位置,
    const YUNXIN_MSG_TYPE_FILE = 6;//文件,
    const YUNXIN_MSG_TYPE_CUSTOM = 100;//自定义

    //普通消息

    const TYPE_GIFT = "gift"; //送礼物
    const TYPE_SHOW_CHAMPION = 'show_champion';//秀场冠军

    //其他
    const TYPE_OTHER = "other"; //其他

    // 系统消息
    public static $_system_type_name = [
        self::TYPE_REGISTER => '注册成功',
        self::TYPE_AUTH_SUCCESS => '认证通过',
        self::TYPE_AUTH_FAIL => '认证失败',
        self::TYPE_USER => '用户名片',
        self::TYPE_INVITE_UPLOAD_PHOTOS => "邀请上传照片墙",
        self::TYPE_DISCUSS_RECOMMEND => "动态推荐",
        self::TYPE_SHOW_CHAMPION => "秀场冠军",

        self::TYPE_WELCOME_BACK => "欢迎再次使用恐龙谷",
        self::TYPE_PAIDQA_NEW_QUESTION => "新问题",
        self::TYPE_PAIDQA_ANSWER => "问题被回答",
        self::TYPE_REPORT_FROM => "举报通知",
        self::TYPE_SHOP_SHIELD => "店铺被封",
        self::TYPE_SHOP_CHECK_SUCCESS => "店铺通过审核",
        self::TYPE_SHOP_CHECK_FAIL => "店铺审核失败",
        self::TYPE_AGENT_CHECK_SUCCESS => "合伙人审核通过",
        self::TYPE_AGENT_CHECK_FAIL => "合伙人审核失败",
        self::TYPE_SHOP_DOWN => "店铺被下架",
        self::TYPE_VIP_DEADLINE_SOON => "vip即将到期",
        self::TYPE_VIP_DEADLINE_HAS_ARRIVED => "vip已经过期",
        self::TYPE_OTHER => "其他",
    ];

    // 动态消息
    public static $_social_type_name = [
        self::TYPE_COMMENT => "评论",
        self::TYPE_MENTION => "@功能",
        self::TYPE_LIKE => "赞",
        self::TYPE_REPLY => "回复",
        self::TYPE_FORWARD => "转发",
        //  self::TYPE_REPORT_FROM => "举报属实->举报人",
        //  self::TYPE_REPORT_TO => "举报属实->被举报人",
        //  self::TYPE_REPORT_FAIL => "举报未通过->举报人",
        self::TYPE_REWARD => "动态-打赏",
        self::TYPE_REWARD_VIDEO => "视频-打赏",

    ];
    // 新朋友
    public static $_friend_type_name = [
        self::TYPE_ATTENTION => "新朋友",
        self::TYPE_ADD_CONTACT => "请求添加为联系人",
        self::TYPE_ATTENTION => "新增关注",
    ];
    // 普通消息
    public static $_normal_type_name = [
        self::TYPE_GIFT => "礼物",
    ];

    // 群内动态消息
    public static $_group_extend_type_name = [
        self::TYPE_JOIN_GROUP => "join_group", //加入群/公开群
        self::TYPE_OUT_GROUP => "out_group", //退出群/公开群
        self::TYPE_INVITE_GROUP => "invite_group", //邀请加入群/公开群
        self::TYPE_UPDATE_GROUP_NAME => "update_group_name", //修改群/公开群名称
        self::TYPE_KICK_GROUP => "kick_group", //被踢出群
    ];

    //消息转发类型(个人/群)/extend_type为transfer,body内的item_type类型
    public static $_transfer_type_name = [
        self::TRANSFER_TYPE_GROUP => '群名片',
        self::TRANSFER_TYPE_USER => "个人名片",
        self::TRANSFER_TYPE_DISCUSS => "动态",
        self::TRANSFER_TYPE_NEWS => "资讯",
        self::TRANSFER_TYPE_ACTIVITY => "活动",
        self::TRANSFER_TYPE_LOCATION => "位置",
    ];


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

    /**
     * 获取单聊用户组合id
     *
     * @param $from
     * @param $to
     * @return float
     */
    public static function getMixId($from, $to)
    {
        return ($from + $to) * ($from + $to + 1) / 2 + min($from, $to);
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
        $new_body = json_encode($body, JSON_UNESCAPED_UNICODE);
        //群系统消息
        if ($gid > 0) {
            /* if ($ext && key_exists($ext, self::$_normal_type_name)) {
                 if ($option) {
                     ServerAPI::init()->asyncSendMsg($from, 1, $gid, $msg_type, $body, $ext_type, $push_content, $option);
                 } else {
                     ServerAPI::init()->asyncSendMsg($from, 1, $gid, $msg_type, $body, $ext_type, $push_content);
                 }
             } else {
                 if ($option) {
                     ServerAPI::init()->sendMsg($from, 1, $gid, $msg_type, $body, $ext_type, $push_content, $option);
                 } else {
                     ServerAPI::init()->sendMsg($from, 1, $gid, $msg_type, $body, $ext_type, $push_content);
                 }
             }*/
            if ($option) {
                ServerAPI::init()->sendMsg($from, 1, $gid, $msg_type, $body, $ext_type, $push_content, $option);
            } else {
                ServerAPI::init()->sendMsg($from, 1, $gid, $msg_type, $body, $ext_type, $push_content);
            }
        } else {
            $account_type = ''; //系统消息账号类型
            $async = false; //同步发送
            //动态通知
            if (key_exists($ext, self::$_social_type_name)) {
                $from = self::ACCOUNT_DYNAMICS;
                $account_type = self::ACCOUNT_TYPE_DYNAMICS;
            } //新的朋友
            elseif (key_exists($ext, self::$_friend_type_name)) {
                $from = self::ACCOUNT_NEW_FRIEND;
                $account_type = self::ACCOUNT_TYPE_NEW_FRIEND;
            } //系统消息
            else if (key_exists($ext, self::$_system_type_name)) {
                $from = self::ACCOUNT_SYSTEM;
                $account_type = self::ACCOUNT_TYPE_SYSTEM;
            } elseif (key_exists($ext, self::$_normal_type_name)) {
                //  $async = true;
            }
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
                //新的朋友 消息去重
                if ($account_type == self::ACCOUNT_TYPE_NEW_FRIEND) {
                    $msg = SystemMessage::findOne('trigger_uid=' . $body['uid'] . ' and user_id=' . $to . ' and (extend_type="' . self::TYPE_ATTENTION . '" or extend_type="' . self::TYPE_ADD_CONTACT . '")');
                    if ($msg) {
                        SystemMessage::updateOne(['extend_type' => $ext, 'body' => $new_body, 'created' => time()], ['id' => $msg['id']]);
                    } else {
                        $sys_msg = new SystemMessage();
                        $data = [
                            'from_account' => $from,
                            'user_id' => $to,
                            'account_type' => $account_type,
                            'extend_type' => $ext,
                            'body' => $new_body,
                            'trigger_uid' => $body['uid'],
                            'created' => time()
                        ];
                        $sys_msg->insertOne($data);
                    }
                    // $sys_msg->trigger_uid = $body['uid'];
                } else {
                    //暂不做记录
                    /*   $sys_msg = new SystemMessage();
                       $sys_msg->from_account = $from;
                       $sys_msg->user_id = $to;
                       $sys_msg->account_type = $account_type;
                       $sys_msg->extend_type = $ext;
                       $sys_msg->body = $new_body;
                       $sys_msg->created = time();
                       $sys_msg->save();*/
                }
            } else {
                Debug::log('send fail:' . var_export($res, true), 'im');
                return false;
            }

        }
        return true;
    }


    public function initMsg($type, $data, $opt = [])
    {
        switch ($type) {
            case self::TYPE_COMMENT:
                //评论
                $id = $data['item_id'];
                $comment_type = $data['type'];
                $type_name = $data['type_name'];
                $user_id = $data['user_id'];
                $to_user_id = $data['to_user_id'];
                $comment_content = $data['comment_content'];
                $user_info = UserStatus::getInstance()->getCacheUserInfo($user_id, false, $to_user_id, true);
                //个人备注
                $user_name = $user_info['username'];
                $msg = self::compileTemple(array("user_name" => $user_name, "type" => $type_name), "comment");
                $data = array("msg" => $msg, "extend_type" => $type, "item_id" => $id, "type" => $comment_type, "uid" => $user_id, 'username' => $user_name, "avatar" => $user_info['avatar'], "comment_content" => $comment_content, "created" => time());
                $data = array_merge($data, SocialManager::init()->getImShortDate($data['type'], $data['item_id']));
                //原贴图片视频
                if ($data['share_original_type'] == SocialManager::TYPE_DISCUSS && $data['share_original_item_id'] != $id) {
                    $original_info = SocialManager::init()->getImShortDate($data['type'], $data['share_original_item_id']);
                    $data['media'] = $original_info['media'];
                    $data['media_type'] = $original_info['media_type'];
                }
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);

                break;
            case self::TYPE_REPLY:
                //回复
                $id = $data['item_id'];
                $comment_id = $data['comment_id'];

                $comment_type = $data['type'];
                $type_name = $data['type_name'];
                $user_id = $data['user_id'];
                $to_user_id = $data['to_user_id'];
                $comment_content = $data['reply_content'];
                $user_info = UserStatus::getInstance()->getCacheUserInfo($user_id, false, $to_user_id, true);
                //个人备注
                $user_name = $user_info['username'];
                $msg = self::compileTemple(array("user_name" => $user_name, "type" => $type_name), "reply");
                $data = array("msg" => $msg, "extend_type" => $type, "item_id" => $id, "type" => $comment_type, "uid" => $user_id, 'username' => $user_name, "avatar" => $user_info['avatar'], 'comment_id' => $comment_id, "reply_content" => $comment_content, "created" => time());
                $data = array_merge($data, SocialManager::init()->getImShortDate($data['type'], $data['item_id']));
                //原贴图片视频
                if ($data['share_original_type'] == SocialManager::TYPE_DISCUSS && $data['share_original_item_id'] != $id) {
                    $original_info = SocialManager::init()->getImShortDate($data['type'], $data['share_original_item_id']);
                    $data['media'] = $original_info['media'];
                    $data['media_type'] = $original_info['media_type'];
                }
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);

                break;
            case self::TYPE_MENTION:
                //@功能
                $id = $data['item_id'];
                $mention_type = $data['type'];
                $content = $data['content'];
                $mention_content = FilterUtil::unPackageContentTagApp($data['content'], $data['to_user_id']);
                $user_id = $data['user_id'];
                $to_user_id = $data['to_user_id'];

                $user_info = UserStatus::getInstance()->getCacheUserInfo($user_id, false, $to_user_id, true);
                //个人备注
                $user_name = $user_info['username'];
                //$msg = self::compileTemple(array("user_name" => $user_name, "content" => $mention_content ? "：" . $mention_content : ''), "mention");
                $msg = self::compileTemple(array("user_name" => $user_name, "content" => $mention_content ? $mention_content : ''), "mention");

                //动态、评论
                if ($mention_type == SocialManager::TYPE_DISCUSS) {
                    $data = array("msg" => $msg, "extend_type" => $type, "item_id" => $id, "type" => $mention_type, "uid" => $user_id, 'username' => $user_name, "avatar" => $user_info['avatar'], "created" => time());
                    $data = array_merge($data, SocialManager::init()->getImShortDate($data['type'], $data['item_id']));
                    //原贴图片视频
                    if ($data['share_original_type'] == SocialManager::TYPE_DISCUSS && $data['share_original_item_id'] != $id) {
                        $original_info = SocialManager::init()->getImShortDate($data['type'], $data['share_original_item_id']);
                        $data['media'] = $original_info['media'];
                        $data['media_type'] = $original_info['media_type'];
                    }
                } //回复
                else if ($mention_type == SocialManager::TYPE_COMMENT) {
                    $data = array("msg" => $msg, "extend_type" => $type, "item_id" => $id, "type" => $mention_type, "uid" => $user_id, 'username' => $user_name, "avatar" => $user_info['avatar'], "created" => time());
                    $data = array_merge($data, SocialManager::init()->getImShortDate($data['type'], $data['item_id']));
                } else if ($mention_type == SocialManager::TYPE_VIDEO) {
                    $data = array("msg" => $msg, "extend_type" => $type, "item_id" => $id, "type" => $mention_type, "uid" => $user_id, 'username' => $user_name, "avatar" => $user_info['avatar'], "created" => time());
                    $data = array_merge($data, SocialManager::init()->getImShortDate($data['type'], $data['item_id']));
                }
                $push_content = self::compileTemple(array("user_name" => $user_name, "content" => FilterUtil::unPackageContentTag($content, $to_user_id)), "mention");
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_CUSTOM, $push_content);

                break;

            case self::TYPE_LIKE:
                //赞
                $id = $data['item_id'];
                $item_type = $data['type'];
                $type_name = $data['type_name'];
                $user_id = $data['user_id'];
                $to_user_id = $data['to_user_id'];
                $user_info = UserStatus::getInstance()->getCacheUserInfo($user_id, false, $to_user_id, true);
                //个人备注
                $user_name = $user_info['username'];

                $msg = self::compileTemple(array("user_name" => $user_name, "type" => $type_name), "like");
                $data = array("msg" => $msg, "extend_type" => $type, "item_id" => $id, "type" => $item_type, "uid" => $user_id, 'username' => $user_name, "avatar" => $user_info['avatar'], "created" => time());
                $data = array_merge($data, SocialManager::init()->getImShortDate($data['type'], $data['item_id']));
                //原贴图片视频
                if ($data['share_original_type'] == SocialManager::TYPE_DISCUSS && $data['share_original_item_id'] != $id) {
                    $original_info = SocialManager::init()->getImShortDate($data['type'], $data['share_original_item_id']);
                    $data['media'] = $original_info['media'];
                    $data['media_type'] = $original_info['media_type'];
                }
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_CUSTOM, '', ['push' => false]);
                break;
            case self::TYPE_FORWARD:
                //转发
                $id = $data['item_id'];
                $item_type = $data['type'];
                $type_name = $data['type_name'];
                $user_id = $data['user_id'];
                $to_user_id = $data['to_user_id'];
                $user_info = UserStatus::getInstance()->getCacheUserInfo($user_id, false, $to_user_id, true);
                //个人备注
                $user_name = $user_info['username'];

                $msg = self::compileTemple(array("user_name" => $user_name, "type" => $type_name), "forward");
                $data = array("msg" => $msg, "extend_type" => $type, "item_id" => $id, "type" => $item_type, "uid" => $user_id, 'username' => $user_name, "avatar" => $user_info['avatar'], "created" => time());
                $data = array_merge($data, SocialManager::init()->getImShortDate($data['type'], $data['item_id']));
                //原贴图片视频
                if ($data['share_original_type'] == SocialManager::TYPE_DISCUSS && $data['share_original_item_id'] != $id) {
                    $original_info = SocialManager::init()->getImShortDate($data['type'], $data['share_original_item_id']);
                    $data['media'] = $original_info['media'];
                    $data['media_type'] = $original_info['media_type'];
                }
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);
                break;
            case self::TYPE_GROUP_TRANSFER:
                //消息转发
                $id = $data['item_id'];
                $item_type = $data['item_type'];
                $user_id = $data['user_id'];
                $user_name = $data['user_name'];
                $user_avatar = $data['user_avatar'];
                $to_user_id = $data['to_user_id'];
                $gid = $data['gid'];
                $detail = $data['detail'];
                $msg = '消息转发';
                $data = array("msg" => $msg, "extend_type" => $type, "item_id" => $id, "item_type" => $item_type, "detail" => $detail, "uid" => $user_id, "username" => $user_name, "avatar" => $user_avatar, "created" => time());
                $json_data = self::createJson($data);
                $res = self::sendSystemMessage($user_id, $to_user_id, $gid, $data, $type);
                return $json_data;
                break;
            case self::TYPE_ATTENTION:
                //关注
                $user_id = $data['user_id'];
                $to_user_id = $data['to_user_id'];
                $user_info = UserStatus::getInstance()->getBaseUserInfo($user_id, 'username,true_name,avatar,sex,is_auth,auth_type,job,industry,company,signature,grade');

                $msg = self::compileTemple(array("user_name" => ''/*$user_info['username']*/), "attention");
                $data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "uid" => $user_id,
                    "created" => time());
                $data = array_merge($data, $user_info);
                if ($opt && $opt['push'] == false) {
                    $res = self::sendSystemMessage(self::ACCOUNT_NEW_FRIEND, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_CUSTOM, $user_info['username'] . $msg, ['push' => false]);
                } else {
                    $res = self::sendSystemMessage(self::ACCOUNT_NEW_FRIEND, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_CUSTOM, $user_info['username'] . $msg);
                }
                break;
            case self::TYPE_ADD_CONTACT:
                //请求添加为联系人
                $user_id = $data['user_id'];
                $to_user_id = $data['to_user_id'];
                $tip = $data['tip'];

                $user_info = UserStatus::getInstance()->getBaseUserInfo($user_id, 'username,true_name,avatar,sex,is_auth,auth_type,job,industry,company,signature,grade');

                $msg = $tip ? $tip : self::compileTemple(array("tip" => ''/*$tip ? $tip . ',' : $user_info['username']*/), "add_contact");
                $data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    'tip' => $tip,
                    "uid" => $user_id,
                    "created" => time());
                $data = array_merge($data, $user_info);
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_CUSTOM, $user_info['username'] . $msg);
                break;
            case self::TYPE_REGISTER:
                //注册成功
                $to_user_id = $data['to_user_id'];
                $username = $data['user_name'];

                $msg = self::compileTemple(array("user_name" => $username), $type);
                $data = array(
                    "msg" => $msg,
                );
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_TEXT);
                break;
            case self::TYPE_DISCUSS_RECOMMEND:
                //动态被设为推荐
                $discuss_id = $data['discuss_id'];
                $to_user_id = $data['to_user_id'];
                $username = $data['user_name'];
                $money = !empty($data['money']) ? $data['money'] : 0;
                if (!$money) {
                    $msg = self::compileTemple(array("user_name" => $username, "reward" => ''), $type);
                } else {
                    $msg = self::compileTemple(array("user_name" => $username, "reward" => "你将获得系统赠送的“" . (round($money / 100, 2)) . "”元现金红包"), $type);
                }
                $data = array(
                    "msg" => $msg,
                    'discuss_id' => $discuss_id,
                    "extend_type" => $type,
                    "money" => $money,
                    "created" => time()
                );
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_CUSTOM);
                break;
            case self::TYPE_AUTH_SUCCESS:
                //认证成功
                $to_user_id = $data['to_user_id'];
                $username = $data['user_name'];
                $auth_desc = $data['auth_desc'];
                $msg = self::compileTemple(array("user_name" => $username, 'auth_desc' => $auth_desc), $type);
                $data = array(
                    "msg" => $msg,
                );
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_TEXT);
                break;
            case self::TYPE_AUTH_FAIL:
                //认证失败
                $to_user_id = $data['to_user_id'];
                $username = $data['user_name'];
                $reason = $data['reason'];
                $msg = self::compileTemple(array("user_name" => $username, 'reason' => $reason), $type);
                $data = array(
                    "msg" => $msg,
                );
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, self::YUNXIN_MSG_TYPE_TEXT);
                break;
            case self::TYPE_USER:
                //用户名片
                $to_user_id = $data['to_user_id'];//发送给谁
                $username = $data['user_name'];//用户名
                $uid = $data['user_id'];//用户id
                $avatar = $data['avatar'];//用户头像
                $constellation = $data['constellation'];//星座
                $sex = $data['sex'];//性别
                $grade = $data['grade'];//等级
                $birthday = $data['birthday'];//生日
                $data = array(
                    "msg" => '',
                    "extend_type" => $type,
                    'username' => $username,
                    "uid" => $uid,
                    "avatar" => $avatar,
                    "constellation" => $constellation,
                    "sex" => $sex,
                    "grade" => $grade,
                    "birthday" => $birthday,
                    "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);
                break;
            case self::TYPE_INVITE_UPLOAD_PHOTOS:
                //邀请上传照片墙
                $to_user_id = $data['to_user_id'];//发送给谁
                $username = $data['user_name'];//用户名
                $uid = $data['user_id'];//用户id

                $data = array(
                    "msg" => '邀请你完善照片墙',
                    "extend_type" => $type,
                    'username' => $username,
                    "uid" => $uid,
                    "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);
                break;
            case self::TYPE_GIFT:
                //送礼物
                $to_user_id = $data['to_user_id'];//发送给谁
                $uid = $data['user_id'];//用户id
                $gift_info = $data['gift_info'];//礼物信息
                if ($data['gid']) {

                    $members = GroupMember::getByColumnKeyList(["gid=" . $data['gid'] . " and user_id in($uid,$to_user_id)", 'columns' => 'user_id,nick,default_nick'], "user_id");

                    if (isset($members[$uid])) {
                        $user_name = $members[$uid]['nick'] ? $members[$uid]['nick'] : $members[$uid]['default_nick'];
                    } else {
                        $user_name = Users::findOne(['id=' . $uid, 'columns' => 'username'])['username'];
                    }
                    if (isset($members[$to_user_id])) {
                        $to_user_name = $members[$to_user_id]['nick'] ? $members[$to_user_id]['nick'] : $members[$to_user_id]['default_nick'];
                    } else {
                        $to_user_name = Users::findOne(['id=' . $to_user_id, 'columns' => 'username'])['username'];
                    }
                    $msg = self::compileTemple(
                        array(
                            'gift_name' => $gift_info['name'],
                            'user_name' => $user_name,
                            'to_user_name' => $to_user_name
                        ), $type);
                } else {
                    $msg = self::compileTemple(array('gift_name' => $gift_info['name'], 'user_name' => '', 'to_user_name' => '你'), $type);
                }
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    "gift_info" => $gift_info,
                    'to_uid' => $to_user_id
                );
                $res = self::sendSystemMessage($uid, $to_user_id, $data['gid'] ? $data['yx_gid'] : '', $im_data, $type);
                break;
            case self::TYPE_OTHER:
                //其他信息
                $user_name = $data['user_name'];
                $to_user_id = $data['to_user_id'];

                $content = $data['content'];
                $msg = self::compileTemple(array("user_name" => $user_name, "content" => $content), "other");
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);
                break;
            //欢迎再次使用恐龙谷
            case self::TYPE_WELCOME_BACK:
                $to_user_id = $data['to_user_id'];
                $msg = self::compileTemple([], "welcome_back");
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0);
                break;
            //秀场冠军
            case self::TYPE_SHOW_CHAMPION:
                $to_user_id = $data['to_user_id'];//接收消息用户id
                $user_id = $data['user_id'];//冠军用户id
                $username = $data['username'];//冠军用户名
                $avatar = $data['avatar'];//冠军头像
                $sex = $data['sex']; //冠军性别
                $rank = $data['rank'];//接收消息用户排名
                $issue = $data['issue'];//第多少期
                $images = $data['images'];//图片
                $msg = ImManager::init()->compileTemple(['user_name' => ($user_id == $to_user_id ? '你' : $username), 'tip' => $sex == 1 ? '帅' : '美'], "show_champion");
                $data = [
                    'extend_type' => $type,
                    'created' => time(),
                    'sex' => $sex,
                    'rank' => $rank,
                    'username' => $username,
                    'avatar' => $avatar,
                    'uid' => $user_id,
                    'issue' => $issue,
                    'images' => $images,
                    'msg' => $msg];
                //单个
                if (strpos($to_user_id, ',') == false) {
                    $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);
                } else {
                    //批量
                    $res = SysMessage::init()->sendBatchNormalMessage(json_encode(explode(',', $to_user_id)), $data);
                }
                break;
            case self::TYPE_REWARD:
                //动态打赏
                $to_user_id = $data['to_user_id'];//发送给谁
                $uid = $data['user_id'];//用户id
                $tag = $data['tag'];//玫瑰花/0.01元红包
                $info = $data['info'];//玫瑰花/0.01元红包
                $user_info = UserStatus::getInstance()->getCacheUserInfo($uid, false, $to_user_id, true);
                //个人备注
                $user_name = $user_info['username'];
                $msg = self::compileTemple(array('user_name' => $user_name, 'tag' => $tag), 'reward');
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'uid' => $uid,
                    'avatar' => $user_info['avatar'],
                    'username' => $user_name
                );
                $im_data = array_merge($im_data, $data['info']);
                $res = self::sendSystemMessage($uid, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_REWARD_VIDEO:
                //附近视频打赏
                $to_user_id = $data['to_user_id'];//发送给谁
                $uid = $data['user_id'];//用户id
                $tag = $data['tag'];//玫瑰花/0.01元红包
                $info = $data['info'];//玫瑰花/0.01元红包
                $user_info = UserStatus::getInstance()->getCacheUserInfo($uid, false, $to_user_id, true);
                //个人备注
                $user_name = $user_info['username'];
                $msg = self::compileTemple(array('user_name' => $user_name, 'tag' => $tag), 'reward');
                $im_data = array(
                    "msg" => $msg,
                    "extend_type" => $type,
                    "created" => time(),
                    'uid' => $uid,
                    'avatar' => $user_info['avatar'],
                    'username' => $user_name
                );
                $im_data = array_merge($im_data, $data['info']);
                $res = self::sendSystemMessage($uid, $to_user_id, '', $im_data, $type);
                break;
            case self::TYPE_PAIDQA_ANSWER:
                $uid_answer = $data['uid'];
                $username_answer = $data['username'];
                $uid_ask = $data['to_uid'];

                //$question = $data['question'];
                //$url = $data['url'];
                $msg = ImManager::init()->compileTemple(['username' => $username_answer], "question_answered");

                $data = [
                    'extend_type' => $type,
                    'uid' => $uid_answer,
                    //'username' => $username_answer,
                    'msg' => $msg
                ];
                $res = self::sendSystemMessage(0, $uid_ask, '', $data, $type);
                break;

            case self::TYPE_PAIDQA_NEW_QUESTION:
                $username_ask = $data['username'];

                $uid_ask = $data['to_uid'];

                $msg = ImManager::init()->compileTemple(['username' => $username_ask], "question_asked");

                $data = [
                    'extend_type' => $type,
                    'uid' => $uid_ask,
                    'username' => $username_ask,
                    'msg' => $msg
                ];
                $res = self::sendSystemMessage(0, $uid_ask, '', $data, $type);

                break;
            case self::TYPE_REPORT_FROM:
                //举报通过 发给被举报人
                $username = $data['username'];//用户名
                $time = $data['time'];//时间
                $title = $data['title'];//标题
                $content = $data['content'];//标题
                $to_user_id = $data['to_user_id'];
                $msg = self::compileTemple(['user_name' => $username, 'time' => $time, 'title' => $title, 'content' => $content], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0);
                break;
            case self::TYPE_SHOP_SHIELD:
                //店铺被封 发给用户
                $username = $data['username'];//用户名
                $content = $data['reason'];//原因
                $to_user_id = $data['to_user_id'];
                $shop = $data['shop'];
                $msg = self::compileTemple(['user_name' => $username, 'reason' => $content, 'shop_name' => $shop], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0, $data['msg']);
                break;
            case self::TYPE_SHOP_CHECK_SUCCESS:
                //店铺审核通过
                $username = $data['username'];//用户名
                $to_user_id = $data['to_user_id'];
                $shop = $data['shop'];
                $msg = self::compileTemple(['user_name' => $username, 'shop_name' => $shop], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0, $data['msg']);
                break;
            case self::TYPE_SHOP_CHECK_FAIL:
                //店铺审核失败
                $username = $data['username'];//用户名
                $content = $data['reason'];//原因
                $to_user_id = $data['to_user_id'];
                $shop = $data['shop'];
                $msg = self::compileTemple(['user_name' => $username, 'reason' => $content, 'shop_name' => $shop], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0, $data['msg']);
                break;
            case self::TYPE_SHOP_DOWN:
                //店铺被下架
                $username = $data['username'];//用户名
                $content = $data['reason'];//原因
                $to_user_id = $data['to_user_id'];
                $shop = $data['shop'];
                $msg = self::compileTemple(['user_name' => $username, 'reason' => $content, 'shop_name' => $shop], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0, $data['msg']);
                break;
            case self::TYPE_AGENT_CHECK_SUCCESS:
                //合伙人审核成功
                $username = $data['username'];//用户名
                $to_user_id = $data['to_user_id'];
                $msg = self::compileTemple(['user_name' => $username], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0, $data['msg']);
                break;
            case self::TYPE_AGENT_CHECK_FAIL:
                //合伙人审核失败
                $username = $data['username'];//用户名
                $content = $data['reason'];//原因
                $to_user_id = $data['to_user_id'];
                $msg = self::compileTemple(['user_name' => $username, 'reason' => $content], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0, $data['msg']);
                break;
            case self::TYPE_GOOD_SHIELD:
                //商品被下架 发给用户
                $username = $data['username'];//用户名
                $content = $data['reason'];//原因
                $to_user_id = $data['to_user_id'];
                $goods = $data['goods'];
                $shop = $data['shop'];
                $msg = self::compileTemple(['user_name' => $username, 'reason' => $content, 'goods_name' => $goods, 'shop_name' => $shop], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type, 0, $data['msg']);
                break;
            case self::TYPE_PAIDQA_CHAT_NEW_QUESTION:
                $qid = $data['qid'];
                $uid_ask = $data['uid_ask'];
                $uid_answer = $data['uid_answer'];
                $question = $data['question'];
                $data = [
                    'extend_type' => $type,
                    'qid' => $qid,
                    'question' => $question,
                    'msg' => "向您提问《{$question}》"
                ];
                $res = self::sendSystemMessage($uid_ask, $uid_answer, '', $data, $type, 100, $data['msg']);

                break;
            case self::TYPE_PAIDQA_CHAT_ANSWER:
                $qid = $data['qid'];
                $uid_ask = $data['uid_ask'];
                $uid_answer = $data['uid_answer'];
                $question = $data['question'];
                $data = [
                    'extend_type' => $type,
                    'qid' => $qid,
                    'question' => $question,
                    'msg' => "回答了您的问题《{$question}》"
                ];
                $res = self::sendSystemMessage($uid_answer, $uid_ask, '', $data, $type, 100, $data['msg']);

                break;
            //vip即将到期
            case self::TYPE_VIP_DEADLINE_SOON:
                $day = $data['day'];
                $to_user_id = $data['to_user_id'];
                $msg = self::compileTemple(['day' => $day], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);


                break;
            //vip已经到期
            case self::TYPE_VIP_DEADLINE_HAS_ARRIVED:
                $to_user_id = $data['to_user_id'];
                $msg = self::compileTemple([], $type);
                $data = array("msg" => $msg, "extend_type" => $type, "created" => time());
                $res = self::sendSystemMessage(0, $to_user_id, "", $data, $type);

                break;
            default:
                $res = false;
                break;
        }
        return $res;
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

    private function createJson($data)
    {
        $data['created'] = time();
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }


    /**组链接
     * @param $url
     * @param $para
     * @return string
     */
    public function createLinkString($url, $para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . (is_array($val) ? implode(',', $val) : $val) . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        return $url . "?" . $arg;
    }
}