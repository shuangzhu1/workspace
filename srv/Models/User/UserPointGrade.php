<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/5
 * Time: 18:31
 */

namespace Models\User;


use Models\BaseModel;

class UserPointGrade extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}