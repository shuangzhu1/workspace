<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/6
 * Time: 16:58
 */

namespace Multiple\Wap\Controllers;


class ActivityController extends ControllerBase
{
    public function listAction()
    {
        $this->view->title = "悬赏活动";
    }

    public function detailAction()
    {
        $this->view->title = "活动详情";
        echo "待开发";
        exit;
    }
}