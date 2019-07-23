<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/20
 * Time: 15:28
 */

namespace Multiple\Open\Module;


class UserController extends ModuleBase
{
    public function indexAction()
    {

    }

    public function authAction()
    {
      //  $ini = parse_ini_file(ROOT . '/Data/site/robot.ini', true);
        $res = [
            'uid' =>$this->uid,
            'token' => $this->robot['token'],
            'expire' => $this->robot['expire'],
            /*  'app_key' =>$ini['base']['yx_app_key']*/
        ];
        $this->ajax->outRight($res);
    }
}