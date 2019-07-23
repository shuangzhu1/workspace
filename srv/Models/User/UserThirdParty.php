<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/2
 * Time: 17:29
 */

namespace Models\User;


use Models\BaseModel;

class UserThirdParty extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}