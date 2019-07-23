<?php

namespace Multiple\Developer\Controllers;

use Models\User\UserProfile;
use Phalcon\Tag;
use Models\Developer\Admins;
class SettingController extends ControllerBase
{
    public function indexAction()
    {
        Tag::setTitle('账号设置 - 资料设置');
        $this->assets->addJs('/srv/static/panel/js/tools/region.select.js');
        $this->view->setVar('admin', $this->admin);
        $tag = true;
        if ($this->request->isPost()) {
            $admin = $this->admin;
            $admin_data = [
                'name' => $this->request->getPost('name', array('string', 'striptags')),
                'app_uid' => $this->request->getPost('app_uid', array('int', 'striptags'))
            ];
            if ($admin_data['app_uid'] > 0) {
                if (!UserProfile::findOne(['user_id=' . $admin_data['app_uid']])) {
                    $this->flash->error("app账号【" . $admin_data['app_uid'] . "】不存在");
                    $tag = false;
                }
            }
            if ($tag) {
                if (Admins::updateOne($admin_data, ['id' => $admin['id']])) {
                    $this->flash->error("更新失败");
                } else {
                    $this->flash->success("资料更新成功！");
                    $this->session->set('admin_info', Admins::findOne('id=' . $admin['id']));
                }
            }
            // $admin->save($data);

        } else {
            //  $this->flash->notice("请设置账号信息并绑定微信公众账号！");
            //$industries = IndustryManager::instance()->getTreeData(HOST_KEY);
            // $this->view->setVar('industries', $industries);
        }
    }

    public function passwordAction()
    {
        Tag::setTitle('账号设置 - 密码修改');
        if ($this->request->isPost()) {
            $original = $this->request->getPost('original', array('string'));
            $original = sha1($original);
            if ($original != $this->admin['password']) {
                $this->flash->error("原始密码有误");
                return false;
            }

            $password = $this->request->getPost('password', 'striptags');
            $repeatPassword = $this->request->getPost('repeatPassword', 'striptags');
            if (empty($password)) {
                $this->flash->error("新密码不能为空");
                return false;
            }
            if ($password != $repeatPassword) {
                $this->flash->error("两次密码不一致");
                return false;
            }
            $password = sha1($password);

            if (!Admins::updateOne(array(
                'password' => $password
            ), ['id' => $this->admin['id']])
            ) {
                $this->flash->error("设置失败");
                return false;
            }
            $this->admin['password'] = $password;
            $this->flash->success("密码修改成功");
        } else {
            $this->flash->notice("为了您的账号安全，请谨慎修改！");
        }
    }

}