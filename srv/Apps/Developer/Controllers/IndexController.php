<?php

namespace Multiple\Developer\Controllers;

use Components\ModuleManager\ModuleManager;
use Phalcon\Tag;

class IndexController extends ControllerBase
{
    public function indexAction()
    {
        Tag::setTitle('运营平台');
        $this->assets->addCss('/srv/static/panel/css/module/module.mine.css');
      /*  $mine = ModuleManager::instance(HOST_KEY, CUR_APP_ID)->getCustomerModules();
        if ($mine) {
            $this->view->setVar("mineModules", $mine);
        }*/

        $notice = '';
        /*if (!$this->customer_weibo) {
            $notice .= "您还没有绑定企业微博粉丝服务";
        }*/

        if (!empty($notice)) {
            $this->flash->notice($notice);
        }

    }

    public function noFoundAction()
    {

    }
}