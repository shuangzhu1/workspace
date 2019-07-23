<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/5
 * Time: 14:36
 */

namespace Models\User;


use Models\BaseModel;

class UserLocation extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}