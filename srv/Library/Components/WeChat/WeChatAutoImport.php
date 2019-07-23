<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 5/4/14
 * Time: 3:53 PM
 */

namespace Components\WeChat;

use Phalcon\Mvc\User\Plugin;

define('COOKIE_PATH', ROOT . '/Cache/cookie/wechat/');

class WeChatAutoImport extends Plugin
{
    private $customer;
    private $token;
    private $account;
    private $errcode;
    private $errmsg;
    private $cookie;
    private $cookie_file;
    private $url = '';
    private $referer = '';
    private $agent = 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:21.0) Gecko/20100101 Firefox/21.0';

    /**
     * 模拟登陆
     *
     * @param $customer
     * @param $user
     * @param $password
     * @param $imgcode
     * @return mixed
     */
    public function init($customer, $user, $password, $imgcode)
    {
        $this->customer = $customer;
        $this->cookie_file = COOKIE_PATH . 'cookie_file_' . $this->customer . '.txt';
        if (!file_exists($this->cookie_file)) {
            $cfh = fopen($this->cookie_file, 'wx');
            fwrite($cfh, '');
            fclose($cfh);
        }
        $this->account = $user;
//        $this->getCodeImg($user);
        if (!$this->token) {
            //初始化，登录微信平台
            //验证码
            $this->url = "https://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN";
            $this->referer = 'https://mp.weixin.qq.com/cgi-bin/loginpage?t=wxm2-login&lang=zh_CN';
            $post['username'] = $user;
            $post['pwd'] = md5($password);
            $post['f'] = 'json';
            $post['imgcode'] = $imgcode;

            $html = $this->_curl_post($post);
            $result = explode("\n", $html);
            foreach ($result as $value) {
                $value = trim($value);
                /*
                 $str = '{"base_resp":{"ret":0,"err_msg":"ok"},"redirect_url":"\/cgi-bin\/home?t=home\/index&lang=zh_CN&token=818603488"}';
                 $s = json_decode($str,true);
                 **/
                $res = $this->apiResult($value);

                if ($res['base_resp']['ret'] == 'ok') {
                    parse_str($res['redirect_url'], $t);
                    $this->token = $t['token'];
                }
            }

        }
        return $this->token;
    }

    /**
     * 开启开发模式
     *
     * @return bool|mixed
     */
    public function openDevelopmentMode()
    {
        $this->url = "https://mp.weixin.qq.com/misc/skeyform?form=advancedswitchform&lang=zh_CN";
        $post['flag'] = 1;
        $post['type'] = 2;
        $post['token'] = $this->token;
        $html = $this->_curl_post($post);

        $result = explode("\n", $html);
        foreach ($result as $value) {
            $value = trim($value);
            /*
             $str = '{"base_resp":{"ret":0,"err_msg":"ok"},"redirect_url":"\/cgi-bin\/home?t=home\/index&lang=zh_CN&token=818603488"}';
             $s = json_decode($str,true);
             **/
            $res = $this->apiResult($value);

            if ($res['base_resp']['ret'] == 'ok') {
                return $res;
            }
        }
        return false;
    }

    /**
     * 获取appid及appsecret
     *
     * @return mixed
     */
    public function getAppKeySecret()
    {
        $this->url = 'https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=' . $this->token . '&lang=zh_CN';
        $html = $this->_curl_get();
        preg_match('/{name:"AppId",value:"(.*)"}/isU', $html, $match);
        $res['app_id'] = $match[1];
        preg_match('/{name:"AppSecret",value:"(.*)"}/isU', $html, $match);
        $res['app_secret'] = $match[1];
        preg_match('/{name:"URL",value:"(.*)"}/isU', $html, $match);
        $res['URL'] = $match[1];
        preg_match('/{name:"Token",value:"(.*)"},/isU', $html, $match);
        $res['Token'] = $match[1];

        return $res;
    }

    /**
     * 设置回调信息
     *
     * @param $url
     * @param $callback_token
     * @return bool|mixed
     */
    public function setCallbackProfile($url, $callback_token)
    {
        //get operation_seq value
        $this->url = 'https://mp.weixin.qq.com/advanced/advanced?action=interface&t=advanced/interface&token=' . $this->token . '&lang=zh_CN';
        $html = $this->_curl_get();
        preg_match('/operation_seq: "(.*)"/', $html, $math);
        $operation_seq = $math[1];
        $this->url = 'https://mp.weixin.qq.com/advanced/callbackprofile?t=ajax-response&token=' . $this->token . '&lang=zh_CN';
        $post['url'] = $url;
        $post['callback_token'] = $callback_token;
        $post['operation_seq'] = $operation_seq;
        $this->di->get('wechatLogger')->debug("post data:" . json_encode($post));
        $html = $this->_curl_post($post, false);
        $res = json_decode($html, true);
        if ($res['ret'] == 0) {
            return $res;
        }
        $this->di->get('wechatLogger')->debug("callback setting result:" . $html);
        return false;
    }

    /**
     * 获取账号信息
     *
     * @return array|mixed
     */
    public function getAccountInfo()
    {
        $this->url = "https://mp.weixin.qq.com/cgi-bin/settingpage?t=setting/index&action=index&token=" . $this->token . "&lang=zh_CN";
        $html = $this->_curl_get();
        $info = array();
        preg_match('/uin.*?"([0-9]+?)"/', $html, $match);
        $fakeid = $info['fakeid'] = $match[1];
        if (preg_match_all('/<div[^>]*class="meta_content"[^>]*>(.*?)<\/div>/si', $html, $match)) {
            $info['avatar_buff'] = $this->getheadimgBuff($fakeid);
            $info['qrcode_buff'] = $this->getqrcodeBuff($fakeid);
            $info['name'] = @trim(strip_tags($match[1][1]));
            $info['email'] = @trim(strip_tags($match[1][2]));
            $info['original_id'] = @trim(strip_tags($match[0][3]));
            $info['app_account'] = @trim(strip_tags($match[0][4]));
            $info['type'] = @trim(strip_tags($match[1][6])) == "服务号" ? 'fw' : 'dy';
            $info['address'] = @trim(strip_tags($match[1][9]));
            $info['desc'] = @trim(strip_tags($match[1][10]));
            return $info;
        }
        return false;
    }

    public function getheadimgBuff($fakeid)
    {
        $this->url = "https://mp.weixin.qq.com/misc/getheadimg?fakeid=" . $fakeid . "&token=" . $this->token;
        $buff = $this->_curl_get();

        return $buff;
    }

    public function getqrcodeBuff($fakeid)
    {
        $this->url = "https://mp.weixin.qq.com/misc/getqrcode?fakeid=" . $fakeid . "&token=" . $this->token;
        $html = $this->_curl_get();

        return $html;
    }

    /**
     * 获取登陆验证码
     *
     * @param $user
     * @return string
     */
    public function getCodeImg($user)
    {
        $this->url = 'https://mp.weixin.qq.com/cgi-bin/verifycode?username=' . $user . '&r=' . time();
        // get imgcode cookie
        $html = $this->_curl_get();
        $result = explode("\n", $html);
        foreach ($result as $value) {
            $value = trim($value);
            if (preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $value, $match)) { //获取cookie
                $this->cookie .= $match[1] . '=' . $match[2] . '; ';
            }
        }

        return $this->url;
    }

    /**
     *
     *
     * @param $value
     * @return bool|mixed
     */
    private function apiResult($value)
    {
        if (preg_match('/^{\"base_resp\"\:(.*)\}$/iu', $value, $match)) {
            // msg
            $res = json_decode($value, true);

            $this->errcode = $res['base_resp']['ret'];
            $this->errmsg = $res['base_resp']['err_msg'];

            return $res;
        }
        return false;
    }

    /**
     * curl post method
     *
     * @param $post
     * @param bool $back_header
     * @return mixed
     */
    private function _curl_post($post, $back_header = true)
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //验证HOST
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //对推送来的消息进行设置   1不自动输出任何内容 0输出返回的内容
        if ($back_header) {
            curl_setopt($ch, CURLOPT_HEADER, 1); //是否返回头文件
        }
        curl_setopt($ch, CURLOPT_REFERER, $this->referer ? $this->referer : $this->url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); //数据传输最大允许时间
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        $html = curl_exec($ch);
        curl_close($ch);

        return $html;
    }

    /**
     * curl get method
     *
     * @return mixed
     */
    private function _curl_get()
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_REFERER, $this->url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        $html = curl_exec($ch);
        curl_close($ch);

        return $html;
    }

    /**
     * return err msg
     *
     * @return mixed
     */
    public function getMsg()
    {
        return $this->errmsg;
    }
}
