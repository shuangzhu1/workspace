<?php
/**
 * Created by PhpStorm.
 * User: wgwang
 * Date: 14-5-8
 * Time: 上午11:49
 */

namespace Multiple\Payment\Controllers;

use Components\Payments\WxPay\WxPayV3\WxPayApi;
use Components\Payments\WxPay\WxPayV3\WxPayData\WxPayNotifyReply;
use Components\Payments\WxPay\WxPayV3\WxPayData\WxPayOrderQuery;
use Components\Payments\WxPay\WxPayV3\WxPayData\WxPayResults;
use Models\Orders\Order;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\User\OrderManager;
use Util\Debug;
use Util\EasyEncrypt;

/**
 *  * @property \Components\Redis\RedisComponent $redis
 */
class WxpayController extends ControllerBase
{

    public $order_type = null;

    public $order_number = null;
    public $platform = "wap";
    public $order = null;
    public $subject = '';
    private $redis = null;

    protected function initParams($order_number = null, $platform = 'wap')
    {
        Debug::log("init start", 'payment');
        $this->redis = $this->di->get("redis");
        $this->platform = $platform;
        $this->order_number = $order_number;
        //   $this->order_number = EasyEncrypt::decode($order_number);
        if (empty($order_number)) {
            Debug::log('对不起，没有接收到有效的订单号，说明这是非法的请求', 'payment');
            // $this->printMessage("对不起，没有接收到有效的订单号，说明这是非法的请求");
            exit;
        }
        Debug::log("order number:" . $this->order_number, 'payment');

        $order_type = OrderManager::init()->getOrderType($this->order_number);
        Debug::log("order_type:" . $order_type, 'payment');
        $this->order_type = $order_type;
        if (empty($order_type)) {
            Debug::log('对不起，没有接收到有效的数据，说明这是非法的请求', 'payment');
            //  $this->printMessage("'对不起，没有接收到有效的数据，说明这是非法的请求'");
            exit;
        }
        switch ($this->order_type) {
            //龙钻
            case  OrderManager::ORDER_TYPE_DIAMOND:
                //  Debug::log("order_number2222='{$this->order_number}'", 'payment');
                $this->order = $this->redis->originalGet(CacheSetting::KEY_PAY_ORDER_LIST . $this->order_number);
                $this->subject = "龙钻充值";
                break;
        }
        if (!$this->order) {
            Debug::log('对不起，订单不存在', 'payment');
            //  $this->printMessage("'对不起，没有接收到有效的数据，说明这是非法的请求'");
            exit;
        }
        Debug::log("init end", 'payment');
        $this->order = json_decode($this->order);
    }

    public function notifyAction($order_number)
    {
        $this->view->disable();
        Debug::log("订单开始回调---", 'payment');
        Debug::log("订单开始回调->order_number:" . $order_number, "payment");
        $postData = file_get_contents('php://input');
        $postXml = simplexml_load_string($postData);
        $pay_detail = json_encode((array)simplexml_load_string($postData, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE);
        Debug::log('回调数据详情----:' . $pay_detail, 'payment');

        if (strlen($postData) > 0 && $postXml instanceof \SimpleXMLElement) {
            $order_number = $postXml->out_trade_no;
            //$type =$postXml->type;
            $this->initParams($order_number);
            if ($this->order->is_paid) {
                Debug::log("订单已经回调过", 'payment');
                exit;
            }
            /*检测微信订单的真实性*/
            $input = new WxPayOrderQuery();
            $input->SetTransaction_id($postXml->transaction_id);
            $orderExist = WxPayApi::orderQuery($input);
            //   Debug::log('result1:' . var_export($orderExist, true), 'payment');
            if (!$orderExist) {
                Debug::log('微信订单不存在', 'payment');
                exit;
            }
            Debug::log('check sign start:', 'payment');
            /*检测签名*/
            $result = new WxPayResults();
            // $result->FromXml($GLOBALS['HTTP_RAW_POST_DATA']);
            $result->FromXml(file_get_contents("php://input"));
            if (!$result->CheckSign()) {
                Debug::log('签名错误', 'payment');
                exit;
            }
            $pay_detail = json_encode((array)simplexml_load_string($postData, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE);

            if ($postXml->result_code == 'SUCCESS') {
                $pay_id = (string)$postXml->transaction_id;
                Debug::log('update order status start:', 'payment');
                //更新订单状态
                OrderManager::init()->updateOrderStatus($order_number, [
                    'type' => $this->order_type,
                    'is_paid' => 1,
                    'paid_number' =>$pay_id,
                    'paid_time' => time(),
                    'pay_detail' => $pay_detail,
                    'open_id' => $postXml->openid,
                    'detail' => json_encode($this->order, JSON_FORCE_OBJECT),
                    "money" => $this->order->money
                ]);

                $transfer_data = [
                    'uid' => intval($this->order->to_uid),
                    'type' => 0,
                    'sub_type' => 1,
                    'money' => intval($this->order->money),
                    "transferway" => "",
                    "description" => "微信充值",
                    "created" => time(),
                    "out_payid" => $pay_id,
                ];
                //钱包充值
                $res1 = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
                    'to_uid' => intval($this->order->to_uid),
                    'money' => intval($this->order->money),
                    'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
                ]);
                if (!$res1 || !$res1['curl_is_success']) {
                    Debug::log("调起钱包充值失败:" . var_export($res1, true), 'payment');
                } else {
                    $content = json_decode($res1['data'], true);
                    if ($content['code'] !== 200) {
                        Debug::log("调起钱包充值失败->code:" . $content['code'] . ":" . var_export($res1, true), 'payment');
                    } else {
                        //余额消费
                        $consume_data = [
                            'goods' => 1,
                            "type" => 1,
                            'data' => json_encode([
                                    'uid' => intval($this->order->to_uid),
                                    'coin' => intval($this->order->coin + $this->order->donate),
                                    'money' => intval($this->order->money),
                                    "way" => 5,
                                    "extend" => json_encode(["money" => $this->order->money, "donate" => $this->order->donate, "uid" => $this->order->uid, "to_uid" => $this->order->to_uid, "coin" => $this->order->coin])
                                ]
                            )];
                        $res2 = Request::getPost(Request::WALLET_BALANCE_CONSUME, $consume_data);
                        if (!$res2 || !$res2['curl_is_success']) {
                            Debug::log("调起余额消费失败:" . var_export($res2, true), 'payment');
                        } else {
                            $content = json_decode($res2['data'], true);
                            if ($content['code'] !== 200) {
                                Debug::log("调起余额消费失败->code:" . $content['code'] . ":" . var_export($res2, true), 'payment');
                            }
                        }
                    }
                }
            }
            //返回给微信成功/失败
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
            $result = WxPayResults::Init($xml);
            $reply = new WxPayNotifyReply();
            $msg = "OK";
            if ($result == false) {
                $reply->SetReturn_code("FAIL");
                $reply->SetReturn_msg($msg);
            } else {
                $reply->SetReturn_code("SUCCESS");
                $reply->SetReturn_msg("OK");
            }
            Debug::log('充值成功:', 'payment');
            echo $reply->ToXml();
            exit;

        }

    }

    public function afterSaleAction()
    {
    }

    public function alertAction()
    {
        echo "success";
    }
}
