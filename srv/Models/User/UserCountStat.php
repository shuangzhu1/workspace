<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/4
 * Time: 14:24
 */

namespace Models\User;


use Models\BaseModel;

class UserCountStat extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}