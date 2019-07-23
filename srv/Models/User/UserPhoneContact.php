<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/9
 * Time: 9:45
 */

namespace Models\User;


use Models\BaseModel;

class UserPhoneContact extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}