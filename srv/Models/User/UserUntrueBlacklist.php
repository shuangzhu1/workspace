<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/20
 * Time: 9:56
 */

namespace Models\User;


use Models\BaseModel;

class UserUntrueBlacklist extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}