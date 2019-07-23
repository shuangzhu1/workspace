<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class QrCodePerm extends AbstractRequest
{
    protected $requestUri = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=";
    protected $grantType = "client_credential";
    protected $action_name = 'QR_LIMIT_SCENE';
    protected $scene_id = 0;

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'action_name' => $this->action_name,
            'action_info' => array(
                'scene' => array(
                    'scene_id' => $this->scene_id
                )
            )
        ), true, true);
    }
}

?>