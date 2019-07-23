<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/7
 * Time: 10:08
 */

namespace Merchant;


use Models\Customer\CustomerUser;
use Models\User\UserInfo;
use Multiple\Api\Merchant\Helper\Ajax;
use Services\Site\CacheSetting;
use Services\User\UserStatus;

class UserController extends ControllerBase
{
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

        //之前或去过用户信息
        if ($customer_user = CustomerUser::findOne(['user_id=' . $app_uid[0], 'columns' => 'open_id'])) {
            $user_info = UserInfo::findOne(['user_id=' . $app_uid[0], 'columns' => 'username,sex,avatar,province_name as province,city_name as city']);
            $user_info['open_id'] = $customer_user['open_id'];
        } else {
            $user = new CustomerUser();
            $data = ['user_id' => $app_uid[0], 'app_id' => $this->app_id, 'open_id' => md5($app_uid[0] . '/' . $this->app['app_secret']), 'created' => time()];
            if (!$user->insertOne($data)) {
                $this->ajax->outError(Ajax::FAIL_GET_INFO);
            }
            $user_info = UserInfo::findOne(['user_id=' . $app_uid[0], 'columns' => 'username,sex,avatar,province_name as province,city_name as city']);
            $user_info['open_id'] = $data['open_id'];
        }
      //  $cacheSetting->remove(CacheSetting::PREFIX_USER_TOKEN, $token);//清除token
        $this->ajax->outRight($user_info);

    }
}