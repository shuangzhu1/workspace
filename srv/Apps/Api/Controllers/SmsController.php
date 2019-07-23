<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/14
 * Time: 10:08
 */

namespace Multiple\Api\Controllers;


use Models\User\Users;
use Services\Site\VerifyCodeManager;
use Util\Ajax;
use Util\Debug;
use Util\Validator;

class SmsController extends ControllerBase
{
    /*发送手机验证码*/
    public function sendPhoneVerifyCodeAction()
    {
        $phone = $this->request->get('phone', 'string', '');
        $device = $this->client_type;
        $type = $this->request->get('type', 'string', '');
        $is_voice = $this->request->get('is_voice', 'int', 0);

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
                $uid = 0; //$user['id'];
                break;
            //临时解锁
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_UNLOCK]:
                $user = Users::findOne(['phone="' . $phone . '"', 'columns' => 'id']);
                if (!$user) {
                    //该用户不存在
                    $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
                }
                $uid = 0;//$user['id'];
                break;
            //设置支付密码
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_SET_PAY_PASSWORD]:
                //找回支付密码
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_PAY_PASSWORD]:
                $user = Users::findOne(['phone="' . $phone . '"', 'columns' => 'id']);
                if (!$user) {
                    //该用户不存在
                    $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
                }
                $uid = $user['id'];
                break;
            //新闻消息-抓取失败
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_MERGENCY_NEWS]:
                $uid = 0;
                VerifyCodeManager::init()->sendPhoneNormalMessage($phone, $type, $device, $uid);
                return;
                break;
            //资金奖励池不足
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_UNSUFFICIENT_REWARD]:
                $uid = 0;
                VerifyCodeManager::init()->sendPhoneNormalMessage($phone, $type, $device, $uid);
                return;
                break;
            //资金奖励池不足
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_UNSUFFICIENT_PROMOTE]:
                $uid = 0;
                VerifyCodeManager::init()->sendPhoneNormalMessage($phone, $type, $device, $uid);
                return;
                break;
            //申请提现
            case VerifyCodeManager::$codetype[VerifyCodeManager::CODE_CASH_OUT]:
                //$uid = 0;
                // VerifyCodeManager::init()->sendPhoneNormalMessage($phone, $type, $device, $uid);
                // return;
                break;
        }

        VerifyCodeManager::init()->sendPhoneVerifyCode($phone, $type, $device, $uid, $is_voice, true);
    }

    /**
     * 校验验证码
     */
    public function checkVerifyCodeAction()
    {
        $phone = $this->request->get('phone', 'string', '');
        $type = $this->request->get('type', 'string', '');
        $code = $this->request->get('code', 'string', '');
        $uid = $this->request->get('uid', 'int', 0);

        if (!$phone || !$type || !$code) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$type || trim($type) == '' || !in_array($type, VerifyCodeManager::$codetype)) {
            $this->ajax->outError(Ajax::INVALID_MSG_TYPE);
        }
        //临时解锁
        if ($type == VerifyCodeManager::CODE_UNLOCK || $type == VerifyCodeManager::CODE_LOGIN_SAFE) {
            $uid = 0;
        }

        $msg = VerifyCodeManager::init()->checkVerifyCode($phone, $type, $this->client_type, $uid, $code);
        if ($msg != '1') {
            $this->ajax->outError(Ajax::ERROR_VERIFY_CODE);
        }
        $this->ajax->outRight("");
    }
}