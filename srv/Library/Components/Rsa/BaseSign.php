<?php
namespace Components\Rsa;
/**
 * Created by PhpStorm.
 * User: ykuang
 * Date: 2016/8/11
 * Time: 16:50
 */
class BaseSign
{
    /**获取公钥加密签名
     * @param $data
     * @return mixed
     */
    public static function rsa_publickey_encrypt($publicKey, $data)
    {
        $pubk = openssl_get_publickey($publicKey);
        openssl_public_encrypt($data, $encrypt, $pubk, OPENSSL_PKCS1_PADDING);
        return $encrypt;
    }

    /**公钥解密
     * @param $data
     * @return mixed
     */
    public static function rsa_publickey_decrypt($priKey, $data)
    {
        $prik = openssl_get_publickey($priKey);
        openssl_public_decrypt($data, $decrypt, $prik, OPENSSL_PKCS1_PADDING);
        return $decrypt;
    }

    /**私钥加密
     * @param $publicKey
     * @param $data
     * @return mixed
     */
    public static function rsa_privatekey_encrypt($publicKey, $data)
    {
        $pubk = openssl_get_privatekey($publicKey);
        openssl_private_encrypt($data, $encrypt, $pubk, OPENSSL_PKCS1_PADDING);
        return $encrypt;
    }

    /**私钥解密
     * @param $data
     * @return mixed
     */
    public static function rsa_privatekey_decrypt($priKey, $data)
    {
        $prik = openssl_get_privatekey($priKey);
        openssl_private_decrypt($data, $decrypt, $prik, OPENSSL_PKCS1_PADDING);
        return $decrypt;
    }

    /**数据长度过长 公钥加密
     * @param $data
     * @param $public_key_path
     * @param int $rsa_bit
     * @return string
     */
    public static function rsa_encrypt($data, $public_key_path, $rsa_bit = 1024)
    {
        $publicKey = file_get_contents($public_key_path);
        $inputLen = strlen($data);
        $offSet = 0;
        $i = 0;
        $maxDecryptBlock = $rsa_bit / 8 - 11;
        $encrypt = '';
        // 对数据分段加密
        while ($inputLen - $offSet > 0) {
            if ($inputLen - $offSet > $maxDecryptBlock) {
                $cache = self::rsa_publickey_encrypt($publicKey, substr($data, $offSet, $maxDecryptBlock));
            } else {
                $cache = self::rsa_publickey_encrypt($publicKey, substr($data, $offSet, $inputLen - $offSet));
            }
            $encrypt = $encrypt . $cache;
            $i++;
            $offSet = $i * $maxDecryptBlock;
        }
        return $encrypt ? base64_encode($encrypt) : $encrypt;
    }

    /**数据过长私钥解密
     * @param $sign
     * @param $private_key_path
     * @param int $rsa_bit
     * @return string
     */
    public static function rsa_decrypt($sign, $private_key_path, $rsa_bit = 1024)
    {
        $data = base64_decode($sign);
        $priKey = file_get_contents($private_key_path);
        $inputLen = strlen($sign);
        $offSet = 0;
        $i = 0;
        $maxDecryptBlock = $rsa_bit / 8;
        $decrypt = '';
        $cache = '';
        // 对数据分段解密
        while ($inputLen - $offSet > 0) {
            if ($inputLen - $offSet > $maxDecryptBlock) {
                $cache = self::rsa_privatekey_decrypt($priKey, substr($data, $offSet, $maxDecryptBlock));
            } else {
                $cache = self::rsa_privatekey_decrypt($priKey, substr($data, $offSet, $inputLen - $offSet));
            }
            $decrypt = $decrypt . $cache;
            $i = $i + 1;
            $offSet = $i * $maxDecryptBlock;
        }
        return $decrypt;
    }
    /**数据长度过长 私钥加密
     * @param $data
     * @param $private_key_path
     * @param int $rsa_bit
     * @return string
     */
    public static function rsa_private_encrypt($data, $private_key_path, $rsa_bit = 1024)
    {
        $privateKey = file_get_contents($private_key_path);
        $inputLen = strlen($data);
        $offSet = 0;
        $i = 0;
        $maxDecryptBlock = $rsa_bit / 8 - 11;
        $encrypt = '';
        // 对数据分段加密
        while ($inputLen - $offSet > 0) {
            if ($inputLen - $offSet > $maxDecryptBlock) {
                $cache = self::rsa_private_encrypt($privateKey, substr($data, $offSet, $maxDecryptBlock));
            } else {
                $cache = self::rsa_privatekey_encrypt($privateKey, substr($data, $offSet, $inputLen - $offSet));
            }
            $encrypt = $encrypt . $cache;
            $i++;
            $offSet = $i * $maxDecryptBlock;
        }
        return $encrypt ? base64_encode($encrypt) : $encrypt;
    }

    /**数据过长公钥解密
     * @param $sign
     * @param $public_key_path
     * @param int $rsa_bit
     * @return string
     */
    public static function rsa_public_decrypt($sign, $public_key_path, $rsa_bit = 1024)
    {
        $data = base64_decode($sign);
        $pubKey = file_get_contents($public_key_path);
        $inputLen = strlen($sign);
        $offSet = 0;
        $i = 0;
        $maxDecryptBlock = $rsa_bit / 8;
        $decrypt = '';
        $cache = '';
        // 对数据分段解密
        while ($inputLen - $offSet > 0) {
            if ($inputLen - $offSet > $maxDecryptBlock) {
                $cache = self::rsa_publickey_decrypt($pubKey, substr($data, $offSet, $maxDecryptBlock));
            } else {
                $cache = self::rsa_publickey_decrypt($pubKey, substr($data, $offSet, $inputLen - $offSet));
            }
            $decrypt = $decrypt . $cache;
            $i = $i + 1;
            $offSet = $i * $maxDecryptBlock;
        }
        return $decrypt;
    }

    /**生成签名前字符串
     * @param $data
     * @return string
     */
    public static function createStr($data)
    {
        $str = "";
        if (is_array($data) || strpos($data, '&')) {
            if (is_string($data) && strpos($data, '&')) {
                $temp_data = explode('&', $data);
                $data = [];
                foreach ($temp_data as $item) {
                    $temp = explode('=', $item);
                    $data[$temp[0]] = $temp[1];
                }
            }
            $data = self::argSort($data);
            //  var_dump($data);exit;
            $temp = [];
            foreach ($data as $k => $item) {
                $temp[] = $k . '=' . $item;
            }
            $str = implode('&', $temp);
        } else {
            $str = $data;
        }
        return $str;
    }

    /**获取解析签名后数组
     * @param $private_key_path
     * @param $sign
     * @return array|bool
     */
    public static function getSignArr($private_key_path, $sign)
    {
        $result = self::rsa_decrypt($sign, $private_key_path);
        if ($result) {
            $data = [];
            $temp_data = explode('&', $result);
            foreach ($temp_data as $item) {
                $temp = explode('=', $item);
                if (count($temp) == 2) {
                    $data[$temp[0]] = $temp[1];
                }
            }
            return $data;
        }
        return false;
        // return $result;
    }

    /**获取解析签名后字符串
     * @param $private_key_path
     * @param $sign
     * @return array|bool
     */
    public static function getSignStr($private_key_path, $sign)
    {
        $result = self::rsa_decrypt($sign, $private_key_path);
        if ($result) {
            return $result;
        }
        return false;
        // return $result;
    }

    /**对数组排序
     * @param $para
     * @return mixed
     */
    public static function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }
}