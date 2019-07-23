<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/15
 * Time: 11:49
 */

namespace Models\User;


use Models\BaseModel;

class UserDragonCoinLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}