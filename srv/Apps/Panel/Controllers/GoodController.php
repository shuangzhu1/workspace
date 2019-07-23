<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/18
 * Time: 14:53
 */

namespace Multiple\Panel\Controllers;


use Models\Shop\Shop;
use Models\Shop\ShopGoods;
use Models\User\UserInfo;
use Util\Pagination;

class GoodController extends ControllerBase
{
    //商品列表
    public function listAction()#商品列表#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        $shop_id = $this->request->get("shop_id", 'int', 0);//店铺id
        $good_id = $this->request->get("good_id", 'int', 0);//商品id

        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("p", 'int', 1);
        $status = $this->request->get("status", 'int', -1);//状态
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $key = $this->request->get('key', 'string', '');//店铺名称

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($user_id) {
            $params[0][] = ' user_id  = ' . $user_id;
        }
        if ($status >= 0) {
            $params[0][] = ' status  = ' . $status;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($key) {
            $params[0][] = ' name like "%' . $key . '%"';
        }
        if ($shop_id) {
            $params[0][] = ' shop_id=' . $shop_id;
        }
        if ($good_id) {
            $params[0][] = ' id=' . $good_id;
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = ShopGoods::dataCount($params[0]);
        $list = ShopGoods::findList($params);

        if ($list) {
            $user_ids = array_unique(array_column($list, 'user_id'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
            $shop_ids = array_unique(array_column($list, 'shop_id'));
            $shops = Shop::getByColumnKeyList(['id in (' . implode(',', $shop_ids) . ')', 'columns' => 'name,id'], 'id');
            $this->view->setVar('shops', $shops);
        }


        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('status', $status);
        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('key', $key);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('shop_id', $shop_id);

    }
}