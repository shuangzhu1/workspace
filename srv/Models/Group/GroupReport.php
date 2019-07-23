<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/19
 * Time: 13:58
 */

namespace Models\Group;


use Models\BaseModel;

class GroupReport extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}