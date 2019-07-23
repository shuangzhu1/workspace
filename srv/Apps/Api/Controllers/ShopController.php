<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/16
 * Time: 14:37
 */

namespace Multiple\Api\Controllers;


use Components\Yunxin\ServerAPI;
use Models\Shop\Shop;
use Models\Shop\ShopApply;
use Models\Shop\ShopCategory;
use Models\Shop\ShopVisitLog;
use Models\User\UserInfo;
use Services\Agent\AgentManager;
use Services\Im\ImManager;
use Services\Shop\ShopManager;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Util\Ajax;
use Util\Debug;

class ShopController extends ControllerBase
{
    //开店、编辑店铺
    public function editAction()
    {
        $name = $this->request->get("name", 'green'); //店名
        $shop_id = $this->request->get("shop_id", "int", 0); //店铺id
        $brief = $this->request->get("brief", "green"); //店铺描述
        $images = $this->request->get("images", "string", '');//banner图
        $category_ids = $this->request->get("category_ids", "string", '');//店铺id
        $category_name = $this->request->get("category_name", "string", '');//店铺名称

        $province = $this->request->get("province", "string", '');//省
        $city = $this->request->get("city", "string", '');//市
        $county = $this->request->get("county", "string", '');//区

        $address_title = $this->request->get("address_title", "string", '');//地址标题【如大冲国际】
        $address = $this->request->get("address", "string", '');//地址
        $address_detail = $this->request->get("address_detail", "green", '');//详细地址

        $contract_number = $this->request->get("contact_number", "string", '');//联系电话
        $url = $this->request->get("url", "string", '');//店铺外部链接

        $lng = $this->request->get("lng", "string", '');//经度
        $lat = $this->request->get("lat", "string", '');//纬度
        $uid = $this->uid;
        //编辑
        if ($shop_id) {
            if (!$name && !$brief && !$images && !$address && !$lng && !$lat && !$contract_number && !$url && !$address_detail) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            $res = ShopManager::init()->edit($uid, $shop_id, $contract_number, $name, $brief, $images, $category_ids, $category_name, $address, $lng, $lat, $url, $address_title, $address_detail, ['province' => $province, 'city' => $city, 'county' => $county]);

        } //添加
        else {
            if (!$uid || !$name || !$images || !$address || !$lng || !$lat) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            $res = ShopManager::init()->open($uid, $name, $contract_number, $brief, $images, $category_ids, $category_name, $address, $lng, $lat, $url, $address_title, $address_detail, ['province' => $province, 'city' => $city, 'county' => $county]);
        }
        if ($res) {
            $this->ajax->outRight($res);
        } else {
            $this->ajax->outError(Ajax::FAIL_SUBMIT);
        }
    }

    //店铺详情
    public function detailAction()
    {
        if (!$this->uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $shop_id = $this->request->get("shop_id", "int", 0); //店铺id
        if (!$shop_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //记录店铺访客
        if (!Shop::exist(['id' => $shop_id, 'user_id' => $this->uid]))//排除自己
        {
            $redis_queue = $this->di->get('redis_queue');
            $redis_queue->lPush(CacheSetting::KEY_SHOP_VISIT_LOG, json_encode(['uid' => $this->uid, 'shop_id' => $shop_id, 'visit_time' => time()]));
        }

        ShopManager::init()->detail($this->uid, $shop_id);
    }

    //我的店铺/他的店铺
    public function listAction()
    {
        $to_uid = $this->request->get("to_uid");
        if (!$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        ShopManager::init()->list($this->uid, $to_uid);
    }

    //附近店铺店铺
    public function nearAction()
    {
        $uid = $this->uid;
        $lng = $this->request->get('lng', 'string', '');//经度
        $lat = $this->request->get('lat', 'string', '');//纬度
        $is_detail = $this->request->get('is_detail', 'string', '');//是否详细列表
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $area_code = $this->request->get("area_code", 'string', '');//区号

        if (!$this->uid || !$lng || !$lat) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        ShopManager::init()->near($is_detail, $lng, $lat, $page, $limit, $area_code);
    }

    //店铺分类
    public function categoryAction()
    {
        // $res = $this->original_mysql->query("select id,name,parent_id as pid,`desc` from dvalley.shop_category where enable = 1 order by parent_id,sort")->fetchAll(\PDO::FETCH_ASSOC);
        $res = ShopCategory::getByColumnKeyList(['enable=1', 'order' => 'parent_id,sort', 'columns' => " id,name,parent_id as pid,`desc`"], 'id');
        if ($res) {
            // $keys = array_column($res, 'id');
            // $res = array_combine($keys, $res);
            $tree = self::getTree($res, 0);
        } else {
            $this->ajax->outError(Ajax::FAIL_GET_SHOP_CATEGORY, '获取店铺分类失败');
        }

        $this->ajax->outRight($tree);

    }

    //支付完成
    public function paySuccessAction()
    {
        $uid = $this->uid;
        $paid_number = $this->request->get("paid_number", 'string', '');
        $trade_no = $this->request->get("trade_no");
        if (!$uid || !$trade_no) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $shopManager = ShopManager::init();
        if ($res = $shopManager->paySuccess($uid, $paid_number, $trade_no)) {

            Debug::log("pay_success income:" . var_export($res, true), 'debug');
            //收益立刻到账
            if ($res['income_id']) {
                foreach ($res['income_id'] as $i) {
                    AgentManager::init()->incomeToAccountSingle($i);
                }
            }

            $this->ajax->outRight("");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "支付失败(余额不足)");
        //$this->ajax->outError(Ajax::FAIL_PAY, $shopManager->getErrorMsg());
    }

    //店主查看店铺访客
    public function getVisitorAction()
    {
        $p = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $uid = $this->uid;
        $shop_id = $this->request->get('shop_id', 'int', 0);
        $shop = Shop::findOne(['id = ' . $shop_id, 'columns' => 'user_id']);
        if (!$shop)
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);//店铺不存在
        if ($shop['user_id'] != $uid)
            $this->ajax->outError(Ajax::ERROR_NOT_SHOP_OWNER, '只有店主才能查看访客');

        //更新最后查看访客时间
        Shop::updateOne(['last_check_visitor_time' => time()], ['id' => $shop_id]);
        $cache = new CacheSetting();
        if ($data = $cache->get(CacheSetting::PREFIX_SHOP_DETAIL, $shop_id))//更新缓存中数据
        {
            $data['last_check_visitor_time'] = time();
            $cache->set(CacheSetting::PREFIX_SHOP_DETAIL, $shop_id, $data);
        }

        $is_vip = UserInfo::exist(['user_id ' => $uid, 'is_vip' => 1]);
        /*if (!$is_vip)//不是vip只能查看最固定个数访客
        {
            $limit_visitor = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege")['shop_visitor'] ?: 3;
            if( $p == 1 )
                $limit = $limit_visitor;


        } else {
            $limit_visitor = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "vip_privilege")['shop_visitor'];
            if ($limit_visitor != -1) {
                $limit = $limit_visitor;
                $p = 1;
            }
        }*/

        $visitor = ShopVisitLog::getByColumnKeyList(['shop_id = ' . $shop_id, 'group' => 'uid', 'order' => 'visit_time desc', 'offset' => ($p-1)*$limit, 'limit' => $limit, 'columns' => 'max(visit_time) as visit_time,uid'], 'uid');
        if ($visitor) {
            $visitorInfo = UserInfo::findList(['user_id in (' . implode(',', array_keys($visitor)) . ')', 'columns' => 'user_id,username,sex,avatar']);
            foreach ($visitorInfo as $item) {
                $visitor[$item['user_id']]['username'] = $item['username'];
                $visitor[$item['user_id']]['sex'] = $item['sex'];
                $visitor[$item['user_id']]['avatar'] = $item['avatar'];
            }

        } else {
            $visitor = [];
        }
        $visitor_count = ShopVisitLog::count('shop_id = ' . $shop_id);
        $res['shop_owner']['uid'] = $shop['user_id'];
        $res['shop_owner']['is_vip'] = $is_vip ? 1 : 0;
        $res['visitor_list'] = array_values($visitor);
        $res['data_count'] = $visitor_count;
        $this->ajax->outRight($res);
    }

    //创建订单
    public function createOrderAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $code = $this->request->get("code", 'string', '');
//        $count = ShopApply::dataCount("user_id=" . $uid . " and created>=" . strtotime(date('Ymd')));
//        if ($count >= 3) {
//            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "您今天的提交次数已达上限");
//        }
        $res = ShopManager::init()->createOrder($uid, $code);
        if ($res) {
            $this->ajax->outRight($res);
        }
        $this->ajax->outError(Ajax::FAIL_SUBMIT);
    }



    private function getTree($data, $pid)
    {
        $tree = array();
        foreach ($data as $v) {
            if ($v['pid'] == $pid) {
                $v['children'] = self::getTree($data, $v['id']);
                unset($v['pid']);
                $tree[] = $v;
            }
        }
        return $tree;
    }


}