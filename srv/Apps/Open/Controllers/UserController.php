<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/10
 * Time: 14:17
 */

namespace Multiple\Open\Controllers;


use Models\Customer\CustomerUser;
use Models\User\UserInfo;
use Multiple\Open\Helper\Ajax;
use Multiple\Open\Helper\Identify;
use Services\Site\CacheSetting;
use Services\User\UserStatus;

/**
 * Class UserController
 * @package Multiple\Open\Controllers
 * @property Ajax $ajax
 */
class UserController extends ControllerBase
{
    //授权
    public function authAction()
    {
        $uid = $this->request->get("uid", 'int', 0);//用户id
        $os_version = $this->request->get("os_version", 'string', '');//ISO10.1.1
        $phone_model = $this->request->get("phone_model", 'string', '');//iphone 5s
        $device_id = $this->request->get("device_id", 'string', '');//手机设备号

        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $user = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'username,sex,avatar,province_name as province,city_name as city']);
        if (!$user) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $user_info = [
            'token' => UserStatus::getInstance()->createToken($uid, $this->app_id),
            'username' => $user['username'],
            'avatar' => $user['avatar'],
            'sex' => $user['sex'],
        ];

        //之前授权过
        if ($customer_user = CustomerUser::findOne(['user_id=' . $uid . " and app_id='" . $this->app_id . "'", 'columns' => 'id,open_id'])) {
            $user_info['open_id'] = $customer_user['open_id'];
            $update_data = [
                'last_os_version' => $os_version,
                'last_sdk_version' => $this->sdk_version,
                'last_device_id' => $device_id,
                'last_device' => $this->client_type,
                'last_phone_model' => $phone_model
            ];
            CustomerUser::updateOne($update_data, "id=" . $customer_user['id']);
        } else {
            $user = new CustomerUser();
            $data = [
                'user_id' => $uid,
                'app_id' => $this->app_id,
                'open_id' => md5($uid . '/' . $this->app['app_key'] . '/' . $this->app['app_key']),
                'created' => time(),
                'last_os_version' => $os_version,
                'last_sdk_version' => $this->sdk_version,
                'last_device_id' => $device_id,
                'last_device' => $this->client_type,
                'last_phone_model' => $phone_model,
            ];
            if (!$user->insertOne($data)) {
                $this->ajax->outError(Ajax::FAIL_GET_INFO);
            }
            $user_info['open_id'] = $data['open_id'];
        }
        $this->ajax->outRight($user_info);
    }

    //获取应用信息
    public function appInfoAction()
    {
        $uid = $this->request->get("uid", 'int', 0);//用户id
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ['name' => $this->app['name'], 'logo' => $this->app['logo'], 'has_auth' => 0, 'pm' => "获取你的公开信息(昵称，头像等)"];
        if (CustomerUser::exist('user_id=' . $uid)) {
            $data['has_auth'] = 1;
        }
        $this->ajax->outRight($data);
    }

    //获取用户信息
    public function getUserInfoAction()
    {
        $token = $this->request->get("token", 'string', '');
        if (!$token || strlen($token) != 32) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        $cacheSetting = new CacheSetting();
        $app_uid = $cacheSetting->get(CacheSetting::PREFIX_USER_TOKEN, $token);
        if (!$app_uid) {
            $this->ajax->outError(Ajax::ERROR_TOKEN);
        }

        $app_uid = explode('/', $app_uid);
        if ($app_uid[1] != $this->app_id) {
            $this->ajax->outError(Ajax::ERROR_ILLEGAL_TOKEN);
        }
        $user = UserInfo::findOne(['user_id=' . $app_uid[0], 'columns' => 'username,sex,avatar,province_name as province,city_name as city']);
        $customer_user = CustomerUser::findOne(['user_id=' . $app_uid[0], 'columns' => 'open_id']);
        $user_info = ['username' => $user['username'], 'avatar' => $user['avatar'], 'open_id' => $customer_user['open_id'], 'sex' => $user['sex']];
        //  $cacheSetting->remove(CacheSetting::PREFIX_USER_TOKEN, $token);//清除token
        $this->ajax->outRight($user_info);
    }
}