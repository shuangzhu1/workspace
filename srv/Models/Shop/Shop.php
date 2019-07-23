<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/16
 * Time: 13:44
 */

namespace Models\Shop;


use Models\BaseModel;

class Shop extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}