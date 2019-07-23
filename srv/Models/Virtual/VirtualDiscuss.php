<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/9
 * Time: 15:13
 */

namespace Models\Virtual;


use Models\BaseModel;

class VirtualDiscuss extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}