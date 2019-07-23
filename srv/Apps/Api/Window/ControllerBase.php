<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/19
 * Time: 17:10
 */

namespace Window;


use Phalcon\Mvc\Controller;
use Util\Ajax;

/**
 *  * @property \Util\Ajax $ajax
 */
class ControllerBase extends Controller
{
    public $ajax;

    protected function initialize()
    {
        $this->view->disable();
        $sign = $this->request->get('sign', 'string', '');//签名字段
        $sign_type = $this->request->get('sign_type', 'string', 'MD5');//签名方式
        //  $lang = $this->request->get("lang", 'int', '1');//语言 1-中文简体 2-中文繁体 3-英文
        //  Debug::log("REQUEST:" . var_export($_REQUEST, true), 'debug');
        //  Debug::log("POST:" . var_export($_POST, true), 'debug');
        //  Debug::log("GET:" . var_export($_GET, true), 'debug');
        //  Debug::log("sign:" . $sign, 'debug');
        $this->ajax = new Ajax();
    }
}