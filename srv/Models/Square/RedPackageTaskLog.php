<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/28
 * Time: 16:06
 */

namespace Models\Square;


use Models\BaseModel;

class RedPackageTaskLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}