<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/12
 * Time: 10:42
 */

namespace Models\User;


use Models\BaseModel;

class UserShow extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}