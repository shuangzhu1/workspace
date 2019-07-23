<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/8
 * Time: 17:44
 */

namespace Multiple\Wap\Api;


use Models\User\Users;
use Multiple\Wap\Helper\Verify;
use Services\Site\VerifyCodeManager;
use Util\Ajax;
use Util\Validator;

class SmsController extends ControllerBase
{
    /*发送手机验证码*/
    public function sendPhoneVerifyCodeAction()
    {
        $params = $this->request->get('params', 'string', '');
        $data = Verify::parseParams($params);
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        $phone = !empty($data['phone']) ? $data['phone'] : '';
        $device = "wap";
        $type = !empty($data['type']) ? $data['type'] : '';
        $is_voice = !empty($data['is_voice']) ? $data['is_voice'] : '';


        $uid = $this->uid;
        if (!$phone || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!Validator::validateCellPhone($phone)) {
            $this->ajax->outError(Ajax::INVALID_PHONE);
        }
        if (!$type || trim($type) == '' || !in_array($type, VerifyCodeManager::$codetype)) {
            $this->ajax->outError(Ajax::INVALID_MSG_TYPE);
        }
        switch ($type) {
            //新闻消息-抓取失败
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_MERGENCY_NEWS]:
                $uid = 0;
                VerifyCodeManager::init()->sendPhoneNormalMessage($phone, $type, $device, $uid);
                return;
                break;
            //注册
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_REGISTER]:
                $user = Users::exist('phone="' . $phone . '"');
                if ($user) {
                    $this->ajax->outError(Ajax::ERROR_PHONE_HAS_BEING_USED);
                }
                $uid = 0;
                break;
            //绑定
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_BIND]:
                $user = Users::exist('phone="' . $phone . '"');
                if ($user) {
                    //该手机已被使用了
                    $this->ajax->outError(Ajax::ERROR_PHONE_HAS_BEING_USED);
                }
                break;
            //修改手机
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_CHANGE]:
                $user = Users::exist('phone="' . $phone . '"');
                if ($user) {
                    //该手机已被使用了
                    $this->ajax->outError(Ajax::ERROR_PHONE_HAS_BEING_USED);
                }
                break;
            //找回密码
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_FORGOT]:
                $user = Users::exist('phone="' . $phone . '"');
                if (!$user) {
                    //该用户不存在
                    $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
                }
                $uid = 0;
                break;
            //登录保护
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_LOGIN_SAFE]:
                $user = Users::findOne(['phone="' . $phone . '"', 'columns' => 'id']);
                if (!$user) {
                    //该用户不存在
                    $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
                }
                $uid = $user['id'];
                break;
            //临时解锁
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_UNLOCK]:
                $user = Users::findOne(['phone="' . $phone . '"', 'columns' => 'id']);
                if (!$user) {
                    //该用户不存在
                    $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
                }
                $uid = $user['id'];
                break;

        }
        VerifyCodeManager::init()->sendPhoneVerifyCode($phone, $type, $device, $uid, $is_voice, false);
    }
}