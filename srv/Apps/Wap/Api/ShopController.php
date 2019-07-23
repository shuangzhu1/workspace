<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/7
 * Time: 13:39
 */

namespace Multiple\Wap\Api;


use Models\Shop\Shop;
use Models\Shop\ShopGoods;
use Services\Shop\GoodManager;
use Util\Ajax;

class ShopController extends ControllerBase
{
    public function goodsAction()
    {
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $to = $this->request->get('to', 'int', '');
        if (!$to) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        if (!$shop = Shop::exist(['id=' . $to . ' and status=' . GoodManager::status_normal])) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }


        $where = 'shop_id=' . $to . " and status=" . GoodManager::status_normal;
        $list = ShopGoods::findList([$where, 'columns' => 'id as good_id,shop_id,images,brief,user_id as uid,name,price,unit,url,status,created', 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
        $data = [];
        if ($list) {
            foreach ($list as $item) {
                $data[] = [$this->getFromOB('shop/partial/goods-item', array('item' => $item))];
            }
        }
        $data = array('count' => ShopGoods::dataCount($where), "limit" => $limit, 'data_list' => $data);
        Ajax::outRight($data);
    }
}