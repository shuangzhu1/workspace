<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24
 * Time: 10:26
 */

namespace Multiple\Callback\Controllers;


use Phalcon\Mvc\Controller;
use Util\Ajax;

class ErrorsController extends Controller
{
    public function show404Action()
    {
        echo json_encode(['result' => 0,'data' => '非法请求'],JSON_UNESCAPED_UNICODE);
    }


}