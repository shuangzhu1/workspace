<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class AccessToken extends AbstractRequest
{
    protected $needAuth = false;
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/token?";
    protected $grantType = "client_credential"; //authorization_code微信内网页授权

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'grant_type' => $this->grantType,
            'appid' => $this->app_id,
            'secret' => $this->app_secret
        ));
    }
}

?>