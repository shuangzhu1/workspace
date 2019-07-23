<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/16
 * Time: 13:47
 */

namespace Models\Shop;


use Models\BaseModel;

class ShopGoods extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}