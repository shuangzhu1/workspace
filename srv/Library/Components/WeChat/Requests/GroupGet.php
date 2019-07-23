<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class GroupGet extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/groups/get?access_token=";
    protected $grantType = "client_credential";

    public function run()
    {
        return $this->singleRequest($this->requestUri);
    }
}

?>