<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/9
 * Time: 17:46
 *
 * 系统通知类消息
 *
 */

namespace Services\Im;


use Components\Yunxin\ServerAPI;
use Models\Site\SiteMaterial;
use Models\User\UserInfo;
use Phalcon\Mvc\User\Plugin;
use Services\Site\SiteKeyValManager;
use Services\User\UserStatus;
use Util\Debug;

class SysMessage extends Plugin
{
    private static $instance = null;

    //extend_type

    const TYPE_GROUP_ANNOUNCEMENT = 'group_announcement';//群公告
    const TYPE_NEW_DISCUSS = 'new_discuss';//新动态
    const TYPE_NEW_VISITOR = 'new_visitor';//新访客
    const TYPE_GROUP_DISMISS = 'group_dismiss';//群被迫解散
    const TYPE_USER_FREEZE = 'user_freeze';//用户被封号
    const TYPE_USER_LOCKED = 'user_locked';//用户被锁定
    const TYPE_SYSTEM_PUSH = 'system_push';//系统推送
    const TYPE_HOT_NEWS = 'hot_news';//热门资讯
    const TYPE_SYSTEM_GIF = 'system_klgGif';//恐龙谷gif图
    const TYPE_USER = 'user';//名片
    const TYPE_NEW_RED_PACKAGE = 'newredbag';//发布红包
    const TYPE_GRAB_RED_PACKAGE = 'grabredbag';//红包被抢
    const TYPE_CASH_OUT_FAIL = 'cashout_fail';//提现失败
    const TYPE_CASH_OUT = 'cashout';//提现成功
    const TYPE_REFUND = 'refund';//红包退还
    const TYPE_NEW_QUESTION = 'new_question';//视频问答-有新问题
    const TYPE_NOTICE_MODIFY = 'notice_modify';//红包广场公告更新
    const TYPE_GROUP_RM_HISTORY_MSG = 'type_group_rm_history_msg';//删除群聊天记录

    const TYPE_OUT_FRIEND = 'out_friend';//移除好友
    const TYPE_IN_FRIEND = 'in_friend';//成为好友
    const TYPE_SHARE_HOT_NEWS = 'ShareHotNews';//热点资讯分享到聊天

    const TYPE_ADS_UPDATE = 'ads_update';//广告更新

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * http 消息发送系统通知(扩展)接口
     *
     * @param $from //发送消息的人UID
     * @param $to //接收消息的人的UID
     * @param $gid //如果gid 不为空则认为是群聊
     * @param string $body //文本消息正文
     * @param string $push_content //推送文本内容
     * @param array $push_payload //推送扩展数据-数组
     * @return bool
     */
    private function sendSystemMessage($to, $gid = "", $body = "", $push_content = '', $push_payload = [])
    {
        $from = ImManager::ACCOUNT_SYSTEM;
        // Debug::log('from:' . $from . 'to:' . $to . 'body:' . json_encode($body, JSON_UNESCAPED_UNICODE), 'im');
        //发给个人
        $body = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($to > 0) {
            $res = ServerAPI::init()->sendAttachMsg($from, 0, $to, $body, $push_content, $push_payload);
        } //发给群
        else {
            $res = ServerAPI::init()->sendAttachMsg($from, 1, $gid, $body, $push_content, $push_payload);
        }
        Debug::log(var_export($res, true), 'im');

        return true;
    }

    /**
     * http 批量消息发送个人系统通知(扩展)接口
     *
     * @param $from //发送消息的人UID
     * @param $to //接收消息的人的UID，array
     * @param string $body //文本消息正文
     * @return bool
     */
    private function sendBatchSystemMessage($to, $body = "", $push_content = '')
    {
        $from = ImManager::ACCOUNT_SYSTEM;
        //发给个人
        $body = json_encode($body, JSON_UNESCAPED_UNICODE);
        $res = ServerAPI::init()->sendBatchAttachMsg($from, $to, $body, $push_content);
        if (!$res)
            Debug::log(var_export($to, true), 'im');
        /*  Debug::log(var_export($body, true), 'im');
          Debug::log($to, 'im');
          Debug::log(var_export($res, true), 'im');*/

        return true;
    }

    /**
     * http 批量消息发送普通自定义通知(扩展)接口
     *
     * @param $from //发送消息的人UID
     * @param $to //接收消息的人的UID，array
     * @param string $body //文本消息正文
     * @param string $ext
     * @return bool
     */
    public function sendBatchNormalMessage($to, $body = "", $ext = '', $option = [], $payload = [], $push_content = '')
    {
        $from = ImManager::ACCOUNT_SYSTEM;
        //发给个人
        $body = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($option) {
            $res = ServerAPI::init()->sendBatchMsg($from, $to, 100, $body, $ext, $option, $payload, $push_content);
        } else {
            $res = ServerAPI::init()->sendBatchMsg($from, $to, 100, $body, $ext);
        }
        Debug::log($to, 'im');
        Debug::log(var_export($body, true) . var_export($ext, true), 'im');
        Debug::log(var_export($res, true), 'im');

        return true;
    }

    public function initMsg($type, $data, $push_content = '')
    {
        switch ($type) {
            //编辑群公告
            case self::TYPE_GROUP_ANNOUNCEMENT:
                $gid = $data['gid'];
                $yx_gid = $data['yx_gid'];
                $an_id = $data['an_id']; //公告id
                $group_name = $data['group_name']; //群名称
                $modify_time = $data['modify_time']; //添加/修改时间
                $avatar = $data['avatar']; //群主头像
                $username = $data['username']; //群主昵称
                $content = $data['content']; //公告内容
                $body = ['extend_type' => $type, 'gid' => $gid, 'an_id' => $an_id, 'group_name' => $group_name, 'modify_time' => $modify_time, 'avatar' => $avatar, 'username' => $username, 'content' => $content, 'created' => time()];
                $res = self::sendSystemMessage(0, $yx_gid, $body, $push_content);
                break;
            //新动态
            case self::TYPE_NEW_DISCUSS:

                $to_user_id = $data['to_user_id'];

                $body = ['extend_type' => $type, 'created' => time(), 'need_push' => false, 'avatar' => $data['avatar']];
                //推送栏推送
                if (isset($data['need_push'])) {
                    $user_id = $data['user_id'];
                    $user_info = UserStatus::getInstance()->getCacheUserInfo($user_id);
                    $mark = UserStatus::getMark($user_id, $to_user_id);
                    //个人备注
                    $user_name = $mark ? $mark : $user_info['username'];
                    $msg = ImManager::init()->compileTemple(['username' => $user_name], "new_discuss");
                    $body['need_push'] = true;
                    $res = self::sendSystemMessage($to_user_id, 0, $body, $msg, ['extend_type' => 'new_discuss', 'need_push' => true, 'avatar' => $data['avatar'], 'discuss_id' => $data['item_id']]);
                } else {
                    $res = self::sendBatchSystemMessage($to_user_id, $body, $push_content);
                }
                break;
            //新访客
            case self::TYPE_NEW_VISITOR:
                $to_user_id = $data['to_user_id'];
                $body = ['extend_type' => $type, 'created' => time()];
                $res = self::sendSystemMessage($to_user_id, 0, $body, $push_content);
                break;
            //群被迫解散
            case self::TYPE_GROUP_DISMISS:
                $gid = $data['gid'];
                $yx_gid = $data['yx_gid'];
                $msg = ImManager::init()->compileTemple([], "group_dismiss");
                $body = ['extend_type' => $type, 'gid' => $gid, 'created' => time(), 'msg' => $msg];
                $res = self::sendSystemMessage(0, $yx_gid, $body, $push_content);
                break;
            //用户被封号
            case self::TYPE_USER_FREEZE:
                $to_user_id = $data['to_user_id'];
                $msg = ImManager::init()->compileTemple([], "user_freeze");
                $body = ['extend_type' => $type, 'created' => time(), 'msg' => $msg];
                $res = self::sendSystemMessage($to_user_id, 0, $body, $push_content);
                break;
            //用户被临时锁定
            case self::TYPE_USER_LOCKED:
                $to_user_id = $data['to_user_id'];
                $msg = ImManager::init()->compileTemple([], "user_locked");
                $body = ['extend_type' => $type, 'created' => time(), 'msg' => $msg];
                $res = self::sendSystemMessage($to_user_id, 0, $body, $push_content);
                break;
            //管理平台发送
            case self::TYPE_SYSTEM_PUSH:
                $to_user_id = $data['to_user_id'];
                $msg = $data['msg'];
                $ext = $data['ext'];
                $tpl_type = $data['tpl_type'];
                $body = ['extend_type' => $type, 'created' => time(), 'msg' => $msg, 'tpl_type' => $tpl_type];
                $res = self::sendBatchNormalMessage($to_user_id, $body, $ext, ['push' => true], [], mb_substr(strip_tags($msg), 0, 40) . '..');
                return $res;
                break;
            //解除好友
            case self::TYPE_OUT_FRIEND:
                $to_user_id = $data['to_user_id'];
                $user_id = $data['user_id'];
                $body = ['extend_type' => $type, 'created' => time(), 'msg' => '', 'uid' => $user_id];
                $res = self::sendSystemMessage($to_user_id, 0, $body, $push_content);
                break;
            //成为好友
            case self::TYPE_IN_FRIEND:
                $to_user_id = $data['to_user_id'];
                $user_id = $data['user_id'];
                $body = ['extend_type' => $type, 'created' => time(), 'msg' => '', 'uid' => $user_id];
                $res = self::sendSystemMessage($to_user_id, 0, $body, $push_content);
                break;
            //广告更新
            case self::TYPE_ADS_UPDATE:
                $to_user_id = $data['to_user_id'];
                $key = $data['ads_key'];
                $body = ['extend_type' => $type, 'created' => time(), 'ads_key' => $key, 'msg' => '',];
                $res = self::sendBatchSystemMessage($to_user_id, $body, $push_content);
                break;
            //有新问题
            case self::TYPE_NEW_QUESTION:
                $to_user_id = $data['to_user_id'];
                $body = ['extend_type' => $type, 'created' => time()];
                $res = self::sendSystemMessage($to_user_id, 0, $body, $push_content);
                break;

            //红包广场公告有更新
            case self::TYPE_NOTICE_MODIFY:
                //获取最新5天内公告
                $start = microtime(true);
                $body['extend_type'] = $type;
                $body['created'] = time();
                $body['type'] = (int)$data['type'];//操作类型 ：增加，编辑，删除公告
                $body['notices'] = $data['notices'];
                //平台所有用户id
                $uids = UserInfo::getColumn(['user_type = 1 and status=' . UserStatus::USER_TYPE_NORMAL, 'columns' => 'user_id as id'], 'id');

                //500人一组推送系统消息
                $i = 0;
                $batch = array_splice($uids, $i, 500);
                $res = true;
                $break1 = microtime(true);
                while ($batch) {
                    $i += 500;
                    if (!self::sendBatchSystemMessage($batch, $body))
                        $res = false;
                    $batch = array_splice($uids, $i, 500);
                }
                $break2 = microtime(true);

                $res ? Debug::log('公告变更-ok', 'im') : Debug::log('公告变更-error', 'im');
                break;
            //删除群聊天记录
            case self::TYPE_GROUP_RM_HISTORY_MSG:
                $gid = $data['gid'];
                $yx_gid = $data['yx_gid'];
                $body = ['extend_type' => $type, 'gid' => $gid, 'yx_gid' => $yx_gid];
                $res = self::sendSystemMessage(0, $yx_gid, $body, $push_content);
                break;

            default:
                $res = false;
                break;
        }
        return $res;
    }

}