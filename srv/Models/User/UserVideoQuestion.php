<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/6
 * Time: 10:26
 */

namespace Models\User;


use Models\BaseModel;

class UserVideoQuestion extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}