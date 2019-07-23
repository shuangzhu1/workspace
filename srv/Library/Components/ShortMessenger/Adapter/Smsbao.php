<?php
/**
 * 短信宝短信接口
 *
 * @author    nongquan
 * @time     2013-12-14 下午2:28
 */

namespace Components\ShortMessenger\Adapter;

use Components\ShortMessenger\MessengerInterface;
use Phalcon\Mvc\User\Plugin;
use Util\Validator;

/**
 * Class Sms 短信发送类
 */
class Smsbao extends Plugin implements MessengerInterface
{

    public $adapterName = "smsbao";

    private $api_url = "api.smsbao.com";

    private $errorMessages = [
        30 => '密码错误',
        40 => '账号不存在',
        41 => '余额不足',
        42 => '帐号过期',
        43 => 'IP地址限制',
        50 => '内容含有敏感词',
        51 => '手机号码不正确',
    ];


    /**
     * 构造函数
     *
     */
    /*public function __construct()
    {
        $host_key = $this->di->get('platform_host');
        $config = $this->di->get('config')->{$host_key}->sms->smsbao;
        $this->user_name = $config->user_name;
        $this->pass_word = $config->pass_word;
    }*/

    public function config(array $config)
    {
        if (isset($config['user_name'])) {
            $this->user_name = $config['user_name'];
        }
        if (isset($config['pass_word'])) {
            $this->pass_word = $config['pass_word'];
        }
        return $this;
    }

    /**
     * 发送自定义短信
     *
     * @param string $phone 手机号码，多个以逗号','隔开
     * @param string $message 自定义短信内容
     * @return array
     */
    public function send($phone, $message)
    {
        /* 验证参数START */
        if (!Validator::validateNumeric($phone) || !Validator::validateLength($phone, 10, 12) || !Validator::validateCellPhone($phone)) {
            return array(
                'error' => array(
                    'code' => 11111,
                    'msg' => '号码格式不正确',
                    'desc' => '号码格式不合法'
                ),
                'result' => 0
            );
        }
        if (empty($message)) {
            //内容为空
            return array(
                'error' => array(
                    'code' => 22222,
                    'msg' => '内容不能为空',
                    'desc' => '内容不能为空'
                ),
                'result' => 0
            );
        }
        /* 验证参数END */

        $flag = 0;
        // 要post的数据
        $argv = array(
            'u' => $this->user_name, // 序列号
            'p' => strtoupper(md5($this->pass_word)), //密码
            'm' => $phone, // 手机号 多个用英文的逗号隔开 post理论没有长度限制.推荐群发一次小于等于10000个手机号
            'c' => $message, // 短信内容
        );
        // 构造要post的字符串
        $params = '';
        foreach ($argv as $key => $value) {
            if ($flag != 0) {
                $params .= "&";
                $flag = 1;
            }
            $params .= $key . "=";
            $params .= urlencode($value);
            $flag = 1;
        }
        $length = strlen($params);
        // 创建socket连接
        $fp = fsockopen($this->api_url, 80, $errno, $errstr, 10) or exit($errstr . "--->" . $errno);
        // 构造post请求的头
        $header = "POST /sms HTTP/1.1\r\n";
        $header .= "Host:{$this->api_url}\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . $length . "\r\n";
        $header .= "Connection: Close\r\n\r\n";
        // 添加post的字符串
        $header .= $params . "\r\n";
        // 发送post的数据
        fputs($fp, $header);
        $inheader = 1;
        $line = '';
        while (!feof($fp)) {
            $line = fgets($fp, 1024); //去除请求包的头只显示页面的返回数据
            if ($inheader && ($line == "\n" || $line == "\r\n")) {
                $inheader = 0;
            }
            if ($inheader == 0) {
                // echo $line;
            }
        }
        if (trim($line) == 0) { // 发送成功
            return array(
                'error' => array(
                    'code' => 0,
                    'msg' => '成功',
                    'desc' => '短信发送成功'
                ),
                'result' => 1,
                'data' => array(
                    'rs' => $line
                )
            );
        } else { // 发送失败
            return array(
                'error' => array(
                    'code' => 33333,
                    'msg' => '失败',
                    'desc' => '短信发送失败'
                ),
                'result' => 0,
                'data' => array(
                    'rs' => $line
                )
            );
        }
    }

    public function massSend(array $phones, $message)
    {

    }
}