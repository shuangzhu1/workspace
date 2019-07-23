<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class FileDownload extends AbstractRequest
{
    protected $needAuth = true;
    protected $requestUri = "http://file.api.weixin.qq.com/cgi-bin/media/upload";
    public $type = '';
    public $media = '';
    public $source = '';

    public function run()
    {
        return $this->singleRequest($this->requestUri, array(
            'access_token' => $this->access_token,
            'type' => $this->type,
            'media' => $this->media
        ));
    }

    public function validation()
    {
        if (empty($this->type)) {
            return false;
        }

        if (empty($this->media)) {
            return false;
        }
        return true;
    }
}

?>