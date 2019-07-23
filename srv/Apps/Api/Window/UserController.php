<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/19
 * Time: 17:09
 */

namespace Window;


use Models\User\UserInfo;
use Models\User\UserProfile;
use Models\User\Users;
use Services\User\UserStatus;
use Util\Ajax;

class UserController extends ControllerBase
{
    public function loginAction()
    {
        $phone = $this->request->getPost('phone');
        $password = $this->request->getPost('pass');
        if (!$phone) {
            $this->ajax->outError(Ajax::ERROR_PHONE_IS_INVALID);
        }

        if ((strlen($password) <= 0)) {
            $this->ajax->outError(Ajax::ERROR_PASSWD_IS_INVALID);
        }

        $user = Users::findOne(['phone = "' . $phone . '"', 'columns' => 'password,password_salt,id,avatar,username,status']);
        if (!$user) {
            $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }

        if ($user['password'] != md5($user['password_salt'] . $password)) {
            $this->ajax->outError(Ajax::ERROR_USER_OR_PASSWORD);
        }

        //被永久删除了
        if ($user['status'] == UserStatus::STATUS_DELETED) {
            $this->ajax->outError(Ajax::ERROR_USER_DELETED);
        } //被临时锁定了
        else if ($user['status'] == UserStatus::STATUS_LOCKED) {
            Ajax::init()->outError(Ajax::ERROR_USER_HAS_BEING_LOCKED);
        }
        $user_profile = UserProfile::findOne(['user_id=' . $user['id'], 'columns' => 'yx_token']);
        $res = [
            'user_id' => $user['id'],
            'email' => '',
            'avatar' => $user['avatar'],
            'username' => $user['username'],
            'token' => $user_profile['yx_token'],
        ];
        // res
        Ajax::init()->outRight($res);
    }

    public function getOtherUserInfoAction()
    {
        $uid = $this->request->getPost("uid", 'int', 0);
        $get_uid = $this->request->getPost("get_uid", 'int', 0);

        if (!$uid || !$get_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $user_info = UserStatus::getInstance()->getUserInfo($uid, $get_uid);
        $this->ajax->outRight($user_info);
    }
}