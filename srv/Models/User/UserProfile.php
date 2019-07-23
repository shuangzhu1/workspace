<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/2
 * Time: 18:29
 */

namespace Models\User;


use Models\BaseModel;

class UserProfile extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}