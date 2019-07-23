<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/12
 * Time: 11:51
 */

namespace Models\Site;


use Models\BaseModel;

class SiteArticleViewLog extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}