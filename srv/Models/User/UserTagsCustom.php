<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/30
 * Time: 9:21
 */

namespace Models\User;


use Models\BaseModel;

class UserTagsCustom extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}