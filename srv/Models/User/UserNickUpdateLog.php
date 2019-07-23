<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/24
 * Time: 11:05
 */

namespace Models\User;


use Models\BaseModel;

class UserNickUpdateLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}