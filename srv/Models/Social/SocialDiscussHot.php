<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/12
 * Time: 18:51
 */

namespace Models\Social;


use Models\BaseModel;

class SocialDiscussHot extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}