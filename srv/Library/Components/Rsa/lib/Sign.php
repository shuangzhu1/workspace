<?php
namespace Components\Rsa\lib;

use Components\Rsa\BaseSign;
use Util\Debug;


/**
 * Created by PhpStorm.
 * User: luguiwu
 * Date: 2016/6/28
 * Time: 11:25
 */
class Sign extends BaseSign
{

    public $public_key = '';
    public $private_key = '';

    public function __construct($client_type = '', $app_version = '')
    {
        if (($client_type == 'android' && version_compare($app_version, '2.1.0100', '>')) ||
            ($client_type == 'ios' && version_compare($app_version, '2.0.3', '>'))
        ) {
            $this->public_key = ROOT . '/Library/Components/Rsa/key/v2/rsa_public_key.pem';
            $this->private_key = ROOT . '/Library/Components/Rsa/key/v2/rsa_private_key.pem';
        } else {
            $this->public_key = ROOT . '/Library/Components/Rsa/key/rsa_public_key.pem';
            $this->private_key = ROOT . '/Library/Components/Rsa/key/rsa_private_key.pem';
        }
    }
    /**RSA签名
     * $data待签名数据
     * 签名用私钥，必须是没有经过pkcs8转换的私钥
     * 最后的签名，需要用base64编码
     * return Sign签名
     */
    /*  public static function sign($data)
      {
          $sign= parent::rsa_encrypt($data,WEB_ROOT . '/plugin/rsa/app/key/rsa_private_key.pem');
          return $sign;
      }*/

    /**公钥加密
     * @param $data
     * @return string
     */
    public function publicSign($data)
    {
        $sign = parent::rsa_encrypt($data, $this->public_key);
        return $sign;
    }

    public function signArr($sign)
    {
        return parent::getSignArr($this->private_key, $sign);
    }

    public function signStr($sign)
    {
        return parent::getSignStr($this->private_key, $sign);
    }

    /**私钥验签
     * @param $prestr
     * @param $sign
     * @return bool
     */
    public function privateRsaVerify($prestr, $sign, $has_salt)
    {
        $result = parent::rsa_decrypt($sign, $this->private_key);

        /*  if ($has_salt) {
              $pos = strrpos($result, '&');
              $result = substr($result, 0, $pos);
              if ($result && $result == $prestr) {
                  return true;
              }
          } else {*/

        if ($result && $result == $prestr) {
            return true;
        }else{
            Debug::log("原串:" . $prestr, 'debug');
            Debug::log("解密后：" . $result, 'debug');
            return false;
        }
        //  }


    }

}