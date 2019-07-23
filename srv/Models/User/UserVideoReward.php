<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/23
 * Time: 9:05
 */

namespace Models\User;


use Models\BaseModel;

class UserVideoReward extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}