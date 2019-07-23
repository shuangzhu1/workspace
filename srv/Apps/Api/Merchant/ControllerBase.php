<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/7
 * Time: 10:08
 */

namespace Merchant;

use Models\Customer\CustomerGame;
use Multiple\Api\Merchant\Helper\Ajax;
use Multiple\Api\Merchant\Helper\Identify;
use Phalcon\Mvc\Controller;
use Util\Debug;

class ControllerBase extends Controller
{
    public $ajax;
    public $app_id = '';
    protected $is_checkApi = true;
    public $app = null;//app

    protected function initialize()
    {
        $this->view->disable();
        $this->ajax = new Ajax();
        $sign = $this->request->get('sign', 'string', '');//签名字段
        $sign_type = $this->request->get('sign_type', 'string', 'MD5');//签名方式
        $time_stamp = $this->request->get('timestamp', 'int', 0);//请求时间戳 10位
        $app_id = $this->request->get('app_id', 'string', '');//app_id
        $development = $this->request->get("development", 'int', 0);//是否可发模式 用于测试 后期移除

        if (!$app_id || !$time_stamp || !$sign_type || !$sign) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $app = CustomerGame::findOne(['app_id="' . $app_id . '" and status=1', 'columns' => 'app_key,support_login,customer_id']);
        if (!$app) {
            $this->ajax->outError(Ajax::ERROR_APP_NOT_EXISTS);
        }
        Debug::log("params:".var_export($_REQUEST,true),'merchant');
        if ($this->is_checkApi && !$development) {
            $this->app = $app;
            if (!$sign) {
                $this->ajax->outError(Ajax::INVALID_SIGN);
            }
            $params = $_REQUEST;
            $verifyResult = Identify::init()->getSignVeryfy($params, $sign, $app['app_key'], $sign_type);
            array_shift($params);//去除_url参数
            if (!$verifyResult) {
                $this->ajax->outError(Ajax::ERROR_SIGN);
            }
        }
        $this->app_id = $app_id;
    }
}