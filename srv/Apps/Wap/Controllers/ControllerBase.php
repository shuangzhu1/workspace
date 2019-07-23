<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/23
 * Time: 19:34
 */

namespace Multiple\Wap\Controllers;

use Models\Site\SiteAppVersion;
use Multiple\Wap\Helper\UserStatus;
use Phalcon\Mvc\Controller;
use Services\Social\ShareManager;
use Util\EasyEncrypt;
use Util\GetClient;
class ControllerBase extends Controller
{
    public $uid = 0;
    public $to_uid = 0;
    public $checkLogin = false;
    public $hide_footer = false;
    public $is_app = false;

    public function initialize()
    {
        $client = new GetClient();
        if ($client->isApp()) {
            $this->is_app = true;
            $this->hide_footer = true;
        }
        if ($client->isKlg()) {
            $this->view->setVar('is_klg', true);
        }

        $version = SiteAppVersion::findOne(['os="android" and is_deleted=0', 'order' => 'version desc,id desc,download_url']);
        $uid_cookie = $this->cookies->get('klg_UID')->getValue();
        $this->view->setVar('download_url', $version['download_url']);
        $this->uid = $this->request->get("uid", 'int', 0);//用户id
        if( $uid_cookie )//如果有cookie,优先使用cookie中uid
        {
            $uid = EasyEncrypt::decode($uid_cookie);
            $this->uid = $uid;
        }
        $this->to_uid = $this->request->get('to', 'int', 0);
        if ($this->uid) {
            if ($this->uid < 50000 || $this->uid > 1000000000) {
                $this->uid = 0;
            }
        }
        if ($this->to_uid) {
            if ($this->to_uid < 50000 || $this->to_uid > 1000000000) {
                $this->to_uid = 0;
            }
        }

        global $global_redis;
        $global_redis = $this->di->get('redis');
        // $this->uid = UserStatus::getUid();
        ShareManager::init()->visitCount();//回访记录
        /*

          if ($this->checkLogin && !$this->uid) {
              if ($this->router->getActionName() == 'rank' && $this->is_app) {
                  $this->response->redirect($this->uri->baseUrl('/user/login?from=app'))->send();
              } else {
                  $this->response->redirect($this->uri->baseUrl('/user/login'))->send();
              }
              return;
          }*/
    }

    public function error404()
    {
        $this->view->title = "404";
        $this->view->setVar('hide_footer', true);
        $this->view->pick('error/404');
        return;
    }
}