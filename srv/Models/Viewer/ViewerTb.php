<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/14
 * Time: 15:08
 */

namespace Models\Viewer;


use Models\BaseModel;

class ViewerTb extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("db_viewer");
    }
}