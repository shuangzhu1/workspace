<?php
/**
 * Created by PhpStorm.
 * User: Arimis
 * Date: 14-6-2
 * Time: 下午9:09
 */

namespace Models\Developer;


use Models\BaseModel;

class AdminMenusCat extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("db2");
    }
} 