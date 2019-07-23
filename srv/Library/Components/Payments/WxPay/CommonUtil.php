<?php
namespace Components\Payments\WxPay;
//---------------------------------------------------------
//---------------------------------------------------------

class CommonUtil
{
    /**
     *
     *
     * @param toURL
     * @param paras
     * @return
     */
    public static function genAllUrl($toURL, $paras)
    {
        $allUrl = null;
        if (null == $toURL) {
            die("toURL is null");
        }
        if (strripos($toURL, "?") == "") {
            $allUrl = $toURL . "?" . $paras;
        } else {
            $allUrl = $toURL . "&" . $paras;
        }

        return $allUrl;
    }

    public static function create_noncestr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            //$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $str;
    }

    /**
     *
     *
     * @param src
     * @param token
     * @return
     */
    public static function splitParaStr($src, $token)
    {
        $resMap = array();
        $items = explode($token, $src);
        foreach ($items as $item) {
            $paraAndValue = explode("=", $item);
            if ($paraAndValue != "") {
                $resMap[$paraAndValue[0]] = $paraAndValue[1];
            }
        }
        return $resMap;
    }

    /**
     * trim
     *
     * @param value
     * @return
     */
    public static function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    public static function formatQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v && "sign" != $k) {
                if ($urlencode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    public static function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            //	if (null != $v && "null" != $v && "sign" != $k) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= strtolower($k) . "=" . $v . "&";
            //}
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

}

?>