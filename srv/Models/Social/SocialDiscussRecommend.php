<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/15
 * Time: 18:59
 */

namespace Models\Social;


use Models\BaseModel;

class SocialDiscussRecommend extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}