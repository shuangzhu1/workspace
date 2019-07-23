<?php

namespace Models\Music;

use Models\BaseModel;

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/20
 * Time: 16:35
 */
class MusicDownloadHistory extends BaseModel
{
    public function initialize()
    {
        $this->setConnectionService("original_mysql");
    }
}