<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2015/4/12
 * Time: 19:52
 */

namespace Components\WeChat;


use Phalcon\Mvc\User\Component;

class WechatWapJsHelper extends Component
{
        /**
         * @var WechatWapJsHelper
         */
        public static $instance = null;

        /**
         * @var \Phalcon\Cache\Backend\Libmemcached
         */
        public $cache = null;

        public static function getInstance()
        {
                if (!self::$instance instanceof WechatWapJsHelper) {
                        self::$instance = new self();
                }

                return self::$instance;
        }

        private function __construct()
        {
                $this->cache = $this->di->get('memcached');
        }

        public function make_ticket($appId, $appsecret)
        {
                // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
                $data = $this->cache->get("wechat_access_token.json");
                if (!$data || $data->expire_time < time()) {
                        $TOKEN_URL = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appsecret;
                        $json = file_get_contents($TOKEN_URL);
                        $result = json_decode($json, true);
                        $access_token = $result['access_token'];
                        if ($access_token) {
                                $data->expire_time = time() + 7000;
                                $data->access_token = $access_token;
                                $this->cache->save("wechat_access_token.json", $data, 7000);
                        }
                } else {
                        $access_token = $data->access_token;
                }
                // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
//        $data = json_decode(file_get_contents("jsapi_ticket.json"));
                $data = $this->cache->get("wechat_jsapi_ticket.json");
                if (!$data || $data->expire_time < time()) {
                        $ticket_URL = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $access_token . "&type=jsapi";
                        $json = file_get_contents($ticket_URL);
                        $result = json_decode($json, true);
                        $ticket = $result['ticket'];
                        if ($ticket) {
                                $data->expire_time = time() + 7000;
                                $data->jsapi_ticket = $ticket;
                                $this->cache->save("wechat_jsapi_ticket.json", $data, 7000);
                        }
                } else {
                        $ticket = $data->jsapi_ticket;
                }
                return $ticket;
        }

        public function make_nonceStr()
        {
                $codeSet = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                for ($i = 0; $i < 16; $i++) {
                        $codes[$i] = $codeSet[mt_rand(0, strlen($codeSet) - 1)];
                }
                $nonceStr = implode($codes);
                return $nonceStr;
        }

        public function make_signature($nonceStr, $timestamp, $jsapi_ticket, $url)
        {
                $tmpArr = array(
                    'noncestr' => $nonceStr,
                    'timestamp' => $timestamp,
                    'jsapi_ticket' => $jsapi_ticket,
                    'url' => $url
                );
                ksort($tmpArr, SORT_STRING);
                $string1 = http_build_query($tmpArr);
                $string1 = urldecode($string1);
                $signature = sha1($string1);
                return $signature;
        }
}