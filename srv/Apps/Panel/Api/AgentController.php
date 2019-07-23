<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/31
 * Time: 17:44
 */

namespace Multiple\Panel\Api;


use Services\Admin\AdminLog;
use Services\Agent\AgentManager;
use Services\Site\SiteKeyValManager;
use Util\Ajax;

class AgentController extends ApiBase
{
    //审核通过
    public function checkSuccessAction()
    {
        $id = $this->request->get('data');
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        foreach ($id as $item) {
            if (AgentManager::init()->check($item, 1, $this->session->get('admin')['id'], '')) {
                AdminLog::init()->add('合伙人审核通过', AdminLog::TYPE_AGENT, $item, array('type' => "update", 'id' => $item));
            }
        }
        $this->ajax->outRight("");

    }

    //审核失败
    public function checkFailAction()
    {
        $id = $this->request->get('id');
        $reason = $this->request->get('reason');

        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$reason) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (AgentManager::init()->check($id, 0, $this->session->get('admin')['id'], $reason)) {
            AdminLog::init()->add('合伙人审核失败', AdminLog::TYPE_AGENT, $id, array('type' => "update", 'id' => $id));
        }
        $this->ajax->outRight("");
    }

    //基础设置
    public function settingAction()
    {
        /**
         *   has_code_money: has_code_money,
         * no_code_money: no_code_money,
         * base_money: base_money,
         * platform_money: platform_money,
         * reward: reward
         */
        $has_code_money = $this->request->getPost("has_code_money", 'float', 0);//有邀请码需要总金额
        $no_code_money = $this->request->getPost("no_code_money", 'float', 0);//没有邀请码需要总金额
        $second_base_money = $this->request->getPost("second_base_money", 'float', 0);//二级邀请人分成金额
        $third_base_money = $this->request->getPost("third_base_money", 'float', 0);//三级邀请人分成金额

        $base_money = $this->request->getPost("base_money", 'float', 0);//一级邀请人分成金额

        $platform_money = $this->request->getPost("platform_money", 'float', 0);//平台提成
        $reward = $this->request->getPost("reward");//奖励

        $price = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
        $price['agent'] = [
            "has_code" => $has_code_money * 100,
            'no_code' => $no_code_money * 100,
            'platform' => $platform_money * 100,
            'base' => $base_money * 100,
            'second_base' => $second_base_money * 100,
            'third_base' => $third_base_money * 100,
            'limit' => $reward ? $reward : [],
        ];
        $price['agent']['reward_radices'] = $price['agent']['has_code'] - $price['agent']['base'] - $price['agent']['platform'] - $price['agent']['second_base'] - $price['agent']['third_base'];
        SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", ['val' => json_encode($price)]);
        SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", json_encode($price));

        $this->ajax->outRight("");
    }

}