<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 11:20
 */

namespace Models\User;


use Models\BaseModel;

class UserAuthApply extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}