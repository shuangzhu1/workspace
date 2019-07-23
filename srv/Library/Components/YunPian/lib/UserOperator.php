<?php
/**
 * Created by PhpStorm.
 * User: bingone
 * Date: 16/1/20
 * Time: 上午10:11
 */

//namespace Yunpian\lib;

namespace Components\YunPian\lib;

use Phalcon\Mvc\User\Plugin;

class UserOperator extends Plugin
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

    public function get($data = array())
    {
        $data['apikey'] = $this->apikey;

        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/user/get.json', $data);
    }

    public function set($data = array())
    {
        $data['apikey'] = $this->apikey;
        return HttpUtil::PostCURL($this->yunpian_config->sms_host . '/v2/user/set.json', $data);
    }
}

?>