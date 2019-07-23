<?php
/**
 * Created by PhpStorm.
 * User: luguiwu
 * Date: 15-10-27
 * Time: 下午3:40
 */

namespace  Components\Passport;

class AppSign extends AbstractIdentify
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
    public function getSignVeryfy($para_temp, $sign)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        $key = $this->di->get('config')->secret_key->sign_key;

        $isSgin = $this->md5Verify($prestr, $sign, $key);
        return $isSgin;
    }

    /**
     * 生成签名结果
     * @$para_temp 请求前的参数数组
     * return 签名结果字符串
     */
    public function buildRequestMysign($para_temp)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        $key = $this->di->get('config')->secret_key->sign_key;
        $mysign = $this->md5Sign($prestr, $key);
        return $mysign;
    }
} 