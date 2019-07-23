<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/20
 * Time: 16:54
 */

namespace Models\Music;


use Models\BaseModel;

class MusicInfo extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}