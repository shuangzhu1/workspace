<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/9
 * Time: 9:22
 */

namespace Models\Site;


use Models\BaseModel;

class SiteReportReason extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}