<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/12
 * Time: 9:50
 */

namespace Multiple\Wap\Api;



class ErrorsController extends ControllerBase
{
    public function show404Action()
    {
        echo "not found";
        exit;
    }
}