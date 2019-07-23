<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/12
 * Time: 9:50
 */

namespace Multiple\Panel\Controllers;


use Phalcon\Mvc\Controller;
use Phalcon\Tag;

class ErrorsController extends Controller
{
    public function show404Action()
    {
        echo "not found";
        exit;
    }

    public function noPrivilegeAction($code = "404", $msg = '404 page no found')
    {
        Tag::setTitle('运行时错误');
        $this->view->setViewsDir(MODULE_PATH . '/Views');
        $this->response->setHeader('content-type', 'text/html;charset=utf-8');
        $this->response->setStatusCode($code, $msg);

        $this->view->setVar('msg', $msg);
        return $this->view->pick('base/error');
    }
}