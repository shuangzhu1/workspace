<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/10
 * Time: 14:13
 */

namespace Multiple\Open\Controllers;


use Models\Customer\CustomerGame;
use Multiple\Open\Helper\Ajax;
use Multiple\Open\Helper\Identify;
use Phalcon\Mvc\Controller;
use Util\Debug;

class ControllerBase extends Controller
{
    public $app_id = '';
    public $app = null;
    public $ajax;
    public $is_sdk = false;//是否是sdk访问
    public $sdk_version = '';//sdk版本号
    public $client_type = '';//客户端类型


    protected function onConstruct()
    {
        $this->view->disable();
        $this->ajax = new Ajax();
        $this->app_id = $this->request->get("app_id", 'string', '');
        $controller = $this->router->getControllerName();
        $action = $this->router->getActionName();
        if (!$this->app_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        global $global_redis;
        $global_redis = $this->di->get('redis');
        $this->app = CustomerGame::findOne(['app_id="' . $this->app_id . '" and status=1', 'columns' => 'app_key,apk_sign,bundle_id,dev_bundle_id,package_id,customer_id,name,thumb as logo']);
        if (!$this->app) {
            $this->ajax->outError(Ajax::ERROR_APP_NOT_EXISTS);
        }
        //需要验证签名
        if (($controller == 'user' && ($action == 'auth' || $action == 'appInfo')) || ($controller == 'social')) {
            $this->is_sdk = true;
            $client_type = strtolower($this->request->get("client_type", 'string', ''));//客户端类型
            $sign = $this->request->get("sign", 'string', '');//签名
            // $apk_sign = $this->request->get("apk_sign", 'string', '');//apk签名【仅安卓使用】
            $time_stamp = $this->request->get("time_stamp", 'int', 0);//时间戳
            $sdk_version = $this->request->get("sdk_version", "string", ''); //sdk版本号
            $app_version = $this->request->get("app_version", "string", ''); //恐龙谷版本号
            $this->sdk_version = $sdk_version;
            $this->client_type = $client_type;

            if (!$client_type || !$sign || !$time_stamp || !in_array($client_type, ['android', 'ios']) || !$sdk_version) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }

            $verify = false;
            if ($client_type == 'android') {
                if (!$app_version) {
                    $verify = Identify::init()->getSignVeryfy($_REQUEST, $sign, $this->app['app_key'] . "&" . $this->app['apk_sign'], 'RSA');
                } else {
                    $verify = Identify::init()->getSignVeryfy($_REQUEST, $sign, $this->app['app_key'] . "&" . $this->app['apk_sign']."&". $this->app['package_id'], 'RSA');
                }
            } else if ($client_type == 'ios') {
                if (version_compare($this->sdk_version, '0.0.2', '<')) {
                    $verify = Identify::init()->getSignVeryfy($_REQUEST, $sign, $this->app['app_key'], 'RSA');
                } else {
                    if (!$verify = Identify::init()->getSignVeryfy($_REQUEST, $sign, $this->app['app_key'] . "&" . $this->app['bundle_id'], 'RSA')) {
                        $verify = Identify::init()->getSignVeryfy($_REQUEST, $sign, $this->app['app_key'] . "&" . $this->app['dev_bundle_id'], 'RSA');
                    }
                }
            }
            if (!$verify) {
                $this->ajax->outError(Ajax::ERROR_SIGN);
            }
        }

    }
}