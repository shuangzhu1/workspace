<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/7
 * Time: 17:59
 */

namespace Multiple\Api\Controllers;


use Components\Kafka\Producer;
use Components\User\UserStatus;
use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserCountStat;
use Models\User\Users;
use Models\User\UserSetting;
use Models\User\UserTags;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\User\ContactManager;
use Util\Ajax;
use Util\Debug;

class ContactController extends ControllerBase
{
    /*--关注--*/
    public function attentionAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $source = $this->request->get('source', 'int', 1);//1-普通关注 2-扫码
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //自己不能关注自己
        if ($uid == $to_uid) {
            $this->ajax->outError(Ajax::ERROR_CANNOT_ATTENTION_SELF);
        }
        /*if (!ContactManager::init()->attention($uid, $to_uid, $source)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }*/
        if (!$user = Users::findOne(['id=' . $to_uid, 'columns' => 'status'])) {
            $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }
        if ($user['status'] == UserStatus::STATUS_DELETED) {
            $this->ajax->outError(Ajax::ERROR_SYSTEM_BLACKLIST);
        }
        //已经关注过了|//已经是好友关系
        if ($res = UserAttention::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id'])) {
            $this->ajax->outError(Ajax::ERROR_HAS_ATTENTION);
        }
        //已经拉黑对方
        if (UserBlacklist::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
            $this->ajax->outError(Ajax::ERROR_IN_BLACKLIST);
        }

        //被对方拉黑
        if (UserBlacklist::findOne('owner_id=' . $to_uid . ' and user_id=' . $uid)) {
            $this->ajax->outError(Ajax::ERROR_REFUSE_YOU_REQUEST);
        }

        $redis = $this->di->get("publish_queue");
        $redis->publish(CacheSetting::KEY_ATTENTION, json_encode(['uid' => $uid, 'to_uid' => $to_uid, 'source' => $source]));

        $robot = parse_ini_file(ROOT . '/Data/site/robot.ini', true);
        //第三方 机器人被关注
        if ($robot) {
            //第三方 游戏关注机器人 自动关注对方
            if ($this->redis->hExists("open_robot", $to_uid)) {
                //if (!empty($robot['account']['user']) && in_array($to_uid, $robot['account']['user'])) {
                //已经拉黑对方
                if (!UserBlacklist::exist('owner_id=' . $to_uid . ' and user_id=' . $uid)) {
                    //已经关注过了|//已经是好友关系
                    if (!UserAttention::findOne(['owner_id=' . $to_uid . ' and user_id=' . $uid, 'columns' => 'id'])) {
                        $redis = $this->di->get("publish_queue");
                        $redis->publish(CacheSetting::KEY_ATTENTION, json_encode(['uid' => $to_uid, 'to_uid' => $uid, 'source' => $source]));
                    }
                }
            }
        }
        //对方没有关注我
        if (!UserAttention::exist('owner_id=' . $to_uid . ' and user_id=' . $uid)) {
            $this->ajax->outRight(5);
        } else {
            $this->ajax->outRight(4);
        }

        // $this->getRelationshipAction();

        //  $this->ajax->outRight('关注成功', Ajax::SUCCESS_ATTENTION);
    }


    /*--批量关注--*/
    public function attentionBatchAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'string', '');
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!ContactManager::init()->attentionBatch($uid, $to_uid)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight("");
    }

    /*--取消关注--*/
    public function unAttentionAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!ContactManager::init()->unAttention($uid, $to_uid)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight('取消关注成功', Ajax::SUCCESS_CANCEL);
    }

    /*--解除好友关系--*/
    public function delFriendAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!ContactManager::init()->delFriend($uid, $to_uid)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight('解除成功', Ajax::SUCCESS_DELETE);
    }

    /*--移除粉丝--*/
    public function delFansAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'string', '');
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!ContactManager::init()->delFans($uid, $to_uid)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight('移除成功', Ajax::SUCCESS_REMOVE);
    }

    /*--我的关注/他的关注*/
    public function followersAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $key = $this->request->get('key', 'string', null);
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $to_uid = $to_uid == 0 ? $uid : $to_uid;
        $res = ContactManager::init()->followers($uid, $key, $to_uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    /*--我的粉丝列表--*/
    public function fansAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $key = $this->request->get('key', 'string', null);
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $to_uid = $to_uid == 0 ? $uid : $to_uid;
        $res = ContactManager::init()->fans($uid, $key, $to_uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    /*--我的好友列表--*/
    public function friendsAction()
    {
        $uid = $this->uid;
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->friends($uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    /*--加入黑名单--*/
    public function addBlacklistAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$uid == $to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->addBlacklist($uid, $to_uid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        // $this->ajax->outRight(Ajax::outRight("操作成功",Ajax::SUCCESS_));
    }

    /*--取消黑名单--*/
    public function cancelBlacklistAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->cancelBlacklist($uid, $to_uid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        // $this->ajax->outRight(Ajax::outRight("操作成功",Ajax::SUCCESS_));
    }

    /*--黑名单列表--*/
    public function blacklistAction()
    {
        $uid = $this->uid;
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->blacklist($uid, $page, $limit);
        $this->ajax->outRight($res);
        // $this->ajax->outRight(Ajax::outRight("操作成功",Ajax::SUCCESS_));
    }

    /*--特殊用户列表--*/
    public function specialAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("type");//1-不看他的动态 ;2-不允许看我动态
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->specialList($type, $uid, $page, $limit);
        $this->ajax->outRight($res);
        // $this->ajax->outRight(Ajax::outRight("操作成功",Ajax::SUCCESS_));
    }


    /*--设置为星标好友--*/
    public function setStarAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);//加为星标好友的用户id
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->setStar($uid, $to_uid);
        if ($res) {
            $this->ajax->outRight("设置成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    /*--取消星标好友--*/
    public function cancelStarAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);//加为星标好友的用户id
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->cancelStar($uid, $to_uid);
        if ($res) {
            $this->ajax->outRight("设置成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    /*--设置好友备注--*/
    public function setMarkAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);//加为星标好友的用户id
        $mark = $this->request->get('mark', 'green', '');//

        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->setMark($uid, $to_uid, $mark);
        if ($res) {
            Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_uums_update)
                ->produce(['uid' => $uid, 'to_uid' => $to_uid, 'to_uid_mark' => $mark]);
            // Request::getPost(Base::USER_INFO_UPDATE, ['uid' => $uid, 'touid' => $to_uid, 'mark' => $mark]);

            $this->ajax->outRight("设置成功", Ajax::SUCCESS_EDIT);
        }
        $this->ajax->outError(Ajax::FAIL_EDIT);
    }

    /*--请求添加为联系人--*/
    public function addContactAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);//加为星标好友的用户id
        $tip = $this->request->get('tip', 'string', '');//发送给对方的内容

        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->addContact($uid, $to_uid, $tip);
        if ($res) {
            $this->ajax->outRight("发送成功", Ajax::SUCCESS_SEND);
        }
        $this->ajax->outError(Ajax::FAIL_SEND);
    }

    /*--添加好友只搜索用户--*/
    public function searchUserAction()
    {
        $uid = $this->uid;
        $key = $this->request->get('key');//搜索关键字 ID/昵称
        if (!$uid || !$key) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $user = ContactManager::init()->searchUser($uid, $key);
        Ajax::init()->outRight($user);
    }

    /*--手机联系人匹配--*/
    public function phoneUserAction()
    {

        $uid = $this->uid;
        $phones = $this->request->get('phones', 'string', '');//手机号码集合{"phones":"18770090785,18770090876","names":"张三,李四"}
        $device_id = $this->request->get('device_id', 'string', '');//手机设备号-手机唯一
        $phone_model = $this->request->get('phone_model', 'string', '');//手机型号 如华为荣耀6， vivo R9 ，iphone 5s

        if (!$uid || !$phones) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(ContactManager::init()->phoneUser($uid, $phones, $device_id, $phone_model));
    }

    /*--获取和指定用户关系--*/
    public function getRelationshipAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        if (!$uid || !$to_uid || $uid == $to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $blacklist = UserBlacklist::findList(['(owner_id=' . $uid . ' and user_id=' . $to_uid . ') or ( owner_id=' . $to_uid . ' and user_id=' . $uid . ')', 'columns' => 'owner_id,user_id']);
        $data = 0;//陌生人
        if ($blacklist) {
            //是黑名单用户
            if ($blacklist) {
                if (count($blacklist) == 2) {
                    $data = 1;//双方拉黑
                } else {
                    $data = $blacklist[0]['owner_id'] == $uid ? 2 : 3; //我拉对方黑/对方拉我黑
                }
            }
        } else {
            if (UserContactMember::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                $data = 4;//互为联系人
            } elseif (UserAttention::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                $data = 5;//我关注了对方
            } elseif (UserAttention::exist('user_id=' . $uid . ' and owner_id=' . $to_uid)) {
                $data = 6;//对方是我的粉丝
            }
        }
        $this->ajax->outRight($data);
    }

    /*--相同关注的人--*/
    public function sameAttentionAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);

        if (!$uid || !$to_uid || $uid == $to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(ContactManager::init()->sameAttentionList($uid, $to_uid, $page, $limit));
    }

    /**
     * 新用户关注人无动态时，推荐几个有动态的用户
     */
    public function recommendAction()
    {

        $uid = $this->uid;
        //关注过的用户id
        $attention_uids = UserAttention::getColumn(['owner_id=' . $uid, 'columns' => 'user_id'], 'user_id');
        //黑名单用户id
        $blacklist = UserBlacklist::getColumn(['owner_id=' . $uid, 'columns' => 'user_id'], 'user_id');
        $not_uids = array_unique(array_merge($attention_uids, $blacklist));

        $where = "s.discuss_cnt>10 and s.fans_cnt>500 and u.user_type<>" . \Services\User\UserStatus::USER_TYPE_ROBOT;
        //剔除关注过的用户和黑名单用户
        if ($not_uids) {
            $where .= " and u.id not in(" . implode(',', $not_uids) . ')';
        }
        $list = $this->original_mysql->query("select username,u.id as uid,avatar from users as u left join user_count_stat as s on u.id=s.user_id where " . $where . " order by rand() limit 10")->fetchAll(\PDO::FETCH_ASSOC);

        $uids = array_column($list, 'uid');
        $tags = UserTags::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ') ', 'columns' => 'tags_name,user_id'], 'user_id');
        foreach ($list as &$item) {
            if (isset($tags[$item['uid']])) {
                $item['tags_name'] = $tags[$item['uid']]['tags_name'];
            } else {
                $item['tags_name'] = '';
            }
        }
        $this->ajax->outRight($list);
    }

}