<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/26
 * Time: 10:03
 */

namespace Multiple\Api\Controllers;

use Models\Agent\AgentApply;
use Services\Agent\AgentManager;
use Util\Ajax;
use Util\Debug;
use Util\Validator;

class AgentController extends ControllerBase
{
    //提交合伙人申请
    public function applyAction()
    {
        $uid = $this->uid;
        $phone = $this->request->get("phone", 'int', 0);//手机号码
        $qq = $this->request->get("qq", 'string', '');//qq
        $weixin = $this->request->get("weixin", 'string', '');//微信
        $email = $this->request->get("email", 'string', '');//邮箱
        $brief = $this->request->get("brief", 'string', '');//简介
        $address = $this->request->get("address", 'string', '');//地址
        $code = $this->request->get("code", 'string', '');//上级合伙人邀请码

        if (!$uid || !$phone || !$address) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!Validator::validateCellPhone($phone)) {
            $this->ajax->outError(Ajax::INVALID_PHONE);
        }
        $count = AgentApply::dataCount("user_id=" . $uid . " and created>=" . strtotime(date('Ymd')));
        if ($count >= 3) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "您今天的提交次数已达上限");
        }
        if (!$res = AgentManager::init()->apply($uid, $brief, $phone, $qq, $weixin, $email, $address, $code)) {
            $this->ajax->outError(Ajax::FAIL_SUBMIT);
        }
        $this->ajax->outRight($res);
    }

    //代理详情
    public function detailAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(AgentManager::init()->detail($uid));
    }

    //支付完成
    public function paySuccessAction()
    {
        $uid = $this->uid;
        $trade_no = $this->request->get("trade_no");
        if (!$uid || !$trade_no) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $agentManager = AgentManager::init();
        if ($res = $agentManager->paySuccess($uid, '', $trade_no)) {
            Debug::log("pay_success income:" . var_export($res, true), 'debug');
            //收益立刻到账
            if ($res['income_id']) {
                foreach ($res['income_id'] as $i) {
                    AgentManager::init()->incomeToAccountSingle($i);
                }
            }
            $this->ajax->outRight("");
        }
        // $this->ajax->outError(Ajax::FAIL_PAY, $agentManager->getErrorMsg());
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "支付失败(余额不足)");

    }

    //收益
    public function incomeAction()
    {
        $uid = $this->uid;
        $status = $this->request->get("status", 'int', -1);
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $type = $this->request->get("type", 'int', 1); //1-开店 3-成为合伙人

        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "uid为空");
        }
        $res = AgentManager::init()->incomeList($uid, $status, $type, $page, $limit);
        $this->ajax->outRight($res);
    }


}