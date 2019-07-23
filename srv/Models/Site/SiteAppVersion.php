<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/14
 * Time: 11:27
 */

namespace Models\Site;


use Models\BaseModel;

class SiteAppVersion extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}