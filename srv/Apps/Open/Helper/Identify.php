<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/10
 * Time: 14:37
 */

namespace Multiple\Open\Helper;


use Components\Rsa\lib\Sign;
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
     * @param string $private_key
     * @return bool
     */
    public function getSignVeryfy($para_temp, $sign, $app_key, $sign_type = 'RSA', $private_key = '')
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        switch ($sign_type) {
            case "MD5":
                $isSgin = false;
                //   $isSgin = $this->md5Verify($prestr, $sign, $key);
                break;
            case "RSA":
                $isSgin = false;
                if (!$private_key) {
                    $private_key = ROOT . '/Library/Components/Rsa/key/open/rsa_private_key.pem';
                }
                $res = Sign::rsa_decrypt($sign, $private_key);
                // Debug::log("res:" . $res,'debug');
                // Debug::log("prestr:" . ($prestr . "&" . $app_key),'debug');

                /*  ($res);
                  var_dump($prestr . "&" . $app_key);
                  exit;*/
                if ($res) {
                    if (($prestr . "&" . $app_key) == $res) {
                        $isSgin = true;
                    }
                }
                break;
            default :
                $isSgin = false;
        }
        return $isSgin;
    }

    public function getParams($params, $secret_key = '', $sign_type = 'RSA', $private_key = '')
    {
        $res = [];
        if (!$private_key) {
            $private_key = ROOT . '/Library/Components/Rsa/key/open/rsa_private_key.pem';
        }
        /*  $params = "bIEoWcCe9KQpM0Tmysbrwymp+fn+eKQPDukqDya9VloteGLWDjr2ezUf4pxsWgHD3zdLx8XSWGxwvxUwwDdJ23UxyVM9nhMuZT4oWB0fa6HeKteGDR44S1c2BSM0XdqcpOy8H9FSHDVTlZyaehR14cNJQzAQuQ8Mn0cZzNmOzNQ=";
         var_dump($result);exit;*/
        $result = Sign::rsa_decrypt($params, $private_key);
        /*  Debug::log("rsa result:params:" . $params, 'open_api');
          Debug::log("rsa result:" . $result, 'open_api');*/
        if ($result) {
            $result = explode('&', $result);
            if ($result) {
                foreach ($result as $i) {
                    $tmp = explode('=', $i);
                    if (count($tmp) == 2) {
                        $res[$tmp[0]] = $tmp[1];
                    }
                }
            }
        }
        if ($secret_key) {
            if (!(isset($res['sk']) && $res['sk'] == $secret_key)) {
                $res = [];
            }
        }
        return $res;
    }
}