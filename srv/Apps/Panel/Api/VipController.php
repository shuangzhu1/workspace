<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/10
 * Time: 16:22
 */

namespace Multiple\Panel\Api;


use Services\Site\SiteKeyValManager;
use Util\Ajax;

class VipController extends ApiBase
{
    public function settingAction()
    {
        $data = $this->request->get("data");
        if (!$data) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $normal_setting = [];
        if (!empty($data['normal_package_pick_count'])) {
            $package_setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "square_package_setting");
            $package_setting = json_decode($package_setting, true);
            $package_setting['day_pick_limit'] = intval($data['normal_package_pick_count']);
            $normal_setting["package_pick_count"] = ($data['normal_package_pick_count']);
            $package_setting = json_encode($package_setting);
            if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting', ['val' => $package_setting])) {
                SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting', ($package_setting));
            }
        }

        if (!empty($data['normal_add_group_count'])) {
            $normal_setting["add_group_count"] = ($data['normal_add_group_count']);
        }
        if (!empty($data['normal_group_member_count'])) {
            $normal_setting["group_member_count"] = ($data['normal_group_member_count']);
        }
        if (!empty($data['normal_shop_visitor'])) {
            $normal_setting["shop_visitor"] = ($data['normal_shop_visitor']);
        }
        if (!empty($data['normal_user_visitor'])) {
            $normal_setting["user_visitor"] = ($data['normal_user_visitor']);
        }

        if ($normal_setting) {
            $normal_privilege = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
            $normal_privilege = json_decode($normal_privilege, true);
            $normal_privilege = array_merge($normal_privilege, $normal_setting);
            $normal_privilege = json_encode($normal_privilege);
            if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'normal_privilege', ['val' => $normal_privilege])) {
                SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'normal_privilege', ($normal_privilege));
            }
        }
        $vip_setting = [];
        if (!empty($data['vip_package_pick_count'])) {
            $vip_setting["package_pick_count"] = ($data['vip_package_pick_count']);
        }
        if (!empty($data['vip_add_group_count'])) {
            $vip_setting["add_group_count"] = ($data['vip_add_group_count']);
        }
        if (!empty($data['vip_group_member_count'])) {
            $vip_setting["group_member_count"] = ($data['vip_group_member_count']);
        }
        if (!empty($data['vip_shop_visitor'])) {
            $vip_setting["shop_visitor"] = ($data['vip_shop_visitor']);
        }
        if (!empty($data['vip_user_visitor'])) {
            $vip_setting["user_visitor"] = ($data['vip_user_visitor']);
        }

        $price_detail = [];
        if (!empty($data['price1'])) {
            $price_detail["1"] = ['money' => (string)($data['price1'] * 100), 'diamond' => $data['diamond1'], 'month' => "1", "appStoreID" => $data['appStoreID1']];
        }
        if (!empty($data['price2'])) {
            $price_detail["3"] = ['money' => (string)($data['price2'] * 100), 'diamond' => $data['diamond2'], 'month' => "3", "appStoreID" => $data['appStoreID2']];
        }
        if (!empty($data['price3'])) {
            $price_detail["6"] = ['money' => (string)($data['price3'] * 100), 'diamond' => $data['diamond3'], 'month' => "6", "appStoreID" => $data['appStoreID3']];
        }


        if ($vip_setting || $price_detail) {
            $vip_privilege = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "vip_privilege");
            $vip_privilege = json_decode($vip_privilege, true);
            $vip_privilege = array_merge($vip_privilege, $vip_setting);
            if ($price_detail) {
                $best_key = 0; #最优惠的金额下标#
                $best_rate = 0; #最优惠的比例#
                foreach ($vip_privilege['price_detail'] as $k => $p) {
                    $vip_privilege['price_detail'][$k] = $price_detail[$k];
                    $vip_privilege['price_detail'][$k]['is_best'] = "0";
                    if ($best_key == 0) {
                        $best_key = $k;
                        $best_rate = $vip_privilege['price_detail'][$k]['month'] / $vip_privilege['price_detail'][$k]['money'];
                    } else {
                        $rate = $vip_privilege['price_detail'][$k]['month'] / $vip_privilege['price_detail'][$k]['money'];
                        if ($rate > $best_rate) {
                            $best_key = $k;
                            $best_rate = $rate;
                        }
                    }
                }
                $vip_privilege['price_detail'][$best_key]['is_best'] = "1";
            }
            $vip_privilege = json_encode($vip_privilege);
            if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'vip_privilege', ['val' => $vip_privilege])) {
                SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'vip_privilege', ($vip_privilege));
            }
        }

        Ajax::outRight("编辑成功");

    }


}