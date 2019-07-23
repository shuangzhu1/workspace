<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/29
 * Time: 11:37
 */

namespace Models\Site;


use Models\BaseModel;

class SiteSkins extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}