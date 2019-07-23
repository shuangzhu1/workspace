<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/23
 * Time: 19:35
 */

namespace Multiple\Wap\Controllers;


use Models\User\UserInfo;
use Models\User\UserPointGrade;
use Models\User\Users;
use Multiple\Wap\Helper\UserStatus;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Util\Cookie;
use Util\EasyEncrypt;

class UserController extends ControllerBase
{
    /*  public static $checkLogin_action = ['rank'];*/

    public function initialize()
    {
        /* if (in_array($this->router->getActionName(), self::$checkLogin_action)) {
             $this->checkLogin = true;
         }*/
        parent::initialize();
    }

    public function indexAction()
    {

        /*  if (!$to) {
              $to = $this->uid;
          }*/
        if (!$this->to_uid) {
            //  $this->response->redirect($this->uri->baseUrl('/user/login'))->send();
            $this->error404();
        }

        $user_info = UserStatus::init()->getUserInfo(0, $this->to_uid);
        //  $service = (Request::getPost(Base::SKILL_SERVICE, ['type' => 1, 'uid' =>  $this->to_uid, 'touid' =>  $this->to_uid]));
        // var_dump($service);exit;
        /*  if ($service) {
              //获取技能列表
              $skill = (Request::getPost(Base::SKILL_LIST, []));
              $skill = json_decode($skill['data'], true)['data'];
              $skill_list = [];
              foreach ($skill as $s) {
                  foreach ($s['skills'] as $son) {
                      $skill_list[$s['type']][$son['subtype']] = ['name' => $son['title']];
                  }
              }
              foreach ($service as $item) {
                  $item['type_name'] = $skill_list[$item['type']][$item['subtype']]['name'];
              }

          }*/
        if (!$user_info) {
            $this->flash->error("用户不存在");
        }
        $this->view->setVar('user_info', $user_info);
        $this->view->setVar('hide_footer', false);
        $this->view->setVar('to', $this->to_uid);
        $this->view->title = $user_info['username'] . '的主页';
        $this->view->description = "用户详情-" . $user_info['username'];
    }

    public function registerAction()
    {
        //$this->response->redirect("http://wap.klgwl.com/Downloads/share");
        $from = $this->request->get("from", 'string', '');
        $promote = $this->request->get("promote", 'string', '');
        if ($from || $promote) {
            $from = $promote ? $promote : $from;
            if ($from = EasyEncrypt::decode($from)) {
                $this->cookies->set("from", $from);
            } else {
                return $this->response->redirect("Downloads/Share");
                $this->error404();
                return;

            }
        } else {
            return $this->response->redirect("Downloads/Share");
            $this->error404();
            return;
        }
        $from = intval($from);
        if (!$from) {
            return $this->response->redirect("Downloads/Share");
            $this->error404();
            return;
        }

        $res = UserInfo::init()->findOne(['user_id = ' . $from]);

        if (!$res) {
            return $this->response->redirect("Downloads/Share");
            $this->error404();
            return;
        }
        $this->view->setVar('userinfo', ['name' => $res['username'], 'avator' => $res['avatar']]);
        $this->view->title = '欢迎下载恐龙谷';

    }

    public function loginAction()
    {
        /*  $go = $this->request->get('go', 'string');
          if ($go) {
              if ($this->uid) {
                  $this->response->redirect($go)->send();
              }
          } else {
              $go = "/user";
          }
          $this->view->setVar('go', $go);
          $this->view->title = "登录";
          $this->view->description = "登录";*/
    }

    public function rankAction()
    {
        if (!$this->to_uid) {
            $this->error404();
            // $this->response->redirect($this->uri->baseUrl('/user/login'))->send();
        }
        $user = Users::findOne(['id=' . $this->to_uid, 'columns' => 'grade,points,username']);
        if (!$user) {
            $this->error404();
        }
        $grade = UserPointGrade::getByColumnKeyList([""], "grade");
        /*  var_dump($user->toArray());
          echo "</br>";
          var_dump($grade);exit;*/
        $this->view->setVar('user', $user);
        $this->view->setVar('grade', $grade);
        $this->view->setVar('hide_footer', $this->hide_footer);
        $this->view->title = "会员中心";/* "用户等级-".$user->username;*/
        $this->view->description = "用户等级详情";
    }

    //粉丝列表
    public function fansAction()
    {
        $to = $this->request->get('to', 'int', 0);
        if (!$to) {
            $to = $this->uid;
        }
        $this->view->setVar('to', $to);
        $this->view->setVar('hide_footer', true);
        $this->view->title = "粉丝列表";
        $this->view->description = "粉丝列表详情";
    }

    //关注列表
    public function attentionsAction()
    {
        $to = $this->request->get('to', 'int', 0);
        if (!$to) {
            $to = $this->uid;
        }
        $this->view->setVar('hide_footer', true);
        $this->view->setVar('to', $to);
        $this->view->title = "关注列表";
        $this->view->description = "关注列表详情";
    }

    //技能详情
    public function skillInfoAction()
    {
        $this->view->title = "技能详情";
        $skill = $this->request->get('skill');
        $info = $this->request->get("info");
        if (!$skill || !$info) {
            $this->error404();
            return;
        }
        //var_dump(base64_decode(($skill)));
        $skill = json_decode(base64_decode(($skill)), true);
        $info = json_decode(base64_decode(($info)), true);
        if (!$skill || !$info) {
            $this->error404();
            return;
        }
        $this->view->setVar('skill', $skill);
        $this->view->setVar('info', $info);

    }

    public function testAction()
    {

    }
}