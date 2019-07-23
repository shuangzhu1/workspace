<?php

namespace Components\WeChat;

use Phalcon\Mvc\User\Component;
use Util\Debug;

abstract class AbstractRequest extends Component
{

    protected $needAuth = true;

    const TOKEN_EXPIRES_TERM = 7200;
    /**
     * @var string
     */
    protected $requestUri = "";

    /**
     * @var string
     */
    protected $app_id = "";

    /**
     * @var string
     */
    protected $app_secret = "";

    /**
     * @var string
     */
    protected $access_token = '';

    /**
     * @var int
     */
    protected $token_expires = 0;

    /**
     * @var int
     */
    protected $customer = 0;

    /**
     * @var array
     */
    protected $result = null;

    protected $errorMessage = '';

    public static $codeMessages = array(
        -1 => "系统繁忙",
        0 => "请求成功",
        40001 => "获取access_token时AppSecret错误，或者access_token无效",
        40002 => "不合法的凭证类型",
        40003 => "不合法的OpenID",
        40004 => "不合法的媒体文件类型",
        40005 => "不合法的文件类型",
        40006 => "不合法的文件大小",
        40007 => "不合法的媒体文件id",
        40008 => "不合法的消息类型",
        40009 => "不合法的图片文件大小",
        40010 => "不合法的语音文件大小",
        40011 => "不合法的视频文件大小",
        40012 => "不合法的缩略图文件大小",
        40013 => "不合法的APPID",
        40014 => "不合法的access_token",
        40015 => "不合法的菜单类型",
        40016 => "不合法的按钮个数",
        40017 => "不合法的按钮个数",
        40018 => "不合法的按钮名字长度",
        40019 => "不合法的按钮KEY长度",
        40020 => "不合法的按钮URL长度",
        40021 => "不合法的菜单版本号",
        40022 => "不合法的子菜单级数",
        40023 => "不合法的子菜单按钮个数",
        40024 => "不合法的子菜单按钮类型",
        40025 => "不合法的子菜单按钮名字长度",
        40026 => "不合法的子菜单按钮KEY长度",
        40027 => "不合法的子菜单按钮URL长度",
        40028 => "不合法的自定义菜单使用用户",
        40029 => "不合法的oauth_code",
        40030 => "不合法的refresh_token",
        40031 => "不合法的openid列表",
        40032 => "不合法的openid列表长度",
        40033 => "不合法的请求字符，不能包含\\uxxxx格式的字符",
        40035 => "不合法的参数",
        40038 => "不合法的请求格式",
        40039 => "不合法的URL长度",
        40050 => "不合法的分组id",
        40051 => "分组名字不合法",
        41001 => "缺少access_token参数",
        41002 => "缺少appid参数",
        41003 => "缺少refresh_token参数",
        41004 => "缺少secret参数",
        41005 => "缺少多媒体文件数据",
        41006 => "缺少media_id参数",
        41007 => "缺少子菜单数据",
        41008 => "缺少oauth code",
        41009 => "缺少openid",
        42001 => "access_token超时",
        42002 => "refresh_token超时",
        42003 => "oauth_code超时",
        43001 => "需要GET请求",
        43002 => "需要POST请求",
        43003 => "需要HTTPS请求",
        43004 => "需要接收者关注",
        43005 => "需要好友关系",
        44001 => "多媒体文件为空",
        44002 => "POST的数据包为空",
        44003 => "图文消息内容为空",
        44004 => "文本消息内容为空",
        45001 => "多媒体文件大小超过限制",
        45002 => "消息内容超过限制",
        45003 => "标题字段超过限制",
        45004 => "描述字段超过限制",
        45005 => "链接字段超过限制",
        45006 => "图片链接字段超过限制",
        45007 => "语音播放时间超过限制",
        45008 => "图文消息超过限制",
        45009 => "接口调用超过限制",
        45010 => "创建菜单个数超过限制",
        45015 => "回复时间超过限制",
        45016 => "系统分组，不允许修改",
        45017 => "分组名字过长",
        45018 => "分组数量超过上限",
        46001 => "不存在媒体数据",
        46002 => "不存在的菜单版本",
        46003 => "不存在的菜单数据",
        46004 => "不存在的用户",
        47001 => "解析JSON/XML内容错误",
        48001 => "api功能未授权",
        50001 => "用户未授权该api",
        49002 => "缺失微信openid或者订单号"
    );

    /**
     * @var \Phalcon\Cache\Backend\Libmemcached
     */
    protected $cache;

    /**
     * @param $customer
     */
    public function __construct($customer, $appId, $appSecret)
    {
        $di = $this->getDI();
        $this->cache = $di->get('redis');
        $this->app_id = $appId;
        $this->app_secret = $appSecret;
        $this->customer = $customer;
    }

    public function beforeRun()
    {
        if ($this->needAuth) {
            Debug::log("get access_token start", 'wechat');
            $this->access_token = $this->getAccessToken($this->customer);
            $this->requestUri .= $this->access_token . '&';
        }
    }

    /**
     * @param $customer
     * @param bool $refresh
     * @return mixed|null|string
     */
    public function getAccessToken($customer, $refresh = false)
    {
        $sessionKey = "customer_access_token_" . $customer;
        $needRefresh = true;
        $accessToken = "";
        if ($this->cache->exists($sessionKey) && !$refresh) {
            $refreshTime = $this->cache->get("customer_access_token_set_time_" . $customer);
            $expireTime = $this->cache->get("customer_access_token_expires_" . $customer);
            Debug::log('token_set_time:' . $refreshTime . ', expires:' . $expireTime . ", now:" . time(), "wechat");
            if ($refreshTime + $expireTime - 10 < time()) {
                $needRefresh = true;
            } else {
                $needRefresh = false;
                $accessToken = $this->cache->get($sessionKey);
            }
        }

        if ($needRefresh) {
            $request = RequestFactory::create("AccessToken", $customer, $this->app_id, $this->app_secret);
            $request->run();
            if (!$request->isFailed()) {
                $result = $request->getResult();
                $accessToken = $result['access_token'];
                $this->cache->save($sessionKey, $accessToken, 7000);
                $this->cache->save("customer_access_token_set_time_" . $customer, time(), 7000);
                $this->cache->save("customer_access_token_expires_" . $customer, $result['expires_in'], 7000);
                $this->access_token = $accessToken;
                $this->token_expires = $result['expires_in'];
            } else {
                Debug::log("'获取用户access_token失败！", "wechat");
            }
        }

        return $accessToken;
    }

    /**
     * @param $url
     * @param array | string $params
     * @param bool $isPost
     * @return bool|mixed
     */
    public function singleRequest($url, $params = null, $isPost = false, $inBody = false)
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($isPost) {
            if ($inBody && is_array($params)) {
                $params = json_encode($params, JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content - Type: application / json; chartset = utf - 8',
                        'Content - Length: ' . strlen($params))
                );
            }
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, 1);
            @curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        } else {
            // set URL and other appropriate options
            if (is_array($params)) {
                $tmpParams = [];
                foreach ($params as $key => $val) {
                    $tmpParams[] = $key . "=" . $val;
                }
                $params = implode('&', $tmpParams);
                unset($tmpParams);
            }

            $url .= $params;
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
       // curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        try {
            $response = curl_exec($ch);
            $response = json_decode($response, true);
        } catch (\Exception $e) {
            $response = false;
            $this->errorMessage = $e->getMessage();
        }
        $this->result = $response;
        return $response;
    }

    public function fileUploadRequest($url, $path, $mime, $name)
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $cfile = @curl_file_create($path, $mime, $name);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('media' => $cfile));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_URL, $url);

        try {
            $response = curl_exec($ch);
            $response = json_decode($response, true);
        } catch (\Exception $e) {
            $response = false;
        }
        $this->result = $response;
        return $response;
    }

    public function multiRequest($urls)
    {
        $queue = curl_multi_init();
        $map = array();

        foreach ($urls as $url) {
            $ch = curl_init();
            if (stripos($url, "https://") !== FALSE) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            // set URL and other appropriate options

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_NOSIGNAL, true);

            curl_multi_add_handle($queue, $ch);
            $map[(string)$ch] = $url;
        }

        $responses = array();
        do {
            while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;

            if ($code != CURLM_OK) {
                break;
            }

            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($queue)) {

                // get the info and content returned on the request
                $info = curl_getinfo($done['handle']);
                $error = curl_error($done['handle']);
                $responses[$map[(string)$done['handle']]] = compact('info', 'error', 'results');

                // remove the curl handle that just completed
                curl_multi_remove_handle($queue, $done['handle']);
                curl_close($done['handle']);
            }

            // Block for data in / output; error handling is done by curl_multi_exec
            if ($active > 0) {
                curl_multi_select($queue, 0.5);
            }

        } while ($active);

        curl_multi_close($queue);
        return json_decode($responses, true);
    }

    public function set($key, $value)
    {
        $this->{$key} = $value;
        return $this;
    }

    public function get($key)
    {
        return $this->{$key};
    }

    /**
     * @return bool
     */
    public function validate()
    {
        if (empty($this->app_id) || empty($this->app_secret)) {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     */
    public abstract function run();

    /**
     * @param int $code
     * @return string
     */
    public function getErrorMessage($code = null)
    {
        if ($code && $code > 0) {
            return self::$codeMessages[$code];
        } else if (isset($this->result['errcode']) && isset(self::$codeMessages[$this->result['errcode']])) {
            return self::$codeMessages[$this->result['errcode']];
        } else {
            return $this->errorMessage;
        }
    }

    /**
     * @return array
     */
    public function getResult()
    {
        if (is_string($this->result)) {
            $this->result = json_decode($this->result, true);
        }
        return $this->result;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        if (empty($this->result)) {
            return true;
        }

        if (is_string($this->result)) {
            $this->result = json_decode($this->result, true);
        }

        if (isset($this->result['errcode']) && $this->result['errcode'] > 0) {
            return true;
        }

        return false;
    }
}

?>