<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/5
 * Time: 11:18
 */

namespace Models\User;


use Models\BaseModel;

class UserWelfare extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}