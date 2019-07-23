<?php
namespace Models\System;

use Models\BaseModel;

/**
 * Created by PhpStorm.
 * User: ykuang
* Date: 2016/12/1
* Time: 18:15
*/
class SystemApiError extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}