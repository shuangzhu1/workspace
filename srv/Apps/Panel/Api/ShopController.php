<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/18
 * Time: 16:34
 */

namespace Multiple\Panel\Api;


use Models\Shop\Shop;
use Models\Shop\ShopApply;
use Models\Shop\ShopCategory;
use Models\Shop\ShopGoods;
use Models\User\UserProfile;
use Models\User\Users;
use Services\Admin\AdminLog;
use Services\Im\ImManager;
use Services\Shop\GoodManager;
use Services\Shop\ShopManager;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Util\Ajax;
use Util\Pagination;

class ShopController extends ApiBase
{
    /*封杀店铺*/
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
        $data = ['status' => ShopManager::status_system_deleted, 'modify' => time()];
        //更新店铺状态
        foreach ($id as $item) {
            $shop = Shop::findOne(['id=' . $item, 'columns' => 'user_id,name']);
            if ($shop) {
                if (Shop::updateOne(['status' => $data['status'], 'modify' => time()], ['id' => $item])) {
                    AdminLog::init()->add('封杀店铺', AdminLog::TYPE_SHOP, $item, array('type' => "update", 'id' => $item, 'reason' => $reason));
                    //删除缓存
                    $cache = new CacheSetting();
                    $cache->remove(CacheSetting::PREFIX_SHOP_DETAIL, $item);
                    $user = Users::findOne(['id=' . $shop['user_id'], 'columns' => 'username']);
                    ImManager::init()->initMsg(ImManager::TYPE_SHOP_SHIELD, ['username' => $user['username'], 'shop' => $shop['name'], 'reason' => $reason, 'to_user_id' => $shop['user_id']]);
                    UserProfile::updateOne(['is_merchant' => 0], 'user_id=' . $shop['user_id']);
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

        $data = ['status' => ShopManager::status_normal, 'modify' => time()];
        //  $ids = implode(',', $id);
        foreach ($id as $item) {
            $shop = Shop::findOne(['id=' . $item, 'columns' => 'user_id']);
            if ($shop) {
                if (Shop::updateOne($data, ['id' => $item])) {
                    AdminLog::init()->add('恢复店铺状态', AdminLog::TYPE_SHOP, $item, array('type' => "update", 'id' => $item));
                }
            }
            UserProfile::updateOne(['is_merchant' => 1], 'user_id=' . $shop['user_id']);
        }
        $this->ajax->outRight("");
    }

    //审核通过
    public function checkSuccessAction()
    {
        $id = $this->request->get('data');
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ['status' => ShopManager::status_normal, 'modify' => time()];
        foreach ($id as $item) {
            $shop = Shop::findOne(['id=' . $item, 'columns' => 'user_id,name']);
            if ($shop) {
                if (Shop::updateOne($data, ['id' => $item])) {
                    AdminLog::init()->add('店铺审核通过', AdminLog::TYPE_SHOP, $item, array('type' => "update", 'id' => $item));
                }
            }
            //更新用户信息
            UserProfile::updateOne(['is_merchant' => 1], 'user_id=' . $shop['user_id']);
            //发送im消息
            $user = Users::findOne(['id=' . $shop['user_id'], 'columns' => 'username']);
            ImManager::init()->initMsg(ImManager::TYPE_SHOP_CHECK_SUCCESS, ['username' => $user['username'], 'shop' => $shop['name'], 'to_user_id' => $shop['user_id']]);

        }
        $this->ajax->outRight("");

    }

    //审核失败
    public function checkFailAction()
    {
        $id = $this->request->get('id');
        $reason = $this->request->get('reason');

        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$reason) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ['status' => ShopManager::status_check_fail, 'modify' => time()];
        //更新店铺状态
        $shop = Shop::findOne(['id=' . $id, 'columns' => 'user_id,name,status']);
        if ($shop) {
            $data['status'] = $shop['status'] == ShopManager::status_normal ? ShopManager::status_checking : ShopManager::status_check_fail;
            if (Shop::updateOne(['status' => $data['status'], 'modify' => time()], ['id' => $id])) {
                //删除缓存
//                    $cache = new CacheSetting();
//                    $cache->remove(CacheSetting::PREFIX_SHOP_DETAIL, $item);
                $user = Users::findOne(['id=' . $shop['user_id'], 'columns' => 'username']);
                if ($shop['status'] == ShopManager::status_normal) {
                    AdminLog::init()->add('店铺下架', AdminLog::TYPE_SHOP, $id, array('type' => "update", 'id' => $id, 'reason' => $reason));
                    ImManager::init()->initMsg(ImManager::TYPE_SHOP_DOWN, ['username' => $user['username'], 'shop' => $shop['name'], 'reason' => $reason, 'to_user_id' => $shop['user_id']]);
                } else {
                    AdminLog::init()->add('店铺审核失败', AdminLog::TYPE_SHOP, $id, array('type' => "update", 'id' => $id, 'reason' => $reason));
                    ImManager::init()->initMsg(ImManager::TYPE_SHOP_CHECK_FAIL, ['username' => $user['username'], 'shop' => $shop['name'], 'reason' => $reason, 'to_user_id' => $shop['user_id']]);
                }
                UserProfile::updateOne(['is_merchant' => 0], 'user_id=' . $shop['user_id']);

            } else {
                $this->ajax->outError("店铺更新状态失败");
            }
        } else {
            $this->ajax->outError("店铺不存在");
        }
        $this->ajax->outRight("");
    }


    public function goodAction()
    {
        $shop_id = $this->request->get('shop_id', 'int', '');
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        //$page = $page == 0 ? 1 : $page + 1;
        $where = 'shop_id=' . $shop_id . " and (status=" . GoodManager::status_normal . " or status=" . GoodManager::status_off . ")";
        $goods = ShopGoods::findList([$where, 'columns' => 'name,brief,price,unit,images,created,status', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);

        $list = $goods;
        $count = ShopGoods::dataCount($where);
        $bar = Pagination::getAjaxPageBar($count, $page, $limit);
        $data = $this->getFromOB('shop/partial/good_item', array('list' => $list, 'bar' => $bar, 'count' => $count));
        $this->ajax->outRight($data);
    }

    //修改店铺的类别
    public function editCategoryAction()
    {
        $shop_id = $this->request->get('shop_id','int',0);
        $cid = $this->request->get('cid','int',0);
        $category = $this->original_mysql->query("select * from shop_category where enable = 1")->fetchAll(\PDO::FETCH_ASSOC);
        $category = array_combine(array_column($category,'id'),$category);
        if( empty($shop_id) || empty($cid) )
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'参数无效');
        if( Shop::updateOne(['category_ids' => $cid,'category_name' => $category[$cid]['name']],['id' => $shop_id]) )
            $this->ajax->outRight();
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'修改失败');

    }

    //基础设置
    public function settingAction()
    {
        /**
         *   has_code_money: has_code_money,
         * no_code_money: no_code_money,
         * base_money: base_money,
         * platform_money: platform_money,
         * reward: reward
         */
        $has_code_money = $this->request->getPost("has_code_money", 'float', 0);//有邀请码需要总金额
        $no_code_money = $this->request->getPost("no_code_money", 'float', 0);//没有邀请码需要总金额
        $second_base_money = $this->request->getPost("second_base_money", 'float', 0);//二级邀请人分成金额
        $third_base_money = $this->request->getPost("third_base_money", 'float', 0);//三级邀请人分成金额

        $base_money = $this->request->getPost("base_money", 'float', 0);//一级邀请人分成金额

        $platform_money = $this->request->getPost("platform_money", 'float', 0);//平台收取手续费金额
        $reward = $this->request->getPost("reward");//奖励

        $price = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
        $price['shop'] = [
            "has_code" => $has_code_money * 100,
            'no_code' => $no_code_money * 100,
            'platform' => $platform_money * 100,
            'base' => $base_money * 100,
            'second_base' => $second_base_money * 100,
            'third_base' => $third_base_money * 100,
            'limit' => $reward ? $reward : [],
        ];
        $price['shop']['reward_radices'] = $price['shop']['has_code'] - $price['shop']['base'] - $price['shop']['platform'] - $price['shop']['second_base'] - $price['shop']['third_base'];
        SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", ['val' => json_encode($price)]);
        SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", json_encode($price));

        $this->ajax->outRight("");

    }

    /**
     * 删除一个分类
     */
    public function delCategoryAction()
    {
        $id = $this->request->getPost('id');
        if( ShopCategory::exist(['id' => $id] ))
        {
            $res = ShopCategory::updateOne(['enable' => 0],['id' => $id]);
            if( $res )
                $this->ajax->outRight();
            else
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'操作失败');
        }else
        {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'分类id错误');
        }
    }
    /**
     * 添加/修改一个店铺分类
     */
    public function addCategoryAction()
    {
        $id = $this->request->getPost('id');
        $name = $this->request->getPost('name');
        $desc = $this->request->getPost('desc');
        $pid = $this->request->getPost('parent_id');
        if( empty($id) )
            $res = ShopCategory::insertOne(['name' => $name,'`desc`' => $desc,'parent_id' => $pid]);
        else
            $res = ShopCategory::updateOne(['name' => $name,'`desc`' => $desc,'parent_id' => $pid],['id' => $id]);
        if( $res )
            $this->ajax->outRight();
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG);

    }

    /**
     * 保存店铺分类信息
     */
    public function saveTreeAction()
    {
        $tree = $this->request->getPost('data');
        $res = self::dealRelation($tree,0);
        $values = [];
        foreach( $res as $item )
        {
            $item['name'] = "'" . $item['name'] . "'";
            $item['desc'] = "'" . $item['desc'] . "'";
            $values[$item['id']] = "(" . implode(',',$item) . ")";
        }
        $res = $this->original_mysql->execute("insert into shop_category(`id`,`name`,`desc`,`parent_id`,`sort`) values " . implode(',',array_values($values)) . " on duplicate key update id=values(id),name=values(name),`desc`=values(`desc`),parent_id=values(parent_id),`sort`=values(`sort`)");
        if( $res )
            $this->ajax->outRight();
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG);


    }

    /**
     * @param $data
     * @param $pid
     * @return array
     * 按前端传过来的父子关系修改pid
     */
    private function dealRelation($data,$pid)
    {
        static $arr = [];
        foreach( $data as $k => $item)
        {
            $item['pid'] = $pid;
            $arr[$item['id']]['id'] = $item['id'];
            $arr[$item['id']]['name'] = $item['name'];
            $arr[$item['id']]['desc'] = $item['desc'];
            $arr[$item['id']]['parent_id'] = $item['pid'];
            $arr[$item['id']]['sort'] = $k + 1;
            if( isset($item['children']) )
                self::dealRelation($item['children'],$item['id']);
        }
        return $arr;
    }

}