<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/15
 * Time: 9:29
 */

namespace Models\User;


use Models\BaseModel;

class UserDragonCoin extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}