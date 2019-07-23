<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/15
 * Time: 11:16
 */

namespace Models\Site;


use Models\BaseModel;

class SiteGame extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}