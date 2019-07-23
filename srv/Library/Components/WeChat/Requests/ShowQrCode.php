<?php

namespace Components\Weixin\Requests;

/**
 *
 */
use Components\Weixin\AbstractRequest;

class QrCodeTemp extends AbstractRequest
{
    protected $requestUri = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=";
    protected $grantType = "client_credential";
    protected $ticket = '';

    public function run()
    {
        $this->requestUri .= $this->ticket;
        return $this->singleRequest($this->requestUri);
    }
}

?>