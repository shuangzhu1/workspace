<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/21
 * Time: 9:37
 */

namespace Models\Shop;


use Models\BaseModel;

class ShopCategory extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}