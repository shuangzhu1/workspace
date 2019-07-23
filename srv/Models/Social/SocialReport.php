<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/9
 * Time: 14:00
 */

namespace Models\Social;


use Models\BaseModel;

class SocialReport extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}