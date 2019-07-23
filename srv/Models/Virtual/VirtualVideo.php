<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/2
 * Time: 18:57
 */

namespace Models\Virtual;


use Models\BaseModel;

class VirtualVideo extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}