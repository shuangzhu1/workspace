<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/8
 * Time: 11:50
 */

namespace Multiple\Api\Controllers;


use Models\Vip\VipOrder;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\Vip\VipCore;
use Util\Ajax;

class VipController extends ControllerBase
{
    //获取vip特权信息
    public function privilegesAction()
    {
        $type = $this->request->get("type", 'int', 0);//0-特权信息及我的vip信息 1-仅特权信息 2-仅我的vip信息
        $data = ["vip_config" => (object)[], "normal_config" => (object)[], 'my_info' => (object)[]];
        $uid = $this->uid;

        if ($type == 0 || $type == 1) {
            $vip_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "vip_privilege");
            $normal_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");

            $vip_setting = $vip_setting ? $vip_setting : [];
            $normal_setting = $normal_setting ? $normal_setting : [];

            if (!empty($vip_setting['price_detail'])) {
                $vip_setting['price_detail'] = array_values($vip_setting['price_detail']);
            }
            $data['vip_config'] = $vip_setting;
            $data['normal_config'] = $normal_setting;
        }
        if ($type == 0 || $type == 2) {
            $order = VipOrder::findOne(["user_id=" . $uid, 'order' => 'created desc', 'columns' => 'month,created,privileges,deadline,is_renew,start_day,end_day,money,status']);
            if ($order) {
                $data['my_info'] = [
                    'month' => $order['month'],
                    'money' => $order['money'],
                    'end_day' => $order['end_day'],
                    'status' => $order['status'],
                    'expire' => $order['deadline'] <= time() ? "0" : (string)(ceil(($order['deadline'] - time()) / 86400))
                ];
                if($uid==40013){
                    $data['my_info']['expire']='6';
                }
            }
        }
        $this->ajax->outRight($data);
    }

    //支付/续费
    public function payAction()
    {
        $uid = $this->uid;
        $is_renew = $this->request->get("is_renew", 'int', 0);//是否属于续费
        $month = $this->request->get("month", 'int', 0);//几个月
        if (!$uid || !$month) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $vipCore = VipCore::getInstance();
        $vipCore->setProperty(['uid' => $this->uid, 'month' => $month]);
        if (!$is_renew) {
            $res = ($vipCore->open());
        } else {
            $res = ($vipCore->renew());
        }
        if ($res) {
            $this->ajax->outRight("提交成功", Ajax::SUCCESS_SUBMIT);
        } else {
            if ($code = $vipCore->getCode()) {
                $this->ajax->outError($code);
            }
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, $vipCore->getMsg());
        }
    }

    /**
     *临时统计
     */
    public function statAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("type", 'int', 0);//1- 进入付款页面 2-点击付款 3-购买成功 4-续费成功
        if (in_array($type, [1, 2])) {
            VipCore::getInstance()->setProperty(['uid' => $uid])->setStat($type);
            $this->ajax->outRight("");
        }


        $this->ajax->outError(Ajax::INVALID_PARAM);
    }

}