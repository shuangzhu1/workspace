<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/27
 * Time: 10:36
 */

namespace Multiple\Panel\Controllers;


use Services\Site\SiteKeyValManager;

class AgreementController extends ControllerBase
{
    //用户协议
    public function userAction()#用户协议#
    {
        $content = SiteKeyValManager::init()->getOneByKey(SiteKeyValManager::KEY_PAGE_DOCUMENT, 'user');
        $this->view->setVar('content', $content);
    }

    //红包规则
    public function packageAction()#红包规则#
    {
        $content = SiteKeyValManager::init()->getOneByKey(SiteKeyValManager::KEY_PAGE_DOCUMENT, 'package');
        $this->view->setVar('content', $content);
    }

    //钱包协议
    public function ruleWalletAction()#钱包协议#
    {
        $content = SiteKeyValManager::init()->getOneByKey(SiteKeyValManager::KEY_PAGE_DOCUMENT, 'wallet');
        $this->view->setVar('content', $content);
    }
}