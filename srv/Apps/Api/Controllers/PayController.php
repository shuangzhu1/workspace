<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/26
 * Time: 11:33
 */

namespace Multiple\Api\Controllers;


use Services\Site\SiteKeyValManager;
use Util\Ajax;

class PayController extends ControllerBase
{
    private static $type = [
        'agent',//合伙人
        'shop'//开店
    ];
    // private static $white_ip = ["112.74.15.30", "119.23.141.70", "120.76.47.205", "119.23.54.215", "120.78.182.253","127.0.0.1"];

    //支付完成
    public function payAction()
    {
//        $ip = $this->request->getClientAddress();
//        if (!in_array($ip, self::$white_ip)) {
//            $this->ajax->outError(Ajax::INVALID_REQUEST);
//        }
        $type = $this->request->get("type", 'string', '');//agent-成为合伙人 shop-开店
        if (!in_array($type, self::$type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "类型不存在");
        }
        switch ($type) {
            case "agent":
                $this->dispatcher->forward([
                        'controller' => 'agent',
                        'action' => 'paySuccess'
                    ]
                );
                break;
            case "shop":
                $this->dispatcher->forward([
                        'controller' => 'shop',
                        'action' => 'paySuccess'
                    ]
                );
        }
    }

    //获取价格配置
    public function priceAction()
    {
        $uid = $this->uid;
        $price = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
        $type = $this->request->get("type", 'string', '');//agent-成为合伙人 shop-开店
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!in_array($type, self::$type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "类型不存在");
        }
        //合伙人
        if ($type == 'agent') {
            $res = [
                'has_code' => ['money' => intval($price[$type]['has_code']), 'favorable_money' => intval($price[$type]['no_code'] - $price[$type]['has_code'])],
                'no_code' => ['money' => intval($price[$type]['no_code']), 'favorable_money' => 0],
                'money' => intval($price[$type]['no_code']),
                'favorable_money' => 0,
            ];
            //  $res = ['money' => intval($price[$type]['money']), 'favorable_money' => isset($price[$type]['favorable_money']) ? intval($price[$type]['favorable_money']) : 0];
        } else {
            $res = [
                'has_code' => ['money' => intval($price[$type]['has_code']), 'favorable_money' => intval($price[$type]['no_code'] - $price[$type]['has_code'])],
                'no_code' => ['money' => intval($price[$type]['no_code']), 'favorable_money' => 0],
            ];
        }
        $this->ajax->outRight($res);
    }
}