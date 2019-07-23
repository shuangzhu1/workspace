<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/2
 * Time: 15:20
 */
namespace Models\Other;
use Models\BaseModel;

class OutsideUrlVisit extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}