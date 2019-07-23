<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class SnsUserInfo extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=";
    protected $accessToken = "";
    protected $openid = '';
    protected $lang = 'zh_CN';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'access_token' => $this->accessToken,
            'openid' => $this->openid,
            'lang' => $this->lang
        ));
    }
}

?>