<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class GroupCreate extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/groups/create?access_token=";
    protected $grantType = "client_credential";
    protected $name = '';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'name' => $this->name
        ));
    }
}

?>