<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/31
 * Time: 18:04
 */

namespace Multiple\Wap\Helper;


class Verify
{
    public static $instance = null;
    private static $md5_text = '123kjh878kjkuy76';
    private static $iv_text = 'klgwl.com4444444';//16位

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function parseParams($params)
    {
        $data = [];
        $key = md5(self::$md5_text);  //CuPlayer.com提示key的长度必须16，32位,这里直接MD5一个长度为32位的key
        $params = base64_decode(str_replace(' ', '+', $params));
        $params = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $params, MCRYPT_MODE_CBC, self::$iv_text);
        $decode = rtrim($params, "\0");
        if ($decode) {
            $params = explode("&", $decode);
            foreach ($params as $item) {
                $temp = explode("=", $item);
                if (count($temp) == 2) {
                    $data[$temp[0]] = $temp[1];
                }

            }
        }
        return $data;
    }
}