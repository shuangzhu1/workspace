<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/12
 * Time: 9:50
 */

namespace Multiple\Panel\Api;


use Phalcon\Mvc\Controller;

class ErrorsController extends Controller
{
    public function show404Action()
    {
        echo "not found";
        exit;
    }
}