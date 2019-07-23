<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/5
 * Time: 15:34
 */

namespace Models\User;


use Models\BaseModel;

class UserWelfareLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}