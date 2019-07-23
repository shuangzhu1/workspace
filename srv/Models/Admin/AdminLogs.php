<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/23
 * Time: 20:00
 */

namespace Models\Admin;


use Models\BaseModel;

class AdminLogs extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}