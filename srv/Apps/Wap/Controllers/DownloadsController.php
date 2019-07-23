<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/23
 * Time: 19:35
 */

namespace Multiple\Wap\Controllers;

use Models\Site\SiteAppVersion;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\View;

class DownloadsController extends Controller
{
    /**
     * 企业包下载页面
     */
    public function enterpriseAction()
    {
        $this->view->disableLevel([
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true
        ]);
        $this->view->pick('downloads/enterprise');
    }

    /**
     * app内邀请好友
     */
    public function shareAction()
    {

        $this->view->disableLevel([
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true
        ]);
        $version = SiteAppVersion::findOne(['os="android" and is_deleted=0', 'order' => 'version desc,id desc,download_url']);
        $this->view->setVar('download_url', $version['download_url']);
        $this->view->pick('downloads/share_v4');
    }
}
