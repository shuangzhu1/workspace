<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/17
 * Time: 19:37
 */

namespace Models\Community;


use Models\BaseModel;

class CommunityGroupApply extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}