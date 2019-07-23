<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/12
 * Time: 17:05
 */

namespace Models\Site;


use Models\BaseModel;

class SiteAdsApplication extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}