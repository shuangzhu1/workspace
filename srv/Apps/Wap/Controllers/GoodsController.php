<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/2
 * Time: 15:01
 */

namespace Multiple\Wap\Controllers;


use Models\Shop\Shop;
use Models\Shop\ShopGoods;
use Services\Shop\GoodManager;
use Services\Shop\ShopManager;

class GoodsController extends ControllerBase
{
    //商品详情
    public function detailAction()
    {
        $id = intval($this->dispatcher->getParam(0));
        if (!$id) {
            $this->error404();
            return;
        }
        $good = ShopGoods::findOne(['id=' . $id /*. " and status=" . GoodManager::status_normal*/]);
        /*if (!$good) {
            $this->error404();
            return;
        }*/

        $shop = Shop::findOne(["id=" . $good['shop_id'], 'columns' => 'status']);
        if ($shop['status'] == ShopManager::status_system_deleted) {
            $this->response->redirect('shop/down');
        }
        $this->view->title = $good['name'];
        $goods_list = ShopGoods::findList(["user_id=" . $good['user_id'] . " and status=" . GoodManager::status_normal . ' and id <>' . $id, 'columns' => 'id,name,images,price','order'=>'rand() desc','limit' => 4]);
        if ($goods_list) {
            foreach ($goods_list as &$item) {
                $item['images'] = explode(',', $item['images'])[0];
            }
        }
        if ($good['status'] == GoodManager::status_user_deleted || $good['status'] == GoodManager::status_off || $good['status'] == GoodManager::status_system_deleted)
            $good = [];
        $this->view->setVar('good', $good);
        $this->view->setVar('goods_list', $goods_list);
        $this->view->setVar('shop', $shop);
        $this->view->title = "商品详情";

    }
}