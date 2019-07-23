<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/23
 * Time: 14:17
 */

namespace Models\System;


use Models\BaseModel;

class SystemImageCheck extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}