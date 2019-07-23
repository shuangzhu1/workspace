<?php
/**
 * Created by PhpStorm.
 * User: luguiwu
 * Date: 15-10-10
 * Time: 下午4:47
 */

namespace Components\Passport;


use Phalcon\Mvc\User\Plugin;
use Components\Rsa\lib\Sign;
use Util\Debug;

class Identify extends Plugin
{
    static $instance = null;

    public function __construct()
    {
    }

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取返回时的签名验证结果
     * @$para_temp 通知返回来的参数数组
     * @返回的签名结果
     * @签名验证结果
     */
    public function getSignVeryfy($para_temp, $sign, $sign_type = 'MD5', $client_type = '', $app_version = '')
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        // ios-版本号大于2.0.4 || 安卓-版本号大于2.1.0101 || 后台调用【MD5加密方式】

        $has_salt = false;
        if (($client_type == 'ios' && version_compare($app_version, '2.0.4', '>')) ||
            ($client_type == 'android' && version_compare($app_version, '2.1.0101', '>')) ||
            $sign_type == 'MD5'
        ) {
            $has_salt = true;
            $timestamp = !empty($para_temp['time_stamp']) ? $para_temp['time_stamp'] : 0;
            $prestr = $prestr . "&" . ($this->getSalt($timestamp));
        }
        switch ($sign_type) {
            case "MD5":
                $key = '';//$this->di->get('config')->secret_key->sign_key;
                $isSgin = $this->md5Verify($prestr, $sign, $key);
                break;
            case "RSA":
                //  Debug::log('prestr:'.$prestr,'sign');
                // Debug::log('sign:'.$sign,'sign');
                $Sign = new Sign($client_type, $app_version);
                $isSgin = $Sign->privateRsaVerify($prestr, $sign, $has_salt);// Sign::privateRsaVerify($prestr, $sign);

                break;
            default :
                $isSgin = false;
        }
        return $isSgin;
    }

    /**
     * 生成签名结果
     * @$para_temp 请求前的参数数组
     * return 签名结果字符串
     */
    public function buildRequestMysign($para_temp, $sign_type = 'MD5')
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        //   $key = $this->di->get('config')->secret_key->sign_key;

        if ($sign_type == 'MD5') {
            $prestr = $prestr . "&" . ($this->getSalt());
        }
        //    echo $prestr;exit;
        if ($sign_type == 'MD5') {
            $key = '';
            $mysign = $this->md5Sign($prestr, $key);
        } else {
            $Sign = new Sign();
            $mysign = $Sign->publicSign($prestr);
            //   $mysign = Sign::publicSign($prestr);
        }

        return $mysign;
    }

    //接口请求撒盐
    public function getSalt($timestamp = '')
    {
        if ($timestamp) {
            $time = $timestamp - (3600 * 8);
        } else {
            $time = time() - (3600 * 8);
        }
        $year = intval(date('Y', $time));
        $month = intval(date('m', $time));
        $day = intval(date('d', $time));
        $salt = (string)($year * $month * $day);
        /*  Debug::log($year . "-" . $month . '-' . $day,'sign');
          Debug::log($salt,'sign');*/
        $salt = $this->SDBMHash($salt);
        return $salt;
    }

    /**
     * 除去数组中的空值和签名参数
     * @$para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    public function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val === "" || $key == "_url") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @$para 排序前的数组
     * return 排序后的数组
     */
    public function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @$para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 签名字符串
     * $prestr 需要签名的字符串
     * $key 私钥
     * return 签名结果
     */
    function md5Sign($prestr, $key)
    {
        //prestr = $prestr ;//. '@' . $key;
        $sign = md5($prestr);
        return $sign;


    }

    /**
     * 验证签名
     * @$prestr 需要签名的字符串
     * @$sign 签名结果
     * @$key 私钥
     * return 签名结果
     */
    function md5Verify($prestr, $sign, $key)
    {
        //$prestr = $prestr;//. '@' . $key;
        $mysgin = md5($prestr);

        if ($mysgin == $sign) {
            return true;
        } else {
            Debug::log("prestr:" . $prestr,'debug');
            Debug::log("签名后:" . $mysgin,'debug');
            Debug::log("传过来的签名:" . $sign,'debug');
            return false;
        }
    }

    function SDBMHash($str) //
    {
        $hash = 0;
        $n = strlen($str);

        for ($i = 0; $i < $n; $i++) {
            $hash = 65599 * $hash + ord($str[$i]);
            $hash = $hash & 0x7FFFFFFF;
        }
        return $hash;

    }

} 