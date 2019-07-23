<?php
namespace Multiple\Developer\Controllers;

use Components\CustomerManager;
use Models\Admin\Admins;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\View;
use Phalcon\Tag as Tag;

class AccountController extends Controller
{
    public $customer;

    public function onConstruct()
    {
        $action = $this->dispatcher->getActionName();
        if ($action != 'logout') {
            $admin = $this->session->get('admin');
            if ($admin instanceof Admins) {
                $this->customer = $admin;
                $redirectUrl = CustomerManager::init()->checkJoinStepRedirect($admin);
                $this->response->redirect(ltrim($redirectUrl, '/'))->send();
                return;
            }
        }
        $this->view->setMainView('account');
        $this->view->setLayout('account');
    }

    public function loginAction()
    {
        Tag::setTitle("客户登陆");
    }

    public function regAction()
    {
        Tag::setTitle("客户入住");
    }

    public function logoutAction()
    {
        CustomerManager::init()->logout();
        $this->response->redirect('account/login')->send();
    }

    public function protocolAction()
    {
        Tag::setTitle('客户协议');
        $this->view->setLayout('plain');
    }

    public function forgotAction()
    {
        Tag::setTitle('找回密码');
    }

}
