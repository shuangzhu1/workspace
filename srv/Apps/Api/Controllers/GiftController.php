<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/27
 * Time: 10:58
 */

namespace Multiple\Api\Controllers;


use Components\Rules\Coin\PointRule;
use Models\Group\Group;
use Models\Site\SiteGift;
use Models\User\UserBlacklist;
use Models\User\UserGift;
use Models\User\UserGiftLog;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserShow;
use Services\Im\ImManager;
use Services\Site\SiteKeyValManager;
use Services\User\DragonCoin;
use Services\User\RewardManager;
use Util\Ajax;
use Util\Debug;

class GiftController extends ControllerBase
{
    //送礼物
    public function givingAction()
    {

        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0);//送给谁
        $gid = $this->request->get("gid", 'int', 0);//群id
        $count = 1;// $this->request->get("count", 'int', 1);//购买数量
        $gift_id = $this->request->get("gift_id", 'int', 0);//礼物id
        if (!$uid || !$to_uid || $count < 1 || !$gift_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($uid == $to_uid) {
            $this->ajax->outError(Ajax::ERROR_TARGET_CAN_NOT_YOURSELF);
        }
        if ($count > 5) {
            $this->ajax->outError(Ajax::ERROR_GIFT_BEYOND_LIMIT);
        }
        $gift = SiteGift::findOne(['id=' . $gift_id, 'columns' => 'id,thumb,name,enable,is_vip,coins,charm,animation']);
        //礼物不存在
        if (!$gift) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //礼物已下架
        if ($gift['enable'] != 1) {
            $this->ajax->outError(Ajax::ERROR_GIFT_OFF);
        }
        if ($gid) {
            $group = Group::findOne(['id=' . $gid, 'columns' => 'yx_gid']);
            if (!$group) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
        }
        //对方在你的黑名单列表中
        if (UserBlacklist::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid])) {
            $this->ajax->outError(Ajax::ERROR_IN_BLACKLIST);
        }
        //对方已把你拉黑
        if (UserBlacklist::findOne(['user_id=' . $uid . ' and owner_id=' . $to_uid])) {
            $this->ajax->outError(Ajax::ERROR_REFUSE_YOU_REQUEST);
        }

        //vip检测 todo

        $gift_info = [
            'gift_id' => $gift_id,
            'name' => $gift['name'],
            'thumb' => $gift['thumb'],
            'is_vip' => $gift['is_vip'],
            'coins' => $gift['coins'],
            'charm' => $gift['charm'],
            'animation' => $gift['animation']
        ];
        try {
            $this->db->begin();
            $this->original_mysql->begin();
            //免费
            if ($gift['coins'] == 0) {

            } else {
                $users = Users::findOne(["id=" . $uid, 'columns' => 'coins']);
                //龙豆不足
                if ($users['coins'] < ($gift['coins'] * $count)) {
                    $this->ajax->outError(Ajax::ERROR_COIN_NOT_ENOUGH);
                }
                if (!PointRule::init()->consumeCoin($uid, $gift['coins'] * $count, PointRule::BEHAVIOR_CONSUME_GIFT, ['uid' => $uid, 'to_uid' => $to_uid, 'gid' => $gid, 'count' => $count, 'gift_id' => $gift_id, 'gift_name' => $gift_info['name']])) {
                    throw  new \Exception("消费记录失败");
                }
            }
            //记录日志
            $gift_log = new UserGiftLog();
            if (!$gift_log->insertOne(['owner_id' => $uid, 'user_id' => $to_uid, 'gift_id' => $gift_id, 'con_count' => $count, 'created' => time(), 'charm' => $gift['charm']])) {
                throw  new \Exception("日志记录失败");
            }
            if (!SiteGift::updateOne('use_count=use_count+1', 'id=' . $gift_id)) {
                throw  new \Exception("更新次数失败");
            }
            $user_gift = UserGift::findOne(['user_id=' . $to_uid . " and gift_id=" . $gift_id, 'columns' => 'id']);
            if ($user_gift) {
                if (!UserGift::updateOne('own_count=own_count+' . $count, 'id=' . $user_gift['id'])) {
                    throw  new \Exception("更新用户拥有礼物数失败");
                }
            } else {
                if (!UserGift::insertOne(['user_id' => $to_uid, 'gift_id' => $gift_id, 'own_count' => $count])) {
                    throw  new \Exception("更新用户拥有礼物数失败");
                }
            }
//            if ($gift['charm'] > 0) {
//                if (!UserProfile::updateOne("charm=charm+" . $gift['charm'], 'user_id=' . $to_uid)) {
//                    throw  new \Exception("更新魅力值失败");
//                }
//                if (!UserShow::updateOne("charm=charm+" . $gift['charm'] . ",current_month_charm=current_month_charm+" . $gift['charm'], 'user_id=' . $to_uid)) {
//                    throw  new \Exception("userShow更新魅力值失败");
//                }
//            }
            if ($gift['coins'] > 0) {
                $res = RewardManager::getInstance()->changeCoin($gift['coins'], $uid, $to_uid, ['id' => $gift_id, 'beans' => $gift['coins']]);
                if (!$res) {
                    throw  new \Exception("礼物兑换为龙币失败:");
                }
            }
            if (!ImManager::init()->initMsg(ImManager::TYPE_GIFT, ['gid' => $gid, 'yx_gid' => $gid ? $group['yx_gid'] : '', 'user_id' => $uid, 'to_user_id' => $to_uid, 'gift_info' => $gift_info])) {
                throw  new \Exception("云信发送消息失败");
            }

            $this->db->commit();
            $this->original_mysql->commit();
            $this->ajax->outRight("发送成功");
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();
            Debug::log("送礼物失败:" . var_export($e->getMessage(), true), 'error');
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
    }

    //礼物列表
    public function listAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);

        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //vip处理 todo
        if ($page && $limit) {
            $res = SiteGift::findList(['enable=1', 'order' => 'is_recommend desc,coins desc,use_count desc', 'columns' => 'id as gift_id,name,thumb,coins,is_vip,is_recommend,use_count,charm,animation', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        } else {
            $res = SiteGift::findList(['enable=1', 'order' => 'is_recommend desc,coins desc,use_count desc', 'columns' => 'id as gift_id,name,thumb,coins,is_vip,is_recommend,use_count,charm,animation']);
        }
        if ($res) {
            foreach ($res as &$item) {
                unset($item['is_recommend']);
                unset($item['use_count']);
            }
        }
        Ajax::outRight(['data_list' => $res]);
    }

    //礼物详情
    public function detailAction()
    {
        $uid = $this->uid;
        $gift_id = $this->request->get("gift_id", 'int', 0);
        if (!$uid || !$gift_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SiteGift::findOne(['id=' . $gift_id, 'columns' => 'id as gift_id,name,thumb,coins,is_vip,is_recommend,use_count,charm,animation']);
        Ajax::outRight($res ? $res : (object)[]);
    }

    //收到的礼物
    public function receivedAction()
    {
        $uid = $this->uid;
        $to_id = $this->request->get("to_uid", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("page", 'int', 0);

        if (!$uid || !$to_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($page >= 1) {
            $res = UserGift::findList(['user_id=' . $to_id, 'order' => 'id desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'columns' => 'gift_id,own_count']);
        } else {
            $res = UserGift::findList(['user_id=' . $to_id, 'order' => 'id desc', 'columns' => 'gift_id,own_count']);
        }
        $data_count = UserGift::dataCount('user_id=' . $to_id);
        $count = 0;
        if ($res) {
            $gift_ids = array_unique(array_column($res, 'gift_id'));
            $gift = SiteGift::getByColumnKeyList(['id in (' . implode(',', $gift_ids) . ')', 'columns' => 'thumb,is_vip,enable,coins,name,charm,animation,id'], 'id');
            foreach ($res as &$item) {
                $item['thumb'] = $gift[$item['gift_id']]['thumb'];
                $item['is_vip'] = $gift[$item['gift_id']]['is_vip'];
                $item['enable'] = $gift[$item['gift_id']]['enable'];
                $item['coins'] = $gift[$item['gift_id']]['coins'];
                $item['name'] = $gift[$item['gift_id']]['name'];
                $item['charm'] = $gift[$item['gift_id']]['charm'];
                $item['animation'] = $gift[$item['gift_id']]['animation'];
                $count += $item['own_count'];
            }
        }
        $this->ajax->outRight(['data_list' => $res, 'data_count' => $count]);
    }

    //
    public function historyAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("type", 'int', 1); //1-收到的 2-送出的
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $to_uid=$this->request->get("to_uid",'int',0);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if(!$to_uid){
            $to_uid=$uid;
        }
        //收到的礼物
        if ($type == 1) {
            $res = UserGiftLog::findList(['user_id=' . $to_uid, 'limit' => $limit, 'offset' => ($page - 1) * $limit, 'order' => 'created desc', 'columns' => 'gift_id,owner_id as uid,created,charm']);
        } //送出的礼物
        else {
            $res = UserGiftLog::findList(['owner_id=' . $to_uid, 'limit' => $limit, 'offset' => ($page - 1) * $limit, 'order' => 'created desc', 'columns' => 'gift_id,user_id as uid,created,charm']);
        }

        if ($res) {
            $gift_ids = array_unique(array_column($res, 'gift_id'));
            $gift = SiteGift::getByColumnKeyList(['id in (' . implode(',', $gift_ids) . ')'], 'id');
            $uids = implode(',', array_unique(array_column($res, 'uid')));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'user_id as uid,avatar,username'], 'uid');
            $person_setting = UserPersonalSetting::getColumn(['owner_id=' . $uid . " and user_id in (" . $uids . ")", 'columns' => 'mark,user_id,owner_id'], 'mark', 'user_id');
            foreach ($res as &$item) {
                $item['thumb'] = $gift[$item['gift_id']]['thumb'];
                $item['name'] = $gift[$item['gift_id']]['name'];
                $item['animation'] = $gift[$item['gift_id']]['animation'];
                $item['user_info'] = ['avatar' => $users[$item['uid']]['avatar'], 'username' => ($person_setting && !empty($person_setting[$item['uid']])) ? $person_setting[$item['uid']] : $users[$item['uid']]['username']];
            }
        }
        $this->ajax->outRight(['data_list' => $res]);
    }

}