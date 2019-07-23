<?php

namespace Components\WeChat\Requests\Payment;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class DeliverNotify extends AbstractRequest
{
    protected $needAuth = true;
    protected $requestUri = "https://api.weixin.qq.com/pay/delivernotify?access_token=";
    protected $grantType = "client_credential";

    protected $appid;
    protected $openid;
    protected $out_trade_no;
    protected $transid;
    protected $deliver_timestamp;
    protected $deliver_status = 1;
    protected $deliver_msg = 'ok';
    protected $app_signature = '';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            "appid" => $this->appid,
            "openid" => $this->openid,
            "transid" => $this->transid,
            "out_trade_no" => $this->out_trade_no,
            "deliver_timestamp" => $this->deliver_timestamp,
            "deliver_status" => $this->deliver_status,
            "deliver_msg" => $this->deliver_msg,
            "app_signature" => $this->app_signature,
            "sign_method" => "sha1"
        ), true, true);
    }
}

?>