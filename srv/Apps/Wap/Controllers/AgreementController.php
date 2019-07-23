<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/27
 * Time: 9:04
 */

namespace Multiple\Wap\Controllers;


use Services\Site\SiteKeyValManager;

class AgreementController extends ControllerBase
{
    //红包规则
    public function ruleAction()
    {
        $type_arr = ['user' => ['c' => 'user'], 'package' => ['c' => 'package'], 'w' => ['c' => 'wallet'],'v'=>['c'=>'video']];
        $type = str_replace('.html', '', $this->dispatcher->getParam(0));
        if (!key_exists($type, $type_arr)) {
            die("");
        }

        $this->view->setVar('hide_footer', true);
        $val = SiteKeyValManager::init()->getOneByKey(SiteKeyValManager::KEY_PAGE_DOCUMENT, $type_arr[$type]['c']);
        $this->view->title = $val['remark'];
        $this->view->setVar('content', $val['val']);
    }

}