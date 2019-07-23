<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/12
 * Time: 16:55
 */

namespace Services\Site;


use Models\Site\AreaCity;
use Models\Site\AreaCounty;
use Models\Site\AreaProvince;
use Phalcon\Mvc\User\Plugin;

class AreaManager extends Plugin
{
    /**
     * @var AreaManager
     */

    private static $instance = null;


    const DEFAULT_LOCATION_AREA_ID = 0;
    const DEFAULT_LOCATION_AREA_NAME = "全国";


    /**
     * @return AreaManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;

    }

    /**
     * 获取省份列表
     * @param bool $refresh
     * @return array
     */
    public function getProvinces($refresh = false)
    {
        $cacheSetting = new CacheSetting();
        $data = $cacheSetting->get(CacheSetting::PREFIX_PROVINCE_LIST); /*缓存数据读取*/
        if (!$data || $refresh) {
            $data = AreaProvince::getByColumnKeyList(['columns' => 'id,name,short_name'], 'id');
            $cacheSetting->set(CacheSetting::PREFIX_PROVINCE_LIST, '', $data);
        }
        return $data;
    }

    /**获取区域列表
     * @param $city_id
     * @param $refresh
     * @return array
     */
    public function getCounties($city_id, $refresh = false)
    {
        $cacheSetting = new CacheSetting();
        $data = $cacheSetting->get(CacheSetting::PREFIX_CITY_LIST, $city_id); /*缓存数据读取*/
        if (!$data || $refresh) {
            $where = "1=1";
            if ($city_id > 0) {
                $where .= " and city_id = {$city_id}";
            }
            $data = AreaCounty::getByColumnKeyList([$where, 'columns' => 'city_id,id,name,short_name'], 'id');
            $cacheSetting->set(CacheSetting::PREFIX_COUNTY_LIST, $city_id, $data);
        }
        return $data;
    }

    /**
     * 获取随机省份
     * @return array
     */
    public static function getRandProvince()
    {
        $provinces = array_values(self::getProvinces());
        return $provinces[rand(0, count($provinces))];
    }

    /**
     * 获取随机城市
     * @param  $province_id
     * @return array
     */
    public static function getRandCity($province_id)
    {
        $cities = array_values(self::getCities($province_id));
        return $cities[rand(0, count($cities))];
    }

    /**获取省份详情
     * @param $province_id
     * @param string $field
     * @param bool $refresh
     * @return array|bool|static
     */
    public static function getProvince($province_id, $field = "", $refresh = false)
    {
        if (empty($province_id) || !is_numeric($province_id)) {
            return false;
        }
        $provinces = self::getProvinces($refresh);
        $provinces = $provinces[$province_id];
        return $field ? $provinces[$field] : $provinces;
    }

    /**获取城市列表
     * @param int $province
     * @param bool $refresh
     * @return array|\Phalcon\Mvc\ResultsetInterface
     */
    public function getCities($province = 0, $refresh = false)
    {
        $cacheSetting = new CacheSetting();
        $data = $cacheSetting->get(CacheSetting::PREFIX_CITY_LIST, $province); /*缓存数据读取*/
        if (!$data || $refresh) {
            $where = "is_active=1";
            if ($province > 0) {
                $where .= " and province_id = {$province}";
            }
            $data = AreaCity::getByColumnKeyList([$where, 'columns' => 'province_id,id,name,short_name,abbr'], 'id');
            $cacheSetting->set(CacheSetting::PREFIX_CITY_LIST, $province, $data);
        }
        return $data;
    }

    /**
     * 获取城市详情
     * @param $city_id
     * @param $field
     * @param $refresh
     * @return array|bool
     */
    public static function getCity($city_id, $field = '', $refresh = false)
    {
        if (empty($city_id) || !is_numeric($city_id)) {
            return false;
        }
        $cities = self::getCities($refresh);
        $cities = $cities[$city_id];
        return $field ? $cities[$field] : $cities;
    }

    /**
     * 通过城市获取城市信息 如南昌
     * @param $city_name
     * @param $field
     * @param $refresh
     * @return array|bool
     */
    public static function getCityByName($city_name, $field = '', $refresh = false)
    {
        if ($city_name == '') {
            return false;
        }
        $cacheSetting = new CacheSetting();
        $city = $cacheSetting->get(CacheSetting::PREFIX_CITY_DETAIL, $city_name); /*缓存数据读取*/
        if (!$city || $refresh) {
            $city = AreaCity::findOne("short_name = '" . $city_name . "' or name ='".$city_name."'");
            if ($city) {
                $cacheSetting->set(CacheSetting::PREFIX_CITY_DETAIL, $city_name, $city);
            } else {
                $city = [];
            }
        }
        if ($city) {
            return $field ? @$city[$field] : $city;
        } else {
            return false;
        }
    }

    /** 通过省份名获取省信息 如江西
     * @param $province_name
     * @param $field
     * @param $refresh
     * @return array|bool
     */
    public static function getProvinceByName($province_name, $field = '', $refresh = false)
    {
        if ($province_name == '') {
            return false;
        }
        $cacheSetting = new CacheSetting();
        $province = $cacheSetting->get(CacheSetting::PREFIX_PROVINCE_DETAIL, $province_name); /*缓存数据读取*/
        if (!$province || $refresh) {
            $province = AreaProvince::findOne("short_name = '" . $province_name . "'");
            if ($province) {
                $cacheSetting->set(CacheSetting::PREFIX_PROVINCE_DETAIL, $province_name, $province);
            } else {
                $province = [];
            }
        }
        if ($province) {
            return $field ? @$province[$field] : $province;
        } else {
            return false;
        }
    }

}