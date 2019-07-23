<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/9
 * Time: 12:03
 */

namespace Services\Vip;


use Components\Kafka\Producer;
use Components\Yunxin\ServerAPI;
use Models\Statistics\VipDayStat;
use Models\User\UserProfile;
use Models\Vip\VipOrder;
use Models\Vip\VipPrivileges;
use Services\Im\ImManager;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Util\Ajax;
use Util\Debug;

class VipCore extends AbstractVip
{
    private static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**开通vip
     * @return bool
     */
    public function open()
    {
        if (!$this->uid) {
            $this->setMsg("uid不能为空");
            return false;
        }
        if (!$this->month) {
            $this->setMsg("month不能为空");
            return false;
        }
        $config = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "vip_privilege");
        if (!$config) {
            $this->setMsg("暂不支持vip");
            return false;
        }
        $new_config = json_decode($config, true);
        $this->config = $new_config;

        if (!isset($new_config['price_detail'][$this->month])) {
            $this->setMsg("暂不支持该月份的vip购买");
            return false;
        }

        //已经是会员了 且还未到期
        if (VipOrder::exist("user_id=" . $this->uid . " and status=1")) {
            $this->setMsg("已经是会员");
            return false;
        }

        $money = $new_config['price_detail'][$this->month]['money'];//总金额
        $this->money = intval($money);

        //ios 不送龙钻【龙钻必须走内购】
        if (client_type != 'ios') {
            $diamond = $new_config['price_detail'][$this->month]['diamond'];//赠送的龙钻数
            $this->diamond = intval($diamond);
        }


        $time = time();
        $start_day = date('Ymd', $time);
        $end_date = self::getMonthDay($start_day, $this->month, '');
        $vip_order = [
            'month' => $this->month,
            'user_id' => $this->uid,
            'created' => $time,
            'privileges' => $config,
            'end_day' => $end_date,
            'start_day' => $start_day,
            'deadline' => strtotime($end_date) + 86400,
            'money' => $money
        ];
        try {
            $this->original_mysql->begin();
            //创建订单
            $res = VipOrder::insertOne($vip_order);
            if (!$res) {
                throw new \Exception("创建vip订单失败");
            }
            //更新 用户表 设置为vip
            $res = UserProfile::updateOne(['is_vip' => 1], 'user_id=' . $this->uid);
            if (!$res) {
                throw new \Exception("更新用户状态失败");
            }
            // 更新用户vip权限 存在数据
            if (VipPrivileges::exist("user_id=" . $this->uid)) {
                $res = VipPrivileges::updateOne([
                    'package_pick_count' => $this->config['package_pick_count'],
                    'add_group_count' => $this->config['add_group_count'],
                    'group_member_count' => $this->config['group_member_count'],
                    'shop_visitor' => $this->config['shop_visitor'],
                    'user_visitor' => $this->config['user_visitor'],
                    'enable' => 1,
                    'deadline' => $vip_order['deadline'],
                    'modify' => $time
                ], "user_id=" . $this->uid);
            } else {
                $res = VipPrivileges::insertOne([
                    'package_pick_count' => $this->config['package_pick_count'],
                    'add_group_count' => $this->config['add_group_count'],
                    'group_member_count' => $this->config['group_member_count'],
                    'shop_visitor' => $this->config['shop_visitor'],
                    'user_visitor' => $this->config['user_visitor'],
                    'enable' => 1,
                    'deadline' => $vip_order['deadline'],
                    'created' => $time,
                    'user_id' => $this->uid
                ]);
            }
            if (!$res) {
                throw new \Exception("更新用户权限失败");
            }
            //余额扣钱
            if (!$this->deductMoney()) {
                throw new \Exception("");
            }
            //增加红包领取次数
            // $this->addPackageCount();

            //赠送龙钻
            $this->donateDiamond();

            //增加创建群聊个数
            // $this->addCreateGroupCount();

            //更新云信用户信息
            ServerAPI::init()->updateUinfo($this->uid, '', '', '', '', '', '', '', json_encode(['is_vip' => 1]));

            $this->original_mysql->commit();

            //更新uums缓存
            $this->updateCache($this->uid, 1);

            //临时统计
            $this->setStat(3);

            //发送消息
            ImManager::init()->initMsg(ImManager::TYPE_OTHER, ['content' => '恭喜您,VIP购买成功']);

            return true;
        } catch (\Exception $e) {
            if ($msg = $e->getMessage()) {
                $this->setMsg($msg);
            }
            $this->original_mysql->rollback();
            return false;
        }

    }

    /**
     *vip续费
     */
    public function renew()
    {
        if (!$this->uid) {
            $this->setMsg("uid不能为空");
            return false;
        }
        if (!$this->month) {
            $this->setMsg("month不能为空");
            return false;
        }
        $config = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "vip_privilege");
        if (!$config) {
            $this->setMsg("暂不支持vip");
            return false;
        }
        $new_config = json_decode($config, true);
        $this->config = $new_config;

        if (!isset($new_config['price_detail'][$this->month])) {
            $this->setMsg("暂不支持该月份的vip续费");
            return false;
        }

        //会员已经过期 请重新申请
        if (!$old_vip = VipOrder::findOne(["user_id=" . $this->uid . " and status=1", 'columns' => 'id,start_day,end_day,month'])) {
            return $this->open();
            $this->setMsg("会员已过期，请重新申请");
            return false;
        }

        $money = $new_config['price_detail'][$this->month]['money'];//总金额
        $this->money = intval($money);

        //ios不送龙钻 【龙钻必须走内购】
        if (client_type != 'ios') {
            $diamond = $new_config['price_detail'][$this->month]['diamond'];//赠送的龙钻数
            $this->diamond = intval($diamond);
        }


        $time = time();
        $start_day = $old_vip['start_day'];

        $end_date = date('Ymd', strtotime($old_vip['end_day']) + 86400);

        $end_date = self::getMonthDay($end_date, $this->month, '');

        $vip_order = [
            'month' => $this->month,
            'user_id' => $this->uid,
            'created' => $time,
            'privileges' => $config,
            'end_day' => $end_date,
            'start_day' => $start_day,
            'deadline' => strtotime($end_date) + 86400,
            'is_renew' => 1,
            'money' => $money
        ];
        try {
            $this->original_mysql->begin();
            //创建订单
            $res = VipOrder::insertOne($vip_order);
            if (!$res) {
                throw new \Exception("创建vip订单失败");
            }
            //把之前的订单置为续费
            VipOrder::updateOne(['status' => self::STATUS_TRANSFER], 'id=' . $old_vip['id']);

            //更新配置
            VipPrivileges::updateOne([
//                'package_pick_cnt' => $this->config['package_pick_count'],
//                'add_group_cnt' => $this->config['add_group_count'],
//                'group_member_cnt' => $this->config['group_member_count'],
//                'shop_visitor' => $this->config['shop_visitor'],
//                'user_visitor' => $this->config['user_visitor'],
                'modify' => $time,
                'deadline' => $vip_order['deadline']
            ], "user_id=" . $this->uid);

            //余额扣钱
            if (!$this->deductMoney()) {
                throw new \Exception("");
            }
            //赠送龙钻
            $this->donateDiamond();

            $this->original_mysql->commit();

            //临时统计
            $this->setStat(4);

            //发送消息
            ImManager::init()->initMsg(ImManager::TYPE_OTHER, ['content' => '恭喜您,VIP续费成功']);
            return true;
        } catch (\Exception $e) {
            if ($msg = $e->getMessage()) {
                $this->setMsg($msg);
            }
            $this->original_mysql->rollback();
            return false;
        }


    }

    /**余额扣钱
     * @return bool
     */
    private function deductMoney()
    {
        if ($this->money == 0) {
            return true;
        }
        #内购#
        if (is_r) {
            return true;
        }
        //----转账调用----
        $transfer_data = [
            'uid' => intval($this->uid),
            'type' => 1,
            'sub_type' => 12,
            'money' => $this->money,
            "transferway" => "",
            "description" => "vip 消费",
            "created" => time(),
            "out_payid" => '',
            'payid' => ""
        ];
        $res = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
            'uid' => intval($this->uid),
            'to_uid' => Request::$system_money_account,
            'money' => $this->money,
            'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
        ], false);
        if ($res && $res['curl_is_success']) {
            $content = json_decode($res['data'], true);
            if (empty($content['code']) || $content['code'] != 200) {
                if ($content['code'] == 501) {
                    $this->setMsg("余额不足", Ajax::ERROR_MONEY_NOT_ENOUGH);
                    return false;
                } else {
                    $this->setMsg("余额扣除失败:" . var_export($content, true));
                    Debug::log(var_export($res, true), 'vip_error');
                    return false;
                }
            }
        } else {
            $this->setMsg("余额扣除请求失败");
            return false;
        }
        return true;
    }

    /**
     * 增加广场红包领取次数
     */
    public function addPackageCount()
    {
        if ($this->config['package_pick_count']) {
            //更新领取红包个数
            $redis = $this->di->getShared("redis");
            $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, $this->uid, $this->config['package_pick_count']);
            if (device_id) {
                $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, device_id, $this->config['package_pick_count']);
            }
        }
    }

    /**
     * 增加创建群聊个数
     */
    public function addCreateGroupCount()
    {
        if ($this->config['add_group_count']) {
            //更新添加群聊个数
            $redis = $this->di->getShared("redis");
            $redis->hIncrBy(CacheSetting::KEY_EXTRA_GROUP_COUNT_PERMANENT, $this->uid, $this->config['package_pick_count']);
        }
    }

    /** 赠送龙钻
     * @return bool
     */
    public function donateDiamond()
    {
        if ($this->diamond) {
            $record = [
//                        "payid" => "Novice_" . $id,             // 交易id
                "coin_type" => 0,           // 虚拟币类型【0红包钻石...其他保留】
                "coin" => ($this->diamond),               // 本次记录变动的钻石
                "type" => 0,                // 【0收入、1支出】
                "desc" => "购买vip",  // 流水描述
                "created" => time(),    // 时间
                "way" => 7,                 // 渠道，对于龙钻(coin_type=0)充值，1表示ios内购、2表示支付宝、3表示微信、4表示余额、5表示公众号、6表示系统赠送奖励；对于收益(coin_type=2)来源，1表示恐龙谷活动、2表示广场红包
                "extend" => ""              // 拓展
            ];
            $res = Request::getPost(Request::VIRTUAL_COIN_UPDATE, ['uid' => intval($this->uid), 'coin_type' => 0, 'coin_num' => $this->diamond, 'record' => json_encode($record, JSON_UNESCAPED_UNICODE)]);
            if ($res && $res['curl_is_success']) {
                $content = json_decode($res['data'], true);
                if (empty($content['code']) || $content['code'] != 200) {
                    $this->setMsg("更新虚拟币失败：" . var_export($content, true));
                    return false;
                }
            } else {
                $this->setMsg("更新虚拟币请求失败");
                return false;
            }
        }
        return true;
    }

    //
    /**
     * 检测是否到期 并执行
     */
    public function checkDeadline()
    {
        $p = 1;
        $limit = 1000;
        $list = VipOrder::findList(['status=' . self::STATUS_NORMAL . " and deadline<=" . time(), 'columns' => 'id,user_id,privileges', 'offset' => ($p - 1), 'limit' => $limit]);
        while ($list) {
            $uids = array_column($list, 'user_id');
            $ids = array_column($list, 'id');
            try {
                $this->original_mysql->begin();
                if (VipOrder::updateOne(['status' => self::STATUS_DEADLINE], 'id in (' . implode(',', $ids) . ')')) {
                    //更新用户信息
                    UserProfile::updateOne(["is_vip" => 0], 'user_id in (' . implode(',', $uids) . ")");
                    //更新用户权限信息
                    VipPrivileges::updateOne("enable=0", 'user_id in (' . implode(',', $uids) . ")");
                }
                foreach ($list as $item) {
                    // $privileges = json_decode($item['privileges'], true);
                    //更新 红包广场永久增加的领取个数
//                    if (isset($privileges['package_pick_count']) && $privileges['package_pick_count'] > 0) {
//                        $redis = $this->di->getShared("redis");
//                        $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, $item['user_id'], -$privileges['package_pick_count']);
//                    }
//                    //更新 额外赠加的群聊个数
//                    if (isset($privileges['add_group_count']) && $privileges['add_group_count'] > 0) {
//                        $redis = $this->di->getShared("redis");
//                        $redis->hIncrBy(CacheSetting::KEY_EXTRA_GROUP_COUNT_PERMANENT, $item['user_id'], -$privileges['add_group_count']);
//                    }
                    //更新云信用户扩展字段
                    ServerAPI::init()->updateUinfo($item['user_id'], '', '', '', '', '', '', '', json_encode(['is_vip' => 0]));
                    //更新uums缓存
                    $this->updateCache($item['user_id'], false);
                    //推送消息
                    ImManager::init()->initMsg(ImManager::TYPE_VIP_DEADLINE_HAS_ARRIVED, ['to_user_id' => $item['user_id']]);
                }
                $this->original_mysql->commit();

            } catch (\Exception $e) {
                $this->original_mysql->rollback();
                Debug::log("vip到期更新失败：" . var_export($e->getMessage(), true), 'vip_error');
                break;
            }
            $p++;
            $list = VipOrder::findList(['status=' . self::STATUS_NORMAL . " and deadline<=" . time(), 'columns' => 'id,user_id,privileges', 'offset' => ($p - 1), 'limit' => $limit]);
        }
    }

    /**更新uums 用户缓存
     * @param $uid
     * @param int $is_vip
     */
    public function updateCache($uid, $is_vip = 1)
    {
        if (!$this->producer) {
            $this->producer = Producer::getInstance($this->di->getShared("config")->kafka->host);
        }
        $this->producer
            ->setTopic($this->topic)
            ->produce(['uid' => $uid, 'vip' => $is_vip ? $is_vip : -1]);

    }

    /**
     * 临时统计到redis
     * @param $type 1- 进入付款页面 2-点击付款 3-购买成功 4-续费成功
     */
    public function setStat($type)
    {
        $redis = $this->di->get("redis");
        $redis->hIncrBy(CacheSetting::KEY_VIP_STAT . "opera" . $type . "_" . date('Ymd'), $this->uid, 1);
        $redis->hIncrBy(CacheSetting::KEY_VIP_STAT . date('Ymd'), "opera" . $type, 1);
    }


    /**统计入库
     * @param $date 20180411
     */
    public function statInsertDb($date)
    {
        if (VipDayStat::exist("ymd=" . $date)) {
            exit;
        }
        $redis = $this->di->get("redis");
        $data = ['created' => time(), 'ymd' => $date];
        for ($i = 1; $i <= 4; $i++) {
            $opera_data = $redis->hGetAll(CacheSetting::KEY_VIP_STAT . "opera" . $i . "_" . $date);
            $opera_data = $opera_data ? $opera_data : [];
            $data['opera' . $i] = json_encode($opera_data, JSON_UNESCAPED_UNICODE);
            $data['opera' . $i . "_cnt"] = 0;
            //删除记录
            $redis->del(CacheSetting::KEY_VIP_STAT . "opera" . $i . "_" . $date);
        }
        $opera_data2 = $redis->hGetAll(CacheSetting::KEY_VIP_STAT . $date);

        foreach ($opera_data2 as $k => $item) {
            $data[$k . "_cnt"] = $item;
        }
        //删除记录
        $redis->del(CacheSetting::KEY_VIP_STAT . $date);
        VipDayStat::insertOne($data);
        exit;
    }

    /**
     *vip 即将过期检测 发消息提示
     */
    public function deadlineTip()
    {
        $p = 1;
        $limit = 1000;
        $today_start = strtotime(date('Ymd'));
        $end = $today_start + 86400 * 3;
        $start = $today_start + 86400;

        $list = VipOrder::findList(['status=' . self::STATUS_NORMAL . " and deadline>=" . $start . " and deadline<=" . $end, 'columns' => 'deadline,user_id', 'offset' => ($p - 1), 'limit' => $limit]);
        while ($list) {
            foreach ($list as $item) {
                $day = floor(($item['deadline'] - $today_start) / 86400);
                //推送消息
                ImManager::init()->initMsg(ImManager::TYPE_VIP_DEADLINE_SOON, ['to_user_id' => $item['user_id'], 'day' => $day]);
            }
            $p++;
            $list = VipOrder::findList(['status=' . self::STATUS_NORMAL . " and deadline>=" . $start . " and deadline<=" . $end, 'columns' => 'deadline,user_id', 'offset' => ($p - 1), 'limit' => $limit]);
        }
    }


}