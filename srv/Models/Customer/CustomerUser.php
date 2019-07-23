<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/6
 * Time: 10:49
 */

namespace Models\Customer;


use Models\BaseModel;

class CustomerUser extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}