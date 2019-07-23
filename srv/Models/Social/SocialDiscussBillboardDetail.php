<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/26
 * Time: 10:22
 */

namespace Models\Social;


use Models\BaseModel;

class SocialDiscussBillboardDetail extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}