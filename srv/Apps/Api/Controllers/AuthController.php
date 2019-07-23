<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 10:14
 */

namespace Multiple\Api\Controllers;


use Models\User\UserAuthApply;
use Services\Site\VerifyCodeManager;
use Services\User\AuthManager;
use Util\Ajax;
use Util\Validator;

class AuthController extends ControllerBase
{
    /*--名人认证提交--*/
    public function applyAction()
    {
        /*--认证提交--*/
        $uid = $this->uid;
        //  $type = $this->request->get('type', 'int', 0);//认证类别 1-职场名人，2-娱乐明星，3-体育人物，4-政府人员
        $true_name = $this->request->get('true_name', 'string', '');//真实姓名
        $id_card = $this->request->get('id_card', 'string', '');//身份证号
        /* $phone = $this->request->get('phone', 'string', '');*///手机号
        // $code = $this->request->get('code', 'string', '');//验证码
        // $introduce = $this->request->get('introduce', 'string', '');//人物介绍
        //  $website = $this->request->get('website', 'string', '');//个人链接
        $card_front = $this->request->get('card_front', 'string', '');//身份证正面
        $card_back = $this->request->get('card_back', 'string', '');//身份证反面
        $card_hand = $this->request->get('card_hand', 'string', '');//手持身份证照

        //  $desc = $this->request->get('desc', 'string', '');//认证说明
        //  $proof = $this->request->get('proof', 'string', '');//证明材料
        //  $industry = $this->request->get('industry_id', 'string', '');//行业
        // $company = $this->request->get('company', 'string', '');//公司/经纪公司/所在运动队/组织机构
        // $job = $this->request->get('job', 'string', '');//职位/职业

//        if (!$uid || !$type || !in_array($type, AuthManager::$auth_type) || !$true_name || !$id_card /*|| !$phone*/ /*|| !$code*/ || !$card_front || !$card_back || !$company || !$job || !$desc) {
//            $this->ajax->outError(Ajax::INVALID_PARAM);
//        }
        if (!$uid || !$true_name || !$id_card || !$card_front || !$card_back || !$card_hand) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        $data = [
            'true_name' => $true_name,
            'id_card' => $id_card,
            'card_front' => $card_front,
            'card_back' => $card_back,
            'card_hand' => $card_hand,
        ];

        //验证身份证
        if (!Validator::validateIDCard($data['id_card'])) {
            $this->ajax->outError(Ajax::INVALID_ID_CARD_TYPE);
        }
        if (AuthManager::init()->apply($uid, $data)) {
            $this->ajax->outRight("提交成功", Ajax::SUCCESS_SUBMIT);
        }
        $this->ajax->outError(Ajax::FAIL_SUBMIT);
    }

    /*--认证详情--*/
    public function detailAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(AuthManager::init()->detail($uid));
    }

    /*--最后一次认证信息--*/
    public function lastInfoAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $detail = UserAuthApply::findOne(['user_id=' . $uid, 'columns' => 'true_name,id_card,card_front,card_back,card_hand,status,check_reason', 'order' => 'created desc']);
        Ajax::outRight($detail ? $detail : (object)[]);
    }
}