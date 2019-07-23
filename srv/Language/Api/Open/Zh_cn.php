<?php

/**
 *
 * 简体中文
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/7
 * Time: 9:15
 */
namespace Language\Api\Open;

use Multiple\Open\Helper\Ajax;
use Util\Debug;

class Zh_cn extends Ajax
{
    //错误信息定义

    public static $errmsg = array(
        self::ERROR_SIGN => "签名验证失败",
        self::ERROR_APP_NOT_EXISTS => "该应用不存在",
        self::ERROR_TOKEN => "token已过期或未获取",
        self::ERROR_ILLEGAL_TOKEN => "非法的token",
        self::ERROR_USER_NOT_SUPPORT => "用户不被支持",

        self::FAIL_GET_INFO => "信息获取失败",

        self::FAIL_SHARE => "分享失败",


        self::INVALID_SIGN => "无效的签名",
        self::INVALID_PARAM => "无效的参数",
    );
    //成功信息定义

    public static $success_msg = array();
    //通用信息定义
    public static $custom_msg = array();

    /**
     * get error msg by defined code
     * @param $code
     * @return string
     */
    public static function getErrorMsg($code)
    {
        return isset(self::$errmsg[$code]) ? self::$errmsg[$code] : '';
    }

    /**
     * get success msg by defined code
     * @param $code
     * @return string
     */
    public static function getSuccessMsg($code)
    {
        return isset(self::$success_msg[$code]) ? self::$success_msg[$code] : '';
    }

    /**
     * get custom msg by defined code
     * @return string
     */
    public static function getCustomMsg($data)
    {
        $msg = isset(self::$custom_msg[$data[0]]) ? self::$custom_msg[$data[0]] : '';
        $msg && count($data) >= 2 && parent::compileTemplate($msg, $data);
        return $msg;
    }
}
