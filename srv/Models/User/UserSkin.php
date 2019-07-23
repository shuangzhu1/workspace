<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/29
 * Time: 11:36
 */

namespace Models\User;


use Models\BaseModel;

class UserSkin extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}