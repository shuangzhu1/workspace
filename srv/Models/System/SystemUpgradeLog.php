<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 10:45
 */

namespace Models\System;


use Models\BaseModel;

class SystemUpgradeLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}