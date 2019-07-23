<?php

namespace Components\WeChat\Requests;

/**
 *
 */
use Components\WeChat\AbstractRequest;

class FileUpload extends AbstractRequest
{
    protected $needAuth = true;
    protected $requestUri = "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=";
    public $type = '';
    public $mime = '';
    public $file_path = '';
    public $name = '';

    public function run()
    {
        $this->requestUri .= '&type=' . $this->type;
        $this->result = $this->fileUploadRequest($this->requestUri, $this->file_path, $this->mime, $this->name);
//        $this->result = $this->singleRequest($this->requestUri, array(
//            'media' => '@' . $this->file_path
//        ));
        return $this->result;
    }
}

?>