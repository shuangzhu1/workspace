<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/12
 * Time: 11:54
 */

namespace Models\Social;


use Models\BaseModel;

class SocialDiscussTagFilter extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}