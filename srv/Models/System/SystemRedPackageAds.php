<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/15
 * Time: 10:05
 */

namespace Models\System;


use Models\BaseModel;

class SystemRedPackageAds extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}