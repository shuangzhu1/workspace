<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class GroupUpdate extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/groups/update?access_token=";
    protected $grantType = "client_credential";
    protected $groupId = '';
    protected $name = '';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'group' => array(
                'id' => $this->groupId,
                'name' => $this->name
            )
        ), true, true);
    }

    public function validate()
    {
        if (empty($this->groupId) || empty($this->name)) {
            return false;
        }
        if (strlen($this->name) > 30) {
            return false;
        }

        return true;
    }
}

?>