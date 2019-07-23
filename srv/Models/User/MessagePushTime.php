<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/22
 * Time: 9:35
 */

namespace Models\User;


use Models\BaseModel;

class MessagePushTime extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}