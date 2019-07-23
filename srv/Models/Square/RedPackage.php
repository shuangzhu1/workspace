<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/27
 * Time: 12:01
 */
namespace Models\Square;

use Models\BaseModel;

class RedPackage extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}