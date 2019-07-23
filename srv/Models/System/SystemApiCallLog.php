<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/11
 * Time: 11:47
 */

namespace Models\System;


use Models\BaseModel;

class SystemApiCallLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}