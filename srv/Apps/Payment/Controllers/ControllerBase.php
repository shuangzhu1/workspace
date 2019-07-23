<?php

namespace Multiple\Payment\Controllers;

use Models\Customers;
use Models\Payments\PaymentLog;
use Phalcon\Mvc\Controller;
use Util\Ajax;

class ControllerBase extends Controller
{

    protected $uri = null;

    protected $isWap = false;

    public function initialize()
    {
        if(Ajax::init()->isMobile()) {
            $this->isWap = true;
        }
        else {
            $this->isWap = false;
        }

//        $this->isWap = isset($_SERVER['HTTP_VIA']) ? (stristr($_SERVER['HTTP_VIA'],"wap") ? true : false) : false;
    }
}