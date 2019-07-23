<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class GroupGetId extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/groups/getid?access_token=";
    protected $grantType = "client_credential";
    protected $openid = '';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'openid' => $this->openid,
        ), true, true);
    }
}

?>