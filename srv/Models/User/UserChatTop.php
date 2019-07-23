<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/10
 * Time: 10:33
 */

namespace Models\User;


use Models\BaseModel;

class UserChatTop extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}