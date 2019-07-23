<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/10
 * Time: 10:31
 */

namespace Services\User;


use Models\Group\Group;
use Models\User\UserChatTop;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Util\Debug;

class ChatManager extends Plugin
{
    private static $instance = null;

    public $ajax = null;

    const TOP_USER = 1;//单聊置顶
    const TOP_GROUP = 2;//群聊置顶

    public static $top_type = [
        self::TOP_USER,
        self::TOP_GROUP
    ];

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**消息置顶
     * @param $uid
     * @param $to -会话id
     * @param $type 1-单聊 2-群聊
     * @return bool
     */
    public function setTop($uid, $type, $to)
    {
        $conversation_id = $to;//云信会话id
        //群聊 云信群id换成 恐龙谷平台群id
        if ($type == self::TOP_GROUP) {
            $group = Group::findOne(['yx_gid="' . $to . '"', 'columns' => 'id']);
            if (!$group) {
                return true;
            }
            $to = $group['id'];
        }

        $conversation = UserChatTop::findOne(['to_uid=' . $to . ' and owner_id=' . $uid . ' and type=' . $type]);
        $data = [];
        //以前置过顶
        if ($conversation) {
            Debug::log("1");
            $data['created'] = time();
            if (UserChatTop::updateOne($data, ['id' => $conversation['id']])) {
                return true;
            }
        } //以前没有置顶过
        else {
            $data['type'] = $type;
            $data['owner_id'] = $uid;
            $data['to_uid'] = $to;
            $data['created'] = $uid;
            $data['conversation'] = $conversation_id;
            if (UserChatTop::insertOne($data)) {
                return true;
            }
        }

        return false;
    }

    /**取消消息置顶
     * @param $uid
     * @param $to
     * @param $type
     * @return bool
     */
    public function unSetTop($uid, $type, $to)
    {
        //群聊 云信群id换成 恐龙谷平台群id
        if ($type == 2) {
            $group = Group::findOne(['yx_gid="' . $to . '"', 'columns' => 'id']);
            if (!$group) {
                return true;
            }
            $to = $group['id'];
        }
        $conversation = UserChatTop::findOne(['to_uid=' . $to . ' and owner_id=' . $uid . ' and type=' . $type, 'columns' => 'id']);
        //以前没有置过顶
        if (!$conversation) {
            return true;
        } //以前有置顶过
        if (UserChatTop::remove(['id' => $conversation['id']])) {
            return true;
        }
        return false;
    }

    /**获取置顶列表
     * @param $uid
     * @return mixed
     */
    public function topList($uid)
    {
        $list = UserChatTop::getColumn(['owner_id=' . $uid, 'columns' => 'conversation,created', 'order' => 'created desc'], 'conversation');
        return $list ? array_values($list) : [];
    }
}