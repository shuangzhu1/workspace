<?php
/**
 * Created by PhpStorm.
 * User: wgwang
 * Date: 14-8-14
 * Time: 下午2:39
 */

namespace Components\ShortMessenger;


use Phalcon\Mvc\User\Plugin;

abstract class AbstractAdapter extends Plugin implements MessengerInterface
{
    protected $user_name = 'SDK-WSS-010-06946';//'SDK-HGG-010-00044';
    protected $pass_word = '32b6-[a0';//'6FEA2600BDF27B7C387B398632BFDF97'; // 处理后的密钥
    protected $sign = '';

    public $adapterName = 'mandao';

    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        $config = $this->di->get('config')->customer_config->{HOST_KEY}->messenger->{$this->adapterName};
        $this->user_name = $config->user_name;
        $this->pass_word = $config->pass_word;
        $this->sign = $config->sign;
    }

    public function postRequest($url, $params = null, $inBody = false)
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($inBody && is_array($params)) {
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; chartset=utf-8',
                    'Content-Length: ' . strlen($params))
            );
        }
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        try {
            $response = curl_exec($ch);
//        $response = json_decode($response, true);
        } catch (\Exception $e) {
            $response = false;
        }
        return $response;
    }
} 