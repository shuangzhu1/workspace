<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class UserInfo extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=";
    protected $grantType = "client_credential";
    protected $openid = '';
    protected $lang = 'zh_CN';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'openid' => $this->openid,
            'lang' => $this->lang
        ));
    }
}

?>