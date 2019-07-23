<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/14
 * Time: 17:08
 */

namespace Models\Site;


use Models\BaseModel;

class SiteKeyVal extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}