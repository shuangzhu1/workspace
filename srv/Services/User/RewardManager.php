<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/27
 * Time: 9:33
 */

namespace Services\User;


use Components\Rules\Coin\PointRule;
use Models\Site\SiteGift;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussReward;
use Models\User\UserGift;
use Models\User\UserGiftLog;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserShow;
use Models\User\UserVideo;
use Models\User\UserVideoReward;
use Phalcon\Mvc\User\Plugin;
use Services\Discuss\DiscussManager;
use Services\Im\ImManager;
use Services\Site\SiteKeyValManager;
use Util\Ajax;
use Util\Debug;

class RewardManager extends Plugin
{
    const TYPE_GIFT = 1;//礼物
    const TYPE_PACKAGE = 2;//红包

    const ITEM_TYPE_DISCUSS = 'discuss';//动态
    const ITEM_TYPE_VIDEO = 'video';//附近的视频

    private static $instance = null;

    //类型对应字段
    public static $type_column = [
        self::TYPE_GIFT,
        self::TYPE_PACKAGE
    ];
    //对象
    public static $item_type = [
        self::ITEM_TYPE_DISCUSS,
        self::ITEM_TYPE_VIDEO

    ];

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * --------打赏----------
     * @param $uid --打赏人用户id
     * @param $to_uid --被打赏人用户id
     * @param $type --类型 1-礼物 2-红包
     * @param string $gift_info --礼物数据
     * @param string $package_info --红包信息
     * @param $item_type --目标对象 discuss-动态
     * @param $item_id -- 目标对象id  动态id
     * @return bool
     */
    public function to($uid, $to_uid, $type, $gift_info = '', $package_info = '', $item_type, $item_id)
    {
        //礼物
        if ($type == self::TYPE_GIFT) {
            /*   $gift_info = json_decode(htmlspecialchars_decode($gift_info), true);
               if (!$gift_info) {
                   Ajax::outError(Ajax::INVALID_PARAM);
               }*/
            $count = 1;// $this->request->get("count", 'int', 1);//购买数量
            $gift = SiteGift::findOne(['id=' . $gift_info['id'], 'columns' => 'id,thumb,name,enable,is_vip,coins,charm,animation']);
            /* //礼物不存在
             if (!$gift) {
                 Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
             }
             //礼物已下架
             if ($gift['enable'] != 1) {
                 Ajax::outError(Ajax::ERROR_GIFT_OFF);
             }*/
            $gift_info = [
                'gift_id' => $gift_info['id'],
                'name' => $gift['name'],
                'thumb' => $gift['thumb'],
                'is_vip' => $gift['is_vip'],
                'coins' => $gift['coins'],
                'charm' => $gift['charm'],
                'animation' => $gift['animation'],
            ];
            try {
                $this->db->begin();
                $this->original_mysql->begin();
                //免费
                if ($gift['coins'] == 0) {

                } else {
                    /*  $users = Users::findOne(["id=" . $uid, 'columns' => 'coins']);
                      //龙豆不足
                      if ($users['coins'] < ($gift['coins'])) {
                          Ajax::outError(Ajax::ERROR_COIN_NOT_ENOUGH);
                      }*/
                    if (!PointRule::init()->consumeCoin($uid, $gift['coins'], PointRule::BEHAVIOR_CONSUME_GIFT, ['uid' => $uid, 'to_uid' => $to_uid, 'gid' => 0, 'count' => $count, 'gift_id' => $gift_info['gift_id'], 'gift_name' => $gift_info['name']])) {
                        throw  new \Exception("消费记录失败");
                    }
                }
                //记录日志
                $gift_log = new UserGiftLog();
                if (!$gift_log->insertOne(['owner_id' => $uid, 'user_id' => $to_uid, 'gift_id' => $gift_info['gift_id'], 'con_count' => $count, 'created' => time()])) {
                    throw  new \Exception("日志记录失败");
                }
                if (!SiteGift::updateOne('use_count=use_count+1', 'id=' . $gift_info['gift_id'])) {
                    throw  new \Exception("更新次数失败");
                }
                $user_gift = UserGift::findOne(['user_id=' . $to_uid . " and gift_id=" . $gift_info['gift_id'], 'columns' => 'id']);
                if ($user_gift) {
                    if (!UserGift::updateOne('own_count=own_count+' . $count, 'id=' . $user_gift['id'])) {
                        throw  new \Exception("更新用户拥有礼物数失败");
                    }
                } else {
                    if (!UserGift::insertOne(['user_id' => $to_uid, 'gift_id' => $gift_info['gift_id'], 'own_count' => $count])) {
                        throw  new \Exception("更新用户拥有礼物数失败");
                    }
                }
                if ($gift['charm'] > 0) {
                    if (!UserProfile::updateOne("charm=charm+" . $gift['charm'], 'user_id=' . $to_uid)) {
                        throw  new \Exception("更新魅力值失败");
                    }
                    if (!UserShow::updateOne("current_month_charm=current_month_charm+" . $gift['charm'], 'user_id=' . $to_uid)) {
                        throw  new \Exception("userShow更新魅力值失败");
                    }
                }

                //动态
                if ($item_type == self::ITEM_TYPE_DISCUSS) {
                    /* if (!SocialDiscuss::exist("id=" . $item_id . " and status=" . DiscussManager::STATUS_NORMAL)) {
                         Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
                     }*/
                    $data = [
                        'discuss_id' => $item_id,
                        'user_id' => $uid,
                        'owner_id' => $to_uid,
                        'type' => $type,
                        'extra' => json_encode(['id' => $gift_info['gift_id'], 'animation' => $gift_info['animation'], 'name' => $gift_info['name'], 'thumb' => $gift_info['thumb']], JSON_UNESCAPED_UNICODE),
                        'created' => time()
                    ];
                    $reward_modal = new SocialDiscussReward();
                    $modal = new SocialDiscuss();
                    $msg_type = ImManager::TYPE_REWARD;

                }  //附近的视频
                else if ($item_type == self::ITEM_TYPE_VIDEO) {
                    $data = [
                        'video_id' => $item_id,
                        'user_id' => $uid,
                        'owner_id' => $to_uid,
                        'type' => $type,
                        'extra' => json_encode(['id' => $gift_info['gift_id'], 'animation' => $gift_info['animation'], 'name' => $gift_info['name'], 'thumb' => $gift_info['thumb']], JSON_UNESCAPED_UNICODE),
                        'created' => time()
                    ];
                    $reward_modal = new UserVideoReward();
                    $modal = new UserVideo();
                    $msg_type = ImManager::TYPE_REWARD_VIDEO;
                }
                if (!empty($reward_modal)) {
                    if (!$reward_modal::insertOne($data)
                    ) {
                        throw  new \Exception("打赏表插入数据失败:" . var_export($data, true));
                    }
                    //更新打赏量
                    $modal::updateOne("reward_cnt=reward_cnt+1", 'id=' . $item_id);

                    //检测是否关闭打赏功能
                    //   $value = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_APP_SETTING, "setting");
                    //  $value = json_decode($value, true);

                    //    if ($value['reward']) {
                    //发送消息
                    $tag = $gift_info['name'];
                    ImManager::init()->initMsg($msg_type, ['user_id' => $uid, 'to_user_id' => $to_uid, 'tag' => $tag, 'info' => ['discuss_id' => $item_id, 'type' => 1, 'gift_info' => ['name' => $gift_info['name'], 'animation' => $gift_info['animation'], 'thumb' => $gift_info['thumb']]]]);
                }
                //接到礼物的人转化为龙币
                if ($gift_info['coins'] > 0) {
                    $res = $this->changeCoin($gift_info['coins'], $uid, $to_uid, ['id' => $gift_info['gift_id'], 'beans' => $gift_info['coins']]);
                    if (!$res) {
                        throw  new \Exception("礼物兑换为龙币失败:");
                    }
                }

                $this->db->commit();
                $this->original_mysql->commit();


                return true;
                //  Ajax::outRight("打赏成功");
            } catch (\Exception $e) {
                $this->db->rollback();
                Debug::log("打赏礼物失败:" . var_export($e->getMessage(), true), 'error');
                return false;
                //Ajax::outError(Ajax::FAIL_HANDLE);
            }
        }
        //红包
        if ($type == self::TYPE_PACKAGE) {

            /*   $package_info = json_decode(htmlspecialchars_decode($package_info), true);
               if (!$package_info) {
                   Ajax::outError(Ajax::INVALID_PARAM);
               }*/
            try {
                $this->db->begin();
                //动态
                if ($item_type == self::ITEM_TYPE_DISCUSS) {
                    /*  if (!SocialDiscuss::exist("id=" . $item_id . " and status=" . DiscussManager::STATUS_NORMAL)) {
                          Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
                      }*/
                    $reward_modal = new SocialDiscussReward();
                    $modal = new SocialDiscuss();
                    $data = [
                        'discuss_id' => $item_id,
                        'user_id' => $uid,
                        'owner_id' => $to_uid,
                        'type' => $type,
                        'extra' => json_encode(['id' => $package_info['id'], 'money' => $package_info['money']], JSON_UNESCAPED_UNICODE),
                        'created' => time()
                    ];
                    $msg_type = ImManager::TYPE_REWARD;

                } //视频
                else if ($item_type == self::ITEM_TYPE_VIDEO) {
                    $reward_modal = new UserVideoReward();
                    $modal = new UserVideo();
                    $data = [
                        'video_id' => $item_id,
                        'user_id' => $uid,
                        'owner_id' => $to_uid,
                        'type' => $type,
                        'extra' => json_encode(['id' => $package_info['id'], 'money' => $package_info['money']], JSON_UNESCAPED_UNICODE),
                        'created' => time()
                    ];
                    $msg_type = ImManager::TYPE_REWARD_VIDEO;
                }

                if (!empty($reward_modal)) {
                    if (!$reward_modal::insertOne($data)
                    ) {
                        throw  new \Exception("打赏表插入数据失败:" . var_export($data, true));
                    }
                    //更新打赏量
                    $modal::updateOne("reward_cnt=reward_cnt+1", 'id=' . $item_id);
                    //检测是否关闭打赏功能
                    $value = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_APP_SETTING, "setting");
                    $value = json_decode($value, true);

                    if ($value['reward']) {
                        //发送消息
                        $tag = round($package_info['money'] / 100, 2) . '元红包';

                        ImManager::init()->initMsg($msg_type, ['user_id' => $uid, 'to_user_id' => $to_uid, 'tag' => $tag, 'info' => ['discuss_id' => $item_id, 'type' => 2, 'package_info' => ['money' => $package_info['money']]]]);
                    }
                }
                $this->db->commit();
                //   Ajax::outRight("打赏成功");
                return true;
            } catch (\Exception $e) {
                $this->db->rollback();
                Debug::log("打赏红包失败:" . var_export($e->getMessage(), true), 'error');
                return false;
                //Ajax::outError(Ajax::FAIL_HANDLE);
            }
        }
    }

    /**接收礼物的人龙币增加
     * @param $coins
     * @param $uid
     * @param $to_uid
     * @param $gift_info
     * @return bool
     */
    public function changeCoin($coins, $uid, $to_uid, $gift_info)
    {
        try {
            $val = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "coin_setting");
            if ($val && $val['rate'] > 0) {
                $coins = floor($coins * $val['rate'] / 100);
                if ($coins > 0) {
                    $gragonCoin = DragonCoin::getInstance();
                    $res = $gragonCoin->setUid($to_uid)
                        ->setInOut(DragonCoin::IN_OUT_IN)
                        ->setType(DragonCoin::TYPE_RECEIVE_GIFT)
                        ->setVal($coins)
                        ->execute('', json_encode(['uid' => $uid, 'gift_id' => $gift_info['id'], 'beans' => $gift_info['beans']]));
                    if (!$res) {
                        $msg = $gragonCoin->getMsg();
                        throw  new \Exception("收到礼物 转换龙币失败：uid:" . $to_uid . ",coins:" . $coins . ",错误信息:" . var_export($msg, true));
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }


    }

    /** 打赏列表
     * @param $uid
     * @param $item_type
     * @param $item_id
     * @param $page
     * @param $limit
     * @return array
     */
    public function list($uid, $item_type, $item_id, $page = 1, $limit)
    {
        $data = ['data_list' => []];
        $list = [];
        //动态
        if ($item_type == self::ITEM_TYPE_DISCUSS) {
            $list = SocialDiscussReward::findList(['discuss_id=' . $item_id, 'columns' => 'extra,type,created,user_id as uid', 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        } //附近的视频
        elseif ($item_type == self::ITEM_TYPE_VIDEO) {
            $list = UserVideoReward::findList(['video_id=' . $item_id, 'columns' => 'extra,type,created,user_id as uid', 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        }
        if ($list) {
            $uids = array_unique(array_column($list, 'uid'));
            $user_infos = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id as uid,sex,grade,username,avatar'], 'uid');
            $person_setting = UserPersonalSetting::getColumn(['owner_id=' . $uid . " and user_id in (" . implode(',', $uids) . ")", 'columns' => 'mark,user_id,owner_id'], 'mark', 'user_id');

            foreach ($list as &$item) {

                $item['user_info'] = $user_infos[$item['uid']];
                if ($person_setting && !empty($person_setting[$item['uid']])) {
                    $item['user_info']['username'] = $person_setting[$item['uid']];
                }
                if ($item['type'] == self::TYPE_GIFT) {
                    $item['gift_info'] = json_decode($item['extra'], true);
                } else {
                    $item['package_info'] = json_decode($item['extra'], true);
                    unset($item['package_info']['id']);
                }
                unset($item['uid']);
                unset($item['extra']);
                $data['data_list'][] = $item;
            }
        }
        return $data;
    }


}