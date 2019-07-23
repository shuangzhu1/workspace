<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/10
 * Time: 14:38
 */

namespace Multiple\Open\Helper;


use Language\Api\Open\Zh_cn;
use Phalcon\Mvc\User\Plugin;
use Util\Debug;
use Util\Uri;

class Ajax extends Plugin
{
    const ERROR_SIGN = 1001; //签名验证失败
    const ERROR_APP_NOT_EXISTS = 1002; //该应用不存在
    const ERROR_TOKEN = 1003; //token已过期或未获取
    const ERROR_ILLEGAL_TOKEN = 1004; //非法的token
    const ERROR_USER_NOT_SUPPORT = 1005; //用户不被支持


    const FAIL_GET_INFO = 2001; //信息获取失败


    const CUSTOM_ERROR_MSG = 3001;


    const INVALID_SIGN = 3002; //无效的签名
    const INVALID_PARAM = 3003;//无效的参数

    const FAIL_SHARE = 4017;//分享失败

    public static $instance = null;
    public static $api_request = null;
    public static $api_redis = null;

    public static function init()
    {
        return new self();
    }

    public function __construct()
    {
        self::$api_request = $this->request;
        self::$api_redis = $this->di->get("redis_queue");

    }

    public static function outRight($data = '', $code = '')
    {
        self::setHead();
        if ($code) {
            $result = array(
                'result' => 1,
                'data' => self::getSuccessMsg($code),
            );
        } else {
            $result = array(
                'result' => 1,
                'data' => $data
            );
        }
        //日志
        //self::recordLog(1, '');
        if (isset($_REQUEST['callback']) && $_REQUEST['callback']) {
            echo $_REQUEST["callback"] . '(' . json_encode($result, JSON_UNESCAPED_UNICODE) . ')'; // php 5.4
        } else {
            echo json_encode($result, JSON_UNESCAPED_UNICODE); // php 5.4
        }
        exit;

    }

    public static function outError($code, $msg = '')
    {
        Debug::log("data:$msg-" . var_export($_REQUEST, true), 'open_api');
        self::setHead();
        $result = array(
            'error' => array('code' => $code, 'msg' => self::getErrorMsg($code), 'more' => $msg),
            'result' => 0,
        );
        if (!$result['error']['msg']) {
            $result['error']['msg'] = $result['error']['more'];
        }

        if (isset($_REQUEST['callback']) && $_REQUEST['callback']) {
            echo $_REQUEST["callback"] . '(' . json_encode($result, JSON_UNESCAPED_UNICODE) . ')';
        } else {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
        self::recordLog(0, $result['error']['msg'], $code);
        exit;
    }

    public static function recordLog($status, $msg, $code = '')
    {
        $time = ceil((microtime(true) - START_TIME) * 1000);
        $now = time();
        $uri = new Uri();
        if (!self::$api_request->isPost()) {
            $data = [
                'user_id' => isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0,
                'api' => $uri->actionUrl(),
                'params' => json_encode(self::$api_request->getQuery(), JSON_UNESCAPED_UNICODE),
                'full_url' => $uri->fullUrl(),
                'ymd' => date('Ymd', $now),
                'h' => date('H', $now),
                'created' => $now,
                'time' => $time,
                'app_version' => isset($_REQUEST['app_version']) ? $_REQUEST['app_version'] : '',
                'client_type' => isset($_REQUEST['client_type']) ? $_REQUEST['client_type'] : '',
                'status' => $status,
                'msg' => $msg,
                'ip' => self::$api_request->getClientAddress(),
                'code' => $code
            ];
        } else {
            $query = self::$api_request->getPost();
            $query_str = "";
            if ($query) {
                foreach ($query as $k => $item) {
                    $query_str .= "&$k=$item";
                }
                $query_str = substr($query_str, 1);
            }
            $data = [
                'user_id' => isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0,
                'api' => $uri->actionUrl(),
                'params' => json_encode(array_merge(self::$api_request->getQuery(), $query), JSON_UNESCAPED_UNICODE),
                'full_url' => $uri->fullUrl() . ($query_str ? "?" . $query_str : ''),
                'ymd' => date('Ymd', $now),
                'h' => date('H', $now),
                'created' => $now,
                'time' => $time,
                'app_version' => isset($_REQUEST['app_version']) ? $_REQUEST['app_version'] : '',
                'client_type' => isset($_REQUEST['client_type']) ? $_REQUEST['client_type'] : '',
                'status' => $status,
                'msg' => $msg,
                'ip' => self::$api_request->getClientAddress(),
                'code' => $code
            ];
        }
        self::$api_redis->rPush("api_call_log", json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 设置ajax跨域head
     */
    public static function setHead()
    {
        header("content-type: text/javascript; charset=utf-8");
        header("Access-Control-Allow-Origin: *"); # 跨域处理
        header("Access-Control-Allow-Headers: content-disposition, origin, content-type, accept");
        header("Access-Control-Allow-Credentials: true");

        // Make sure file is not cached (as it happens for example on iOS devices)
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }


    /**
     * get error msg by defined code
     * @param $code
     * @return string
     */
    public static function getErrorMsg($code)
    {
        $msg = Zh_cn::getErrorMsg($code);
        return $msg;
    }

    /**
     * get success msg by defined code
     * @param $code
     * @return string
     */
    public static function getSuccessMsg($code)
    {
        $msg = Zh_cn::getSuccessMsg($code);
        return $msg;
    }

    /**
     * get success msg by defined code
     * like getCustomMsg(50001,4,4)
     * @return string
     */
    public static function getCustomMsg()
    {
        $msg = Zh_cn::getCustomMsg(func_get_args());
        return $msg;
    }

    /*编译模板消息*/
    public static function compileTemplate(&$msg, $args)
    {
        array_shift($args);
        foreach ($args as $k => $item) {
            $msg = str_replace('${' . ($k + 1) . '}', $item, $msg);
        }
    }
}