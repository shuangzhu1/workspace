<?php

namespace Components\Queue;

use Components\Curl\CurlManager;
use Components\Passport\Identify;
use Util\Debug;

class Queue
{
    const SECOND = 1;
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;
    const WEEK = 604800;
    const MONTH = 2592000;//30天
    const QUEUE_SERVER = "http://service.klgwl.com/htq/task";
    const QUEUE_SERVER_CANCEL = "http://service.klgwl.com/htq/task/cancel";

    private static $instance = null;

    public static function init()
    {
        if( self::$instance === null)
            return self::$instance = new self();
        return self::$instance;
    }
    /**
     * @param string $callbackUrl
     * @param array $params
     * @param int $delay
     * @param int $outer
     * @param string $notifyUrl
     * @param string $requestMethod
     * @return bool
     */
    public function push( $callbackUrl,$params = [],$delay = 0,$outer = 0,$notifyUrl = '',$requestMethod = 'POST')
    {
        if( empty($callbackUrl) )
        {
            Debug::log("回调url为空，数据：\n" . '$params:' . var_export($params,true) . "\n" . '$delay:' . $delay . "\n" . '$outer:' . $outer . "\n" .  "\n" . '$notifyUrl:' . $notifyUrl,'queue');
            return false;
        }
        if( is_array( $params ) && !empty($params) )
            $params = self::createLinkString( $params );

        $data = [];
        $data['url'] = $callbackUrl;
        $data['method'] = $requestMethod;
        !empty($params) && $data['params'] = $params;
        $outer && $data['outer'] = $outer;
        $delay && $data['delay'] = $delay;
        !empty($notifyUrl) && $data['notify'] = $notifyUrl;
        Debug::log('添加任务时数据：' . var_export($data,true),'notify');
        $res = self::sendRequest(self::QUEUE_SERVER,$data);
        return json_decode($res,true)['data'];

    }

    /**
     * @param string $taskid
     * @return  bool
     */
    public function delete($taskid)
    {
        if( !empty( $taskid ) )
            $res = self::sendRequest(self::QUEUE_SERVER_CANCEL,['taskid' => $taskid ]);
        else
            return false;
        return $res ? true : false;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param array $para
     * @return bool|string
     */
    private static function createLinkString($para)
    {
        $arg = [];
        foreach($para as $k => $v)
        {
            $arg[] = $k . '=' . $v;
        }
        $arg = implode('&',$arg);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * @param $url
     * @param $data
     * @return bool
     */
    private static function sendRequest( $url,$data )
    {
        $res = CurlManager::init()->CURL_POST($url,$data);
        if( $res['curl_is_success'] === 0 )//请求失败
        {
            Debug::log('队列操作失败，错误信息：' . var_export($res['curl_parse_err_msg'],true) . "\n" .'请求数据：' . var_export($data,true),'queue');
            return false;
        }
        return $res['data'];
    }
}