<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/2
 * Time: 16:28
 */

namespace Models\Community;


use Models\BaseModel;

class CommunityManager extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}