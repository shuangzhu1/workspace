<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/27
 * Time: 9:05
 */

namespace Multiple\Api\Controllers;

use Models\Site\SiteGift;
use Models\Social\SocialDiscuss;
use Models\User\UserNoviceGift;
use Models\User\Users;
use Models\User\UserVideo;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\Site\CashRewardManager;
use Services\User\RewardManager;
use Util\Ajax;
use Util\Debug;

class RewardController extends ControllerBase
{
    //打赏
    public function toAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0); //被打赏的用户id
        $type = $this->request->get("type", 'int', 1);//1-礼物 2-红包
        $gift_info = $this->request->get("gift_info", 'string', '');//礼物信息{"id":"23988"}
        $package_info = $this->request->get("package_info", 'string', '');//红包信息{"id":"672346534","money":"10000"}
        $item_type = $this->request->get("item_type", 'string', 'discuss');//被打赏的对象类型:discuss-动态 video-视频
        $item_id = $this->request->get("item_id", 'int', 0);//对象id：动态id 视频id
        if (!$uid
            || !$to_uid
            || !$type
            || !in_array($type, RewardManager::$type_column)
            || (!$gift_info && !$package_info)
            || !$item_type
            || !in_array($item_type, RewardManager::$item_type)
            || !$item_id
        ) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($uid == $to_uid) {
            $this->ajax->outError(Ajax::ERROR_TARGET_CAN_NOT_YOURSELF);
        }
        //礼物
        if ($type == RewardManager::TYPE_GIFT) {
            $gift_info = json_decode(htmlspecialchars_decode($gift_info), true);
            if (!$gift_info) {
                Ajax::outError(Ajax::INVALID_PARAM);
            }
            $gift = SiteGift::findOne(['id=' . $gift_info['id'], 'columns' => 'enable,coins']);
            //礼物不存在
            if (!$gift) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            //礼物已下架
            if ($gift['enable'] != 1) {
                Ajax::outError(Ajax::ERROR_GIFT_OFF);
            }
            //龙豆不足
            if ($gift['coins'] !== 0) {
                $users = Users::findOne(["id=" . $uid, 'columns' => 'coins']);
                if ($users['coins'] < ($gift['coins'])) {
                    Ajax::outError(Ajax::ERROR_COIN_NOT_ENOUGH);
                }
            }
        }   //红包
        else if ($type == RewardManager::TYPE_PACKAGE) {
            $package_info = json_decode(htmlspecialchars_decode($package_info), true);
            if (!$package_info) {
                Ajax::outError(Ajax::INVALID_PARAM);
            }
        }
        if ($item_type == RewardManager::ITEM_TYPE_DISCUSS) {
            if (!SocialDiscuss::exist("id=" . $item_id . " and user_id=" . $to_uid)) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
        } else if ($item_type == RewardManager::ITEM_TYPE_VIDEO) {
            if (!UserVideo::exist("id=" . $item_id . " and user_id=" . $to_uid)) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
        }
        $redis = $this->di->get("publish_queue");
        $redis->publish(CacheSetting::KEY_REWARD, json_encode(['uid' => $uid, 'to_uid' => $to_uid, 'type' => $type, 'gift_info' => $gift_info, 'package_info' => $package_info, 'item_type' => $item_type, 'item_id' => $item_id]));
        // RewardManager::getInstance()->to($uid, $to_uid, $type, $gift_info, $package_info, $item_type, $item_id);
        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);

    }

    //打赏列表
    public function listAction()
    {
        $uid = $this->uid;
        $item_type = $this->request->get("item_type", 'string', 'discuss');//被打赏的对象类型:discuss-动态 video-附近视频
        $item_id = $this->request->get("item_id", 'int', 0);//对象id：动态id 视频id
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        if (!$uid || !$item_type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = RewardManager::getInstance()->list($uid, $item_type, $item_id, $page, $limit);
        Ajax::outRight($res);
    }

    //开奖
    public function drawAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("type", 'int', 2);//2-分享
        $item_id = $this->request->get("item_id", "string", "");

        if (!$uid || !$item_id || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $CashRewardManager = new CashRewardManager();
        $res = $CashRewardManager->draw($uid, $type, $item_id);
        $res = $res ? $res : (object)[];
        $this->ajax->outRight($res);
    }

    //是否可领新手礼包
    public function noviceCheckAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (UserNoviceGift::exist('user_id=' . $uid)) {
            $this->ajax->outRight(0);
        }
        $this->ajax->outRight(1);
    }

    //领取新手礼包
    public function pickNoviceGiftAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!UserNoviceGift::exist('user_id=' . $uid)) {
            try {
                $coin = 15;
                $this->di->getShared("original_mysql")->begin();
                $id = UserNoviceGift::insertOne(['user_id' => $uid, 'created' => time(), 'gift_info' => json_encode(['type' => 'diamond', 'val' => $coin]), 'has_pick' => 1]);
                if ($id) {
                    $record = [
//                        "payid" => "Novice_" . $id,             // 交易id
                        "coin_type" => 0,           // 虚拟币类型【0红包钻石...其他保留】
                        "coin" => $coin,               // 本次记录变动的钻石
                        "type" => 0,                // 【0收入、1支出】
                        "desc" => "新手礼包",  // 流水描述
                        "created" => time(),    // 时间
                        "way" => 6,                 // 渠道，对于龙钻(coin_type=0)充值，1表示ios内购、2表示支付宝、3表示微信、4表示余额、5表示公众号、6表示系统赠送奖励；对于收益(coin_type=2)来源，1表示恐龙谷活动、2表示广场红包
                        "extend" => ""              // 拓展
                    ];
                    $res = Request::getPost(Request::VIRTUAL_COIN_UPDATE, ['uid' => intval($uid), 'coin_type' => 0, 'coin_num' => $coin, 'record' => json_encode($record, JSON_UNESCAPED_UNICODE)]);
                    if ($res && $res['curl_is_success']) {
                        $content = json_decode($res['data'], true);
                        if (empty($content['code']) || $content['code'] != 200) {
                            throw new \Exception("更新虚拟币失败：" . var_export($content, true));
                        }
                    } else {
                        throw new \Exception("更新虚拟币失败");
                    }
                }
                $this->di->getShared("original_mysql")->commit();
                $this->ajax->outRight("领取成功");
            } catch (\Exception $e) {
                $this->di->getShared("original_mysql")->rollback();
                $this->ajax->outError(Ajax::FAIL_PICK, var_export($e->getMessage(), true));
            }


        }
    }


}