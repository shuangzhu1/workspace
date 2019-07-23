<?php
namespace Components\Payments\WxPay;

use Components\Payments\PaymentUtil;
use Models\Shop\ShopOrders;
use Models\SystemOrders;
use Phalcon\Mvc\User\Plugin;
use Util\GetClient;

/**
 *
 */
class WxPayHelper extends Plugin
{
    /**
     * @var WxPayHelper
     */
    public static $instance;
    public $parameters; //cft 参数
    public $appId = '';
    public $appKey = '';
    public $partnerKey = '';
    public $appSecret = '';
    public $partnerId = '';
    public $signType = 'SHA1';

    private function __construct()
    {

    }

    public static function instance($config)
    {
        if (!self::$instance instanceof WxPayHelper) {
            self::$instance = new self();
        }

        if (isset($config['appid'])) {
            self::$instance->appId = $config['appid'];
        }
        if (isset($config['appkey'])) {
            self::$instance->appKey = $config['appkey'];
        }
        if (isset($config['appsecret'])) {
            self::$instance->appSecret = $config['appsecret'];
        }
        if (isset($config['partnerkey'])) {
            self::$instance->partnerKey = $config['partnerkey'];
        }

        if (isset($config['partnerid'])) {
            self::$instance->partnerId = $config['partnerid'];
        }
        return self::$instance;
    }

    public function setParameter($parameter, $parameterValue)
    {
        $this->parameters[CommonUtil::trimString($parameter)] = CommonUtil::trimString($parameterValue);
    }

    public function getParameter($parameter)
    {
        return $this->parameters[$parameter];
    }

    protected function create_noncestr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            //$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $str;
    }

    public function check_cft_parameters()
    {
        if ($this->parameters["bank_type"] == null || $this->parameters["body"] == null || $this->parameters["partner"] == null ||
            $this->parameters["out_trade_no"] == null || $this->parameters["total_fee"] == null || $this->parameters["fee_type"] == null ||
            $this->parameters["notify_url"] == null || $this->parameters["spbill_create_ip"] == null || $this->parameters["input_charset"] == null
        ) {
            return false;
        }
        return true;

    }

    protected function get_cft_package()
    {
        try {

            if (null == $this->partnerKey || "" == $this->partnerKey) {
                throw new SDKRuntimeException("密钥不能为空！" . "<br>");
            }
            ksort($this->parameters);
            $unSignParaString = CommonUtil::formatQueryParaMap($this->parameters, false);
            $paraString = CommonUtil::formatQueryParaMap($this->parameters, true);

            return $paraString . "&sign=" . MD5SignUtil::sign($unSignParaString, CommonUtil::trimString($this->partnerKey));
        } catch (SDKRuntimeException $e) {
            $this->di->get("paymentLogger")->error($e->errorMessage());
            return "";
        }

    }

    public function get_biz_sign($bizObj)
    {
        foreach ($bizObj as $k => $v) {
            $bizParameters[strtolower($k)] = $v;
        }
        try {
            if ($this->appKey == "") {
                throw new SDKRuntimeException("APPKEY为空！" . "<br>");
            }
            $bizParameters["appkey"] = $this->appKey;
            ksort($bizParameters);
            //var_dump($bizParameters);
            $bizString = CommonUtil::formatBizQueryParaMap($bizParameters, false);
            //var_dump($bizString);
            return sha1($bizString);
        } catch (SDKRuntimeException $e) {
            $this->di->get("paymentLogger")->error($e->errorMessage());
            return "";
        }
    }
    //生成app支付请求json
    /*
    {
    "appid":"wwwwb4f85f3a797777",
    "traceid":"crestxu",
    "noncestr":"111112222233333",
    "package":"bank_type=WX&body=XXX&fee_type=1&input_charset=GBK&notify_url=http%3a%2f%2f
        www.qq.com&out_trade_no=16642817866003386000&partner=1900000109&spbill_create_ip=127.0.0.1&total_fee=1&sign=BEEF37AD19575D92E191C1E4B1474CA9",
    "timestamp":1381405298,
    "app_signature":"53cca9d47b883bd4a5c85a9300df3da0cb48565c",
    "sign_method":"sha1"
    }
    */
    public function create_app_package($traceid = "")
    {
        //echo $this->create_noncestr();
        try {
            //var_dump($this->parameters);
            if ($this->check_cft_parameters() == false) {
                throw new SDKRuntimeException("生成package参数缺失！" . "<br>");
            }
            $nativeObj["appid"] = $this->appId;
            $nativeObj["package"] = $this->get_cft_package();
            $nativeObj["timestamp"] = time();
            $nativeObj["traceid"] = $traceid;
            $nativeObj["noncestr"] = $this->create_noncestr();
            $nativeObj["app_signature"] = $this->get_biz_sign($nativeObj);
            $nativeObj["sign_method"] = $this->signType;
            return json_encode($nativeObj);
        } catch (SDKRuntimeException $e) {
            $this->di->get("paymentLogger")->error($e->errorMessage());
            return "";
        }
    }
    //生成jsapi支付请求json
    /*
    "appId" : "wxf8b4f85f3a794e77", //公众号名称，由商户传入
    "timeStamp" : "189026618", //时间戳这里随意使用了一个值
    "nonceStr" : "adssdasssd13d", //随机串
    "package" : "bank_type=WX&body=XXX&fee_type=1&input_charset=GBK&notify_url=http%3a%2f
    %2fwww.qq.com&out_trade_no=16642817866003386000&partner=1900000109&spbill_create_i
    p=127.0.0.1&total_fee=1&sign=BEEF37AD19575D92E191C1E4B1474CA9",
    //扩展字段，由商户传入
    "signType" : "SHA1", //微信签名方式:sha1
    "paySign" : "7717231c335a05165b1874658306fa431fe9a0de" //微信签名
    */
    public function create_biz_package()
    {
        try {

            if ($this->check_cft_parameters() == false) {
                throw new SDKRuntimeException("生成package参数缺失！" . "<br>");
            }
            $nativeObj["appId"] = $this->appId;
            $nativeObj["package"] = $this->get_cft_package();
            $nativeObj["timeStamp"] = strval(time());
            $nativeObj["nonceStr"] = $this->create_noncestr();
            $nativeObj["paySign"] = $this->get_biz_sign($nativeObj);
            $nativeObj["signType"] = strtoupper($this->signType);

            return json_encode($nativeObj);

        } catch (SDKRuntimeException $e) {
            $this->di->get("paymentLogger")->error($e->errorMessage());
            return "";
        }

    }
    //生成原生支付url
    /*
     * weixin://wxpay/bizpayurl?sign=XXXXX&appid=XXXXXX&productid=XXXXXX&timestamp=XXXXXX&noncestr=XXXXXX
     * @param $order_number 订单编号
     */
    public function create_native_url($order_number)
    {

        $nativeObj["appid"] = $this->appId;
        $nativeObj["productid"] = urlencode($order_number);
        $nativeObj["timestamp"] = time();
        $nativeObj["noncestr"] = $this->create_noncestr();
        try {
            $nativeObj["sign"] = $this->get_biz_sign($nativeObj);
        } catch (\Phalcon\Exception $e) {
            $nativeObj["sign"] = '';
        }
        $bizString = CommonUtil::formatBizQueryParaMap($nativeObj, false);
        return "weixin://wxpay/bizpayurl?" . $bizString;

    }
    //生成原生支付请求xml
    /*
    <xml>
    <AppId><![CDATA[wwwwb4f85f3a797777]]></AppId>
    <Package><![CDATA[a=1&url=http%3A%2F%2Fwww.qq.com]]></Package>
    <TimeStamp> 1369745073</TimeStamp>
    <NonceStr><![CDATA[iuytxA0cH6PyTAVISB28]]></NonceStr>
    <RetCode>0</RetCode>
    <RetErrMsg><![CDATA[ok]]></ RetErrMsg>
    <AppSignature><![CDATA[53cca9d47b883bd4a5c85a9300df3da0cb48565c]]>
    </AppSignature>
    <SignMethod><![CDATA[sha1]]></ SignMethod >
    </xml>
    */
    public function create_native_package($retcode = 0, $reterrmsg = "ok")
    {
        try {
            if ($this->check_cft_parameters() == false && $retcode == 0) { //如果是正常的返回， 检查财付通的参数
                throw new SDKRuntimeException("生成package参数缺失！" . "<br>");
            }
            $nativeObj["AppId"] = $this->appId;
            $nativeObj["Package"] = $this->get_cft_package();
            $nativeObj["TimeStamp"] = time();
            $nativeObj["NonceStr"] = $this->create_noncestr();
            $nativeObj["RetCode"] = $retcode;
            $nativeObj["RetErrMsg"] = $reterrmsg;
            $nativeObj["AppSignature"] = $this->get_biz_sign($nativeObj);
            $nativeObj["SignMethod"] = $this->signType;

            return CommonUtil::arrayToXml($nativeObj);

        } catch (SDKRuntimeException $e) {
            $this->di->get("paymentLogger")->error($e->errorMessage());
            return "";
        }

    }

    /**
     * generate a js function
     * @param $title
     * @param $total_cash
     * @param $jsCallBackName
     * @return string
     */
    public function getJsCode($order_number, $order_type, $jsCallBackName)
    {
        $title = "购买商品";
        if (strtolower($order_type) == PaymentUtil::ORDER_TYPE_PLATFORM) {
            $order = SystemOrders::findFirst("order_number='{$order_number}'");
            $title = "购买平台功能";
        } else if (strtolower($order_type) == PaymentUtil::ORDER_TYPE_SHOP_ORDER) {
            $order = ShopOrders::findFirst("order_number='{$order_number}'");
            $title = "购买商品";
        } else {
            $order = ShopOrders::findFirst("order_number='{$order_number}'");
            $title = "购买商品";
        }
        $this->setParameter("bank_type", "WX");
        $this->setParameter("body", $title);
        $this->setParameter("partner", $this->partnerId);
        $this->setParameter("out_trade_no", $order_number);
        $this->setParameter("total_fee", ($order->paid_cash + $order->logistics_fee) * 100);
        $this->setParameter("product_fee", $order->paid_cash * 100);
        $this->setParameter("transport_fee", $order->logistics_fee * 100);
        $this->setParameter("fee_type", "1");
        $this->setParameter("notify_url", "http://" . FRONT_DOMAIN . "/payment/wxpay/notice/{$order_type}");
        $this->setParameter("spbill_create_ip",  GetClient::Getip());
        $this->setParameter("input_charset", "UTF-8");

        try {
            $bizPackage = $this->create_biz_package();
            $bizPackage = empty($bizPackage) ? "''" : $bizPackage;
            $callBackStr = <<<EOF
    function wxpayCallBack()
    {
        WeixinJSBridge.invoke('getBrandWCPayRequest',{$bizPackage},function(res){
            WeixinJSBridge.log(res.err_msg);
            {$jsCallBackName}(res);
        });
    }\r\n
EOF;

            return $callBackStr;
        } catch (\Phalcon\Exception $e) {
            return "";
        }

    }

    public function getAccessToken()
    {
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appId . '&secret=' . $this->appSecret);
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        $jsondata = json_decode($data);
        return $jsondata;
    }

    public function getPrepayId($accessToken, $packageStr)
    {
        //get prepay id
        $ch2 = curl_init();//初始化curl
        curl_setopt($ch2, CURLOPT_URL, 'https://api.weixin.qq.com/pay/genprepay?access_token=' . $accessToken);
        curl_setopt($ch2, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch2, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $packageStr);
        $data2 = curl_exec($ch2);//运行curl
        curl_close($ch2);
//$jsondata2 = json_decode($data2);

        return $data2;
    }

}

?>