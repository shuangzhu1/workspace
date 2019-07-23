<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/16
 * Time: 15:28
 */
namespace Models\Orders;

use Models\BaseModel;

class Order extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}