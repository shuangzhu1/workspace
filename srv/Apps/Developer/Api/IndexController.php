<?php

namespace Multiple\Developer\Api;


class IndexController extends ApiBase
{
    public function indexAction()
    {
        echo 888888;exit;
    }

    public function validAction()
    {
        $wx_server = $this->di->getShared('wx_server');
        $wx_server->valid($this->request);
    }
}
