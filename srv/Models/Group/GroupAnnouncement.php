<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/7
 * Time: 14:21
 */

namespace Models\Group;


use Models\BaseModel;

class GroupAnnouncement extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}