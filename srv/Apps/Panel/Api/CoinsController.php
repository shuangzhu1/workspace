<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/16
 * Time: 9:36
 */

namespace Multiple\Panel\Api;


use Services\Site\SiteKeyValManager;
use Util\Ajax;

class CoinsController extends ApiBase
{
    //设置龙豆兑换龙币比例
    public function settingAction()
    {
        $rate = $this->request->get("rate", 'int', 0);
        $change_type = $this->request->get("change_type");
        $diamond_rate = $this->request->get("diamond_rate", 'int', 10);

        if (!$rate || $rate > 100 || $rate < 0) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "比例不正确");
        }
        $res = SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "coin_setting",
            ['name' => '龙币设置', 'val' => json_encode([
                'rate' => $rate,
                'change_type' => $change_type,
                'diamond_rate' => $diamond_rate
            ])]);
        if ($res) {
            SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "coin_setting",
                json_encode([
                    'rate' => $rate,
                    'change_type' => $change_type,
                    'diamond_rate' => $diamond_rate
                ]));
        }
        $this->ajax->outRight('操作成功');
    }
}