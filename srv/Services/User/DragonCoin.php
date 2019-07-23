<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/15
 * Time: 10:58
 */

namespace Services\User;


use Components\Kafka\Producer;
use Components\Rules\Coin\PointRule;
use Models\User\UserDragonCoin;
use Models\User\UserDragonCoinLog;
use Phalcon\Exception;
use Services\MiddleWare\Sl\Request;
use Services\Site\SiteKeyValManager;
use Services\User\DragonCoin\AbstractCoin;
use Util\Debug;

class DragonCoin extends AbstractCoin
{

    static private $instance = null;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**龙币变化执行
     * @param $desc
     * @param $extra
     * @return bool
     */
    public function execute($desc = '', $extra = '')
    {
        if (!$this->check()) {
            return false;
        }
        $db = $this->di->getShared('original_mysql');
        try {
            $db->begin();
            if (!$this->changeCoin()) {
                throw new Exception("龙币数据更新失败");
            }
            switch ($this->type) {
                //兑换现金
                case self::TYPE_CHANGE_CASH:
                    if (!$this->cashOut()) {
                        throw new Exception("龙币提现失败");
                    }
                    break;
                //兑换龙钻
                case self::TYPE_CHANGE_DIAMOND:
                    if (!$this->diamondOut()) {
                        throw new Exception("龙钻兑换失败");
                    }
                    break;
                //兑换龙豆
                case self::TYPE_CHANGE_DRAGON_BEANS:
                    if (!$this->beansOut()) {
                        throw new Exception("龙豆兑换失败");
                    }
                    break;
            }
            if (!$this->writeLog($desc, $extra)) {
                throw new Exception("龙币记录日志失败");
            };
            if (!$this->changeChangedMount()) {
                throw new Exception("用户龙币表更新历史兑换数失败");
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            Debug::log("龙币操作失败：" . var_export($_REQUEST, true) . $e->getMessage() . var_export($this->getMsg(), true), 'error/coin');
            $db->rollback();
            return false;
        }
    }


    /**
     *提现
     */
    private function cashOut()
    {
        /***请求中间键******/
        $transfer_data = [
            'uid' => intval($this->uid),
            'type' => 0,
            'sub_type' => 10,
            'money' => intval($this->val),
            "transferway" => "",
            "description" => "龙币提现",
            "created" => time(),
            "out_payid" => '',
            'payid' => ""
        ];
        $res = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
            'to_uid' => intval($this->uid),
            'uid' => Request::$gift_money_account,
            'money' => intval($this->val),
            'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
        ], false);
        if ($res && $res['curl_is_success']) {
            $content = json_decode($res['data'], true);
            if (empty($content['code']) || $content['code'] != 200) {
                $this->setError("提现失败【" . var_export($content, true) . "】");
                return false;
            }
        } else {
            $this->setError("提现失败【发起提现请求失败】");
            return false;
        }
        /******请求中间键 结束******/
        $this->extra_info = ['val' => $this->val, 'export' => $this->val];//金额【分】
        return true;
    }

    //兑换龙钻
    /**
     * @return bool
     */
    private function diamondOut()
    {
        $record = [
//                        "payid" => "Novice_" . $id,             // 交易id
            "coin_type" => 0,           // 虚拟币类型【0红包钻石...其他保留】
            "coin" => intval($this->extra_info['export']), // 本次记录变动的钻石
            "type" => 0,                // 【0收入、1支出】
            "desc" => "龙币兑换",     // 流水描述
            "created" => time(),      // 时间
            "way" => 7,                 // 渠道，对于龙钻(coin_type=0)充值，1表示ios内购、2表示支付宝、3表示微信、4表示余额、5表示公众号、6表示系统赠送奖励、7龙币兑换；对于收益(coin_type=2)来源，1表示恐龙谷活动、2表示广场红包
            "extend" => ""              // 拓展
        ];
        $data = ['uid' => intval($this->uid), 'coin_type' => 0, 'coin' => intval($this->extra_info['export']), 'record' => json_encode($record, JSON_UNESCAPED_UNICODE)];
        $res = Producer::getInstance($this->di->getShared("config")->kafka->host)
            ->setTopic("update_virtualcoin")
            ->produce($data);
        if (!$res) {
            $this->setError("龙币兑换龙豆失败【消息队列请求失败】");
            return false;
        }
//        $res = Request::getPost(Request::VIRTUAL_COIN_UPDATE, ['uid' => intval($this->uid), 'coin_type' => 0, 'coin_num' => intval($this->extra_info['export']), 'record' => json_encode($record, JSON_UNESCAPED_UNICODE)]);
//        if ($res && $res['curl_is_success']) {
//            $content = json_decode($res['data'], true);
//            if (empty($content['code']) || $content['code'] != 200) {
//                $this->setError("龙币兑换龙豆失败【" . var_export($content, true) . '】');
//                return false;
//            }
//        } else {
//            $this->setError("龙币兑换龙豆失败【发起更新虚拟币失败】");
//            return false;
//        }
        return true;
    }

    //兑换龙豆
    /**
     * @return bool
     */
    private function beansOut()
    {
        $res = PointRule::init()->changeCoin($this->uid, $this->val, "龙币兑换");
        if (!$res) {
            $this->setError("龙豆更新失败");
            return false;
        }
        $this->extra_info = ['val' => $this->val, 'export' => $this->val];//龙豆数
        return true;
    }

    /**
     * 记录改变的值
     */
    private function changeChangedMount()
    {
        $res = true;
        switch ($this->type) {
            case  self::TYPE_CHANGE_CASH:
                $res = UserDragonCoin::updateOne(
                    [
                        'changed_money' => 'changed_money+' . intval($this->extra_info['val']),
                    ], 'user_id=' . $this->uid
                );
                break;
            case  self::TYPE_CHANGE_DIAMOND:
                $res = UserDragonCoin::updateOne(
                    [
                        'changed_diamond' => 'changed_diamond+' . intval($this->extra_info['val']),
                    ], 'user_id=' . $this->uid
                );
                break;
            case  self::TYPE_CHANGE_DRAGON_BEANS:
                $res = UserDragonCoin::updateOne(
                    [
                        'changed_beans' => 'changed_beans+' . intval($this->extra_info['val']),
                    ], 'user_id=' . $this->uid
                );
                break;
        }
        if (!$res) {
            $this->setError("记录龙豆更换后历史兑换数量失败：" . var_export($this->extra_info, true));
            return false;
        }
        return true;
    }


    /**更新龙币值
     * @return bool
     */
    protected function changeCoin()
    {
        $dragon_coin = UserDragonCoin::findOne(['user_id=' . $this->uid, 'columns' => 'available_count'], true);
        //没有开过户
        if (!$dragon_coin) {
            if ($this->in_out == self::IN_OUT_OUT) {
                $this->setError("龙币不足");
                $this->setErrorCode(1);
                return false;
            }
            $coin_update = UserDragonCoin::insertOne(
                [
                    'user_id' => $this->uid,
                    'history_count' => $this->val,
                    'available_count' => $this->val,
                    'created' => time()
                ]
            );
            $this->current_val = 0;
        } else {
            //开过户
            //加龙币
            if ($this->in_out == self::IN_OUT_IN) {
                $coin_update = UserDragonCoin::updateOne(
                    [
                        'history_count' => 'history_count+' . $this->val,
                        'available_count' => 'available_count+' . $this->val,
                    ], 'user_id=' . $this->uid
                );
            } //减龙币
            else {
                //龙钻龙币比例不是1:1 避免龙币多扣的情况 采用动态比例来计算 多出的返还
                if ($this->type == self::TYPE_CHANGE_DIAMOND) {
                    $setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'coin_setting');
                    $coin = floor($this->val / $setting['diamond_rate']);
                    if ($coin <= 0) {
                        $this->setError("龙币不足");
                        $this->setErrorCode(1);
                        return false;
                    }
                    $this->val = $coin * $setting['diamond_rate'];

                    $this->extra_info = ['val' => $this->val, 'export' => $coin];

                }

                if ($dragon_coin['available_count'] < $this->val) {
                    $this->setError("龙币不足");
                    $this->setErrorCode(1);
                    return false;
                }
                $coin_update = UserDragonCoin::updateOne(
                    [
                        'available_count' => 'available_count-' . $this->val,
                    ], 'user_id=' . $this->uid
                );
            }

            $this->current_val = $dragon_coin['available_count'];
        }
        if (!$coin_update) {
            return false;
        }
        return true;
    }

    //获取记录
    public function getRecords($uid, $last_id = 0, $limit = 20)
    {
        $data = ['data_list' => [], 'last_id' => 0];
        $where = 'user_id=' . $uid;
        if ($last_id) {
            $where .= " and id<" . $last_id;
        }

        $list = UserDragonCoinLog::findList([$where, 'columns' => 'id,coins,in_out,brief,created', 'order' => 'created desc', 'limit' => $limit]);

        if ($list) {
            $data['last_id'] = $list[count($list) - 1]['id'];
            $data['data_list'] = $list;
        }
        return $data;
    }


}