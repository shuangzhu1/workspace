<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/27
 * Time: 15:09
 */

namespace Models\User;


use Models\BaseModel;

class UserGift extends BaseModel
{
        public function initialize()
        {
            $this->setConnectionService("original_mysql");
        }
}