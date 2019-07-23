<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/14
 * Time: 11:48
 */

namespace Multiple\Panel\Api;


use Services\Site\AreaManager;
use Util\Ajax;

class AreaController extends ApiBase
{
    /*获取市区列表*/
    public function getCitiesAction()
    {
        $province_id = $this->request->get('province_id', 'int', 0); //省份
        $cities = AreaManager::getInstance()->getCities($province_id);
        $this->ajax->outRight($cities);
    }

    /*获取中心大厦区域列表*/
    public function getCountiesAction()
    {
        $city_id = $this->request->get('city_id', 'int', 0); //省份
        $counties = AreaManager::getInstance()->getCounties($city_id);
        $this->ajax->outRight($counties);
        //$district_list = AreaManager::getInstance()->($city_id);
        // $this->ajax->outRight($district_list);
    }
}