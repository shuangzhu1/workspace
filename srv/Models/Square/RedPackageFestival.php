<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/9
 * Time: 10:08
 */

namespace Models\Square;


use Models\BaseModel;

class RedPackageFestival extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}