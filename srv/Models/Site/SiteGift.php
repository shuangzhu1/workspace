<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/26
 * Time: 11:41
 */

namespace Models\Site;


use Models\BaseModel;

class SiteGift extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}