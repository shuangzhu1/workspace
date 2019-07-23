<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/29
 * Time: 15:21
 */
namespace Components\Music\Tools\Baidu;

class BaiduMusicApi
{
    public function __construct()
    {

    }

    private static $input = "2012171402992850";
    private static $iv = "2012061402992850";

    const OPENSSL_CIPHER_NAME = "aes-128-cbc";
    const CIPHER_KEY_LEN = 16; //128 bits

    public static function encrypt($data)
    {
        $key = substr(strtoupper(md5(self::$input)), 16, 16);
        $data = urlencode(base64_encode(openssl_encrypt($data, self::OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, self::$iv)));
        return $data;
    }
}