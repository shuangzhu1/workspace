<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/6
 * Time: 10:27
 */

namespace Multiple\Panel\Controllers;


use Models\Site\SiteTags;
use Services\Discuss\TagManager;

class TagsController extends ControllerBase
{
    //动态标签
    public function discussAction()#动态标签#
    {
        $list = SiteTags::findList(['type=' . TagManager::TYPE_DISCUSS, 'order' => 'sort_num asc,created desc']);
        $this->view->setVar('list', $list);
    }

    //用户标签
    public function userAction()#用户标签#
    {
        $type = $this->request->get("type", 'int', 0);
        $where = 'type=' . TagManager::TYPE_USER;
        if ($type) {
            $where .= " and extra=" . $type;
        }
        $list = SiteTags::findList([$where, 'order' => 'extra asc,sort_num asc,created desc']);
        $this->view->setVar('list', $list);
        $this->view->setVar('type',$type);
    }
}