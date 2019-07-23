<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/27
 * Time: 10:43
 */

namespace Models\Shop;


use Models\BaseModel;

class ShopCheckLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}