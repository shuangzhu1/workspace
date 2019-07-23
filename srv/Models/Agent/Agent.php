<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/26
 * Time: 10:11
 */

namespace Models\Agent;


use Models\BaseModel;

class Agent extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}