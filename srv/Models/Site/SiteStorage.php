<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/28
 * Time: 15:39
 */

namespace Models\Site;


use Models\BaseModel;

class SiteStorage extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}