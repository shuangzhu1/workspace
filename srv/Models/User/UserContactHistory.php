<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/24
 * Time: 14:00
 */

namespace Models\User;


use Models\BaseModel;

class UserContactHistory extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}