<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24
 * Time: 10:26
 */

namespace Multiple\Callback\Controllers;


use Phalcon\Mvc\Controller;
use Util\Ajax;
use Util\GetClient;

class ControllerBase extends Controller
{
    //信任ip数组，不在该数组内的ip回调被丢弃
    private static $trust_ip = [
        '119.23.54.215',
    ];
    protected $ajax;
    public function initialize()
    {
        $this->ajax = new Ajax();
        $remote_ip =  GetClient::Getip();
        if( !in_array($remote_ip,self::$trust_ip) )
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG,'非法请求');

    }
}