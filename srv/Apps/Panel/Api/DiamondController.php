<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/11
 * Time: 13:31
 */

namespace Multiple\Panel\Api;


use Services\Admin\AdminLog;
use Services\Site\SiteKeyValManager;
use Util\Ajax;

class DiamondController extends ApiBase
{
    //增加龙豆规则
    public function addWechatChargeRuleAction()
    {
        $coin = $this->request->getPost("coin", 'int', 0);
        $money = $this->request->getPost("money", 'float', 0);
        $donate = $this->request->getPost("donate", 'int', 0);
        $rule = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules");
        if (!$coin || !$money || $coin < 0 || $money < 0 || $donate < 0) {
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        }
        if ($rule) {
            $rule = json_decode($rule, true);
            $money_arr = array_column($rule, 'money');
            if (in_array($money, $money_arr)) {
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, "该条规则已经添加过,请勿重复添加");
            }

            $rule[$money] = ["coin" => $coin, 'money' => $money, 'donate' => $donate];
            ksort($rule);
            SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules", ['val' => json_encode($rule)]);
            //更新缓存
            SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules", json_encode($rule));
            AdminLog::init()->add('更新龙钻微信充值规则', AdminLog::TYPE_DIAMOND, 0, array('type' => "update", 'id' => 0, 'data' => $rule));


        } else {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, "数据不存在");
        }
        Ajax::init()->outRight();
    }

    //编辑规则
    public function saveWechatChargeRuleAction()
    {
        $data = $this->request->getPost('data');
        if (!($data && is_array($data))) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }
        $rule = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules");
        if (!$rule) {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, "请先添加规则");
        }
        $rule = json_decode($rule, true);
        foreach ($data as $row) {
            if (!$row['coin'] || !$row['money']) {
                continue;
            }
            unset($rule[$row['id']]);
            $rule[$row['money']] = [
                "coin" => $row['coin'], 'money' => $row['money'], 'donate' => $row['donate']
            ];
            ksort($rule);
        }
        SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules", ['val' => json_encode($rule)]);
        //更新缓存
        SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules", json_encode($rule));

        AdminLog::init()->add('更新龙钻微信充值规则', AdminLog::TYPE_DIAMOND, 0, array('type' => "update", 'id' => 0, 'data' => $rule));

        Ajax::init()->outRight();
    }

    //删除规则
    public function delWechatChargeRuleAction()
    {
        $id = $this->request->getPost("id", 'int', 0);
        $rule = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules");
        if (!$rule) {
            Ajax::init()->outRight();
        }
        if (!$rule) {
            Ajax::init()->outRight();
        }
        $rule = json_decode($rule, true);
        if (isset($rule[$id])) {
            unset($rule[$id]);
            SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules", ['val' => json_encode($rule)]);
            //更新缓存
            SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules", json_encode($rule));
            AdminLog::init()->add('更新龙钻微信充值规则', AdminLog::TYPE_DIAMOND, 0, array('type' => "update", 'id' => 0, 'data' => $rule));

        }
        Ajax::init()->outRight();
    }
}