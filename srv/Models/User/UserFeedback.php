<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/6
 * Time: 16:58
 */

namespace Models\User;


use Models\BaseModel;

class UserFeedback extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}