<?php

namespace Models\Community;

use Models\BaseModel;

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/2
 * Time: 16:25
 */
class Community extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}