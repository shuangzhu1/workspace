<?php

namespace Multiple\Panel\Api;


use Components\Curl\CurlManager;
use Components\Passport\Identify;
use Models\Customers;
use Multiple\Panel\Plugins\AdminPrivilege;
use Phalcon\Mvc\Controller;
use Util\Ajax;

/**
 * Class ApiBase
 * @package Multiple\Panel\Api
 * @property \Util\Ajax ajax
 */
class ApiBase extends Controller
{
    protected $app = 0;
    protected $_check_login = true;
    protected $ajax;
    protected $customer;
    protected $customer_id;
    protected $customer_wechat;
    protected $customer_weibo;
    protected $admin = null;

    protected function onConstruct()
    {
        $this->view->disable();
        $this->ajax = new Ajax();

        global $global_redis;
        $global_redis = $this->di->get('redis');

        if ($this->_check_login) {
            $auth = $this->session->get('customer_auth');
            if (!$auth) {
                $this->ajax->outError(Ajax::ERROR_USER_HAS_NOT_LOGIN);
                $this->afterExecuteRoute();
                die;
            }
            $this->admin = $admin = $this->session->get('admin');
            if ($this->admin) {
                if (!defined('CUR_APP_ID')) define('CUR_APP_ID', $this->admin['id']);
            } else { //尚未登陆
                $this->ajax->outError(Ajax::ERROR_USER_HAS_NOT_LOGIN);
                $this->afterExecuteRoute();
                die;
            }
            #AdminPrivilege::init()->checkApiPermission();

            $this->customer_wechat = $this->session->get('customer_wechat');
            $this->customer_weibo = $this->session->get('customer_weibo');
        }


    }

    // ajax 输出
    public function afterExecuteRoute()
    {
        $this->setHead();
        $data = $this->view->getParamsToView();

        $result = array(
            'error' => array('code' => Ajax::ERROR_RUN_TIME_ERROR_OCCURRED, 'msg' => Ajax::getErrorMsg(Ajax::ERROR_RUN_TIME_ERROR_OCCURRED), 'more' => "数据无返回"),
            'result' => 0,
        );
        // 设置了数据
        if (isset($data['data'])) {
            $result = $data['data'];
        }

        $this->response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $this->response->send();
    }

    public function setHead()
    {
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Headers', 'content-disposition, origin, content-type, accept');
        $this->response->setHeader('Access-Control-Allow-Credentials', 'true');
        $this->response->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
        $this->response->setHeader('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        $this->response->setHeader('Pragma', 'no-cache');
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

    /**
     * @param string $path url中path信息 前面不带‘/’
     * @param array $data
     * @param bool $domain 为空取默认值：http://service.klgwl.com/,
     * @return mixed
     */
    public function postApi($path = '', $data = [], $domain = '')
    {
        $apiDomain = $this->config->api_domain;
        if (empty($domain)) {
            $url = $apiDomain['service'] . $path;
            $data['random'] = rand(1000, 1000000);
            $data['sign_type'] = 'MD5';
            $data['time_stamp'] = time();
            $data['sign'] = Identify::init()->buildRequestMysign($data, 'MD5');
        }
        $result = CurlManager::init()->CURL_POST($url, $data);
        if ($result['curl_is_success'] != 1) {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, $result);
        } else {
            if (json_decode($result['data'])->code == 200 || json_decode($result['data'])->code == 0) {
                return json_decode($result['data'], true)['data'];
            } else {
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, json_decode($result['data'], true)['data']);
            }

        }
    }
}