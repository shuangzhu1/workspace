<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/11
 * Time: 16:59
 */

namespace Window;


use Phalcon\Mvc\Controller;

class ErrorsController extends Controller
{
    public function show404Action()
    {
        echo "not found";
        exit;
    }
}