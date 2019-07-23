<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/3
 * Time: 10:28
 */

namespace Multiple\Wap\Controllers;


use Models\Site\SiteGame;

class GameController extends ControllerBase
{
    //H5小游戏列表
    public function listAction()
    {
        $this->view->title = 'H5小游戏';
    }

    public function indexAction()
    {

        $referer = $this->request->getHTTPReferer();
        $old = $_SESSION;
        $old[rand(1000, 9999)] = $referer;
        $this->session->set(rand(1000, 9999), $old);
        $this->view->setVar('name',$this->name);

    }

    public function h5GameAction()
    {
        if( $this->di->get('redis')->originalGet('game_switch') == 'false' )
            $list = SiteGame::findList(['enable = 1 and status = 1','order' => 'view_cnt desc']);
        else
            $list = [];
        $this->view->setVar('list',$list);
        $this->view->title = '休闲小游戏';
        $this->view->pick('game/h5game');
    }

    public function visitIncrAction()
    {
        $id = $this->request->get('id');
        SiteGame::increment($id,'view_cnt');
    }

    public function game2048Action()
    {

    }


}