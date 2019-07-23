<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class GroupMoveMember extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=";
    protected $grantType = "client_credential";
    protected $openId = '';
    protected $toGroupId = '';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'openid' => $this->openId,
            'to_groupid' => $this->toGroupId,
        ), true, true);
    }
}

?>