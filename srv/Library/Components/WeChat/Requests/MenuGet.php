<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class MenuGet extends AbstractRequest
{
    protected $needAuth = true;
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=";

    public function run()
    {
        return $this->singleRequest($this->requestUri);
    }
}

?>