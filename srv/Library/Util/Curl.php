<?php
/**
 * Created by PhpStorm.
 * User: wgwang
 * Date: 14-8-18
 * Time: 下午3:21
 */

namespace Util;


class Curl
{

    /**
     * @var Curl
     */
    public static $instance = null;

    public $connecttimeout = 60;

    public $timeout = 60;

    public $ssl_verifypeer = FALSE;

    public $http_info = '';

    public $http_code = 0;

    private function __construct()
    {
    }

    public static function instance()
    {
        if (!self::$instance instanceof Curl) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $method
     * @param $url
     * @param array $headers
     * @param array $params
     * @param bool $inBody
     * @return mixed
     */
    public function http($method, $url, $headers = array(), $params = array(), $inBody = false)
    {
        $method = strtoupper($method);
        $this->http_info = array();
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, '');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_ENCODING, "");
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
        if (version_compare(phpversion(), '5.4.0', '<')) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, 1);
        } else {
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, 2);
        }
        curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        curl_setopt($ci, CURLOPT_HEADER, FALSE);


        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if ($inBody && is_array($params)) {
                    $params = json_encode($params, JSON_UNESCAPED_UNICODE);
                    curl_setopt($ci, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json; chartset=utf-8',
                            'Content-Length: ' . strlen($params))
                    );
                }
                if (!empty($params)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                    $this->postdata = $params;
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (is_array($params)) {
                    $tmpParams = [];
                    foreach ($params as $key => $val) {
                        $tmpParams[] = $key . "=" . $val;
                    }
                    $params = implode('&', $tmpParams);
                    unset($tmpParams);
                }
                if (strpos($url, '?') === false) {
                    $url .= '?';
                }
                $url .= $params;
                break;
            case 'GET' :
                if (is_array($params)) {
                    $tmpParams = [];
                    foreach ($params as $key => $val) {
                        $tmpParams[] = $key . "=" . $val;
                    }
                    $params = implode('&', $tmpParams);
                    unset($tmpParams);
                }
                if (strpos($url, '?') === false) {
                    $url .= '?';
                }
                $url .= $params;
        }

        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);

        $response = curl_exec($ci);
        $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
        $this->url = $url;
        curl_close($ci);
        return $response;
    }
} 