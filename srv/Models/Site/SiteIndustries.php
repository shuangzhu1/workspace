<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 9:28
 */

namespace Models\Site;


use Models\BaseModel;

class SiteIndustries extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}