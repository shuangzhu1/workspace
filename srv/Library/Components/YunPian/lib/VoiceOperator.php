<?php
/**
 * Created by PhpStorm.
 * User: bingone
 * Date: 16/1/19
 * Time: 下午5:42
 */

namespace Components\YunPian\lib;

use Phalcon\Mvc\User\Plugin;

class VoiceOperator extends Plugin
{
    public $apikey;
    public $api_secret;
    public $yunpian_config;

    public function __construct($apikey = null, $api_secret = null)
    {
        $this->yunpian_config = $this->di->get('config')->yun_pian;
        if ($api_secret == null)
            $this->api_secret = $this->yunpian_config->app_secret;
        else
            $this->api_secret = $apikey;
        if ($apikey == null)
            $this->apikey = $this->yunpian_config->app_key;
        else
            $this->apikey = $api_secret;
    }

    public function encrypt(&$data)
    {

    }

    public function send($data = array())
    {
        if (!array_key_exists('mobile', $data))
            return new Result($error = 'mobile 为空');
        if (!array_key_exists('code', $data))
            return new Result($error = 'code 为空');
        $data['apikey'] = $this->apikey;
//        encrypt($data);
        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/voice/send.json', $data);
    }

    public function pull_status($data = array())
    {
        $data['apikey'] = $this->apikey;
        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/voice/pull_status.json', $data);
    }

}
