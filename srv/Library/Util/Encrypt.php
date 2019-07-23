<?php
namespace Util;

/**
 * Class Encrypt
 * @package Util
 *
 *  $cipher_list = mcrypt_list_algorithms();//mcrypt支持的加密算法列表
 * $mode_list = mcrypt_list_modes();//mcrypt支持的加密模式列表
 */

class Encrypt
{

    private $data;
    private $key;
    private $ajaxKey;
    private $mode;
    private $cipher;
    private $isAjax = false;
    private $salt = "klgwl.com";

    /**
     * @var Encrypt
     */
    private static $instance = null;

    /**
     * @var source
     */
    private static $module = null;

    const MCRYPT_CIPHER = MCRYPT_RIJNDAEL_128;
    const MCRYPT_MOD = MCRYPT_MODE_ECB;
    const MINIMUM_LENGTH = 16;

    private function __construct()
    {
        $this->checkEnvironment();
        $key = Config::getSite('crypt', 'encrypt.secret');
        $this->key = $key;
        $ajaxKey = $key = Config::getSite('crypt', 'encrypt.secret');
        $this->ajaxKey = $ajaxKey;
        $this->mode = self::MCRYPT_MOD;
        $this->cipher = self::MCRYPT_CIPHER;
        if (empty(self::$module)) {
            $iv_size = mcrypt_get_iv_size(self::MCRYPT_CIPHER, self::MCRYPT_MOD);
            self::$module = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        }
    }

    public static function instance()
    {
        if (!self::$instance instanceof Encrypt) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Checks the environment for mcrypt and mcrypt module
     *
     *
     */
    private function checkEnvironment()
    {
        if ((!extension_loaded('mcrypt')) || (!function_exists('mcrypt_module_open'))) {
            throw new \Exception('The PHP mcrypt extension must be installed for encryption', 1);
        }
        if (!in_array(self::MCRYPT_CIPHER, mcrypt_list_algorithms())) {
            throw new \Exception("The cipher used self::MCRYPT_MODULE does not appear to be supported by the installed version of libmcrypt", 1);
        }
    }

    public function getModule()
    {
        return self::$module;
    }

    /**
     * Sets the data for encryption or decryption
     *
     * @param mixed $data
     * @author Osman Üngür
     * @return Encrypt
     */
    public function setData($data)
    {
        $this->my_encoding($data, 'UTF-8');
        $this->data = $data;
        return $this;
    }

    /**设置盐值
     * @param $salt
     * @return $this
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * 自动检测字符串编码并转换为指定编码
     * @param string $data
     * @param string $to
     * @return string
     */
    private function my_encoding($data, $to)
    {
        $encode_arr = array('UTF-8', 'ASCII', 'GBK', 'GB2312', 'BIG5', 'JIS', 'eucjp-win', 'sjis-win', 'EUC-JP');
        $encoded = mb_detect_encoding($data, $encode_arr);
        $data = mb_convert_encoding($data, $to, $encoded);
        return $data;
    }

    /**
     * @param $key
     * @param bool $isAjax
     * @return $this
     * @throws \Exception
     */
    public function setKey($key, $isAjax = false)
    {
        if (strlen($key) != self::MINIMUM_LENGTH) {
            $message = sprintf('The secret key must be a minimum %s character long', self::MINIMUM_LENGTH);
            throw new \Exception($message, 1);
        }
        if ($isAjax) {
            $this->ajaxKey = $key;
        } else {
            $this->key = $key;
        }
        return $this;
    }

    /**
     * Returns the encrypted or decrypted data
     *
     * @return mixed
     * @author Osman Üngür
     */
    private function getData()
    {
        return $this->data;
    }

    /**
     * Returns the secret key for encryption
     *
     * @return string
     * @author Osman Üngür
     */
    private function getKey()
    {
        if ($this->isAjax()) {
            return $this->ajaxKey;
        }
        return $this->key;
    }

    /**
     * Encrypts the given data using symmetric-key encryption
     *
     * @return string
     */
    public function encode()
    {
        $td = mcrypt_module_open(self::MCRYPT_CIPHER, "", self::MCRYPT_MOD, "");//使用MCRYPT_DES算法,ecb模式
        $size = mcrypt_enc_get_iv_size($td);       //设置初始向量的大小
        $iv = mcrypt_create_iv($size, MCRYPT_RAND); //创建初始向量

        $key_size = mcrypt_enc_get_key_size($td);       //返回所支持的最大的密钥长度（以字节计算）
        $subkey = substr(md5(md5($this->key) . $this->salt), 0, $key_size);//对key复杂处理，并设置长度

        mcrypt_generic_init($td, $subkey, $iv);
        $endata = mcrypt_generic($td, $this->data);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $this->stripUrlChars(base64_encode($endata));
    }

    /**
     * Decrypts encrypted cipher using symmetric-key encryption
     *
     * @return mixed
     * @author Osman Üngür
     */
    public function decode()
    {
        $this->data = base64_decode($this->destripUrlChars($this->data));
        $td = mcrypt_module_open(self::MCRYPT_CIPHER, "", self::MCRYPT_MOD, "");//使用MCRYPT_DES算法,ecb模式
        $size = mcrypt_enc_get_iv_size($td);       //设置初始向量的大小
        $iv = mcrypt_create_iv($size, MCRYPT_RAND); //创建初始向量
        $key_size = mcrypt_enc_get_key_size($td);       //返回所支持的最大的密钥长度（以字节计算）
        $subkey = substr(md5(md5($this->key) . $this->salt), 0, $key_size);//对key复杂处理，并设置长度
        mcrypt_generic_init($td, $subkey, $iv);
        $data = rtrim(mdecrypt_generic($td, $this->data));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $data;
    }

    /**
     * 将字符串里的+ / = 替换成- . *
     */
    public function stripUrlChars($str)
    {
        return str_replace(array('+', '/', '='), array('-', '.', '*'), $str);
    }

    public function destripUrlChars($str)
    {
        return str_replace(array('-', '.', '*'), array('+', '/', '='), $str);
    }

    public function isAjax()
    {
        $this->isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        return $this->isAjax;
    }

    /**
     * hash encrypt
     *
     * @param string $data
     * @return string
     */
    public static function hashCode($data)
    {
        $context = hash_init('sha512', HASH_HMAC, '!@:\"#$%^&*<>?{}$^$@*^&*I@!');
        hash_update($context, $data);

        return md5(hash_final($context));
    }

}

?>