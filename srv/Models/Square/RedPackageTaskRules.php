<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/6
 * Time: 9:34
 */

namespace Models\Square;


use Models\BaseModel;

class RedPackageTaskRules extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}