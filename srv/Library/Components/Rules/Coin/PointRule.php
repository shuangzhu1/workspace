<?php
/**
 * Created by PhpStorm.
 * User: yanue-mi
 * Date: 14-8-19
 * Time: 上午10:21
 */

namespace Components\Rules\Coin;

use Models\User\UserCoinLog;
use Models\User\UserCoinRules;
use Models\User\UserPointLog;
use Models\User\Users;
use Services\MiddleWare\Sl\Request;
use Util\Debug;


class PointRule extends AbstractRule
{
    static $instance = null;
    /**
     * behaviors list
     */

    const BEHAVIOR_SIGN_IN = 1000; //登录
    const BEHAVIOR_SIGN_IN_WEEKEND = 1001; //周末登录
    const BEHAVIOR_INVITE = 1002; //邀请好友
    const BEHAVIOR_AUTH = 1003; //成为认证用户
    const BEHAVIOR_TOP_DISCUSS = 1004; //置顶动态
    const BEHAVIOR_CHARGE = 1005; //充值
    const BEHAVIOR_CONSUME_GIFT = 1006; //消费-购买礼物
    const BEHAVIOR_SYSTEM_REWARD = 1007; //系统赠送
    const BEHAVIOR_BEANS_CHANGE = 1008; //龙币兑换


    const CHARGE_TYPE_APPLE = 1; //苹果支付
    const CHARGE_TYPE_WEIXIN = 2;//微信支付
    const CHARGE_TYPE_ALIPAY = 3; //支付宝支付
    const CHARGE_TYPE_WALLET = 4; //余额支付


    public static $behaviorNameMap = array(
        self::BEHAVIOR_SIGN_IN => "登陆",
//        self::BEHAVIOR_VIPCARD_CHECK_IN => '每日登陆',
        // self::BEHAVIOR_CHECK_IN_5 => "连续5天登陆",
        //  self::BEHAVIOR_CHECK_IN_10 => "连续10天登陆",
        //   self::BEHAVIOR_CHECK_IN_20 => "连续20天登陆",
        self::BEHAVIOR_SIGN_IN_WEEKEND => "周末登录",
        self::BEHAVIOR_INVITE => "邀请好友",
        self::BEHAVIOR_AUTH => "成为认证用户",
        self::BEHAVIOR_TOP_DISCUSS => "动态置顶",
        self::BEHAVIOR_CHARGE => '充值', //充值
        self::BEHAVIOR_CONSUME_GIFT => "消费-购买礼物", //消费-购买礼物
        self::BEHAVIOR_SYSTEM_REWARD => "系统赠送", //系统赠送
        self::BEHAVIOR_BEANS_CHANGE => "龙币兑换" //龙币兑换

    );
    public static $charge_type = [
        self::CHARGE_TYPE_APPLE => "苹果支付",
        self::CHARGE_TYPE_WEIXIN => "微信",
        self::CHARGE_TYPE_ALIPAY => "支付宝",
        self::CHARGE_TYPE_WALLET => "余额",
    ];

    public static $_forum_bad_behaviors = array();

    public static function getForumBadBehaviors()
    {
        return self::$_forum_bad_behaviors;
    }

    public static function getBadBehaviorName($behavior)
    {
        if (in_array($behavior, self::$_forum_bad_behaviors)) {
            return self::$behaviorNameMap[$behavior];
        }
        return "其他";
    }

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function executeRule($user_id, $behavior)
    {
        $rule = $this->getRule($behavior);
        return $this->execute($user_id, $rule);
    }

    public function execute($user_id, RuleStructure $rule)
    {
        if (!$rule->isValid()) {
            return false;
        }
        $userModel = Users::findOne(['id = ' . $user_id, 'columns' => 'coins']);
        if (!($userModel && $this->checkLogUnique($user_id, $rule))) {
            return false;
        }
        $userPoints = $userModel['coins'];
        if ($rule->action == self::ACTION_UP) {
            $userPoints += $rule->getValue();
        }

        if ($rule->action == self::ACTION_DOWN) {
            $userPoints -= $rule->getValue();
        }
        $this->db->begin();
        try {
            // 更新龙豆
            $data = array(
                'coins' => $userPoints,
            );

            if (!Users::updateOne($data, ['id' => $user_id])) {
                $messages = [];
                throw new \Phalcon\Exception(join(',', $messages));
            }
            $rule->total = $userPoints;
            self::writeLog($user_id, $rule);
            $this->db->commit();
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            $this->db->rollback();
            return false;
        }
        return true;
    }

    /*充值*/
    public function chargeCoin($uid, $coin, $params)
    {
        $where = 'behavior="' . self::BEHAVIOR_CHARGE . '"';
        $rule = UserCoinRules::findOne([$where, 'columns' => 'params']);
        $donate = 0;//赠送龙豆数
        $true_coin = $coin;//真正充的龙豆值
        if ($rule) {
            $rule = json_decode($rule['params'], true);
            $key = $params['money'] / 100;
            if (isset($rule[$key])) {
                $donate = intval($rule[$key]['donate']);
                $true_coin = $rule[$key]['coin'];
            }
        }
        if (Users::updateOne('coins=coins+' . ($true_coin + $donate), 'id=' . $uid)) {
            $params['donate'] = $donate;
            $params['coin'] = $true_coin;
            UserCoinLog::insertOne([
                    'user_id' => $uid,
                    'in_out' => self::ACTION_UP,
                    'action' => self::BEHAVIOR_CHARGE,
                    'action_desc' => self::$behaviorNameMap[self::BEHAVIOR_CHARGE],
                    'created' => time(),
                    'value' => $true_coin,
                    'params' => json_encode($params, JSON_UNESCAPED_UNICODE)
                ]
            );
            $transfer_data = [
                'uid' => intval(Request::$gift_money_account),
                'type' => 0,
                'sub_type' => 0,
                'money' => intval($params['money']),
                "transferway" => "",
                "description" => "龙豆充值",
                "created" => time(),
                "out_payid" => '',
                'payid' => ""
            ];
            $res = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
                'to_uid' => intval(Request::$gift_money_account),
                'money' => intval($params['money']),
                'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
            ], false);
            if ($res && $res['curl_is_success']) {
                $content = json_decode($res['data'], true);
                if (empty($content['code']) || $content['code'] != 200) {
                    Debug::log("龙豆充值 系统账号加钱失败【" . var_export($content, true) . "】", 'error/chargeBeans');
                }
            } else {
                Debug::log("龙豆充值【发起系统账号充值请求失败】", 'error/chargeBeans');
            }

            return $donate;
        } else {
            return false;
        }

    }

    /*兑换龙豆*/
    public function changeCoin($uid, $coin, $mark = '', $params = [])
    {
        if (Users::updateOne('coins=coins+' . ($coin), 'id=' . $uid)) {
            $params['coin'] = $coin;
            UserCoinLog::insertOne([
                    'user_id' => $uid,
                    'in_out' => self::ACTION_UP,
                    'action' => self::BEHAVIOR_BEANS_CHANGE,
                    'action_desc' => self::$behaviorNameMap[self::BEHAVIOR_BEANS_CHANGE] . '-' . $mark,
                    'created' => time(),
                    'value' => $coin,
                    'params' => json_encode($params, JSON_UNESCAPED_UNICODE)
                ]
            );
            return true;
        } else {
            return false;
        }

    }

    /*奖励*/
    public function rewardCoin($uid, $coin, $mark = '', $params = [])
    {
        if (Users::updateOne('coins=coins+' . ($coin), 'id=' . $uid)) {
            $params['coin'] = $coin;
            UserCoinLog::insertOne([
                    'user_id' => $uid,
                    'in_out' => self::ACTION_UP,
                    'action' => self::BEHAVIOR_SYSTEM_REWARD,
                    'action_desc' => self::$behaviorNameMap[self::BEHAVIOR_SYSTEM_REWARD] . '-' . $mark,
                    'created' => time(),
                    'value' => $coin,
                    'params' => json_encode($params, JSON_UNESCAPED_UNICODE)
                ]
            );
            return true;
        } else {
            return false;
        }

    }

    //消费
    public function consumeCoin($uid, $coin, $behavior, $params = [])
    {
        if (Users::updateOne('coins=coins-' . ($coin), 'id=' . $uid)) {
            if (!UserCoinLog::insertOne([
                    'user_id' => $uid,
                    'in_out' => self::ACTION_DOWN,
                    'action' => $behavior,
                    'action_desc' => self::$behaviorNameMap[$behavior],
                    'created' => time(),
                    'value' => $coin,
                    'params' => json_encode($params, JSON_UNESCAPED_UNICODE)
                ]
            )
            ) {
                return false;
            }
            return true;
        }
        return false;
    }

    //获取记录
    public function getRecords($uid, $type = 0, $page = 1, $limit = 20)
    {
        $data = ['data_list' => []];
        $where = 'user_id=' . $uid . " and status=1";
        if ($type) {
            if ($type == 1) {
                $where .= " and in_out='in'";
            } else if ($type == 2) {
                $where .= " and in_out='out'";
            }
        }
        $list = UserCoinLog::findList([$where, 'columns' => 'action,action_desc,created,value,in_out,params as detail', 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        if ($list) {
            foreach ($list as &$item) {
                $item['in_out'] = (string)($item['in_out'] == 'in' ? 1 : 2);
                if ($item['detail']) {
                    $item['detail'] = json_decode($item['detail'], true);
                    $item['detail']['donate'] = (string)($item['detail']['donate']);
                } else {
                    $item['detail'] = (object)[];
                }
            }
        }
        $data['data_list'] = $list;
        return $data;
    }

    public function getRule($behavior)
    {
        $rule = $this->getRuleData($behavior);
        $ruleStructure = new RuleStructure();
        if ($rule) {
            $ruleStructure->setAction($rule['action']);
            $ruleStructure->setBehavior($rule['behavior']);
            $ruleStructure->setTerm($rule['term']);
            $ruleStructure->setValue($rule['points']);
            $ruleStructure->setLimit($rule['limit_count']);
        }
        return $ruleStructure;
    }

    private function getRuleData($behavior)
    {
        $where = 'behavior="' . $behavior . '"';
        $rule = UserCoinRules::findOne($where);
        return $rule;
    }

    public function getRulePoints($behavior)
    {
        $rule = $this->getRuleData($behavior);
        if (!$rule) {
            return 0;
        }
        return intval($rule->points);
    }

    protected function checkLogUnique($user, RuleStructure $rule)
    {
        if (!$rule->isValid()) {
            return false;
        }
        $behavior = $rule->getBehavior();

        $where = 'user_id=' . $user . ' AND action=' . $behavior;
        switch ($rule->term) {
            case self::TERM_ONLY_ONE:
                $pointLog = UserCoinLog::findOne($where);
                if ($pointLog) {
                    return false;
                } else {
                    return true;
                }
                break;
            case self::TERM_ONCE_A_DAY:
                $pointLog = UserCoinLog::findOne(array($where, 'order' => 'created DESC'));
                if ($pointLog) {
                    $timeStart = strtotime(date('Y-m-d'));
                    if ($pointLog['created'] >= $timeStart) {
                        return false;
                    } else {
                        return true;
                    }
                } else {
                    return true;
                }
                break;
            //每天有次数限制
            case self::TERM_DAY_LIMIT:
                $state_time = strtotime(date('Y-m-d'));
                $where .= " and created>=" . $state_time;
                $LogCount = UserCoinLog::dataCount(array($where));
                if ($LogCount) {
                    if ($LogCount >= $rule->limit_count) {
                        return false;
                    } else {
                        return true;
                    }
                } else {
                    return true;
                }
                break;
            case self::TERM_EVERY_BEHAVIOR:
            default:
                return true;
        }
    }

    /**
     * @param $user
     * @param RuleStructure $rule
     * @return bool|mixed
     * @throws \Phalcon\Exception
     */
    public function writeLog($user, RuleStructure $rule)
    {
        if ($rule->isValid()) {
            $data['user_id'] = $user;
            $data['in_out'] = $rule->getAction();
            $data['action'] = $rule->getBehavior();
            /* $data['total'] = $rule->total;*/
            $data['created'] = $_SERVER['REQUEST_TIME'];
            $data['value'] = $rule->getValue();
            $data['action_desc'] = $this->getBehaviorName($rule->getBehavior());
            $pointLog = new UserCoinLog();
            if (!$pointLog->insertOne($data)) {
                $messages = [];
                foreach ($pointLog->getMessages() as $msg) {
                    $messages[] = (string)$msg;
                }
                Debug::log(json_encode($messages), 'error');
            }
        }
        return true;
    }

    /**
     * @param $behavior
     * @param $action
     * @param $value
     * @param $term
     * @return bool|mixed
     */
    public function setRule($behavior, $action, $value, $term)
    {
        if (!self::behaviorCheck($behavior)) {
            return false;
        }

        $rule = UserCoinRules::findOne('behavior=' . $behavior);
        if ($rule) {
            if ($rule['action'] != $action || $value != $rule['points'] || $rule['term'] != $term) {
                return

                    UserCoinRules::updateOne(array(
                        'action' => $action,
                        'points' => $value,
                        'term' => $term
                    ), ['id=' . $rule['id']]);
            } else {
                return true;
            }
        } else {
            $data = array(
                'behavior' => $behavior,
                'action' => $action,
                'points' => $value,
                'term' => $term,
                'created' => time()
            );

            $rule = new UserCoinRules();

            return $rule->insertOne($data);
        }
    }
} 
