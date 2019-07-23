<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/3
 * Time: 13:57
 */

namespace Models\Community;


use Models\BaseModel;

class CommunityInfo extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}