<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/4
 * Time: 14:36
 */

namespace Models\Site;


use Models\BaseModel;

class SiteArticle extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}