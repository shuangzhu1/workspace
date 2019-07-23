<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/27
 * Time: 17:53
 */

namespace Models\Agent;


use Models\BaseModel;

class AgentIncome extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}