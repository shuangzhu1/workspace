<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/8
 * Time: 9:30
 */

namespace Models\User;


use Models\BaseModel;

class UserBlacklist extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}