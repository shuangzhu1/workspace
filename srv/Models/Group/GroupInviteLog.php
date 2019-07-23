<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/2
 * Time: 16:29
 */

namespace Models\Group;


use Models\BaseModel;

class GroupInviteLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}