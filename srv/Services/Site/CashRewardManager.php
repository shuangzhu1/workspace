<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/7
 * Time: 9:52
 */

namespace Services\Site;


use Components\Rules\Coin\PointRule;
use Models\Site\SiteCashReward;
use Models\Site\SiteRewardLog;
use Models\Social\SocialShare;
use Models\Statistics\SiteCashRewardTotal;
use Models\Statistics\SiteReward;
use Phalcon\Mvc\User\Plugin;
use Services\Im\ImManager;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Util\Debug;
use Util\Exception;
use Util\Probability;

class CashRewardManager extends Plugin
{
    const TYPE_DISCUSS = 1;//动态推荐
    const TYPE_SHARE = 2;//动态分享
    const TYPE_ROBOT_DISCUSS_PACKAGE = 3;//动态红包`
    const TYPE_ROBOT_SQUARE_PACKAGE = 4;//广场红包

    const REWARD_TYPE_CASH = 1;//中现金
    const REWARD_TYPE_COIN = 2;//中龙豆


    // static $instance = null;
    static $day_money_limit = 500;//每天的最大金额 5-块钱
    static $reward_start = 5; //5分
    static $reward_end = 10;//1毛
    private $money = 0;//奖励金
    private $reward_type = 0;//奖励方式 1-现金红包 2-龙豆

    static $reward_name = [
        self::TYPE_DISCUSS => "动态推荐",
        self::TYPE_SHARE => "分享",
        self::TYPE_ROBOT_DISCUSS_PACKAGE => "动态红包",
        self::TYPE_ROBOT_SQUARE_PACKAGE => "广场红包",

    ];
    static $reward_description = [
        self::TYPE_DISCUSS => "动态被推荐",
        self::TYPE_SHARE => "分享",
        self::TYPE_ROBOT_DISCUSS_PACKAGE => "动态红包",
        self::TYPE_ROBOT_SQUARE_PACKAGE => "广场红包",

    ];


    /*  public function init()
      {
          if (!self::$instance) {
              self::$instance = new self();
          }
          return self::$instance;
      }*/

    public function reward($uid, $type, $item_id, $extra = [])
    {
        try {
            $this->db->begin();
            $this->db_statistics->begin();

            $time = time();

            //检测是否关闭该功能
            $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "reward");
            $setting = json_decode($setting, true);


            $probability = 0; //中现金概率
            $probability_start = 0;  //现金奖励随机起始金额
            $probability_end = 0;  //现金奖励随机结束金额


            $probability_coin = 100; //中龙豆概率
            $probability_coin_start = 0;  //龙豆奖励随机起始金额
            $probability_coin_end = 0;  //龙豆奖励随机结束金额

            $rest_money = 0;    //可用余额
            $money = 0; //最终赠送的金额
            $reward_type = 0; //最终赠送的类型 1-现金 2-龙豆

            $ymd = date("Ymd");//当时时间 年月日

            //奖励功能已关闭
            if ($setting) {
                if (!$setting['enable']) {
                    return false;
                }
                switch ($type) {
                    //动态推荐
                    case self::TYPE_DISCUSS:
                        //已经关闭了
                        if (!$setting['discuss_recommend']['enable']) {
                            return false;
                        }
                        //已经赠送过
                        if (SiteCashReward::exist("type='" . $type . "' and item_id='" . $item_id . "'")) {
                            return false;
                        }

                        $total = SiteCashRewardTotal::findOne(["ymd=" . $ymd . ' and type=' . $type, 'columns' => 'money'], true);
                        $total = $total ? $total['money'] : 0;
                        //金额已经超额
                        if ($total >= $setting['discuss_recommend']['limit']) {
                            return false;
                        }

                        $rest = $setting['discuss_recommend']['limit'] - $total['money'];
                        $probability = $setting['discuss_recommend']['probability'];
                        $probability_start = $setting['discuss_recommend']['start'];
                        $probability_end = $setting['discuss_recommend']['end'];
                        $reward_type = self::REWARD_TYPE_CASH;

                        break;

                    //分享
                    case  self::TYPE_SHARE:

                        //已经关闭了
                        if (!$setting['share']['enable']) {
                            return false;
                        }
                        $cache = new CacheSetting();
                        $count = $cache->get($cache::PREFIX_REWARD_COUNT, "$uid:" . $type . "_" . $ymd);

                        //每人每天的次数上限
                        //  $count = SiteCashReward::dataCount("type='" . $type . "' and user_id=" . $uid);
                        if ($count && $count >= $setting['share']['day_count']) {
                            return false;
                        }

                        $probability = $setting['share']['probability'];
                        $probability_start = $setting['share']['start'];
                        $probability_end = $setting['share']['end'];

                        $probability_coin_start = $setting['share']['coin_start'];
                        $probability_coin_end = $setting['share']['coin_end'];

                        break;
                    default:
                        return false;
                        break;
                }
            }
            //现金中奖概率 大于0
            if ($probability != 0) {
                //分享 判断金额是否超额
                if ($type == self::TYPE_SHARE) {
                    $total = SiteCashRewardTotal::findOne(["ymd=" . $ymd . ' and type=' . $type, 'columns' => 'money']);
                    $total = $total ? $total['money'] : 0;
                    //金额已经超额
                    if ($total >= $setting['share']['limit']) {
                        //送龙豆
                        $reward_type = self::REWARD_TYPE_COIN;
                    } else {
                        $result = Probability::get_rand([$probability, 100 - $probability]);
                        //没中奖 送龙豆
                        if ($result == 1) {
                            $reward_type = self::REWARD_TYPE_COIN;
                        } else {
                            $reward_type = self::REWARD_TYPE_CASH;
                            $rest = $setting['share']['limit'] - $total['money'];
                        }
                    }
                } else {
                    $result = Probability::get_rand([$probability, 100 - $probability]);
                    //没中奖
                    if ($result == 1) {
                        return false;
                    } else {
                        $reward_type = $result + 1;
                    }
                }
            } //龙豆必中
            else {
                $reward_type = self::REWARD_TYPE_COIN;
            }
            //现金中奖
            if ($reward_type == self::REWARD_TYPE_CASH) {
                //金额充足
                $rest = $rest - $probability_end;
                if ($rest >= 0) {
                    $rand_start = $probability_start;
                    $rand_end = $probability_end;
                    $money = self::createMoney($rand_start, $rand_end);
                } else {
                    //余额 比 随机金额起始值还低
                    if ($rest <= $probability_start) {
                        $money = $rest;
                    } //余额大于随机起始值 但是小于随机结束值
                    else {
                        $rand_start = $probability_start;
                        $rand_end = $rest;
                        $money = self::createMoney($rand_start, $rand_end);
                    }
                }
                if ($extra) {
                    if (is_array($extra)) {
                        $extra = json_encode($extra, JSON_UNESCAPED_UNICODE);
                    }
                } else {
                    $extra = '';
                }

                $data = [
                    "user_id" => $uid,
                    "type" => $type,
                    "item_id" => $item_id,
                    'money' => $money,
                    'extra' => $extra && is_array($extra) ? json_encode($extra, JSON_UNESCAPED_UNICODE) : $extra,
                    'created' => $time,
                    'ymd' => $ymd
                ];


                if (!$log = SiteCashReward::insertOne($data)) {
                    throw new \Exception("SiteCashReward表插入数据失败：" . var_export($data, true));
                }
                //今天没有赠送过奖励金
                if (!$total) {
                    $total_data = ['ymd' => $ymd, 'money' => $money, 'type' => $type];
                    if (!SiteCashRewardTotal::insertOne($total_data)) {
                        throw new \Exception("SiteCashRewardTotal插入数据失败：" . var_export($total_data, true));
                    }
                } else {
                    if (!SiteCashRewardTotal::updateOne("money=money+" . $money, 'ymd=' . $ymd . ' and type=' . $type)) {
                        throw new \Exception("SiteCashRewardTotal更新数据失败：" . "money=money+" . $money);
                    }
                }

                //todo 宋亮接口调用 失败则回滚
                $data = [
                    'uid' => 11,
                    'to_uid' => intval($uid),
                    'money' => intval($money),
                    'record' => json_encode(["uid" => intval($uid), 'type' => 0, "sub_type" => 6, "money" => $money, "description" => self::$reward_description[$type], "created" => time()], JSON_UNESCAPED_UNICODE),
                    'timestamp' => time(),
                ];
                $res = Request::getPost(Base::WALLET_BALANCE_TRANSFER, $data);
                if ($res && $res['curl_is_success'] && !empty($res['data'])) {
                    $res_data = json_decode($res['data'], true);
                    if (isset($res_data['code']) && $res_data['code'] == 200) {
                        // SiteCashReward::updateOne(["pay_order" => $res_data['data']], "id=" . $log);
                    } else {
                        throw new \Exception("调用接口失败：" . var_export($res, true));
                    }
                } else {
                    throw new \Exception("调用接口失败：" . var_export($res, true));
                }
                $this->money = $money;
            } //龙豆中奖
            else if ($reward_type == self::REWARD_TYPE_COIN) {
                $coin = 0;
                if ($probability_coin_end > 0) {
                    //固定值
                    if ($probability_coin_start == $probability_coin_end) {
                        $coin = $probability_coin_end;
                    } else {
                        //随机值
                        $coin = mt_rand($probability_coin_start, $probability_coin_end);
                    }
                }
                if ($coin) {
                    PointRule::init()->rewardCoin($uid, $coin, self::$reward_description[$type], ['type' => $type, 'item_id' => $item_id, 'extra' => $extra]);
                }
                $this->money = $coin;
            }


            $this->db->commit();
            $this->db_statistics->commit();
            $this->reward_type = $reward_type;

            //更新奖励次数
            if ($type == self::TYPE_SHARE) {
                $count = $count ? $count + 1 : 1;
                $cache->set($cache::PREFIX_REWARD_COUNT, "$uid:" . $type . "_" . $ymd, $count);
            }
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->db_statistics->rollback();

            Debug::log("奖励失败：" . var_export($e->getMessage(), true), 'error');
            return false;
        }
    }


    //开奖
    public function draw($uid, $type, $item_id)
    {
        $data = [];
        switch ($type) {
            case self::TYPE_SHARE:
                $data = SocialShare::findOne(["user_id=" . $uid . " and spm='" . $item_id . "'", 'columns' => 'type,item_id,spm,platform'], false, true);
        }
        if ($data) {
            $res = self::reward($uid, $type, $data['item_id'], ['type' => $data['type'], 'item_id' => $data['item_id'], 'spm' => $data['spm']]);
            if ($res) {
                $this->db->begin();
                //插入日志
                $log = [
                    'user_id' => $uid,
                    'platform' => $data['platform'],
                    'type' => 2,
                    'money' => $this->money,
                    'reward_type' => $this->reward_type,
                    'created' => time(),
                    'extra' => json_encode(['type' => $data['type'], 'item_id' => $data['item_id'], 'spm' => $data['spm']], JSON_UNESCAPED_UNICODE)
                ];
                $log['ymd'] = date('Ymd', $log['created']);
                SiteRewardLog::insertOne($log);
                $this->db->commit();
                if ($this->money > 0) {
                    return ['value' => $this->money, 'type' => $this->reward_type];
                }

            }
        } else {
            Debug::log("data not exist" . $item_id, 'debug');
        }
        return false;
    }

    //开奖检测
    public function drawCheck($uid, $type)
    {
        //检测是否关闭该功能
        $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "reward");
        $setting = json_decode($setting, true);

        if ($setting['enable'] == 0) {
            return false;
        }
        if (!$setting['share']['enable']) {
            return false;
        }
        if ($type == self::TYPE_SHARE) {

            $cache = new CacheSetting();
            $count = $cache->get($cache::PREFIX_REWARD_COUNT, "$uid:" . $type . "_" . date("Ymd"));
            //每人每天的次数上限
            //  $count = SiteCashReward::dataCount("type='" . $type . "' and user_id=" . $uid);
            if ($count && $count >= $setting['share']['day_count']) {
                return false;
            }
        }
        return true;
    }

    //机器人发动态红包
    public function sendPackage($uid, $discuss_id)
    {
        //检测是否关闭该功能
        $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "reward");
        $setting = json_decode($setting, true);

        if ($setting['enable'] == 0) {
            return false;
        }
        if (!$setting['robot_discuss_package']['enable']) {
            return false;
        }
        $start = $setting['robot_discuss_package']['start'];//最低金额
        $end = $setting['robot_discuss_package']['end'];//最高金额

        $limit = $setting['robot_discuss_package']['limit'];//每天最大的金额限制

        $total = SiteCashRewardTotal::findOne(["ymd=" . date('Ymd') . ' and type=' . self::TYPE_ROBOT_DISCUSS_PACKAGE, 'columns' => 'money']);
        $total = $total ? $total['money'] : 0;
        //金额已经超额
        if ($total >= $limit) {
            return false;
        }

        $rest = $setting['robot_discuss_package']['limit'] - $total;//剩下的金额
        //余额不足
        if ($rest < $start) {
            return false;
        }

        $probability_start = $start;
        if ($rest > $end) {
            $probability_end = $setting['robot_discuss_package']['end'];
        } else {
            $probability_end = $rest;
        }


        $probability = $setting['robot_discuss_package']['probability'];

        //现金中奖概率 大于0
        if ($probability != 0) {
            $result = Probability::get_rand([$probability, 100 - $probability]);
            //没中奖
            if ($result == 1) {
                return false;
            } else {
                //随机值
                $reward_money = mt_rand($probability_start, $probability_end);
                if ($reward_money > 10) {
                    // $num = mt_rand(10, $reward_money);
                    $num = mt_rand(2, 5);
                } else {
                    $num = mt_rand(2, 4);
                    // $num = mt_rand(1, $reward_money);
                }
                try {
                    $this->original_mysql->begin();
                    $res = false;
                    $post_data = ['uid' => $uid, 'num' => $num, 'money' => $reward_money, 'random' => 1, 'to_square' => 1, 'agent' => 12, 'version' => 1];
                    //exit;
                    $res = Request::getPost(Base::SEND_RED_PACKAGE, $post_data);
                    Debug::log("data:" . var_export($res, true), 'debug');
                    if ($res && $res['curl_is_success']) {
                        $content = json_decode($res['data'], true);
                        if ($content['code'] == '0') {
                            $ymd = date('Ymd');
                            $time = time();
                            $data = [
                                "user_id" => $uid,
                                "type" => self::TYPE_ROBOT_DISCUSS_PACKAGE,
                                "item_id" => $discuss_id,
                                'money' => $reward_money,
                                'extra' => '',
                                'created' => $time,
                                'ymd' => $ymd
                            ];

                            if (!$log = SiteCashReward::insertOne($data)) {
                                throw new \Exception("SiteCashReward表插入数据失败：" . var_export($data, true));
                            }
                            //今天没有赠送过奖励金
                            if (!$total) {
                                $total_data = ['ymd' => $ymd, 'money' => $reward_money, 'type' => self::TYPE_ROBOT_DISCUSS_PACKAGE];
                                if (!SiteCashRewardTotal::insertOne($total_data)) {
                                    throw new \Exception("SiteCashRewardTotal插入数据失败：" . var_export($total_data, true));
                                }
                            } else {
                                if (!SiteCashRewardTotal::updateOne("money=money+" . $reward_money, 'ymd=' . $ymd . ' and type=' . self::TYPE_ROBOT_DISCUSS_PACKAGE)) {
                                    throw new \Exception("SiteCashRewardTotal更新数据失败：" . "money=money+" . $reward_money);
                                }
                            }

                            $res = ['id' => $content['data']['redbagid'], 'money' => $reward_money];

                        }
                    }
                    $this->original_mysql->commit();
                    return $res;
                } catch (\Exception $e) {
                    $this->original_mysql->rollback();
                    Debug::log("奖励失败：" . var_export($e->getMessage(), true), 'error');
                    return false;
                }
            }
        } else {
            return false;
        }

    }

    //广场红包
    public function squarePackage($uid, $package_id, $money)
    {
        $ymd = date('Ymd');
        $time = time();
        $data = [
            "user_id" => $uid,
            "type" => self::TYPE_ROBOT_SQUARE_PACKAGE,
            "item_id" => $package_id,
            'money' => $money,
            'extra' => '',
            'created' => $time,
            'ymd' => $ymd
        ];

        if (!$log = SiteCashReward::insertOne($data)) {
            throw new \Exception("SiteCashReward表插入数据失败：" . var_export($data, true));
        }

        //今天没有赠送过奖励金
        if (!SiteCashRewardTotal::exist("ymd=" . $ymd, true)) {
            $total_data = ['ymd' => $ymd, 'money' => $money, 'type' => self::TYPE_ROBOT_SQUARE_PACKAGE];
            if (!SiteCashRewardTotal::insertOne($total_data)) {
                throw new \Exception("SiteCashRewardTotal插入数据失败：" . var_export($total_data, true));
            }
        } else {
            if (!SiteCashRewardTotal::updateOne("money=money+" . $money, 'ymd=' . $ymd . ' and type=' . self::TYPE_ROBOT_SQUARE_PACKAGE)) {
                throw new \Exception("SiteCashRewardTotal更新数据失败：" . "money=money+" . $money);
            }
        }
    }

    //日统计
    public static function statistic($date)
    {
        if (!$date) {
            return false;
        }
        $list = SiteRewardLog::findList(['ymd="' . $date . '"', 'columns' => 'sum(money) as total_money,platform,type,reward_type', 'group' => 'platform,type,reward_type']);
        $data = [
            //奖励类型
            'type' => [
                //现金
                "cash" => [
                    'total' => 0,
                    'list' => ["1" => 0, "2" => 0, "3" => 0, '4' => 0, "5" => 0]
                ],
                //龙豆
                "coin" => [
                    'total' => 0,
                    'list' => ["1" => 0, "2" => 0, "3" => 0, '4' => 0, "5" => 0]
                ]
            ],
            //奖励方式
            'reward_type' => [
                //动态推荐
                "discuss" => [
                    //现金
                    "cash" => [
                        'total' => 0,
                        'list' => ["1" => 0, "2" => 0, "3" => 0, '4' => 0, "5" => 0]
                    ],
                    //龙豆
                    "coin" => [
                        'total' => 0,
                        'list' => ["1" => 0, "2" => 0, "3" => 0, '4' => 0, "5" => 0]
                    ]
                ],
                //分享
                "share" => [
                    //现金
                    "cash" => [
                        'total' => 0,
                        'list' => ["1" => 0, "2" => 0, "3" => 0, '4' => 0, "5" => 0]
                    ],
                    //龙豆
                    "coin" => [
                        'total' => 0,
                        'list' => ["1" => 0, "2" => 0, "3" => 0, '4' => 0, "5" => 0]
                    ]
                ]
            ],
            //平台类型
            'platform' => [
                "1" => ['cash' => 0, 'coin' => 0],
                "2" => ['cash' => 0, 'coin' => 0],
                "3" => ['cash' => 0, 'coin' => 0],
                '4' => ['cash' => 0, 'coin' => 0],
                "5" => ['cash' => 0, 'coin' => 0]
            ]
        ];
        if ($list) {
            foreach ($list as $item) {
                $reward_type = $item['reward_type'] == 1 ? 'cash' : 'coin';
                $type = $item['type'] == 1 ? 'discuss' : 'share';

                $data['type'][$reward_type]['total'] += $item['total_money'];
                $data['type'][$reward_type]['list'][$item['platform']] = $item['total_money'];
                $data['reward_type'][$type][$reward_type]['total'] += $item['total_money'];
                $data['reward_type'][$type][$reward_type]['list'][$item['platform']] += $item['total_money'];
                $data['platform'][$item['platform']][$reward_type] += $item['total_money'];
            }
        }
        echo $date . "<br/>";
        return SiteReward::insertOne(['ymd' => intval($date), 'detail' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
    }

    //生成一个随机金额
    public static function createMoney($start, $end)
    {
        if ($start == $end) {
            return $start;
        }
        return rand($start, $end);
    }

    public function getMoney()
    {
        return $this->money;
    }
}