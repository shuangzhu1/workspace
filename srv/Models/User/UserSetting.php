<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/4
 * Time: 14:17
 */

namespace Models\User;


use Models\BaseModel;

class UserSetting extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}