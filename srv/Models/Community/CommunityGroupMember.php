<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/4
 * Time: 9:50
 */

namespace Models\Community;


use Models\BaseModel;

class CommunityGroupMember extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}