<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/3
 * Time: 17:39
 */

namespace Models\Agent;


use Models\BaseModel;

class AgentIncomeTransferLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}