<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/5
 * Time: 17:27
 */

namespace Multiple\Panel\Controllers;


use Models\Customer\Customer;
use Models\Site\SiteGame;
use Util\Pagination;

class GameController extends ControllerBase
{
    //游戏列表
    public function listAction()#应用列表#
    {
        $customers = Customer::findList([]);
        $enable_customers = [];
        if ($customers) {
            foreach ($customers as $item) {
                if ($item['status'] == 1) {
                    $enable_customers[] = $item;
                }
            }
        }
        $this->view->setVar('customers', $customers);
        $this->view->setVar('enable_customers', $enable_customers);
    }

    //游戏提供商
    public function customerAction()#应用提供商#
    {

    }

    //H5游戏列表
    public function WebGameListAction()#H5游戏列表#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',15);
        $name = $this->request->get('name','string','');
        $where = ['enable = 1'];
        if( !empty($name) )
        {
            $where[] = "name like '%" . $name . "%'";
        }
        if(!empty($where))
            $where = 'where ' . implode(' and ',$where);
        $count = $this->original_mysql->query("select count(1) from site_game $where")->fetch(\PDO::FETCH_ASSOC)['count(1)'];
        $list = $this->original_mysql->query("select * from site_game  $where order by status desc,id desc limit " . $limit*($p-1) . ",$limit")->fetchAll(\PDO::FETCH_ASSOC);
        $this->view->setVar('list',$list);
        $this->view->setVar('name',$name);
        $this->view->pick('game/webGameList');
        Pagination::instance($this->view)->showPage($p,$count,$limit);
    }
}