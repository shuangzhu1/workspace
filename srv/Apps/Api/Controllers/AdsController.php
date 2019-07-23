<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/13
 * Time: 15:57
 */

namespace Multiple\Api\Controllers;


use Models\Square\RedPackage;
use Models\User\UserInfo;
use Services\Site\AdvertiseManager;
use Services\Site\CacheSetting;
use Services\User\SquareManager;
use Util\Ajax;
use Util\Probability;
use Util\Time;

class AdsController extends ControllerBase
{
    /*获取广告列表*/
    public function listAction()
    {
        $ads_key = $this->request->get('ads_key', 'string', '');
        if (!$ads_key) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = AdvertiseManager::init()->getAdList($ads_key, true);
        if ($res['data_list']) {
            //引导页广告
            if ($res['data_list'] && $ads_key == AdvertiseManager::ADS_TYPE_START_GUIDE) {
                $sort_arr = array_column($res['data_list'], 'sort');
                $mt = Probability::get_rand($sort_arr);
                $content = json_decode($res['data_list'][$mt]['content'], true);
                $res['data_list'] = [['image' => $content['img'], 'content_type' => $res['data_list'][$mt]['content_type'], 'title' => $content['title'], 'value' => $content['value']]];
                $res['data_count'] = 1;
            } else {
                foreach ($res['data_list'] as &$item) {
                    $content = json_decode($item['content'], true);
                    $item = ['image' => $content['img'], 'content_type' => $item['content_type'], 'title' => $content['title'], 'value' => $content['value']];
                }
            }
        }
        $this->ajax->outRight($res);
    }

//    //动态-红包广告
//    public function packageAction()
//    {
//        $data = ['data_list' => []];
//        $uid = $this->uid;
//        $limit = $this->request->get("limit", 'int', 20);
//        $where =/* "deadline>=" . (time() - 86400 * 3) . */
//            " type=" . SquareManager::TYPE_GOODS;//红包没有过期 并且是商品红包
//
//        $list = RedPackage::findList([$where, 'limit' => $limit, 'columns' => 'user_id as uid,package_id,deadline,type,package_id,type,item_id,extra', 'order' => 'rand() desc']);
//        if ($list) {
//            $uids = array_unique(array_column($list, 'uid'));
//            $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id as uid,username,sex,avatar,is_auth'], 'uid');//用户信息集合
//
//            foreach ($list as $item) {
//                $tmp = [
//                    'uid' => $item['uid'],
//                    'user_info' => $user_info[$item['uid']],
//                    'type' => $item['type'],
//                    'item_id' => $item['item_id'],
//                    'package_id' => $item['package_id'],
//                    'extra' => json_decode($item['extra'])
//                ];
//                $data['data_list'][] = $tmp;
//            }
//        }
//        $this->ajax->outRight($data);
//
//    }

    public function getTheNewYearAdAction()
    {
        $uid = $this->request->get('uid', 'int', 0);
        if (!$uid)
            $this->ajax->outError(Ajax::INVALID_PARAM);
        $redis = $this->di->get('redis');
        $appear_ad_interval = ($redis->originalGet(CacheSetting::KEY_THE_INTERVAL_APPEAR_NEW_YEAR_AD)) ?: 7200;//默认2小时 单位：秒
        if (!$last_ad_time = $redis->hGet(CacheSetting::KEY_THE_TIME_GET_NEW_YEAR_AD, $uid)) {
            $current_time = time();
            $redis->hSet(CacheSetting::KEY_THE_TIME_GET_NEW_YEAR_AD, $uid, $current_time);
            $last_ad_time = $current_time - $appear_ad_interval;
        }


        $current_time = time();
        $ads = [];
        if ($current_time - $last_ad_time >= $appear_ad_interval)//距离上次拉广告间隔大于$appear_ad_interval
        {
            $ads = $this->original_mysql->query('select * from site_new_year_ad where enable = 1 and period_start <= ' . $current_time . ' and period_end >= ' . $current_time . ' order by created desc limit 1')->fetch(\PDO::FETCH_ASSOC);
        }
        if (!empty($ads)) {
            $redis->hSet(CacheSetting::KEY_THE_TIME_GET_NEW_YEAR_AD, $uid, time());
            $this->ajax->outRight(['cover' => $ads['cover'], 'content' => $ads['content_img'], 'btn_name' => $ads['btn_name']]);
        } else {
            $this->ajax->outRight((object)[]);
        }
    }
}