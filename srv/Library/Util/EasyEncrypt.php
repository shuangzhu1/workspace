<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 4/17/14
 * Time: 2:53 PM
 */

namespace Util;


class EasyEncrypt
{
    /**
     * 简单对称加密算法之加密
     * @param String $string 需要加密的字串
     * @param String $skey 加密EKY
     * @author Anyon Zou <cxphp@qq.com>
     * @date 2013-08-13 19:30
     * @update 2014-01-21 28:28
     * @return String
     */
    static function encode($string = '', $skey = 'klgwl')
    {
        $skey = str_split(base64_encode($skey));
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach ($skey as $key => $value) {
            $key < $strCount && $strArr[$key] .= $value;
        }
        return str_replace('=', '-', join('', $strArr));
    }

    /**
     * 简单对称加密算法之解密
     * @param String $string 需要解密的字串
     * @param String $skey 解密KEY
     * @author Anyon Zou <cxphp@qq.com>
     * @date 2013-08-13 19:30
     * @update 2014-01-21 28:28
     * @return String
     */
    static function decode($string = '', $skey = 'klgwl')
    {
        if (is_numeric($string)) {
            return $string;
        }
        $skey = str_split(base64_encode($skey));
        $strArr = str_split(str_replace('-', '=', $string), 2);
        $strCount = count($strArr);
        foreach ($skey as $key => $value) {
            $key < $strCount && @$strArr[$key][1] === $value && $strArr[$key] = @$strArr[$key][0];
        }
        return base64_decode(join('', $strArr));
    }

} 
