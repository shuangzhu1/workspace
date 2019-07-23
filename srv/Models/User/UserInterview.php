<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/27
 * Time: 15:17
 */

namespace Models\User;


use Models\BaseModel;

class UserInterview extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}