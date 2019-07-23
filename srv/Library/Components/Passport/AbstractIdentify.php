<?php
/**
 * Created by PhpStorm.
 * User: luguiwu
 * Date: 15-10-27
 * Time: 下午3:34
 */

namespace Components\Passport;


use Phalcon\Mvc\User\Plugin;

abstract class  AbstractIdentify extends Plugin
{
    abstract public function getSignVeryfy($para_temp, $sign);

    abstract public function buildRequestMysign($para_temp);


    /**
     * 除去数组中的空值和签名参数
     * @$para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    public function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            echo $val === "" . "/";
            if ($key == "sign" || $key == "sign_type" || (is_string($val) && $val == "") || $key == "_url") continue;
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
        $prestr = $prestr . '@'.$key;
        return md5($prestr);
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

        $prestr = $prestr . '@'.$key;
        $mysgin = md5($prestr);
        // Debug::log("BCMS_RSA签名前prestr:" . $prestr,'sign');
        //Debug::log("BCMS_RSA签名后:" . $mysgin,'sign');
        // Debug::log("BCMS_RSA传过来的签名:" . $sign,'sign');
        //echo $mysgin;exit;
        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }
} 