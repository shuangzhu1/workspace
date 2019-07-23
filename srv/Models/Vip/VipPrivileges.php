<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/9
 * Time: 16:08
 */

namespace Models\Vip;


use Models\BaseModel;

class VipPrivileges extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}