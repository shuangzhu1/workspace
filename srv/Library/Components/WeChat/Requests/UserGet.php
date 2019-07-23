<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class UserGet extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=";
    protected $grantType = "client_credential";
    protected $next_openid = '';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'next_openid' => $this->next_openid
        ));
    }
}

?>