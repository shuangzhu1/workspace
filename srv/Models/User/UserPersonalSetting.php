<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/22
 * Time: 10:58
 */

namespace Models\User;


use Models\BaseModel;

class UserPersonalSetting extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}