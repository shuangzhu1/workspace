<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/11
 * Time: 13:58
 */

namespace Multiple\Wap\Controllers;


use Models\User\UserInfo;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;

class DiamondController extends ControllerBase
{
    //龙钻充值
    public function chargeAction()
    {
        $open_id = $this->session->get("open_id");
        if (!$open_id) {
            $this->session->set("callback_url", "/diamond/charge");
            $this->response->redirect("/wechat/auth");
        } else {
            $this->view->title = "龙钻充值";

//            $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'user_id,avatar,username,sex']);
//            $result1 = Request::getPost(Base::PACKAGE_VIRTUAL_COIN, ['uid' => $uid, 'coin_type' => 0], true);//红包钻石
//            $user_info['diamond'] = $result1;
//
//            $this->view->setVar('user_info', $user_info);
            $res = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules");
            $this->view->setVar('price_list', $res);
            //  $this->view->setVar('uid', $uid);
        }
    }

    //龙钻充值记录
    public function historyAction()
    {
        $open_id = $this->session->get("open_id");
        // $uid = 50000;
        if (!$open_id) {
            $this->session->set("callback_url", "/diamond/history");
            $this->response->redirect("/wechat/auth");
        } else {
            $this->view->title = "龙钻充值记录";

            //  $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'user_id,avatar,username,sex']);

            // $this->view->setVar('user_info', $user_info);
            $res = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules");
            $this->view->setVar('price_list', $res);
            // $this->view->setVar('uid', $uid);
        }
    }
}