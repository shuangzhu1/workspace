<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/11
 * Time: 16:59
 */

namespace Multiple\Open\Controllers;


use Multiple\Open\Helper\Ajax;

use Multiple\Open\Helper\SocialManager;

class SocialController extends ControllerBase
{
    //分享
    public function shareAction()
    {
        $uid = $this->request->get("uid", 'int', 0);
        $content = $this->request->get("content");
        if (!$uid || !$content) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!SocialManager::init()->share($uid, $this->app_id, $this->app['name'], $content)) {
            $this->ajax->outError(Ajax::FAIL_SHARE);
        }
        $this->ajax->outRight("分享成功");
    }
}