<?php
/**
 * Created by PhpStorm.
 * User: yanue-mi
 * Date: 2014/9/23
 * Time: 13:52
 */

namespace Components;

use Library\PHPMailer\PHPMailer;
use Models\Admin\Admins;
use Phalcon\Exception;
use Phalcon\Mvc\User\Plugin;
use Util\Ajax;
use Util\Validator;

class CustomerManager extends Plugin
{

    const ACCOUNT_STATUS_EXPIRED_TRIAL = '-1'; // 试用过期
    const ACCOUNT_STATUS_EXPIRED_PACKAGE = '-2'; // 套餐过期
    const ACCOUNT_STATUS_TRYOUT = '0'; // 试用中
    const ACCOUNT_STATUS_IN_USE = '1'; // 使用中

    const ACCOUNT_TRIAL_PERIOD = 2592000; //试用期限30天 30*3600*24

    public static $_active_name = array(
        self::ACCOUNT_STATUS_EXPIRED_TRIAL => '试用过期',
        self::ACCOUNT_STATUS_EXPIRED_PACKAGE => '套餐已过期',
        self::ACCOUNT_STATUS_TRYOUT => '试用中',
        self::ACCOUNT_STATUS_IN_USE => "使用中"
    );

    private static $instance = null;

    // 检查账号状态
    public function getAccountInfo()
    {

    }

    public static function init()
    {
        if (!static::$instance) {
            return new self();
        }

        return static::$instance;
    }

    // 登陆
    public function login($account, $password)
    {
        if (!Validator::validEmail($account)) {
            if (!Validator::validateAliasName($account)) {
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, "账号格式不准确，请填写正确的用户名或邮箱");
            }
        }

        if (!Validator::validPassword($password)) {
            Ajax::init()->outError(Ajax::ERROR_PASSWD_IS_INVALID);
        }

        $password = sha1($password);
        $sql = '(email="' . $account . '" or account="' . $account . '") AND password="' . $password . '" AND host_key="' . HOST_KEY . '"';
        $user = Admins::findOne(array($sql));
        if (!$user) {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '用户名或密码不正确！');
        }

        // 判断跳转
        $redirect = $this->checkJoinStepRedirect();
        $this->_registerSession($user);
        return Ajax::init()->outRight('', $redirect);
    }

    public function forgot($email)
    {
        if (!Validator::validEmail($email)) {
            Ajax::init()->outError(Ajax::ERROR_EMAIL_IS_INVALID);
        }

        $user = Admins::findOne("email='" . $email . "' AND host_key='" . HOST_KEY . "'");
        if (!$user) {
            Ajax::init()->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }

        $pass = mt_rand(100000, 999999);
        // 如果邮箱不存在，则保存到当前用户
        if (!Admins::updateOne(['password' => sha1($pass)], ['id' => $user['id']])) {

            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '');
        }

        // todo send mail
        // 邮箱注册用户，要进行邮件通知
        $loginUrl = $this->uri->baseUrl('/account/login');
        $this->session->set("current_request_url", $this->uri->baseUrl('/panel/setting/password'));
        $hostBrand = HOST_BRAND;
        $date = date('Y');
        $message = <<<EOF
                <html lang="zh">
                <head>
                <title>重置用户密码</title>
                <style type="text/css">
                span {
                    color: red;
                }
                </style>
                </head>
                <body>
                尊敬的客户您好：<br/>
                    您在我们网站平台进行了密码重置，以下是信息： <br/>
                    登录邮箱：<span style='color:red;'>{$email}</span><br />
                    重置后的密码：<span style='color:red;'>{$pass}</span>。</br>
                    请登陆后自行修改或妥善保管。祝您愉快！
                    <br/>
                    <br/>
                    登陆地址：<br/>
                    <a href="{$loginUrl}">{$loginUrl}</a>
                    <br/>
                    <br/>
                -----------------------<br/>
                {$date} @ <a href="{$hostBrand}">{$hostBrand}管理团队</a><br/>
                -----------------------<br/>
                系统邮件，请勿回复！
                </body>
                </html>
EOF;
        require_once(ROOT . '/Library/PHPMailer/PHPMailerAutoload.php');
        require_once(ROOT . '/Library/PHPMailer/PHPMailer.php');

        $mailer = new PHPMailer();
        $mailer->CharSet = "UTF-8";
        $mailer->Subject = "重置用户密码";
        //$mailer->From = "service@" . MAIN_DOMAIN;
        $mailer->From = "wwglfy@163.com";
        $mailer->FromName = HOST_BRAND;
        $mailer->msgHTML($message);
        $mailer->addAddress($email);

        // var_dump($mailer);
        if (!$mailer->send()) {
            Ajax::init()->outError(Ajax::ERROR_RUN_TIME_ERROR_OCCURRED, $mailer->ErrorInfo);
        }
        //  return Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'邮件发送失败！',1000);
        Ajax::init()->outRight('邮件发送成功！');
    }

    public function checkJoinStepRedirect()
    {
        $current_request_url = $this->session->get("current_request_url");
        // 重置密码优先
        if (strpos('/panel/setting/password', $current_request_url)) {
            return $redirect = '/panel/setting/password';
        }

        return $current_request_url && $current_request_url != $this->uri->baseUrl() ? $current_request_url : '/panel';
    }

    // 保存登陆信息
    private function _registerSession($user)
    {
        $this->session->set('customer_info', $user);
        $this->session->set('customer_auth', true);
    }

    public function setGuideSteps($guide_steps, $type, $val = true)
    {
        $guide = $guide_steps ? json_decode($guide_steps, true) : [];

        if ($type == "profile") {
            $guide['profile'] = true;
        }

        if ($type == "pick") {
            $guide['pick'] = true;
        }

        if ($type == "order") {
            $guide['order'] = true;
        }

        return json_encode($guide, JSON_UNESCAPED_UNICODE);
    }

    // 登出
    public function logout()
    {
        $this->session->remove('customer_auth');
        $this->session->remove('customer_info');
        $this->session->remove('customer_wechat');
        $this->session->remove('customer_weibo');
    }
}