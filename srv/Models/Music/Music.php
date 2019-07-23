<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/25
 * Time: 15:37
 */

namespace Models\Music;


use Models\BaseModel;

class Music extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}