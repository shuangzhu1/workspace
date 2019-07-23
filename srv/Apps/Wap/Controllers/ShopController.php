<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/7
 * Time: 9:53
 */

namespace Multiple\Wap\Controllers;


use Models\Shop\Shop;
use Models\Shop\ShopGoods;
use Services\Shop\GoodManager;
use Services\Shop\ShopManager;

class ShopController extends ControllerBase
{
    public function detailAction()
    {
        $id = $this->dispatcher->getParam(0);
        if (!$id) {
            $this->error404();
            return;
        }
        $shop = Shop::findOne(['id=' . $id, 'columns' => '']);
        if (!$shop) {
            $this->error404();
            return;
        }
        if ($shop['status'] != ShopManager::status_normal ) {
            $this->response->redirect('shop/down');
            return;
        }
        $goods_count = ShopGoods::dataCount('shop_id=' . $id . " and status=" . GoodManager::status_normal);
        $this->view->title = $shop['name'];
        $this->view->setVar('item', $shop);
        $this->view->setVar('good_count', $goods_count);
    }

    //店铺关闭
    public function downAction()
    {
        $this->view->title = '店铺违规';

    }
}