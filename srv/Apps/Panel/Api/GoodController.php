<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/18
 * Time: 19:03
 */

namespace Multiple\Panel\Api;


use Models\Shop\Shop;
use Models\Shop\ShopGoods;
use Models\User\Users;
use Services\Admin\AdminLog;
use Services\Im\ImManager;
use Services\Shop\GoodManager;
use Services\Site\CacheSetting;
use Util\Ajax;

class GoodController extends ApiBase
{
    /*删除商品*/
    public function delAction()
    {
        $id = $this->request->get('data');
        $reason = $this->request->get('reason');

        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$reason) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ['status' => GoodManager::status_system_deleted, 'modify' => time()];
        //更新商品状态
        foreach ($id as $item) {
            $goods = ShopGoods::findOne(['id=' . $item, 'columns' => 'user_id,name,shop_id']);
            if ($goods) {
                if (ShopGoods::updateOne(['status' => $data['status'], 'modify' => time()], ['id' => $item])) {
                    AdminLog::init()->add('删除商品', AdminLog::TYPE_GOOD, $item, array('type' => "update", 'id' => $item, 'reason' => $reason));
                    //删除缓存
                    $cache = new CacheSetting();
                    $cache->remove(CacheSetting::PREFIX_GOOD_DETAIL, $item);
                    //发送消息
                    $user = Users::findOne(['id=' . $goods['user_id'], 'columns' => 'username']);
                    $shop = Shop::findOne(['id=' . $goods['shop_id'], 'columns' => 'name']);
                    ImManager::init()->initMsg(ImManager::TYPE_GOOD_SHIELD, ['username' => $user['username'], 'shop' => $shop['name'], 'goods' => $goods['name'], 'reason' => $reason, 'to_user_id' => $goods['user_id']]);
                }
            }
        }

        $this->ajax->outRight("");

    }

    /*恢复正常*/
    public function recoveryAction()
    {
        $id = $this->request->get('data');
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //更新店铺状态

        $data = ['status' => GoodManager::status_normal, 'modify' => time()];
        //  $ids = implode(',', $id);
        foreach ($id as $item) {
            $shop = ShopGoods::exist('id=' . $item);
            if ($shop) {
                if (ShopGoods::updateOne($data, ['id' => $item])) {
                    AdminLog::init()->add('恢复商品状态', AdminLog::TYPE_GOOD, $item, array('type' => "update", 'id' => $item));
                }
            }
        }
        $this->ajax->outRight("");
    }
}