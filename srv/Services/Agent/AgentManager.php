<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/26
 * Time: 10:14
 */
namespace Services\Agent;

use Components\Yunxin\ServerAPI;
use Models\Agent\Agent;
use Models\Agent\AgentApply;
use Models\Agent\AgentApplyCheckLog;
use Models\Agent\AgentIncome;
use Models\Agent\AgentIncomeTransferLog;
use Models\Shop\Shop;
use Models\Shop\ShopApply;
use Models\User\UserInfo;
use Phalcon\Mvc\User\Plugin;
use Services\Im\ImManager;
use Services\MiddleWare\Sl\Request;
use Services\Shop\ShopManager;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\User\AuthManager;
use Services\User\OrderManager;
use Services\User\UserStatus;
use Util\Ajax;
use Util\Debug;

class AgentManager extends Plugin
{
    private static $instance = null;
    private static $ajax = null;
    const STATUS_WAIT_PAY = 0;//待付款
    const STATUS_WAIT_CHECK = 1;//待审核
    const STATUS_CHECK_SUCCESS = 2;//审核通过
    const STATUS_CHECK_FAIL = 3;//审核失败
    const STATUS_HAS_CANCELED = 4;//订单已被取消

    const income_status_has_paid = 0;//已付款待进行下一步
    const income_status_wait_income = 1;//待平台划账
    const income_status_has_end = 2;//已划账

    const income_income_type_shop = 1;//收益类型-开店
    const income_income_type_bonus = 2;//收益类型-奖励
    const income_income_type_agent = 3;//收益类型-成为合伙人

    private static $pay_expire_time = 1800;//支付时间-30分钟

    //收益类型
    private static $income_type_name = [
        self::income_income_type_shop => '开店提成',
        self::income_income_type_bonus => '系统奖励',
        self::income_income_type_agent => '邀请合伙人提成',
    ];

    public $error_msg = "";//错误信息

    public function __construct($is_cli = false)
    {
        if (!$is_cli) {
            self::$ajax = new Ajax();
        }
    }

    //
    public static function init($is_cli = false)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    //获取错误信息
    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    //设置错误信息
    public function setErrorMsg($msg)
    {
        $this->error_msg = $msg;
    }

    //提交申请
    /**
     * @param $uid --用户id
     * @param $brief --简介
     * @param $phone --手机号码
     * @param string $qq --qq
     * @param string $weixin --微信
     * @param string $email --邮箱
     * @param string $address --地址
     * @param string $code --邀请码
     * @return int
     */
    public function apply($uid, $brief = '', $phone, $qq = '', $weixin = '', $email = '', $address = '', $code = '')
    {
        $apply = AgentApply::findOne(['user_id=' . $uid, 'order' => 'created desc', 'columns' => 'id,status,favorable_money,money,trade_no,deadline,true_name']);
        $favorable_money = 0; //优惠金额
        $money = 0;//最终支付金额
        $trade_no = '';//支付流水号
        $time = time();
        $expire = 0;

        $bonus = 0;//总奖金
        $bonus_detail = [];//奖金详情

        $setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
        $setting = $setting['agent'];
        //填写了邀请码
        $code_owner = 0;//邀请码属于谁
        $level_second = 0;//二级推荐人
        $level_third = 0;//三级推荐人


        if ($code) {
            if (!$agent = Agent::findOne(["code='" . $code . "' and is_partner=1", 'columns' => 'user_id,parent_partner'])) {
                self::$ajax->outError(Ajax::INVALID_INVITE_CODE, "无效的邀请码");
            }
            $code_owner = $agent['user_id'];
            //一级提成
            if ($setting['base']) {
                $bonus_detail[$agent['user_id']] = $setting['base'];
                $bonus = $setting['base'];
            }
            //合伙人的上级邀请人 拿提成
            if ($agent['parent_partner'] && $setting['second_base']) {
                $second_inviter = Agent::findOne(["user_id=" . $agent['parent_partner'] . " and is_partner=1 and is_offline=0", 'columns' => 'user_id,parent_partner']);
                //二级提成
                if ($second_inviter) {
                    $bonus_detail[$second_inviter['user_id']] = $setting['second_base'];
                    $bonus += $setting['second_base'];
                    $level_second = $second_inviter['user_id'];
                    //三级提成
                    if ($setting['third_base'] && $third_inviter = Agent::findOne(["user_id=" . $second_inviter['parent_partner'] . " and is_partner=1 and is_offline=0", 'columns' => 'user_id'])) {
                        $bonus_detail[$third_inviter['user_id']] = $setting['third_base'];
                        $bonus += $setting['third_base'];
                        $level_third = $third_inviter['user_id'];
                    }
                }
            }
            $money = $setting['has_code'];
            $favorable_money = ($setting['no_code'] - $money);
            $total_money = $setting['no_code'];
        } else {
            $money = $setting['no_code'];
            $total_money = $money;
        }
        //之前提交过申请
        if ($apply) {
            //审核通过 //修改合伙人信息
            if ($apply['status'] == self::STATUS_CHECK_SUCCESS) {
                $data = [
                    'brief' => $brief,
                    'phone' => $phone,
                    "qq" => $qq,
                    'address' => $address,
                    'weixin' => $weixin,
                    'email' => $email,
                    'modify' => $time,
                ];
                $res = Agent::updateOne($data, 'user_id=' . $apply['id']);

                $favorable_money = intval($apply['favorable_money']);
                $money = intval($apply['money']);
                $trade_no = $apply['trade_no'];
            } else {
                //审核失败 //重新提交信息
                if ($apply['status'] == self::STATUS_CHECK_FAIL) {
                    //已付款或者被拒绝
                    $data = [
                        'brief' => $brief,
                        'phone' => $phone,
                        "qq" => $qq,
                        'address' => $address,
                        'weixin' => $weixin,
                        'email' => $email,
                        'modify' => $time,
                        'status' => self::STATUS_WAIT_CHECK,
                    ];
                    $res = AgentApply::updateOne($data, 'id=' . $apply['id']);

                    $favorable_money = intval($apply['favorable_money']);
                    $money = intval($apply['money']);
                    $trade_no = $apply['trade_no'];
                } //还未支付
                elseif ($apply['status'] == self::STATUS_WAIT_PAY) {
                    //还未到订单作废时间
                    if ($time - $apply['deadline'] < 0) {
                        self::$ajax->outError(Ajax::ERROR_SUBMIT_REPEAT, "订单未过期");
                    } //订单已作废 重新生成订单 价格重算
                    else {

                        $data = [
                            'user_id' => $uid,
                            'brief' => $brief,
                            'phone' => $phone,
                            "qq" => $qq,
                            'address' => $address,
                            'weixin' => $weixin,
                            'email' => $email,
                            'created' => $time,
                            'status' => self::STATUS_WAIT_PAY,
                            'true_name' => $apply['true_name'],
                            'money' => $money,
                            'total_money' => $total_money,
                            'favorable_money' => $favorable_money,
                            'trade_no' => "OA" . OrderManager::init()->generateOrderNumber(),
                            'deadline' => $time + self::$pay_expire_time,
                            'code' => $code,
                            'code_owner' => $code_owner,
                            'bonus' => $bonus,
                            'bonus_detail' => json_encode($bonus_detail),
                            'level_second' => $level_second,
                            'level_third' => $level_third,
                        ];
                        //免费 自动标记为已支付待审核
                        if ($data['money'] == 0) {
                            $data['paid_time'] = $time;
                            $data['is_paid'] = 1;
                            $data['status'] = self::STATUS_WAIT_CHECK;
                        }
                        $res = AgentApply::insertOne($data);
                        $favorable_money = intval($data['favorable_money']);
                        $money = intval($data['money']);
                        $trade_no = $data['trade_no'];
                        if ($data['money'] > 0) {
                            //开启计时任务
                            OrderManager::init()->startTask($data['trade_no'], date('Y-m-d H:i:s', $data['deadline']));
                            $expire = $data['deadline'] - $time;
                            if (!TEST_SERVER) {
                                //发送消息给审核人员
                                ServerAPI::init()->sendBatchMsg(ImManager::ACCOUNT_SYSTEM, json_encode([50000, 50037, 60034]), 0, json_encode(['msg' => "有人提交了合伙人申请,赶紧登录后台查看吧"]));
                            }
                        }
                    }
                } //待审核
                else if ($apply['status'] == self::STATUS_WAIT_CHECK) {
                    self::$ajax->outError(Ajax::ERROR_SUBMIT_REPEAT, "申请正在审核中");
                } else {
                    //订单已作废 //重新生成订单 价格重算
                    $price = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
                    $data = [
                        'user_id' => $uid,
                        'brief' => $brief,
                        'phone' => $phone,
                        "qq" => $qq,
                        'address' => $address,
                        'weixin' => $weixin,
                        'email' => $email,
                        'created' => $time,
                        'status' => self::STATUS_WAIT_PAY,
                        'true_name' => $apply['true_name'],
                        'money' => $money,
                        'total_money' => $total_money,
                        'favorable_money' => $favorable_money,
                        'trade_no' => "OA" . OrderManager::init()->generateOrderNumber(),
                        'deadline' => $time + self::$pay_expire_time,
                        'code' => $code,
                        'code_owner' => $code_owner,
                        'bonus' => $bonus,
                        'bonus_detail' => json_encode($bonus_detail),
                        'level_second' => $level_second,
                        'level_third' => $level_third,
                    ];
                    //免费 自动标记为已支付待审核
                    if ($data['money'] == 0) {
                        $data['paid_time'] = $time;
                        $data['is_paid'] = 1;
                        $data['status'] = self::STATUS_WAIT_CHECK;
                    }
                    $res = AgentApply::insertOne($data);
                    $favorable_money = intval($data['favorable_money']);
                    $money = intval($data['money']);
                    $trade_no = $data['trade_no'];
                    if ($data['money'] > 0) {
                        //开启计时任务
                        OrderManager::init()->startTask($data['trade_no'], date('Y-m-d H:i:s', $data['deadline']));
                        $expire = $data['deadline'] - $time;
                        if (!TEST_SERVER) {
                            //发送消息给审核人员
                            ServerAPI::init()->sendBatchMsg(ImManager::ACCOUNT_SYSTEM, json_encode([50000, 50037, 60034]), 0, json_encode(['msg' => "有人提交了合伙人申请,赶紧登录后台查看吧"]));
                        }
                    }
                }
            }
        } else {
            //$result = Request::getPost(Request::AUTH_DETAIL, ['uid' => $uid], true);
            $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'is_auth,true_name']);
            //没有实名认证
//            if ($user_info['is_auth'] != 1) {
//                self::$ajax->outError(Ajax::ERROR_NO_AUTH);
//            }
            $price = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
            $data = [
                'user_id' => $uid,
                'brief' => $brief,
                'phone' => $phone,
                "qq" => $qq,
                'address' => $address,
                'weixin' => $weixin,
                'email' => $email,
                'created' => $time,
                'status' => self::STATUS_WAIT_PAY,
                'true_name' => $user_info['true_name'],
                'money' => $money,
                'total_money' => $total_money,
                'favorable_money' => $favorable_money,
                'trade_no' => "OA" . OrderManager::init()->generateOrderNumber(),
                'deadline' => $time + self::$pay_expire_time,
                'code' => $code,
                'code_owner' => $code_owner,
                'bonus' => $bonus,
                'bonus_detail' => json_encode($bonus_detail),
                'level_second' => $level_second,
                'level_third' => $level_third,
            ];
            //免费 自动标记为已支付待审核
            if ($data['money'] == 0) {
                $data['paid_time'] = $time;
                $data['is_paid'] = 1;
                $data['status'] = self::STATUS_WAIT_CHECK;
            }
            $res = AgentApply::insertOne($data);
            $favorable_money = intval($data['favorable_money']);
            $money = intval($data['money']);
            $trade_no = $data['trade_no'];
            if ($data['money'] > 0) {
                //开启计时任务
                OrderManager::init()->startTask($data['trade_no'], date('Y-m-d H:i:s', $data['deadline']));
                $expire = $data['deadline'] - $time;
                if (!TEST_SERVER) {
                    //发送消息给审核人员
                    ServerAPI::init()->sendBatchMsg(ImManager::ACCOUNT_SYSTEM, json_encode([50000, 50037, 60034]), 0, json_encode(['msg' => "有人提交了合伙人申请,赶紧登录后台查看吧"]));
                }
            }
        }
        //返回一个价格和支付单号
        if ($res) {
            return ['money' => $money, 'favorable_money' => $favorable_money, 'trade_no' => $trade_no, 'expire' => intval($expire)];
        }
        return false;
    }

    /**订单成功回调
     * @param $uid --用户id
     * @param $paid_number --支付流水号
     * @param $trade_no --支付流水号
     * @return bool
     */
    public function paySuccess($uid, $paid_number, $trade_no)
    {
        $apply = AgentApply::findOne(["trade_no='" . $trade_no . "' and user_id=" . $uid]);
        if ($apply) {
            $money = $apply['money'];
            try {
                $this->di->getShared("original_mysql")->begin();
                $income_id = [];//收益
                //更改订单状态
                if (AgentApply::updateOne([
                    'paid_number' => $paid_number,
                    'paid_time' => time(),
                    'is_paid' => 1,
                    //'status' => self::STATUS_WAIT_CHECK,
                    'status' => self::STATUS_CHECK_SUCCESS,
                    'modify' => time()],
                    "trade_no='" . $trade_no . "'")
                ) {
                    if ($money > 0) {
                        if ($apply['bonus_detail']) {
                            $bonus_detail = json_decode($apply['bonus_detail'], true);
                            $level = 1;
                            foreach ($bonus_detail as $u => $bo) {
                                $tmp_income_id = AgentManager::init()->income($u, $uid, $bo, $trade_no, AgentManager::income_income_type_agent, 0, $level);
                                if (!$tmp_income_id) {
                                    throw new \Exception("上级商户入账记录失败:$u:$bo");
                                }
                                $income_id[] = $tmp_income_id;
                                $level++;
                            }
                        }
                        //扣钱
                        $transfer_data = [
                            'uid' => intval($uid),
                            'type' => 1,
                            'sub_type' => 11,
                            'money' => intval($money),
                            "transferway" => "",
                            "description" => "成为合伙人",
                            "created" => time(),
                            "out_payid" => '',
                            'payid' => $trade_no
                        ];
                        $res = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
                            'to_uid' => Request::$system_money_account,
                            'uid' => $uid,
                            'money' => $money,
                            'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
                        ], false);
                        if ($res && $res['curl_is_success']) {
                            $content = json_decode($res['data'], true);
                            if ($content['code'] && $content['code'] == 501) {
                                throw new \Exception("余额不足");
                            } else if (!($content['code'] && $content['code'] == 200)) {
                                throw new \Exception("余额扣钱失败:" . var_export($res, true));
                            }
                        } else {
                            throw new \Exception("余额扣钱失败:" . var_export($res, true));
                        }
                    }

                } else {
                    throw new \Exception("更新订单状态失败:");
                }
                if (Agent::exist("user_id=" . $apply['user_id'])) {
                    // 合作伙伴信息更新
                    Agent::updateOne([
                        'true_name' => $apply['true_name'],
                        'phone' => $apply['phone'],
                        'qq' => $apply['qq'],
                        'email' => $apply['email'],
                        'weixin' => $apply['email'],
                        'brief' => $apply['brief'],
                        'address' => $apply['address'],
                        'is_partner' => 1,
                        'parent_partner' => $apply['code_owner'],
                    ], 'user_id=' . $apply['user_id']);
                } else {
                    // 生成合作伙伴
                    Agent::insertOne([
                        "user_id" => $apply['user_id'],
                        'true_name' => $apply['true_name'],
                        'phone' => $apply['phone'],
                        'qq' => $apply['qq'],
                        'email' => $apply['email'],
                        'weixin' => $apply['email'],
                        'brief' => $apply['brief'],
                        'address' => $apply['address'],
                        'is_partner' => 1,
                        'parent_partner' => $apply['code_owner'],
                        'code' => self::createCode(),
                        'created' => time()
                    ]);
                }

                $this->di->getShared("original_mysql")->commit();

                //关闭计时任务
                OrderManager::init()->cancelTask($trade_no);
                if (!TEST_SERVER) {
                    //发送消息给审核人员
                    ServerAPI::init()->sendBatchMsg(ImManager::ACCOUNT_SYSTEM, json_encode([50000, 50037, 60034]), 0, json_encode(['msg' => "有用户合伙人申请支付成功:" . sprintf('%.2f', $money / 100) . ",赶紧登录后台查看吧"]));
                }
                return ['income_id' => $income_id];
            } catch (\Exception $e) {
                $this->di->getShared("original_mysql")->rollback();
                Debug::log("支付处理失败:" . var_export($e->getMessage(), true), 'payment');
                $this->setErrorMsg(var_export($e->getMessage(), true));

                return false;
            }
        } else {
            Debug::log("订单不存在:" . var_export($_REQUEST, true), 'order');
            $this->setErrorMsg("订单不存在");

        }
        return false;
    }

    //审核
    /**
     * @param $apply_id --申请id
     * @param $is_success --是否审核通过
     * @param $check_user --审核人id
     * @param string $reason --审核不通过原因
     * @return bool
     */
    public function check($apply_id, $is_success, $check_user, $reason = '')
    {
        $info = AgentApply::findOne(['id=' . $apply_id]);
        if (!$info) {
            return false;
        }
        //审核通过
        if ($is_success) {
            //已经审核通过了
            if ($info['status'] == self::STATUS_CHECK_SUCCESS) {
                return false;
            }
            try {
                $this->di->getShared("original_mysql")->begin();
                //插入审核日志
                AgentApplyCheckLog::insertOne([
                    'apply_id' => $apply_id,
                    'check_user' => $check_user,
                    'check_time' => time(),
                    'status' => 1,
                    'info' => json_encode($info, JSON_UNESCAPED_UNICODE)]);
                //更新审核状态
                AgentApply::updateOne(['status' => self::STATUS_CHECK_SUCCESS, "modify" => time()], 'id=' . $apply_id);
                // 生成合作伙伴
                Agent::insertOne([
                    "user_id" => $info['user_id'],
                    'true_name' => $info['true_name'],
                    'phone' => $info['phone'],
                    'qq' => $info['qq'],
                    'email' => $info['email'],
                    'weixin' => $info['email'],
                    'brief' => $info['brief'],
                    'address' => $info['address'],
                    'code' => self::createCode(),
                    'created' => time()
                ]);

                $this->di->getShared("original_mysql")->commit();
            } catch (\Exception $e) {
                $this->di->getShared("original_mysql")->rollback();
            }

            //发送im消息
            $user_info = UserInfo::findOne(['user_id=' . $info['user_id'], 'column' => 'username']);
            ImManager::init()->initMsg(ImManager::TYPE_AGENT_CHECK_SUCCESS, ['username' => $user_info['username'], 'to_user_id' => $info['user_id']]);

        } else {
            //已经审核失败了
            if ($info['status'] == self::STATUS_CHECK_FAIL) {
                return false;
            }
            AgentApplyCheckLog::insertOne([
                'apply_id' => $apply_id,
                'check_user' => $check_user,
                'reason' => $reason,
                'check_time' => time(),
                'status' => 0,
                'info' => json_encode($info, JSON_UNESCAPED_UNICODE)]);
            //更新审核状态
            AgentApply::updateOne(['status' => self::STATUS_CHECK_FAIL, "modify" => time()], 'id=' . $apply_id);

            //发送im消息
            $user_info = UserInfo::findOne(['user_id=' . $info['user_id'], 'column' => 'username']);
            ImManager::init()->initMsg(ImManager::TYPE_AGENT_CHECK_FAIL, ['username' => $user_info['username'], "reason" => $reason, 'to_user_id' => $info['user_id']]);
        }
    }

    /**获取合作人信息
     * @param $uid
     * @return mixed|object
     */
    public function detail($uid)
    {
        $agent = Agent::findOne(['user_id=' . $uid . " and is_partner=1", 'columns' => 'phone,qq,weixin,email,brief,address,code']);
        if ($agent) {
            $agent['status'] = self::STATUS_CHECK_SUCCESS;
            $agent['money'] = 0;
            $agent['favorable_money'] = 0;
            $agent['trade_no'] = '';
            $agent['expire'] = 0;
            return $agent;
        } else {
            $apply = AgentApply::findOne(["user_id=" . $uid, 'columns' => 'phone,qq,weixin,email,money,favorable_money,status,brief,address,trade_no,"" as code,deadline', 'order' => 'created desc']);
            if ($apply) {
                $apply['expire'] = 0;
                if ($apply['status'] == self::STATUS_WAIT_PAY) {
                    if ($apply['deadline'] <= time()) {
                        $apply['status'] = self::STATUS_HAS_CANCELED;
                    } else {
                        $apply['expire'] = $apply['deadline'] - time();
                    }
                }
                unset($apply['deadline']);
            }
            return $apply ? $apply : (object)[];
        }
    }

    //生成邀请码，考虑到拥有邀请码的用户很少 固没有采用很严谨的生成邀请码方式
    public function createCode()
    {
        $num = 6;//多少位的邀请码
        $code = "";
        $length = 0;
        while ($length < $num) {
            $code .= mt_rand(0, 9);
            $length++;
        }
        if (!Agent::exist("code='" . $code . "'")) {
            return $code;
        } else {
            return self::createCode();
        }
    }

    //收益
    /**
     * @param $owner_id
     * @param $user_id
     * @param $money
     * @param $trade_no
     * @param $type
     * @param $item_id
     * @param $level --几级收益
     * @return int
     */
    public function income($owner_id, $user_id, $money, $trade_no, $type, $item_id, $level = 1)
    {
        $time = time();
        return AgentIncome::insertOne([
                "owner_id" => $owner_id,
                'user_id' => $user_id,
                'money' => $money,
                'status' => self::income_status_wait_income,
                'created' => $time,
                'created_ymd' => date('Ymd', $time),
                'income_ym' => date('Ym', $time),
                'trade_no' => $trade_no,
                'type' => $type,
                'item_id' => $item_id,
                'level' => $level
            ]
        );
    }

    //收益列表
    /**
     * @param $uid --用户id
     * @param $status --状态
     * @param int $type --1-店铺 3-成为合伙人
     * @param int $page --第几页
     * @param int $limit --每页显示的数量
     * @return array
     */
    public function incomeList($uid, $status, $type = self::income_income_type_shop, $page = 1, $limit = 20)
    {
        $res = [
            'last_month_income' => 0, //本月收入
            'total_income' => 0, //总收入
            'last_month_reward' => 0, //上月系统奖励
            'total_reward' => 0,//总平台奖励
            'invite_count' => 0, //邀请的人数
            'inviter' => (object)[], //我的邀请人
            'data_list' => [], //记录列表
        ];
        $month = date('Ym');
        $last_month = date('Ym', time() - 86400);
        //   $income_where = 'owner_id=' . $uid . " and status=" . self::income_status_has_end;

//        if ($type == self::income_income_type_shop) {
//            $income_where .= " and type=" . self::income_income_type_shop;
//        } else if ($type == self::income_income_type_agent) {
//            $income_where .= " and type=" . self::income_income_type_agent;
//        }
//        $income = AgentIncome::findOne([$income_where, 'columns' => 'sum(money) as total']);
//        if ($income) {
//            $res['total_income'] = intval($income['total']);
//        }
//        $month_income = AgentIncome::findOne(['owner_id=' . $uid . ' and income_ym =' . $month, 'columns' => 'sum(money) as total']);
//        if ($month_income) {
//            $res['last_month_income'] = intval($month_income['total']);
//        }
//        $month_bonus = AgentIncome::findOne(['owner_id=' . $uid . ' and income_ym=' . $month . " and type=" . self::income_income_type_bonus, 'columns' => 'sum(money) as total']);
//        if ($month_bonus) {
//            $res['last_month_reward'] = intval($month_bonus['total']);
//        }
        $where = '(code_owner=' . $uid . " or level_second=" . $uid . ' or level_third=' . $uid . ') ';

        //开店
        if ($type == self::income_income_type_shop) {
            if ($page == 1) {
                $agent = Agent::findOne(['user_id=' . $uid . " and is_merchant=1", 'columns' => 'parent_merchant']);
                if ($agent) {
                    $res['invite_count'] = Agent::dataCount("parent_merchant=" . $uid);
                    $total_income = AgentIncome::findOne(['owner_id=' . $uid . ' and type =' . self::income_income_type_shop, 'columns' => 'sum(money) as total']);
                    $res['total_income'] = $total_income ? intval($total_income['total']) : 0;
                    $month_bonus = AgentIncome::findOne(['owner_id=' . $uid . ' and income_ym=' . $last_month . " and type=" . self::income_income_type_bonus . " and sub_type=" . self::income_income_type_shop, 'columns' => 'sum(money) as total']);
                    if ($month_bonus) {
                        $res['last_month_reward'] = intval($month_bonus['total']);
                    }
                    $total_bonus = AgentIncome::findOne(['owner_id=' . $uid . " and type=" . self::income_income_type_bonus . " and sub_type=" . self::income_income_type_shop, 'columns' => 'sum(money) as total']);
                    if ($total_bonus) {
                        $res['total_reward'] = intval($total_bonus['total']);
                    }
                    $month_income = AgentIncome::findOne(['owner_id=' . $uid . ' and income_ym =' . $month . " and type=" . self::income_income_type_shop, 'columns' => 'sum(money) as total']);
                    if ($month_income) {
                        $res['last_month_income'] = intval($month_income['total']);
                    }

                    //有推荐人
                    if ($agent['parent_merchant']) {
                        $user_info = UserInfo::findOne(['user_id=' . $agent['parent_merchant'], 'columns' => 'username,avatar']);
                        $user_info['uid'] = $agent['parent_merchant'];
                        $res['inviter'] = $user_info;
                    }
                }
            }

            //待支付
            if ($status == 0) {
                $where .= " and status=" . ShopManager::pay_status_wait_pay;
            } //支付成功 待开店
            else if ($status == 1) {
                $where .= " and status=" . ShopManager::pay_status_has_paid;
            } else {
                $where .= " and (status=" . ShopManager::pay_status_wait_pay . " or status=" . ShopManager::pay_status_has_paid . ")";
            }
            $shop_apply = ShopApply::findList([$where, 'columns' => 'bonus as money,status,user_id as uid,created,is_paid,bonus_detail', 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
            if ($shop_apply) {
                $uids = array_column($shop_apply, 'uid');
                $user_info = UserInfo::getByColumnKeyList(['user_id in(' . implode(',', $uids) . ")", 'columns' => 'user_id as uid,true_name,username,avatar'], 'uid');
                foreach ($shop_apply as $item) {
                    $item['user_info'] = $user_info[$item['uid']];
                    if ($item['is_paid'] == 1) {
                        $item['status'] = 1;
                    } else {
                        $item['status'] = 0;
                    }
                    $bonus_detail = json_decode($item['bonus_detail'], true);
                    $item['money'] = intval($bonus_detail[$uid]);
                    unset($item['is_paid']);
                    unset($item['bonus_detail']);

                    $res['data_list'][] = $item;
                }
            }
        } else if ($type == self::income_income_type_agent) {
            if ($page == 1) {
                $agent = Agent::findOne(['user_id=' . $uid . " and is_partner=1", 'columns' => 'parent_partner']);
                if ($agent) {
                    $res['invite_count'] = Agent::dataCount("parent_partner=" . $uid);
                    $total_income = AgentIncome::findOne(['owner_id=' . $uid . ' and type =' . self::income_income_type_agent, 'columns' => 'sum(money) as total']);
                    $res['total_income'] = $total_income ? intval($total_income['total']) : 0;
                    $month_bonus = AgentIncome::findOne(['owner_id=' . $uid . ' and income_ym=' . $last_month . " and type=" . self::income_income_type_bonus . " and sub_type=" . self::income_income_type_agent, 'columns' => 'sum(money) as total']);
                    if ($month_bonus) {
                        $res['last_month_reward'] = intval($month_bonus['total']);
                    }
                    $total_bonus = AgentIncome::findOne(['owner_id=' . $uid . " and type=" . self::income_income_type_bonus . " and sub_type=" . self::income_income_type_agent, 'columns' => 'sum(money) as total']);
                    if ($total_bonus) {
                        $res['total_reward'] = intval($total_bonus['total']);
                    }
                    $month_income = AgentIncome::findOne(['owner_id=' . $uid . ' and income_ym =' . $month . " and type=" . self::income_income_type_agent, 'columns' => 'sum(money) as total']);
                    if ($month_income) {
                        $res['last_month_income'] = intval($month_income['total']);
                    }
                    //有推荐人
                    if ($agent['parent_partner']) {
                        $user_info = UserInfo::findOne(['user_id=' . $agent['parent_partner'], 'columns' => 'username,avatar']);
                        $user_info['uid'] = $agent['parent_partner'];
                        $res['inviter'] = $user_info;
                    }
                }
            }
            //
            //待支付
            if ($status == 0) {
                $where .= " and status=" . self::STATUS_WAIT_PAY;
            } //支付成功
            else if ($status == 1) {
                $where .= " and is_paid=1 ";
            } else {
                $where .= " and (status=" . self::STATUS_WAIT_PAY . " or is_paid=1)";
            }
            $apply = AgentApply::findList([$where, 'columns' => 'bonus as money,status,user_id as uid,created,bonus_detail,is_paid', 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
            if ($apply) {
                $uids = array_column($apply, 'uid');
                $user_info = UserInfo::getByColumnKeyList(['user_id in(' . implode(',', $uids) . ")", 'columns' => 'user_id as uid,true_name,username,avatar'], 'uid');
                foreach ($apply as $item) {
                    $item['user_info'] = $user_info[$item['uid']];
                    if ($item['is_paid'] == 1) {
                        $item['status'] = 1;
                    } else {
                        $item['status'] = 0;
                    }
                    $bonus_detail = json_decode($item['bonus_detail'], true);
                    $item['money'] = intval($bonus_detail[$uid]);
                    unset($item['is_paid']);
                    unset($item['bonus_detail']);
                    $res['data_list'][] = $item;
                }
            }
        }
        return $res;
    }

    //收益到账
    //月平台收益到账
    public function incomeToAccount($month)
    {
        //已经发放过奖励了
        if (AgentIncome::exist("type=" . self::income_income_type_bonus . " and income_ym=$month")) {
            return;
        }
        $price = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
        $shop_setting = $price['shop'];
        $agent_setting = $price['agent'];
        $shop_radices = $shop_setting['reward_radices'];//开店平台奖励基数
        $agent_radices = $agent_setting['reward_radices'];//成为合伙人平台奖励基数
        if ($shop_radices > 0 && $shop_setting['limit']) {
            $income = AgentIncome::findList(['income_ym=' . $month . " and level=1 and type=" . self::income_income_type_shop, 'columns' => 'count(1) as count,owner_id', 'group' => 'owner_id']);
            if ($income) {
                foreach ($income as $item) {
                    try {
                        $this->di->getShared("original_mysql")->begin();
                        $reward = $this->getRewardPrice($shop_setting['limit'], $item['count'], $shop_radices);
                        //---------平台发放奖金开始------------
                        if ($reward) {
                            $reward_record = AgentIncome::insertOne([
                                'owner_id' => $item['owner_id'],
                                'money' => $reward,
                                'status' => self::income_status_has_end,
                                'created' => time(),
                                'success_time' => time(),
                                'type' => self::income_income_type_bonus,
                                'income_ym' => $month,
                                'created_ymd' => date('Ymd'),
                                'mark' => '开店',
                                'sub_type' => self::income_income_type_shop,
                            ]);
                            if (!$reward_record) {
                                throw new \Exception("插入奖励数据失败");
                            }
                        }
                        //增加一条流水
                        AgentIncomeTransferLog::insertOne(['user_id' => $item['owner_id'], 'money' => $reward, 'created' => time()]);

                        $transfer_data = [
                            'uid' => intval($item['owner_id']),
                            'type' => 0,
                            'sub_type' => 10,
                            'money' => intval($reward),
                            "transferway" => "",
                            "description" => "邀请开店-系统奖励",
                            "created" => time(),
                            "out_payid" => '',
                            'payid' => ""
                        ];
                        $res = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
                            'to_uid' => intval($item['owner_id']),
                            'uid' => Request::$system_money_account,
                            'money' => intval($reward),
                            'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
                        ], false);
                        if ($res && $res['curl_is_success']) {
                            $content = json_decode($res['data'], true);
                            if (empty($content['code']) || $content['code'] != 200) {
                                throw new \Exception("奖金发放失败：" . var_export($content, true));
                            }
                        } else {
                            throw new \Exception("奖金发放失败");
                        }
                        //---------平台发放奖金结束---------
                        $this->di->getShared("original_mysql")->commit();
                    } catch (\Exception $e) {
                        $this->di->getShared("original_mysql")->rollback();
                        Debug::log("发放奖金:" . var_export($e->getMessage(), true), 'agent_reward');
                    }
                }
            }
        }
        if ($agent_radices > 0 && $agent_setting['limit']) {
            $income = AgentIncome::findList(['income_ym=' . $month . " and level=1 and type=" . self::income_income_type_agent, 'columns' => 'count(1) as count,owner_id', 'group' => 'owner_id']);
            if ($income) {
                foreach ($income as $item) {
                    try {
                        $this->di->getShared("original_mysql")->begin();
                        $reward = $this->getRewardPrice($agent_setting['limit'], $item['count'], $agent_radices);
                        //---------平台发放奖金开始------------
                        if ($reward) {
                            $reward_record = AgentIncome::insertOne([
                                'owner_id' => $item['owner_id'],
                                'money' => $reward,
                                'status' => self::income_status_has_end,
                                'created' => time(),
                                'success_time' => time(),
                                'type' => self::income_income_type_bonus,
                                'income_ym' => $month,
                                'created_ymd' => date('Ymd'),
                                'mark' => '合伙人',
                                'sub_type' => self::income_income_type_agent,
                            ]);
                            if (!$reward_record) {
                                throw new \Exception("插入奖励数据失败");
                            }
                        }
                        //增加一条流水
                        AgentIncomeTransferLog::insertOne(['user_id' => $item['owner_id'], 'money' => $reward, 'created' => time()]);

                        $transfer_data = [
                            'uid' => intval($item['owner_id']),
                            'type' => 0,
                            'sub_type' => 10,
                            'money' => intval($reward),
                            "transferway" => "",
                            "description" => "邀请合伙人-系统奖励",
                            "created" => time(),
                            "out_payid" => '',
                            'payid' => ""
                        ];
                        $res = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
                            'to_uid' => intval($item['owner_id']),
                            'uid' => Request::$system_money_account,
                            'money' => intval($reward),
                            'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
                        ], false);
                        if ($res && $res['curl_is_success']) {
                            $content = json_decode($res['data'], true);
                            if (empty($content['code']) || $content['code'] != 200) {
                                throw new \Exception("奖金发放失败：" . var_export($content, true));
                            }
                        } else {
                            throw new \Exception("奖金发放失败");
                        }
                        //---------平台发放奖金结束---------
                        $this->di->getShared("original_mysql")->commit();
                    } catch (\Exception $e) {
                        $this->di->getShared("original_mysql")->rollback();
                        Debug::log("发放奖金:" . var_export($e->getMessage(), true), 'agent_reward');
                    }
                }
            }
        }

    }

    //单次收益立刻到账
    public function incomeToAccountSingle($income_id)
    {
        try {
            $this->di->getShared("original_mysql")->begin();
            $reward_record = AgentIncome::findOne(['id=' . $income_id . " and status=" . self::income_status_wait_income, 'columns' => 'type,money,owner_id,type']);
            if ($reward_record) {
                //更新状态
                if (!AgentIncome::updateOne(['status' => self::income_status_has_end, 'success_time' => time()], 'id=' . $income_id)) {
                    throw new \Exception("更新agent_income表数据失败");
                }
                //增加一条流水
                AgentIncomeTransferLog::insertOne(['user_id' => $reward_record['owner_id'], 'money' => $reward_record['money'], 'created' => time()]);
                $transfer_data = [
                    'uid' => intval($reward_record['owner_id']),
                    'type' => 0,
                    'sub_type' => 10,
                    'money' => intval($reward_record['money']),
                    "transferway" => "",
                    "description" => self::$income_type_name[$reward_record['type']],
                    "created" => time(),
                    "out_payid" => '',
                    'payid' => ""
                ];
                $res = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
                    'to_uid' => intval($reward_record['owner_id']),
                    'uid' => Request::$system_money_account,
                    'money' => intval($reward_record['money']),
                    'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
                ], false);
                if ($res && $res['curl_is_success']) {
                    $content = json_decode($res['data'], true);
                    if (empty($content['code']) || $content['code'] != 200) {
                        throw new \Exception("开店提成发放失败：" . var_export($content, true));
                    }
                } else {
                    throw new \Exception("开店提成发放失败");
                }
            }
            $this->di->getShared("original_mysql")->commit();
            return true;

        } catch (\Exception $e) {
            $this->di->getShared("original_mysql")->rollback();
            Debug::log("开店提成发放失败:" . $e->getMessage(), 'agent_reward');
            return false;
        }

    }

    //根据人数获取奖励金额
    public function getRewardPrice($limit, $count, $base_price)
    {
        // var_dump($limit);
        $price = 0;
        if ($limit) {
            foreach ($limit as $item) {
                if ($count >= $item['start'] && $count <= $item['end']) {
                    $price = ceil($base_price * $item['rate'] / 100 * $count);
                    break;
                }
            }
        }
        return $price;
    }
}