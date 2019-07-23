<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/21
 * Time: 15:36
 */

namespace Models\User;


use Models\BaseModel;

class UserTags extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}