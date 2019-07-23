<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/5
 * Time: 11:19
 */

namespace Models\User;


use Models\BaseModel;

class UserInviter extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}