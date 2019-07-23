<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/19
 * Time: 18:40
 */

namespace Multiple\Api\Controllers;


use Components\Yunxin\ServerAPI;
use Models\Group\Group;
use Models\System\SystemMessage;
use Models\User\UserAttention;
use Models\User\UserPersonalSetting;
use Services\Im\ImManager;
use Services\Im\SysMessage;
use Services\Site\CurlManager;
use Services\Site\SiteKeyValManager;
use Services\User\SystemPushManager;
use Util\Ajax;

class MessageController extends ControllerBase
{
    //系统消息 列表
    public function listAction()
    {
        $uid = $this->uid;
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $type = $this->request->get('type', 'int', 3); //3-系统通知 4-动态通知 5-新的朋友
        if (!$uid || !$page || !$limit || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $params = ['user_id=' . $uid . ' and account_type=' . $type, 'order' => 'created desc', 'columns' => 'id as message_id,account_type as type,body,extend_type,created', 'offset' => ($page - 1) * $limit, 'limit' => $limit];
        $list = SystemMessage::findList($params);
        $count = SystemMessage::dataCount($params[0]);


        $new_list = [];
        //新的朋友需要返回关系
        if ($list && $type == 5) {
            $uids = [];
            foreach ($list as &$item) {
                $body = json_decode($item['body'], true);
                if (!$body) {
                    $count--;
                    continue;
                }
                if ($attention = UserAttention::findOne(['owner_id=' . $uid . ' and user_id=' . $body['uid']])) {
                    $item['is_attention'] = 1;
                    if ($attention['enable'] == 0) {
                        $item['is_contact'] = 1;
                    } else {
                        $item['is_contact'] = 0;
                    }
                } else {
                    $item['is_attention'] = 0;
                    $item['is_contact'] = 0;
                }
                $item['uid'] = $body['uid'];
                $new_list[] = $item;
                $uids[] = $body['uid'];
            }
            $person_setting = UserPersonalSetting::getColumn(["owner_id=" . $uid . ' and user_id in(' . implode(',', $uids) . ')', 'columns' => 'mark,user_id'], 'mark', 'user_id');
            if ($person_setting) {
                foreach ($new_list as &$i) {
                    if (isset($person_setting[$i['uid']]) && $person_setting[$i['uid']]) {
                        $body = json_decode($i['body'], true);
                        $body['username'] = $person_setting[$i['uid']];
                        $i['body'] = json_encode($body, JSON_UNESCAPED_UNICODE);
                    }
                }
            }
        }
        $res = ['data_list' => $new_list, 'data_count' => $count];
        $this->ajax->outRight($res);
    }

    //删除系统消息
    public function removeAction()
    {
        $uid = $this->uid;
        $mid = $this->request->get('message_id', 'string', ''); //支持批量 单个 一键删除
        $type = $this->request->get('type', 'int', 0); //3-系统消息 4-动态通知 5-新的朋友

        if (!$uid || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($mid) {
            $where = "id in (" . $mid . ') and user_id=' . $uid . ' and account_type=' . $type;
        } else {
            $where = 'user_id=' . $uid . ' and account_type=' . $type;
        }

        $res = SystemMessage::remove($where);
        if ($res) {
            $this->ajax->outRight("删除成功", Ajax::SUCCESS_DELETE);
        }
        $this->ajax->outError(Ajax::FAIL_DELETE);
    }

    //发送消息
    public function sendAction()
    {
        $uid = $this->uid;
        $to = $this->request->get("to", 'int', 0);
        $type = $this->request->get("type", 'int', 0); //0-个人 1-群
        $msg = $this->request->get("msg");
        if (!$uid || !$to || !in_array($type, [0, 1]) || !$msg) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        if ($type == 1) {
            $group = Group::findOne(['id=' . $to . " and status=1", 'columns' => 'yx_gid']);
            if (!$group) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $to = $group['yx_gid'];
        }
        $res = ServerAPI::init()->sendMsg($uid, $type, $to, 0, ['msg' => $msg]);
        if ($res && $res['code'] == 200) {
            $this->ajax->outRight("发送成功", Ajax::SUCCESS_SUBMIT);
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, $res);
        }
    }

    //欢迎再次使用恐龙谷
    public function welcomeAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        ImManager::init()->initMsg(ImManager::TYPE_WELCOME_BACK, ['to_user_id' => $uid]);
        //推送新手教程
        SystemPushManager::init()->NewbieTutorial($uid);
        Ajax::outRight("");
    }

    //获取活动聊天室快捷回复短语
    public function getShortReplyAction()
    {

        $val = SiteKeyValManager::init()->getOneByKey('chat_room', 'shortReply');
        if ($val) {
            $arr = [];
            $val = json_decode($val['val'], true);
            foreach ($val as $k => $v) {
                $arr = array_merge($arr, $v['phrases']);
            }
        } else
            $arr = [];
        $this->ajax->outRight($arr);
    }

    //获取活动聊天室快捷回复短语 后台用
    public function getPhrasesAction()
    {
        $val = SiteKeyValManager::init()->getOneByKey('chat_room', 'shortReply');
        if ($val) {
            $reply = json_decode($val['val']);

        } else
            $reply = [];
        $this->ajax->outRight($reply);
    }

}