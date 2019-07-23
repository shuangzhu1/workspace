<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/9
 * Time: 19:46
 */

namespace Models\User;


use Models\BaseModel;

class UserInfo extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}