<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/16
 * Time: 9:52
 */

namespace Multiple\Wap\Api;


use Components\Payments\WxPay\WxPayV3\JsApiPay;
use Components\Payments\WxPay\WxPayV3\WxPayApi;
use Components\Payments\WxPay\WxPayV3\WxPayData\WxPayUnifiedOrder;
use Models\User\UserInfo;
use Services\Site\CacheSetting;
use Services\User\OrderManager;
use Util\Ajax;
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

    public function indexAction()
    {
        echo "success";
        exit;

    }

    protected function initParams($order_number = null, $platform = 'wap')
    {
        $this->redis = $this->di->get("redis");
        $this->platform = $platform;
        $this->order_number = $order_number;
        if (empty($order_number)) {
            Debug::log('对不起，没有接收到有效的订单号，说明这是非法的请求', 'payment');
            // $this->printMessage("对不起，没有接收到有效的订单号，说明这是非法的请求");
            exit;
        }
        $order_type = OrderManager::init()->getOrderType($order_number);
        Debug::log("order_type:" . $order_type, 'payment');
        if (empty($order_type)) {
            Debug::log('对不起，没有接收到有效的数据，说明这是非法的请求', 'payment');
            //  $this->printMessage("'对不起，没有接收到有效的数据，说明这是非法的请求'");
            exit;
        }
        $this->order_type = $order_type;
        switch ($this->order_type) {
            //龙钻
            case  OrderManager::ORDER_TYPE_DIAMOND:
                //  Debug::log("order_number2222='{$this->order_number}'", 'payment');
                $this->order = $this->redis->originalGet(CacheSetting::KEY_PAY_ORDER_LIST . $order_number);
                $this->subject = "龙钻充值";
                break;
        }
        if (!$this->order) {
            Debug::log('对不起，订单不存在', 'payment');
            //  $this->printMessage("'对不起，没有接收到有效的数据，说明这是非法的请求'");
            exit;
        }
        $this->order = json_decode($this->order);
    }

    public function payAction()
    {

        $type = $this->request->get("type");
        //$uid = $this->request->get("uid");
        $uid = 0;// $this->session->get("uid");
        $to_uid = $this->request->get("to_uid");
        $money = $this->request->get("money", 'int', 0);
        if (!$to_uid) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请填写需要充值的账号ID");
        }
        if (!UserInfo::exist("user_id=" . $to_uid)) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "填写的充值账号不存在");
        }

        $order = OrderManager::init()->createDiamondOrder($uid, $to_uid, $money);
        if (!$order) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "订单提交失败");
        }
        $this->initParams($order['order_number'], "wap");
        $jsapi = new JsApiPay();

        $open_id = $this->session->get("open_id");
        //$this->ajax->outRight($open_id);
        //$open_id = 'onPFn0tCP-0RafDSTJ-dsDnS9Eoc';
        if (!$open_id) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "获取open_id失败");
        } else {
            //$openId = 'oeZ6AuEivzIC6C712GyKIDzxh1so';
            //②、统一下单

            $input = new WxPayUnifiedOrder();
            $input->SetBody("购买商品");
            $input->SetAttach("购买商品");
            $input->SetOut_trade_no($this->order_number);
            $input->SetTotal_fee(($this->order->money));
            //   $input->SetTime_start(date("yyyyMMddHHmmss"));
            //  $input->SetTime_expire(date("yyyyMMddHHmmss", time() + 600));
            $input->SetGoods_tag("商品购买");
            $input->SetNotify_url("http://wap.klgwl.com/payment/wxpay/notify/" . EasyEncrypt::encode($this->order_number));
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($open_id);
            $order = WxPayApi::unifiedOrder($input);


            if (isset($order['prepay_id'])) {
                //更新订单状态
                OrderManager::init()->updateOrderStatus($this->order_number, ["prepay_id" => $order['prepay_id']]);
                $jsApiParameters = $jsapi->GetJsApiParameters($order['appid'], $order['prepay_id']);
                $this->ajax->outRight($jsApiParameters);

            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "支付失败");
            }
        }
    }
}