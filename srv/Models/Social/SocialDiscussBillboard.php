<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/19
 * Time: 9:37
 */

namespace Models\Social;


use Models\BaseModel;

class SocialDiscussBillboard extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}