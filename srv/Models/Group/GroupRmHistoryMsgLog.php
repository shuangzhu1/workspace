<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/2
 * Time: 13:40
 */

namespace Models\Group;


use Models\BaseModel;

class GroupRmHistoryMsgLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService('original_mysql');
    }
}