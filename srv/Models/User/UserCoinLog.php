<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/5
 * Time: 11:38
 */

namespace Models\User;


use Models\BaseModel;

class UserCoinLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}