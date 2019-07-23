<?php
/**
 *
 * 后台系统消息推送
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/24
 * Time: 14:34
 */

namespace Models\System;


use Models\BaseModel;

class SystemMessagePush extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}