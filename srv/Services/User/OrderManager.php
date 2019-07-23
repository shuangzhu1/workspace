<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/16
 * Time: 11:31
 */

namespace Services\User;


use Models\Agent\AgentApply;
use Models\Orders\Order;
use Models\Shop\ShopApply;
use Phalcon\Mvc\User\Plugin;
use Services\Agent\AgentManager;
use Services\Shop\ShopManager;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\Task\TaskManager;
use Util\Ajax;

/**
 *  * @property \Components\Redis\RedisComponent $redis
 */
class OrderManager extends Plugin
{
    private static $instance = null;
    private static $task_url = 'http://127.0.0.1:4346/';
    /*订单类型*/
    const ORDER_TYPE_DIAMOND = 1001; //龙钻充值

    const PAID_TYPE_WEIXIN = 1;//支付类型-微信

    private $redis = null;
    public static $order_prefix = array(
        self::ORDER_TYPE_DIAMOND => "OD", //龙钻充值订单
    );
    public static $order_type = array(
        self::ORDER_TYPE_DIAMOND => "龙钻充值",
    );


    public function __construct($is_cli = false)
    {
        $this->redis = $this->di->get("redis");
    }

    public static function init($is_cli = false)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    //生成龙钻订单
    public function createDiamondOrder($uid, $to_uid, $money)
    {

        $list = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules");
        //
        $key = (string)($money / 100);
        if (key_exists($key, $list)) {
            $id = $this->generateOrderNumber();
            //订单号
            $order_number = self::$order_prefix[self::ORDER_TYPE_DIAMOND] . $id;
            $data = [
                "type" => self::ORDER_TYPE_DIAMOND,
                "uid" => $uid,
                "to_uid" => $to_uid,
                'money' => $money,
                "coin" => $list[$key]['coin'],
                "donate" => $list[$key]['donate'],
                'created' => time(),
                'ia_paid' => false,
                'paid_number' => '',
                'paid_time' => '',
                "prepare_id" => '',
                'order_number' => $order_number
            ];
            //30分钟过期时间
            $this->redis->originalSet(CacheSetting::KEY_PAY_ORDER_LIST . $order_number, json_encode($data), 360);
            $this->redis->hSet(CacheSetting::KEY_PAY_ORDER_USER, $uid, json_encode($data));
            return $data;
        } else {
            return false;
        }
    }

    //更新砖石支付状态
    public function updateOrderStatus($order_number, $data)
    {
        $order = $this->redis->originalGet(CacheSetting::KEY_PAY_ORDER_LIST . $order_number);
        if ($order) {
            $order = json_decode($order, true);
            $order = array_merge($order, $data);
            $this->redis->originalSet(CacheSetting::KEY_PAY_ORDER_LIST . $order_number, json_encode($order));
            $this->redis->hSet(CacheSetting::KEY_PAY_ORDER_USER, $order['uid'], json_encode($order));
            //订单支付完成
            if (isset($data['is_paid'])) {
                Order::insertOne([
                    'type' => $order['type'],
                    'order_number' => $order_number,
                    'uid' => $order['uid'],
                    'to_uid' => $order['to_uid'],
                    'created' => $order['created'],
                    'paid_time' => $order['paid_time'],
                    'paid_number' => $order['paid_number'],
                    'pay_detail' => $order['pay_detail'],
                    'open_id' => $order['open_id'],
                    'detail' => $order['detail'],
                    'money' => $order['money'],
                    'paid_type' => 1
                ]);
            }
        }
    }

    //获取砖石订单列表
    /**获取订单列表
     * @param $uid
     * @param $open_id
     * @param int $limit
     * @param int $last_id
     * @return array
     */
    public function list($uid, $open_id = '', $limit = 20, $last_id = 0)
    {
        $res = ['data_list' => [], 'data_count' => 0, 'last_id' => 0];
        if ($open_id) {
            $where = "open_id='" . $open_id . "' and type=" . self::ORDER_TYPE_DIAMOND;
        } else {
            $where = "uid='" . $uid . "' and type=" . self::ORDER_TYPE_DIAMOND;
        }
        if ($last_id) {
            $where .= " and id<" . $last_id;
        }
        $list = Order::findList([$where, 'limit' => $limit, 'columns' => '', 'order' => 'created desc']);
        $res['data_count'] = Order::dataCount("uid=" . $uid . " and type=" . self::ORDER_TYPE_DIAMOND);
        if ($list) {
            foreach ($list as $item) {
                $res['data_list'][] = $item;
            }
            $res['last_id'] = $list[count($list) - 1]['id'];
        }
        return $res;
    }

    /**获取订单模型
     * @param $order_number
     * @return bool|AgentApply|Order|ShopApply
     */
    public function getOrderModal($order_number)
    {
        $prefix = $sub_prefix = substr($order_number, 0, 2);
        switch ($prefix) {
            case "OD":
                return new Order();
            case "OS":
                return new ShopApply();
            case "OA":
                return new AgentApply();
            default:
                return false;
        }
    }

    /**
     * 订单倒计时结束 订单置为已取消
     * @param $order_number
     * @return bool
     */
    public function cancelOrder($order_number)
    {
        $prefix = $sub_prefix = substr($order_number, 0, 2);
        switch ($prefix) {
            case "OD":
                return false;
            case "OS":
                return ShopApply::updateOne(['status' => ShopManager::pay_status_has_canceled, 'modify' => time()], 'trade_no="' . $order_number . '" and status=' . ShopManager::pay_status_wait_pay);
            case "OA":
                return AgentApply::updateOne(['status' => AgentManager::STATUS_HAS_CANCELED, 'modify' => time()], 'trade_no="' . $order_number . '" and status=' . AgentManager::STATUS_WAIT_PAY);
            default:
                return false;
        }
    }

    /**
     * 取消订单倒计时任务
     * @param $order_number
     * @return bool
     */
    public function cancelTask($order_number)
    {
        return TaskManager::init(self::$task_url)->remove_job($order_number);
    }

    /**
     * 开始订单倒计时任务
     * @param $order_number
     * @param $date
     * @return bool
     */
    public function startTask($order_number, $date)
    {
        if (TEST_SERVER) {
            $cmd = "php -f /mnt/www/dvalley/scripts/start.php order notify " . $order_number;
        } else {
            $cmd = "php -f /var/www/dvalley/scripts/start.php order notify " . $order_number;
        }
        return TaskManager::init(self::$task_url)->add_job("date", ['run_date' => $date], $order_number, "订单:" . $order_number . "计时器", '', '', $cmd);

    }

    // 生成订单号，
    public function generateOrderNumber()
    {
        $t = explode(' ', microtime());
        $strtime = $t[1];
        # 时间戳后四位+micortime+时间戳前6位随随机
        $o = substr($strtime, 6) . substr($t[0], 2, 6) . mt_rand(substr($strtime, 0, 6), 999999);
        return $o;
    }

    /*根据订单号获取订单类型*/
    public function getOrderType($order_number)
    {
        $sub_prefix = substr($order_number, 0, 2);
        $order_type = array_flip(self::$order_prefix);
        return isset($order_type[$sub_prefix]) ? $order_type[$sub_prefix] : '';
    }
}