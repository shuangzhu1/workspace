<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/19
 * Time: 18:28
 */

namespace Models\System;


use Models\BaseModel;

class SystemMessage extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}