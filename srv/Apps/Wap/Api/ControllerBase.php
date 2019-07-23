<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/3
 * Time: 16:36
 */

namespace Multiple\Wap\Api;


use Multiple\Wap\Helper\UserStatus;
use Phalcon\Mvc\Controller;
use Util\Ajax;
use Util\GetClient;
use Util\Uri;

/**
 *  * @property \Util\Ajax $ajax
 */
class ControllerBase extends Controller
{
    protected $checkLogin = false;
    protected $user_id = 0;
    protected $ajax;

    public function initialize()
    {
        if ( GetClient::Getip() == '14.17.44.173') {
            echo 403;
            exit;
        }
        /*if( !$this->request->isAjax() || empty($_SERVER['HTTP_REFERER']) || !strpos($_SERVER['HTTP_REFERER'],WAP_DOMAIN_DS.'.'.MAIN_DOMAIN)  )
            Ajax::init()->outError(Ajax::INVALID_PARAM);*/
        $this->view->disable();
        $this->user_id = UserStatus::init()->getUid();
        $this->uri = $this->uri = new Uri();

        if ($this->checkLogin) {
            $this->checkLogin();
        }
        global $global_redis;
        $global_redis = $this->di->get('redis');
        // page and limit
        $this->ajax = new Ajax();
    }

    protected function checkLogin()
    {
        if (!UserStatus::init()->isLogged()) {
            Ajax::init()->outError(Ajax::ERROR_USER_HAS_NOT_LOGIN);
            exit;
        }
    }

    protected function getFromOB($path, $item)
    {
        ob_start();
        if (is_string($item)) {
            $this->view->setVar("item", $item);
        } else if (is_array($item)) {
            extract($item);
            /*  foreach ($item as $k => $i) {
                  $this->view->setVar("$k", $i);
              }*/
        }
        include MODULE_PATH . "/Views/" . $path . ".phtml";
        $str = ob_get_contents();
        ob_get_clean();
        return $str;
    }
}