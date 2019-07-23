<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/4
 * Time: 11:50
 */

namespace Multiple\Panel\Api;


use Services\User\UserStatus;
use Util\Ajax;

class SettingController extends ApiBase
{
    /**
     * 编辑绑定账号信息
     */
    public function editBoundUserAction()
    {
        $uid = $this->request->get('uid');
        $username = $this->request->get('username');
        $avatar = $this->request->get('avatar');
        $birth = $this->request->get('birth');
        $sex = $this->request->get('sex');
        if( !$uid || !$username || !$avatar || !$birth )
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'参数非法');
        $res = UserStatus::getInstance()->editInfo($uid,['avatar' => $avatar, 'username' => $username, 'sex' => $sex, 'birthday' => $birth]);
        if(!$res)
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'保存失败');
        else
            $this->ajax->outRight();
    }
}