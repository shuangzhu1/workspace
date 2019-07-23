<?php
/**
 * Created by PhpStorm.
 * User: Arimis
 * Date: 14-6-13
 * Time: 下午2:37
 */

namespace Components\Payments;


use Components\Payments\WxPay\WxPayHelper;
use Components\WeChat\MessageManager;
use Components\WeChat\RequestFactory;
use Models\CustomerOpenInfo;
use Models\Shop\ShopOrders;
use Models\SystemOrders;
use Phalcon\Mvc\User\Plugin;

class OrderPaymentUtil extends Plugin
{
    private static $instance = null;
    public $host_key = null;
    public $customer_id = null;

    const ORDER_STATUS_WAIT_BUYER_PAY = 1001;
    const ORDER_STATUS_WAITE_SEND_GOODS = 1002;
    const ORDER_STATUS_WAITE_CONFIRM_GOODS = 1003;
    const ORDER_STATUS_SUCCESS = 1004;
    const ORDER_STATUS_CASH_ARRIVED = 1005;
    const ORDER_STATUS_FINISHED = 1006;

    private function __construct()
    {

    }

    /**
     * @return OrderPaymentUtil
     */
    public static function instance()
    {
        if (!self::$instance instanceof OrderPaymentUtil) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function paymentCallBack($order_type, $order_number, $status, $paid_order = null)
    {
        switch ($order_type) {
            case PaymentUtil::ORDER_TYPE_PLATFORM: {
                $this->platformOrderPaid($order_number, $status);
                break;
            }
            case PaymentUtil::ORDER_TYPE_SHOP_ORDER: {
                $this->shopOrderPaid($order_number, $status);
                break;
            }
            case PaymentUtil::ORDER_TYPE_VIP_CARD: {
                $this->cardOrderPaid($order_number, $status);
                break;
            }
        }
    }

    public function platformOrderPaid($order_number, $status)
    {
        $order = SystemOrders::findFirst("order_number='{$order_number}'");
        if ($order) {
            $order->update(array("status" => '1', 'paid_time' => time(), 'paid_type' => 'alipay', 'paid_order_no' => $order_number));
        }
    }

    public function shopOrderPaid($order_number, $status)
    {
        $order = ShopOrders::findFirst("order_number='{$order_number}'");
        if ($order) {
            $order->update(array("status" => '1', 'paid_time' => time(), 'paid_type' => 'alipay', 'paid_order_no' => $order_number));
        }
    }

    public function cardOrderPaid($order_number, $status)
    {
        $order = ShopOrders::findFirst("order_number='{$order_number}'");
        if ($order) {
            $order->update(array("status" => '1', 'paid_time' => time(), 'paid_type' => 'alipay', 'paid_order_no' => $order_number));
        }
    }

    public function deliverNotice($customer, $order_number, $status)
    {
        $order = ShopOrders::findFirst("order_number='{$order_number}'");
        if (!$order) {
            return false;
        }
        if (strtolower($order->paid_type) == PaymentUtil::PAYMENT_KEY_WXPAY) {
            $wxpay_config = PaymentUtil::instance($this->host_key)->getPaymentConfig(PaymentUtil::BELONG_TYPE_CUSTOMER, $customer, PaymentUtil::PAYMENT_KEY_WXPAY);
            $_t = (array)$wxpay_config;
            $wxHelper = WxPayHelper::instance($_t);
            $sign = $wxHelper->get_biz_sign(array(
                "appid" => $wxpay_config['appid'],
                'appkey' => $wxpay_config['appkey'],
                "openid" => "{$order->wx_open_id}",
                "transid" => $order->paid_order,
                "out_trade_no" => $order_number,
                "deliver_timestamp" => $order->delivered_time,
                "deliver_status" => $status,
                "deliver_msg" => "ok",
            ));

            $this->di->get('paymentLogger')->info("start backend deliver goods notify wechat payment server: sign array data:" . json_encode($sign));

            $openInfo = CustomerOpenInfo::findFirst("customer_id = '{$customer}' AND platform='" . MessageManager::PLATFORM_TYPE_WEIXIN . "'");

            $request = RequestFactory::create("Payment\\DeliverNotify", $customer, $openInfo->app_id, $openInfo->app_secret);
            $request->set("appid", $wxpay_config['appid']);
            $request->set("openid", $order->remark1);
            $request->set("transid", $order->paid_order);
            $request->set("out_trade_no", $order_number);
            $request->set("deliver_timestamp", $order->delivered_time);
            $request->set("deliver_status", $status);
            $request->set("deliver_msg", 'ok');
            $request->set("app_signature", $sign);
            $request->set("sign_method", 'sha1');
            $request->run();
            if ($request->isFailed()) {
                $this->di->get('paymentLogger')->info("backend deliver goods notify failed:" . $request->getErrorMessage());
                return $request->getErrorMessage();
            }
            return true;
        }
    }
} 