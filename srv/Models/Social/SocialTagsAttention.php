<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/29
 * Time: 15:08
 */

namespace Models\Social;


use Models\BaseModel;

class SocialTagsAttention extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}