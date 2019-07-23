<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/12
 * Time: 16:57
 */

namespace Models\Site;


use Models\BaseModel;

class AreaCounty extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}