<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/15
 * Time: 14:07
 */

namespace Models\Site;


use Models\BaseModel;

class SiteMaterial extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService('original_mysql');
    }
}