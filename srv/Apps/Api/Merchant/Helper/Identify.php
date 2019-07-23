<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/7
 * Time: 14:40
 */

namespace Multiple\Api\Merchant\Helper;


use Util\Debug;

class Identify extends \Components\Passport\Identify
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

    /*** 获取返回时的签名验证结果
     * @$para_temp 通知返回来的参数数组
     * @返回的签名结果
     * @签名验证结果
     * @param $para_temp
     * @param $sign
     * @param $app_key
     * @param string $sign_type
     * @return bool
     */
    public function getSignVeryfy($para_temp, $sign, $app_key, $sign_type = 'MD5')
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        switch ($sign_type) {
            case "MD5":
                $key = $app_key;
                Debug::log("prestr:".$prestr,'merchant');
                Debug::log("sign:".$sign,'merchant');
                Debug::log("key:".$key,'merchant');

                $isSgin = $this->md5Verify($prestr, $sign, $key);
                break;
            case "RSA":
                $isSgin = false;
                break;
            default :
                $isSgin = false;
        }
        return $isSgin;
    }

    /** 生成签名结果
     * @$para_temp 请求前的参数数组
     * return 签名结果字符串
     * @param $para_temp
     * @param string $sign_type
     * @param $app_key
     * @return string
     */
    public function buildRequestMysign($para_temp, $app_key, $sign_type = 'MD5')
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        $key = $app_key;
        if ($sign_type == 'MD5') {
            $mysign = $this->md5Sign($prestr, $key);
        } else {
            $mysign = '';
        }

        return $mysign;
    }

}