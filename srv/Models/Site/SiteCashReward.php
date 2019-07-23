<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/5
 * Time: 18:38
 */

namespace Models\Site;


use Models\BaseModel;

class SiteCashReward extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}