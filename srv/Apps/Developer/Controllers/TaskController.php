<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/5
 * Time: 10:28
 */

namespace Multiple\Developer\Controllers;


class TaskController extends ControllerBase
{
    public function listAction()
    {
        $port = $this->request->get("port", 'string', 4343);
        $this->view->setVar('port', $port);
    }
}