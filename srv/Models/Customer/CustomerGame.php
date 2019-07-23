<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/6
 * Time: 10:48
 */

namespace Models\Customer;


use Models\BaseModel;

class CustomerGame extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}