<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class SnsAccessToken extends AbstractRequest
{
    protected $needAuth = false;
    protected $requestUri = "https://api.weixin.qq.com/sns/oauth2/access_token?";
    protected $grantType = "authorization_code"; //authorization_code微信内网页授权
    protected $code = '';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'grant_type' => $this->grantType,
            'appid' => $this->app_id,
            'secret' => $this->app_secret,
            'code' => $this->code
        ));
    }
}

?>