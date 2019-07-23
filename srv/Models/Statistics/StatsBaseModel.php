<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/28
 * Time: 9:40
 */

namespace Models\Statistics;


use Models\BaseModel;

class StatsBaseModel extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("db_statistics");
    }
}