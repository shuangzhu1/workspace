<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/12
 * Time: 17:00
 */

namespace Services\Site;


use Models\Site\SiteAds;
use Models\Site\SiteAdsApplication;
use Phalcon\Mvc\User\Plugin;

class AdvertiseManager extends Plugin
{
    private static $instance = null;

    /**
     * @var int
     */
    private $area = 0;

    const ADS_TYPE_NORMAL = "link"; //普通图文
    const ADS_TYPE_DISCUSS = "discuss";
    const ADS_TYPE_START_GUIDE = "app_start_guide"; //app启动引导


    const ADS_PLATFORM_PC = 'pc';
    const ADS_PLATFORM_APP = 'app';
    const ADS_PLATFORM_WECHAT = 'wechat';

    const ADS_TERM_LAST = 'last';
    const ADS_TERM_CURRENT = 'this';
    const ADS_TERM_NEXT = 'next';

    const ADS_FREQUENCY_DAY = 'day';
    const ADS_FREQUENCY_WEEK = 'week';
    const ADS_FREQUENCY_MONTH = 'month';
    const ADS_FREQUENCY_SEASON = 'season'; //一季度
    const ADS_FREQUENCY_HALF_YEAR = 'half_year'; // 半年
    const ADS_FREQUENCY_YEAR = 'year';
    const ADS_FREQUENCY_FOREVER = 'forever';




    public static $_type_name = array(
        self::ADS_TYPE_NORMAL => "超链接",
        self::ADS_TYPE_DISCUSS => "动态",
        self::ADS_TYPE_START_GUIDE => "app启动引导",
    );
    public static $_platform_name = array(
        self::ADS_PLATFORM_PC => "PC端",
        self::ADS_PLATFORM_APP => "APP端",
        self::ADS_PLATFORM_WECHAT => "微信平台",
    );
    public static $_frequency_name = array(
        self::ADS_FREQUENCY_DAY => "一天",
        self::ADS_FREQUENCY_WEEK => "一周",
        self::ADS_FREQUENCY_MONTH => "一月",
        self::ADS_FREQUENCY_SEASON => "一季度",
        self::ADS_FREQUENCY_HALF_YEAR => "半年",
        self::ADS_FREQUENCY_YEAR => "一年",
        self::ADS_FREQUENCY_FOREVER => "永不过期",
    );
    private $cache = null;

    private function __construct()
    {
        $this->cache = new CacheSetting('redis');
    }

    public static function init()
    {
        if (!self::$instance instanceof AdvertiseManager) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**获取广告列表
     * @param $keys
     * @param $refresh
     * @return array
     */
    public function getAdsList($keys, $refresh = false)
    {
        $res = [];
        foreach ($keys as &$item) {
            $data = $this->getAdList($item, $refresh);
            $res[$item] = $data;
        }
        return $res;
    }

    //获取广告列表
    public function getAdList($key, $refresh = false)
    {
        $data = $this->cache->get(CacheSetting::PREFIX_ADS_LIST . $key); /*缓存数据读取*/
        if (!$data || $refresh) {
            $res = SiteAdsApplication::findList(['ads_key="' . $key . '" and status=1','order'=>'sort desc,created desc']);
            if ($res) {
                $data['data_list'] = $res;
                $data['data_count'] = count($res);
            } else {
                $data['data_list'] = [];
                $data['data_count'] = 0;
            }
            $this->cache->set(CacheSetting::$setting[CacheSetting::PREFIX_ADS_LIST]['prefix'] . $key, $data, CacheSetting::$setting[CacheSetting::PREFIX_ADS_LIST]['life_time']);
        }
        return $data;
    }


    // 获取所有广告位信息
    public function getAllAdsPosition($platform = null)
    {
        $condition = '';
        if (is_string($platform) && strlen($platform) > 0) {
            $condition .= " platform = '{$platform}'";
        }

        $data = SiteAds::findList($condition);

        foreach ($data as &$_ad) {
            // 本期正在使用
            $now = time();
            $_ad['current'] = SiteAdsApplication::dataCount("ads_key='" . $_ad['ads_key'] . "'");
            // 下期
            $_ad['next'] = SiteAdsApplication::dataCount("ads_key='" . $_ad['ads_key'] . "'");
        }
        return $data;
    }


    // 获取广告位剩余
    public function getRestAdCount($ads_key)
    {

    }

    /**
     * @param int $frequency
     * @param string $term ['last', 'this', 'next']
     * @return array
     */
    public static function getTermTimeRange($frequency = 1, $term = 'this')
    {
        $date = new \DateTime();
        $start_time = "";
        $end_time = "";
        $interval = "";

        $term = in_array($term, [self::ADS_TERM_CURRENT, self::ADS_TERM_NEXT]) ? $term : self::ADS_TERM_CURRENT;

        switch ($frequency) {
            case self::ADS_FREQUENCY_DAY: {
                $start_time = strtotime(date("Y-m-d") . " 00:00:00");
                $end_time = $start_time + 76800;
                break;
            }
            case self::ADS_FREQUENCY_WEEK: {
                $start_key = $term . " week";
                $interval = 3600 * 24 * 7;
                $start_date = date("Y-m-d", strtotime($start_key));
                $start_time = strtotime($start_date . " 00:00:00");
                $end_time = $start_time + $interval;
                break;
            }
            case self::ADS_FREQUENCY_MONTH: {
                $start_key = $term . " month";
                $start_date = $date->modify($start_key)->format("Y-m-01");
                $start_time = strtotime($start_date . " 00:00:00");
                // $end_data=date('Y-m-d', strtotime("$start_date +1 month -1 day"));
                $t = date("t", $start_time);
                $interval = 3600 * 24 * $t;
                $end_time = $start_time + $interval;
                break;
            }
            case self::ADS_FREQUENCY_SEASON: {
                $range = self::getSeasonTimeRange($term);
                list($start_time, $end_time) = array_values($range);
                break;
            }
            case self::ADS_FREQUENCY_HALF_YEAR: {
                $range = self::getHalfYearTimeRange($term);
                list($start_time, $end_time) = array_values($range);
                break;
            }
            case self::ADS_FREQUENCY_YEAR: {
                $start_time = strtotime(date("Y" . "-01-01 00:00:00"));
                $end_time = strtotime(date("Y" . "-12-31 00:00:00"));;
                break;
            }
        }

        return array(
            'start' => $start_time,
            'end' => $end_time
        );
    }

    /**
     * 获取一季度时间
     *
     * @param string $term
     * @return array
     */
    private static function getSeasonTimeRange($term = 'this')
    {
        $time = time();
        if ($term == self::ADS_TERM_LAST) {
            $time = strtotime("-3 month");
        } else if ($term == self::ADS_TERM_NEXT) {
            $time = strtotime('+3 month');
        }
        $m = date('m', $time);
        $y = date('Y', $time);
        if ($m <= 3) {
            $start = strtotime($y . "-01-01 00:00:00");
            $end = strtotime($y . "-03-31 23:59:59");
        } else if ($m <= 6) {
            $start = strtotime($y . "-04-01 00:00:00");
            $end = strtotime($y . "-06-30 23:59:59");
        } else if ($m <= 9) {
            $start = strtotime($y . "-07-01 00:00:00");
            $end = strtotime($y . "-09-30 23:59:59");
        } else {
            $start = strtotime($y . "-10-01 00:00:00");
            $end = strtotime($y . "-12-31 23:59:59");
        }
        return array(
            'start' => $start,
            'end' => $end
        );
    }

    // 获取半年时间
    private static function getHalfYearTimeRange($term = 'this')
    {
        $time = time();
        if ($term == self::ADS_TERM_LAST) {
            $time = strtotime("-3 month");
        } else if ($term == self::ADS_TERM_NEXT) {
            $time = strtotime('+6 month');
        }
        $m = date('m', $time);
        $y = date('Y', $time);
        if ($m <= 6) {
            $start = strtotime($y . "-01-01 00:00:00");
            $end = strtotime($y . "-05-31 23:59:59");
        } else {
            $start = strtotime($y . "-06-01 00:00:00");
            $end = strtotime($y . "-12-31 23:59:59");
        }
        return array(
            'start' => $start,
            'end' => $end
        );
    }

}