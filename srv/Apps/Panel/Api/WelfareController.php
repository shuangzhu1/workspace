<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/6
 * Time: 15:38
 */

namespace Multiple\Panel\Api;


use Services\Site\SiteKeyValManager;

class WelfareController extends ApiBase
{
    public function settingAction()
    {
        $point = $this->request->getPost("point", 'int', 0);//邀请一个用户所得爱心值
        $register_point = $this->request->getPost("register_point", 'int', 0);//被邀请人获取爱心值
        $rate = $this->request->getPost("rate", 'int', 0);//多少爱心值对应一块钱
        $setting = [
            "point" => $point,
            'register_point' => $register_point,
            'rate' => $rate,
        ];
        SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "welfare_setting", ['val' => json_encode($setting)]);
        SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "welfare_setting", json_encode($setting));

        $this->ajax->outRight("");
    }
}