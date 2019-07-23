<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/2
 * Time: 17:18
 */

namespace Models\User;


use Models\BaseModel;

class UserNoviceGift extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}