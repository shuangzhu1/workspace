<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/12
 * Time: 16:05
 */

namespace Multiple\Panel\Controllers;


use Models\Site\SiteAds;
use Models\Site\SiteAdsApplication;
use Services\Site\AdvertiseManager;
use Services\Site\CacheSetting;
use Util\Pagination;

class AdsController extends ControllerBase
{
    //广告列表
    public function listAction()#广告列表#
    {
        $type = $this->request->get("type", 'string', 'app');
        $key = $this->request->get('key','string','find_top'); //广告关键key

        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);

        if (!$type) {
            $where = 'platform="app"';
        } else {
            $where = 'platform="' . $type . '"';
        }
        $names = SiteAds::getColumn([$where, 'columns' => 'name,ads_key'], "name", "ads_key");
        /*if ($key) {*/
            $count = SiteAdsApplication::dataCount("ads_key='" . $key . "'");
            $res = SiteAdsApplication::findList(["ads_key='" . $key . "'", 'order' => 'sort asc,created desc']);
            /*} else {
                $keys = SiteAds::getColumn([$where], "ads_key");
                foreach ($keys as &$item) {
                    $item = "'" . $item . "'";
                }
                $count = SiteAdsApplication::dataCount('ads_key in(' . implode(',', $keys) . ')');
                $res = SiteAdsApplication::findList(['ads_key in(' . implode(',', $keys) . ')', 'order' => 'sort asc,created desc']);
            }*/
        if ($res) {
            foreach ($res as &$item) {
                $item['content'] = json_decode($item['content'], true);
                $item['name'] = $names[$item['ads_key']];
            }
        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('ads_keys', $names);
        $this->view->setVar('list', $res);
        $this->view->setVar('type', $type);
        $this->view->setVar('key', $key);
    }

    //广告位
    public function posAction()#广告位#
    {
        $type = $this->request->get("type", 'string', 'app');
        $key = $this->request->get('key'); //广告位关键key

        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);

        $res = AdvertiseManager::init()->getAllAdsPosition($type);
        $this->view->setVar('list', $res);

    }

    //首页弹窗广告
    public function popupAdListAction()#首页弹窗广告#
    {

        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',20);
        $name = $this->request->get('name');
        $start = $this->request->get('start');
        $end = $this->request->get('end');
        $where = ['enable = 1'];
        !empty($name) && $where[] = "name like '%$name%'";
        !empty($start) && $where[] = "period_start >= " . strtotime($start);
        !empty($end) && $where[] = "period_end <= " . (strtotime($end) + 86399);
        $where = 'where ' . implode(' and ',$where);
        $count = $this->original_mysql->query('select count(1) as sum from site_new_year_ad ' . $where)->fetch(\PDO::FETCH_ASSOC)['sum'];
        $list = $this->original_mysql->query("select * from site_new_year_ad $where order by created desc limit " . $limit*($p -1) . ",$limit" )->fetchAll(\PDO::FETCH_ASSOC);
        $this->view->setVar('list',$list);
        $this->view->setVar('name',$name);
        $this->view->setVar('start',$start);
        $this->view->setVar('end',$end);
        $this->view->pick('ads/popupAdList');
        Pagination::instance($this->view)->showPage($p,$count,$limit);
        //广告弹出间隔
        $redis = $this->di->get('redis');
        $interval = $redis->originalGet(CacheSetting::KEY_THE_INTERVAL_APPEAR_NEW_YEAR_AD) ?: 7200;//默认2小时
        $this->view->setVar('interval',$interval);

    }
}