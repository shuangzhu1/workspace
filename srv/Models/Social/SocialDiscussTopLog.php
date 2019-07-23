<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/15
 * Time: 18:43
 */

namespace Models\Social;


use Models\BaseModel;

class SocialDiscussTopLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}