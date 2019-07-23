<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/8
 * Time: 14:02
 */
namespace Models\Vip;
use Models\BaseModel;

class VipOrder extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}