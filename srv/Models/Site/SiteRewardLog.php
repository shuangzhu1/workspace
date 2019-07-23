<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/1
 * Time: 10:53
 */

namespace Models\Site;


use Models\BaseModel;

class SiteRewardLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}