<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class MenuCreate extends AbstractRequest
{
    protected $needAuth = true;
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=";
    protected $menu = "{}";

    public function run()
    {
        $result = $this->singleRequest($this->requestUri, $this->menu, true, true);
        $this->di->get('wechatLogger')->info(json_encode($result));
        if ($result['errcode'] == 0) {
            return true;
        } else {
            return $result;
        }
    }
}

?>