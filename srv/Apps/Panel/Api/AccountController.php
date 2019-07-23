<?php
/**
 * Created by PhpStorm.
 * User: yanue-mi
 * Date: 2014/9/23
 * Time: 13:49
 */

namespace Multiple\Panel\Api;


use Models\Admin\Admins;
use Models\CustomerOpenInfo;
use Models\Customers;
use Services\Admin\AdminLog;
use Services\Site\CacheSetting;
use Util\Ajax;
use Util\Validator;

class AccountController extends ApiBase
{
    protected $_check_login = false;

    public function loginAction()
    {
        $account = $this->request->getPost('account');
        $password = $this->request->getPost('password');

        if (!Validator::validEmail($account)) {
            if (!Validator::validateAliasName($account)) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "账号格式不准确，请填写正确的用户名或邮箱");
            }
        }

        /*   if (!Validator::validPassword($password)) {
               $this->ajax->outError(Ajax::ERROR_PASSWD_IS_INVALID);
           }*/

        $password = sha1($password);
        //$password = "8cefa542a79b5895dc677623f293bce256bc1466";
        $sql = '(email="' . $account . '" or account="' . $account . '")';
        $user = Admins::findOne(array($sql));
        if (!$user) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '用户名或密码不正确！');
        }

        if ($user['status'] != 1) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '该账号已被禁用～！');
        }

        if ($user['password'] != $password) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '用户名或密码不正确!!' );
        }

        // 更新
        Admins::updateOne(array(
            'login_times' => $user['login_times'] + 1,
            'last_login' => time(),
        ), ['id' => $user['id']]);

        $current_request_url = $this->session->get("current_request_url");

        $redirect = $current_request_url && $current_request_url != $this->uri->baseUrl() ? $current_request_url : '/panel';
        // 判断跳转
        $redirect = "http://localhost:8081/srv/panel";
        $this->_registerSession($user);
        AdminLog::init()->add('登录', AdminLog::TYPE_LOGIN, '', array('type' => "login"));
        $this->ajax->outRight($redirect);
    }

    // 保存登陆信息
    private function _registerSession($admin)
    {
        // $wechat = CustomerOpenInfo::findFirst("");

        //  $user = Customers::findFirst();

        //  $this->session->set('customer_wechat', $wechat);
        // $this->session->set('customer_info', $user);
        $this->session->set('customer_auth', true);
        $this->session->set('admin', $admin);
    }
}