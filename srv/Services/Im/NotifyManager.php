<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 14:51
 */

namespace Services\Im;


use Components\Curl\CurlManager;
use Models\Group\Group;
use Models\User\Message;
use Phalcon\Mvc\User\Plugin;
use Services\Site\CacheSetting;
use Util\Debug;
use Util\Ip;

class NotifyManager extends Plugin
{
    private static $instance = null;

    //事件类型------------------------------------------
    const eventType_CONVERSATION = 1;//即会话类型的消息（目前包括P2P聊天消息，群组聊天消息，群组操作，好友操作）
    const eventType_LOGIN = 2;//即用户登录事件的消息
    const eventType_LOGOUT = 3;//即用户登出事件的消息
    const eventType_CHATROOM = 4;//即聊天室中聊天的消息
    const eventType_AUDIO = 5;//表示AUDIO/VEDIO/DataTunnel消息，即汇报实时音视频通话时长、白板事件时长的消息
    const eventType_AUDIO_STORAGE = 6;//表示音视频/白板文件存储信息，即汇报音视频/白板文件的大小、下载地址等消息
    const eventType_CHATROOM_INOUT = 9;//即汇报主播或管理员进出聊天室事件消息

    //会话类型----------------------------------------
    const convType_PERSON = 'PERSON';//二人会话数据
    const convType_TEAM = 'TEAM';//群聊数据
    const convType_CUSTOM_PERSON = 'CUSTOM_PERSON';//个人自定义系统通知
    const convType_CUSTOM_TEAM = 'CUSTOM_TEAM';//群组自定义系统通知

    //发送客户端类型------------------------------------
    const fromClientType_AOS = 'AOS';//安卓
    const fromClientType_IOS = 'IOS';//IOS
    const fromClientType_PC = 'PC';//PC
    const fromClientType_WINPHONE = 'WINPHONE';//WINPHONE
    const fromClientType_WEB = 'WEB';//WEB
    const fromClientType_REST = 'REST';//REST

    //消息类型--------------------------------------------------
    //--会话具体类型PERSON、TEAM对应的通知消息类型
    const msgType_TEXT = 'TEXT';//文本消息
    const msgType_PICTURE = 'PICTURE';//图片消息
    const msgType_AUDIO = 'AUDIO';//音频
    const msgType_VIDEO = 'VIDEO';//视频
    const msgType_LOCATION = 'LOCATION ';//位置
    const msgType_NOTIFICATION = 'NOTIFICATION';//系统提醒
    const msgType_FILE = 'FILE';//文件消息
    const msgType_NETCALL_AUDIO = 'NETCALL_AUDIO';//网络电话音频聊天
    const msgType_NETCALL_VEDIO = 'NETCALL_VEDIO';//网络电话视频聊天
    const msgType_DATATUNNEL_NEW = 'NETCALL_VEDIO';//新的数据通道请求通知
    const msgType_TIPS = 'TIPS';//提醒类型消息
    const msgType_CUSTOM = 'CUSTOM';//自定义消息

    //会话具体类型CUSTOM_PERSON对应的通知消息类型
    const msgType_FRIEND_ADD = 'FRIEND_ADD';//加好友
    const msgType_FRIEND_DELETE = 'FRIEND_DELETE';//删除好友
    const msgType_CUSTOM_P2P_MSG = 'CUSTOM_P2P_MSG';//个人自定义系统通知

    //会话具体类型CUSTOM_TEAM对应的通知消息类型
    const msgType_TEAM_APPLY = 'TEAM_APPLY';//申请入群
    const msgType_TEAM_APPLY_REJECT = 'TEAM_APPLY_REJECT';//拒绝入群申请
    const msgType_TEAM_INVITE = 'TEAM_INVITE';//邀请进群
    const msgType_TEAM_INVITE_REJECT = 'TEAM_INVITE_REJECT ';//拒绝邀请
    const msgType_TLIST_UPDATE = 'TLIST_UPDATE ';//群信息更新
    const msgType_CUSTOM_TEAM_MSG = 'CUSTOM_TEAM_MSG';//群组自定义系统通知


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

    /**消息入库
     * @param $data
     * @return bool
     */
    public function write($data)
    {
        //登录事件
        if ($data['eventType'] == self::eventType_LOGIN) {
            //测试消息
            $this->redirect_test($data['accid']);
            $redis = $this->di->get('redis');
            $redis_queue = $this->di->get('redis_queue');
            // if (!$redis->hExists(CacheSetting::KEY_USER_ONLINE_LIST, $data['accid'])) {
            //$redis->hIncrBy(CacheSetting::KEY_USER_ONLINE, CacheSetting::KEY_USER_ONLINE_COUNT, 1);
            //获取地址
            $address = Ip::getAddress($data['clientIp']);// $res=CurlManager::init()->curl_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=" . $data['clientIp']);
            //  $address = json_decode($res['data'], true);
            if ($data['accid'] == 50000) {
                Debug::log("登录:time:" . $data['timestamp'] . ",date:" . date('Y-m-d H:i:s', intval($data['timestamp'] / 1000)), 'im_login');
            }
            $redis->hSet(CacheSetting::KEY_USER_ONLINE_LIST, $data['accid'], json_encode(['uid' => $data['accid'], 'time' => $data['timestamp'], 'ip' => $data['clientIp'], 'province' => $address['province']], JSON_UNESCAPED_UNICODE));
            $redis_queue->rPush(CacheSetting::KEY_USER__ONLINE, json_encode(['act' => 'login', 'uid' => $data['accid'], 'time' => $data['timestamp'], 'ip' => $data['clientIp']]));
            //  }
        } //登出事件
        else if ($data['eventType'] == self::eventType_LOGOUT) {
            //测试消息
            $this->redirect_test($data['accid']);

            $redis = $this->di->get('redis');
            $redis_queue = $this->di->get('redis_queue');
            // if ($redis->hExists(CacheSetting::KEY_USER_ONLINE_LIST, $data['accid'])) {
            // $redis->hIncrBy(CacheSetting::KEY_USER_ONLINE, CacheSetting::KEY_USER_ONLINE_COUNT, -1);
            if ($data['accid'] == 50000) {
                Debug::log("退出:time:" . $data['timestamp'] . ",date:" . date('Y-m-d H:i:s', intval($data['timestamp'] / 1000)), 'im_login');
            }
            $redis->hDel(CacheSetting::KEY_USER_ONLINE_LIST, $data['accid']);
            $redis_queue->rPush(CacheSetting::KEY_USER__ONLINE, json_encode(['act' => 'logout', 'uid' => $data['accid'], 'time' => $data['timestamp'], 'ip' => $data['clientIp']]));
            //  }
        } //会话消息才会记录
        else if ($data['eventType'] == self::eventType_CONVERSATION) {
            //测试消息
            $this->redirect_test($data['fromAccount']);
            if (Message::exist('message_id="' . $data['msgidServer'] . '"')) {
                return true;
            }


            if (!in_array($data['msgType'],
                [
                    self::msgType_TEXT,
                    self::msgType_PICTURE,
                    self::msgType_AUDIO,
                    self::msgType_VIDEO,
                    self::msgType_FILE,
                    self::msgType_CUSTOM,
                    self::msgType_CUSTOM_P2P_MSG,
                    self::msgType_CUSTOM_TEAM_MSG
                ])
            ) {
                return false;
            }
            //个人自定义系统通知 过滤掉
            if (in_array($data['msgType'], [self::msgType_CUSTOM_P2P_MSG])) {
                return true;
            }

            $msg = [];

            $msg['from_uid'] = $data['fromAccount'];//消息发送者
            $msg['client_type'] = $data['fromClientType'];
            $msg['device_id'] = $data['fromDeviceId'];
            $msg['send_time'] = $data['msgTimestamp'];//intval($data['msgTimestamp'] / 1000);

            $ymd = date('Y-m-d', $msg['send_time'] / 1000);
            $ymd = explode('-', $ymd);

            $msg['year'] = $ymd[0];
            $msg['month'] = $ymd[1];
            $msg['day'] = $ymd[2];
            $msg['media_type'] = $data['msgType'];
            $msg['message_id'] = $data['msgidServer'];
            $msg['extend_json'] = $data['ext'];


            if ($data['msgType'] == self::msgType_TEXT) {
                $msg['body'] = $data['body'];
            } else {
                $msg['body'] = $data['attach'];
            }
            $msg['to_uid'] = 0;
            $msg['created'] = time();
            //会话类型
            if ($data['convType'] == self::convType_PERSON) {
                $msg['type'] = 1; //单聊
                $msg['to_uid'] = $data['to'];
                $msg['media_type'] = strtolower($data['msgType']);
                //测试消息
                $this->redirect_test($data['to']);
            } else if ($data['convType'] == self::convType_TEAM) {
                $msg['type'] = 2;//群聊
                $msg['gid_yx'] = $data['to'];
                $msg['media_type'] = strtolower($data['msgType']);
                $group = Group::findOne(['yx_gid="' . $data['to'] . '"', 'columns' => 'id,user_id']);
                $msg['gid'] = $group['id'];
                //测试消息
                $this->redirect_test($group['user_id']);
                $redis = $this->di->get('redis');
                $redis->hSet(CacheSetting::KEY_GROUP_ACTIVE, $group['id'], $msg['send_time']);

            } else if ($data['convType'] == self::convType_CUSTOM_PERSON || $data['convType'] == self::convType_CUSTOM_TEAM) {
                $msg['type'] = 3;//系统消息
                //群系统消息
                if ($data['convType'] == self::convType_CUSTOM_TEAM) {
                    $group = Group::findOne(['yx_gid="' . $data['to'] . '"', 'columns' => 'id,user_id']);
                    $msg['gid_yx'] = $data['to'];
                    $msg['gid'] = $group['id'];
                    //测试消息
                    $this->redirect_test($group['user_id']);
                } else {
                    $msg['to_uid'] = $data['to'];
                    //测试消息
                    $this->redirect_test($data['to']);
                }
                $msg['media_type'] = strtolower($data['msgType']);
            }

            //自定义消息
            if ($data['msgType'] == self::msgType_CUSTOM) {
                $body = $msg['body'] ? json_decode($msg['body'], true) : '';
                // //扩展类型
                if ($body && !empty($body['extend_type'])) {
                    //过滤一部分消息
                    if (in_array($body['extend_type'], [
                        SysMessage::TYPE_NEW_DISCUSS,
                        SysMessage::TYPE_HOT_NEWS,
                        SysMessage::TYPE_GRAB_RED_PACKAGE,
                        SysMessage::TYPE_CASH_OUT_FAIL,
                        SysMessage::TYPE_CASH_OUT,
                        SysMessage::TYPE_REFUND,
                        SysMessage::TYPE_OUT_FRIEND,
                        SysMessage::TYPE_IN_FRIEND,
                        SysMessage::TYPE_SHARE_HOT_NEWS,
                        ImManager::TYPE_LIKE,
                        ImManager::TYPE_COMMENT,
                        ImManager::TYPE_MENTION,
                        ImManager::TYPE_REPLY,
                        ImManager::TYPE_FORWARD,
                        ImManager::TYPE_ATTENTION,
                        ImManager::TYPE_ADD_CONTACT,

                    ])) {
                        return false;
                    }
                    $msg['extend_type'] = $body['extend_type'];
                }
            }
            $msg['mix_id'] = !empty($msg['to_uid']) ? $this->getMixId($msg['from_uid'], $msg['to_uid']) : 0;
            $redis = $this->di->get('redis');
            $redis_queue = $this->di->get('redis_queue');
            if ($msg['mix_id']) {
                $this->redirect_test($data['to_uid']);

                $redis = $this->di->get('redis');

                //记录会话列表
                $redis->hSet(CacheSetting::KEY_CONVERSATION_LIST . $msg['from_uid'], $msg['to_uid'],
                    json_encode([
                        'send_time' => $msg['send_time'],
                        'body' => $msg['body'],
                        'from_uid' => $msg['from_uid'],
                        'to_uid' => $msg['to_uid'],
                        'media_type' => $msg['media_type'],
                        'message_id' => $msg['message_id'],
                        'extend_json' => $msg['extend_json'],
                        'mix_id' => $msg['mix_id']
                    ], JSON_UNESCAPED_UNICODE));
                $redis->hSet(CacheSetting::KEY_CONVERSATION_LIST . $msg['to_uid'], $msg['from_uid'],
                    json_encode([
                        'send_time' => $msg['send_time'],
                        'body' => $msg['body'],
                        'media_type' => $msg['media_type'],
                        'message_id' => $msg['message_id'],
                        'extend_json' => $msg['extend_json'],
                        'from_uid' => $msg['from_uid'],
                        'to_uid' => $msg['to_uid'],
                        'mix_id' => $msg['mix_id']
                    ], JSON_UNESCAPED_UNICODE));
            } else if (!empty($msg['gid'])) {
                $redis->hSet(CacheSetting::KEY_GROUP_CONVERSATION_LIST, $msg['gid'],
                    json_encode([
                        'send_time' => $msg['send_time'],
                        'body' => $msg['body'],
                        'media_type' => $msg['media_type'],
                        'message_id' => $msg['message_id'],
                        'extend_json' => $msg['extend_json'],
                        'from_uid' => $msg['from_uid'],
                        // 'to_uid' => $msg['to_uid'],
                        'gid' => $msg['gid'],
                        'mix_id' => 0
                    ], JSON_UNESCAPED_UNICODE));
            }

            if (!$redis->hExists(CacheSetting::KEY_MESSAGE_NOTIFY_LIST, $data['msgidServer'])) {
                $redis->hset(CacheSetting::KEY_MESSAGE_NOTIFY_LIST, $data['msgidServer'], $data['msgidServer']);
                $redis_queue->rPush(CacheSetting::KEY_MESSAGE_PUSH_LIST, json_encode($msg));//入队列
            }

            /*    $message = new Message();
                if (!$message->save($msg)) {
                    Debug::log('消息抄送入库失败' . var_export($msg, true) . var_export($message->getMessages(), true), 'im');
                    return false;
                }*/
            return true;
        }
    }

    public function redirect_test($uid)
    {
        // Debug::log("重定向:" . $uid, 'im');
        //  Debug::log("重定向:" . var_export(TEST_SERVER,true), 'im');
        if (!TEST_SERVER && $uid >= 40000 and $uid < 50000) {
            $json = @file_get_contents('php://input');//要发送的json
            $url = 'http://120.78.182.253:8181/im/notify';//接收XML地址
            $headers = $this->request->getHeaders();
            $request_header = [];
            foreach ($headers as $k => $v) {
                $request_header[] = "$k:$v";
            }
            Debug::log("重定向:" . var_export($request_header, true), 'im');
            Debug::log("重定向:" . var_export($json, true), 'im');
            $ch = curl_init(); //初始化curl
            curl_setopt($ch, CURLOPT_URL, $url);//设置链接
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);//设置HTTP头
            curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);//POST数据
            $response = curl_exec($ch);//接收返回信息
            if (curl_errno($ch)) {//出错则显示错误信息
                Debug::log("重定向失败:" . var_export(curl_getinfo($ch), true), 'im');
            } else {
                Debug::log("重定向成功:" . var_export($response, true), 'im');
            }
            curl_close($ch); //关闭curl链接
            //echo $response;//显示返回信息
            echo "200";
            exit;
        }

    }

}