<?php
/**
 * uri helper
 *
 * User: yanue
 * Date: 4/2/14
 * Time: 5:24 PM
 */

namespace Util;

use Phalcon\Mvc\User\Component;
use Phalcon\Mvc\View;

class Uri extends Component
{
    static $uri = '';
    static $paramPath = null;
    private static $_hostUrl = null;
    private static $_webrootUrl = null;
    private static $_requestUri = null;
    /**
     * @var ViewHelper
     */
    static $viewHelper = null;

    /**
     * 全面解析当前url
     * --说明:解析出完整url,uri,path部分,query部分
     *
     * @return void.
     */
    private function parseUrl()
    {
        # 解决通用问题
        $requestUri = '';
        if (isset($_SERVER['REQUEST_URI'])) { #$_SERVER["REQUEST_URI"] 只有 apache 才支持,
            $requestUri = $_SERVER['REQUEST_URI'];
        } else {
            if (isset($_SERVER['argv'])) {
                $requestUri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
            } else if (isset($_SERVER['QUERY_STRING'])) {
                $requestUri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
            }
        }
        $https = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = strstr(strtolower($_SERVER["SERVER_PROTOCOL"]), "/", true) . $https;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);

        # 保存地址域
        self::$_hostUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $port;
        # 获取的完整url


        # 当前脚本名称
        $script_name = $_SERVER['SCRIPT_NAME'];
        # 当前脚本目录
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        # 去除uri中当前脚本文件名 (如果存在)
        $script = false === strpos($requestUri, $script_name) ? $script_dir : $script_name;
        $script = str_replace('\\', '/', $script);
        # 当前应用根url
        self::$_webrootUrl = self::$_hostUrl . $script;
        self::$_requestUri = substr($requestUri, strlen($script));
    }

    public function fullUrl()
    {
        $this->parseUrl();
        return self::$_webrootUrl . self::$_requestUri;
    }

    public function baseUrl($uri = '')
    {
        return rtrim($this->baseUri(), '/') . '/' . ltrim($uri, '/');
    }

    public function moduleUrl($uri = '')
    {
        $module = $this->router->getModuleName();
        $module = $module == 'wap' ? '' : '/' . $module;

        return rtrim($this->baseUri(), '/') . '/' . trim($module, '/') . '/' . ltrim($uri, '/');
    }


    public function controllerUrl($uri = '')
    {
        $controller = $this->router->getControllerName();
        $module = $this->router->getModuleName();

        $module = $module == 'wap' ? '' : '/' . $module;

        return rtrim($this->baseUri(), '/') . '/' . trim($module, '/') . '/' . $controller . '/' . ltrim($uri, '/');
    }

    public function actionUrl($uri = '')
    {
        $module = $this->router->getModuleName();
        $controller = $this->router->getControllerName();
        $action = $this->router->getActionName();

        $module = $module == 'wap' ? '' : '/' . $module;

        return rtrim($this->baseUri(), '/') . '/' . trim($module, '/') . '/' . $controller . '/' . $action . '/' . ltrim($uri, '/');
    }

    /**
     * 获取基本地址: baseUrl
     * --说明: 返回不包含mvc结构,可以通过uri参数传入设置
     *
     * param string $uri 包含mvc结构的uri参数
     * return string
     * */
    public function baseUri($uri = '')
    {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
        $baseUrl .= isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : getenv('HTTP_HOST');
        $dirname = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : dirname(getenv('SCRIPT_NAME'));
        $dir = $dirname == '/' ? '' : $dirname; // 避免根目录情况下多一个'/'
        $dir = str_replace($dir, '\\', '/');
        $baseUri = rtrim($baseUrl . $dir, '/');
        if (strlen($uri)) {
            return $baseUri . '/' . ltrim($uri, '/');
        } else {
            return $baseUri;
        }
    }

    /**
     * app url
     * -- please don't add module name in uri
     * @param string $uri
     * @return string
     */
    public function appUrl($uri = '')
    {
        /*if (strpos(MAIN_DOMAIN, 'home') !== false) {
            return 'http://' . MAIN_DOMAIN . '/' . ltrim($uri, '/');
        }*/

        //$url = 'http://' . CUR_APP_ID . '.' . WAP_DOMAIN_DS . '.' . MAIN_DOMAIN . '/' . ltrim($uri, '/');
        $url = 'http://' . WAP_DOMAIN_DS . '.' . MAIN_DOMAIN . '/' . ltrim($uri, '/');

        return $url;
    }

    /**
     * app url
     * -- please don't add module name in uri
     * @param string $uri
     * @return string
     */
    public function wapModuleUrl($uri = '')
    {
        /*if (strpos(MAIN_DOMAIN, 'home') !== false) {
            return 'http://' . MAIN_DOMAIN . '/addon/' . ltrim($uri, '/');
        }*/

        // 通过子域名
        //$url = 'http://' . CUR_APP_ID . '.' . WAP_DOMAIN_DS . '.' . MAIN_DOMAIN . '/addon/' . ltrim($uri, '/');
        $url = 'http://' . WAP_DOMAIN_DS . '.' . MAIN_DOMAIN . '/addon/' . ltrim($uri, '/');

        return $url;
    }

    /**
     * 支付地址
     *
     * @param $uri
     * @return string
     */
    public function payUrl($uri = '')
    {
        // 通过子域名
        $url = 'http://' . FRONT_DOMAIN . '/' . ltrim($uri, '/');

        return $url;
    }

    /**
     * 当前页面下改变部分url
     *
     * @param $uri
     * @param $del_arr array --需要删除的参数
     * @return string
     */
    public function setUrl($uri, $del_arr = array())
    {
        // 获取query部分
        $request_uri = parse_url($_SERVER['REQUEST_URI']);
        $request_uri_query = isset($request_uri['query']) ? $request_uri['query'] : '';
        parse_str($request_uri_query, $request_arr);
        // 输入的query部分
        if (is_string($uri)) {
            $replace_uri = parse_url($uri);
            $replace_uri_query = isset($replace_uri['query']) ? $replace_uri['query'] : '';
            parse_str($replace_uri_query, $replace_arr);
        } else if (is_array($uri)) {
            $replace_arr = $uri;
        } else {
            $replace_arr = [];
        }

        // 最终query部分
        $requestParams = array_merge($request_arr, $replace_arr);
        $query_str = '';

        # 移除匹配参数
        if ($del_arr) {
            $del_arr = is_array($del_arr) ? $del_arr : [$del_arr];
            $requestParams = array_diff_key($requestParams, array_flip((array)$del_arr));
        }
        foreach ($requestParams as $key => $val) {
            $query_str .= $key . '=' . $val . '&';
        }
        $query_str = trim($query_str, '&');

        $module = $this->router->getModuleName();
        $controller = $this->router->getControllerName();
        $action = $this->router->getActionName();

        $module = $module == 'wap' ? '' : '/' . $module;
        $params = $this->dispatcher->getParams();
        // wap 模式下
        if (isset($params['app'])) {
            unset($params['app']);
        }
        $param = $params ? '/' . implode('/', $params) : '';
        $last_param = isset($replace_uri['path']) ? '/' . trim($replace_uri['path'], '/') : '';
        //兼容官网v3 2018/05/10
        if ($module == '/home' && $controller == 'index') {

            $url = rtrim($this->baseUri(), '/') . '/' . $action . $param . $last_param . '?' . $query_str;
            return $url;
        }

        $url = rtrim($this->baseUri(), '/') . $module . '/' . $controller . '/' . $action . $param . $last_param . '?' . $query_str;
        return $url;
    }

    /**
     * 设置query部分
     */
    public function setQuery($query)
    {
        // 获取query部分
        $request_uri = parse_url($_SERVER['REQUEST_URI']);
        $request_uri_query = isset($request_uri['query']) ? $request_uri['query'] : '';
        parse_str($request_uri_query, $arr);

        // 输入的query部分
        $replace_uri = parse_url($query);
        $replace_uri_query = isset($replace_uri['query']) ? $replace_uri['query'] : '';
        parse_str($replace_uri_query, $arr1);
        $requestParams = array_merge($arr, $arr1);
        print_r($requestParams);

    }

    private function parseParam()
    {
        $params = $this->dispatcher->getParams();
        #2. 取出mvc后的path部分
        $paramPath = array();
        if (($len = count($params)) > 0) {
            for ($i = 0; $i < ceil(($len) / 2); $i++) {
                $paramPath[$params[$i * 2]] = isset($params[$i * 2 + 1]) ? $params[$i * 2 + 1] : '';
            }
        }
        self::$paramPath = $paramPath;

    }

    public function getParam($key)
    {
        if (self::$paramPath == null) $this->parseParam();
        return isset(self::$paramPath[$key]) ? self::$paramPath[$key] : '';
    }

}
