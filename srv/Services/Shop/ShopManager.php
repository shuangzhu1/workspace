<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/16
 * Time: 13:49
 */

namespace Services\Shop;


use Components\Yunxin\ServerAPI;
use Models\Agent\Agent;
use Models\Agent\AgentApply;
use Models\Agent\AgentIncome;
use Models\Shop\Shop;
use Models\Shop\ShopApply;
use Models\Shop\ShopCheckLog;
use Models\Shop\ShopGoods;
use Models\Shop\ShopVisitLog;
use Models\Social\SocialFav;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\UserProfile;
use Phalcon\Mvc\User\Plugin;
use Services\Agent\AgentManager;
use Services\Im\ImManager;
use Services\MiddleWare\Sl\Request;
use Services\Site\AreaManager;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\Social\SocialManager;
use Services\User\AuthManager;
use Services\User\OrderManager;
use Util\Ajax;
use Util\Debug;
use Util\LatLng;
use Util\Validator;

class ShopManager extends Plugin
{
    private static $instance = null;

    const status_system_deleted = 0;//系统删除
    const status_normal = 1; //正常
    const status_checking = 2; //审核中
    const status_check_fail = 3; //审核失败
    const status_user_deleted = 4; //用户删除
    const status_deadline = 5; //套餐已到期【3年】

    //店铺下单 套餐状态
    const combo_status_abnormal = 0;//还未生效
    const combo_status_normal = 1;//正常 已生效
    const combo_status_renew = 2;//已续费到其他订单
    const combo_status_deadline = 5;//已到期


    const pay_status_wait_pay = 0;//待付款
    const pay_status_has_paid = 1;//已付款
    const pay_status_has_canceled = 2;//支付已超时

//    const open_shop_status_not_commit = 0;//未提交信息
//    const open_shop_status_success = 1;//开店铺成功
//    const open_shop_status_checking = 2;//开店铺审核中
//    const open_shop_status_fail = 3;//开店铺审核失败


    public static $pay_expire_time = 1800;//支付时间-30分钟
    public static $combo_deadline = 94608000;//套餐时长-3年


    private static $ajax = null;
    static $status = [
        self::status_system_deleted => '系统删除',
        self::status_normal => "正常",
        self::status_checking => "审核中",
        self::status_check_fail => "审核失败",
        self::status_user_deleted => "用户删除",
    ];
    public $error_msg = "";//错误信息

    public static function init($is_cli = false)
    {
        if (!self::$instance) {
            self::$instance = new self($is_cli);
        }

        return self::$instance;
    }

    public function __construct($is_cli = false)
    {
        if (!$is_cli) {
            self::$ajax = new Ajax();
        }
    }

    //获取错误信息
    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    //设置错误信息
    public function setErrorMsg($msg)
    {
        $this->error_msg = $msg;
    }
    //开店
    /**
     * @param $uid
     * @param $name
     * @param string $brief
     * @param string $imgs
     * @param string $category_ids
     * @param string $category_name
     * @param string $address
     * @param string $address_detail
     * @param string $contact_number
     * @param string $lng
     * @param string $lat
     * @param string $url
     * @param array $address_info
     * @param string $address_title
     * @return int
     */
    public function open($uid, $name, $contact_number = '', $brief = '', $imgs = '', $category_ids, $category_name, $address = '', $lng = '', $lat = '', $url = '', $address_title = '', $address_detail = '', $address_info = [])
    {
        //店铺数量限制 todo
        //请勿重复提交店铺
        if (Shop::exist("name='" . $name . "' and user_id=" . $uid . " and (status=" . self::status_normal . " or status=" . self::status_checking . ")")) {
            self::$ajax->outError(Ajax::ERROR_SHOP_HAS_EXISTS);
        }


        if ($url && $url != 'empty') {
            if (!Validator::validateUrl($url)) {
                self::$ajax->outError(Ajax::INVALID_WEBSITE);
            }
        }
        $apply = ShopApply::findOne(['user_id=' . $uid . " and status=" . self::pay_status_has_paid, 'order' => 'created desc', 'columns' => 'code_owner,id,combo_status,combo_deadline']);
        if (!$apply) {
            self::$ajax->outError(Ajax::INVALID_PARAM, "您还没有支付费用或服务已过期请重新购买在付费");
        }
        $time = time();
        $data = [
            'user_id' => $uid,
            'name' => $name,
            'brief' => $brief ? $brief : '',
            'address_title' => $address_title,
            'address' => $address,
            'lng' => $lng,
            'lat' => $lat,
            'images' => $imgs,
            'category_ids' => $category_ids,
            'category_name' => $category_name,
            'contact_number' => $contact_number,
            "created" => $time,
            'modify' => $time,
            'url' => ($url || $url == 'empty') ? '' : $url,
            'address_detail' => $address_detail,
            //'status' => self::status_checking
            'status' => self::status_normal,
            'combo_deadline' => $apply['combo_deadline']
        ];
        if ($apply && $apply['code_owner']) {
            $data['inviter'] = $apply['code_owner'];
        }
        if ($address_info) {
            if ($address_info['city']) {
                $data['province'] = $address_info['province'];
                $data['city'] = $address_info['city'];
                $data['county'] = $address_info['county'];
                if ($area_code = AreaManager::getInstance()->getCityByName($address_info['city'], 'area_code')) {
                    $data['area_code'] = $area_code;
                };
            }
        }
        //没有省市区地址信息
        if (empty($data['province'])) {
            $address_info = LatLng::init()->getAddress($lng, $lat);
            if ($address_info) {
                $data['province'] = $address_info['province'];
                $data['city'] = $address_info['city'];
                $data['county'] = $address_info['district'];
                if ($area_code = AreaManager::getInstance()->getCityByName($address_info['city'], 'area_code')) {
                    $data['area_code'] = $area_code;
                };
            }
        }
        $shop_id = Shop::insertOne($data);
        if ($shop_id) {
            //添加缓存
//            $data['id'] = $shop_id;
//            $data['status'] = self::status_checking;
//            $cache = new CacheSetting();
//            $cache->set(CacheSetting::PREFIX_SHOP_DETAIL, $shop_id, $data);
            UserProfile::updateOne(['is_merchant' => 1], 'user_id=' . $uid);
            return $shop_id;
        }
        return false;
    }

    //编辑店铺
    /**
     * @param $uid
     * @param $shop_id
     * @param $name
     * @param string $brief
     * @param string $imgs
     * @param string $category_ids
     * @param string $category_name
     * @param string $contact_number
     * @param string $address
     * @param string $address_detail
     * @param string $lng
     * @param string $lat
     * @param string $url
     * @param array $address_info
     * @param string $address_title
     * @return bool
     */
    public function edit($uid, $shop_id, $contact_number = '', $name = '', $brief = '', $imgs = '', $category_ids, $category_name, $address = '', $lng = '', $lat = '', $url = '', $address_title = '', $address_detail = '', $address_info = [])
    {
        $shop = Shop::findOne(["id=" . $shop_id . " and user_id=" . $uid . " and (status=" . self::status_normal . " or status=" . self::status_checking . " or status=" . self::status_check_fail . ")"]);
        if (!$shop) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        /*   if ($url) {
               if (!Validator::validateUrl($url)) {
                   self::$ajax->outError(Ajax::INVALID_WEBSITE);
               }
           }*/
        $data = [];


        if ($name) {
//            //请勿重复提交店铺
//            if (Shop::exist("id <> $shop_id and name='" . $name . "' and user_id=" . $uid . " and (status=" . self::status_normal . " or status=" . self::status_checking . ")")) {
//                self::$ajax->outError(Ajax::ERROR_SHOP_HAS_EXISTS);
//            }
            $data['name'] = $name;
        }
        if ($url) {
            //清空
            if ($url == 'empty') {
                $data['url'] = '';
            } else {
                if (!Validator::validateUrl($url)) {
                    self::$ajax->outError(Ajax::INVALID_WEBSITE);
                }
                $data['url'] = $url;
            }
        }

        if ($contact_number) {
            $data['contact_number'] = $contact_number;
        }
        if ($brief) {
            if ($brief == 'empty') {
                $data['brief'] = '';
            } else {
                $data['brief'] = $brief;
            }
        }
        if ($imgs) {
            $data['images'] = $imgs;
        }
        if ($category_ids) {
            $data['category_ids'] = $category_ids;
        }
        if ($category_name) {
            $data['category_name'] = $category_name;
        }
        if ($address) {
            $data['address'] = $address;
        }
        if ($address_title) {
            $data['address_title'] = $address_title;
        }
        if ($address_detail) {
            $data['address_detail'] = $address_detail;
        }
        if ($address_info) {
            if ($address_info['province']) {
                $data['province'] = $address_info['province'];
                $data['city'] = $address_info['city'];
                $data['county'] = $address_info['county'];
                if ($area_code = AreaManager::getInstance()->getCityByName($address_info['city'], 'area_code')) {
                    $data['area_code'] = $area_code;
                };
            } else {
                $address_info = LatLng::init()->getAddress($lng, $lat);
                if ($address_info) {
                    $data['province'] = $address_info['province'];
                    $data['city'] = $address_info['city'];
                    $data['county'] = $address_info['district'];
                    if ($area_code = AreaManager::getInstance()->getCityByName($address_info['city'], 'area_code')) {
                        $data['area_code'] = $area_code;
                    };
                }
            }

        }
        if ($lng) {
            $data['lng'] = $lng;
        }
        if ($lat) {
            $data['lat'] = $lat;
        }
        /*   if ($url) {
               $data['url'] = $url;
           }*/
        if ($data) {
            $data['modify'] = time();
            //审核失败 再次提交
            if ($shop['status'] == self::status_check_fail) {
                $data['status'] = self::status_checking;
            }
            if (Shop::updateOne($data, 'id=' . $shop_id)) {
                //更新缓存
                $cache = new CacheSetting();
                $cache->set(CacheSetting::PREFIX_SHOP_DETAIL, $shop_id, Shop::findOne(['id=' . $shop_id]));
                return $shop_id;
            }
            return false;
        }
        return true;
    }

    //删除店铺
    /**
     * @param $uid
     * @param $shop_id
     * @return bool
     */
    public function removeAction($uid, $shop_id)
    {
        $shop = Shop::exist("id=" . $shop_id . " and user_id=" . $uid . " and (status=" . self::status_normal . " or status=" . self::status_checking . ")");
        if (!$shop) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (Shop::updateOne(["status" => self::status_user_deleted], "id=" . $shop_id)) {
            $cache = new CacheSetting();
            $cache->remove(CacheSetting::PREFIX_SHOP_DETAIL, $shop_id);
            return true;
        }
        return false;
    }

    //店铺详情
    public function detail($uid, $shop_id)
    {
        $cache = new CacheSetting();
        $data = $cache->get(CacheSetting::PREFIX_SHOP_DETAIL, $shop_id);
        if (!$data) {
            $shop = Shop::findOne(["id=" . $shop_id . " and (status=" . self::status_normal . " or status=" . self::status_checking . ")"]);
            if (!$shop) {
                self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $data = $shop;
            $cache->set(CacheSetting::PREFIX_SHOP_DETAIL, $shop_id, $shop);
            // $good = ShopGoods::findOne(["id=" . $good_id . " and (status=" . self::status_normal . " or status=" . self::status_checking . " or status=" . self::status_off . ")", 'columns' => 'id as good_id,shop_id,images,brief,user_id as uid,name,price,unit,url,status,created']);
        } else {
            $shop = $data;
        }
        if ($shop['status'] == self::status_system_deleted) {
            self::$ajax->outError(Ajax::ERROR_SHOP_HAS_BEEN_SHIELD);
        } else if ($shop['status'] == self::status_user_deleted) {
            self::$ajax->outError(Ajax::ERROR_SHOP_HAS_BEEN_DELETED);
        }


        $user_info = UserInfo::findOne(['user_id=' . $data['user_id'], 'columns' => 'user_id as uid,username,sex,avatar,grade,is_auth']);//用户信息;
        $contact_mark = $user_info['username'];

        /*  if ($contact = UserPersonalSetting::findOne(['owner_id=' . $uid . ' and user_id=' . $data['user_id'], 'columns' => 'mark'])) {
              if ($contact['mark']) {
                  $contact_mark = $contact['mark'];
              }
          }*/
        //获取新增访客
        $last_check_visitor_time = $shop['last_check_visitor_time'];//上次查看访客时间
        $count_new_visitor = ShopVisitLog::findOne(['shop_id = ' . $shop_id . ' and visit_time >= ' . $last_check_visitor_time, 'columns' => 'count(distinct uid) as num'])['num'];
        $shop = [
            'shop_id' => $data['id'],
            'images' => $data['images'],
            'category_name' => $data['category_name'] ?: '',
            'brief' => $data['brief'],
            'uid' => $data['user_id'],
            'username' => $contact_mark,
            'name' => $data['name'],
            'address' => $data['address'],
            'lng' => $data['lng'],
            'lat' => $data['lat'],
            'contact_number' => $data['contact_number'],
            'url' => $data['url'],
            'status' => $data['status'],
            'created' => $data['created'],
            'address_detail' => $data['address_detail'],
            'address_title' => $data['address_title'],
            'user_info' => ['uid' => $data['user_id'], 'is_auth' => $user_info['is_auth']],
            'new_visitor' => $count_new_visitor
        ];
        //是否收藏
        if (SocialFav::exist('user_id = ' . $uid . ' and item_id = ' . $data['id'] . ' and enable = 1 and type="' . SocialManager::TYPE_SHOP . '"'))
            $shop['is_collected'] = "1";
        else
            $shop['is_collected'] = "0";
        self::$ajax->outRight($shop);
    }

    //我的/他的 店铺列表
    public function list($uid, $to_uid)
    {
        $res = ['data_count' => 0, 'data_list' => [], 'status' => -1, 'trade_info' => (object)[], 'code' => '', 'renew_status' => -1, 'renew_trade_info' => (object)[], 'combo_deadline' => -1, 'combo_status' => -1];
        if ($uid == $to_uid) {
            $where = "user_id=" . $to_uid . " and status<>" . self::status_user_deleted;
        } else {
            $where = "user_id=" . $to_uid . " and status=" . self::status_normal;
        }
        $shop = Shop::findList([$where, 'columns' => 'id as shop_id,user_id as uid,name,brief,province,city,county,address,address_detail,address_title,lng,lat,images,category_name,contact_number,status,created,url,combo_deadline,combo_status']);
        if ($shop) {
            $res['data_count'] = count($shop);
            $res['data_list'] = $shop;
        }
        if ($uid == $to_uid) {
            //首次支付且订单状态为待支付或已支付的订单状态
            $apply = ShopApply::findOne(["user_id=" . $to_uid . " and is_renew=0 and (status=" . self::pay_status_wait_pay . " or status=" . self::pay_status_has_paid . ")", 'order' => 'created desc', 'columns' => 'status,money,trade_no,favorable_money,code,code_owner,deadline']);
            if ($apply) {
                $res['status'] = $apply['status'];
                $res['trade_info'] = [
                    'money' => intval($apply['money']),
                    'trade_no' => $apply['trade_no'],
                    'deadline' => $apply['deadline'],
                    'favorable_money' => intval($apply['favorable_money']),
                    'code' => $apply['code'],
                    'expire' => 0,
                    'user_info' => (object)[]
                ];
                if ($apply['status'] == self::pay_status_wait_pay) {
                    if ($apply['deadline'] <= time()) {
                        $res['status'] = self::pay_status_has_canceled;
                        $res['trade_info']['expire'] = 0;
                    } else {
                        $res['trade_info']['expire'] = intval($apply['deadline'] - time());
                    }
                }
                unset($res['trade_info']['deadline']);
                if ($apply['code']) {
                    $user_info = UserInfo::findOne(['user_id=' . $apply['code_owner'], 'columns' => 'user_id as uid,username,avatar']);//用户信息;
                    $res['trade_info']['user_info'] = $user_info;
                }
            }
            //最后一个待支付/已支付 的续费订单
            $renew_apply = ShopApply::findOne(["user_id=" . $to_uid . " and is_renew=1 and (status=" . self::pay_status_wait_pay . " or status=" . self::pay_status_has_paid . ")", 'order' => 'created desc', 'columns' => 'status,money,trade_no,favorable_money,code,code_owner,deadline']);
            if ($renew_apply) {
                $res['renew_status'] = $renew_apply['status'];
                $res['renew_trade_info'] = [
                    'money' => intval($renew_apply['money']),
                    'trade_no' => $renew_apply['trade_no'],
                    'deadline' => $renew_apply['deadline'],
                    'favorable_money' => intval($renew_apply['favorable_money']),
                    'code' => $renew_apply['code'],
                    'expire' => 0,
                    'user_info' => (object)[]
                ];
                if ($renew_apply['status'] == self::pay_status_wait_pay) {
                    if ($renew_apply['deadline'] <= time()) {
                        $res['renew_status'] = self::pay_status_has_canceled;
                        $res['renew_trade_info']['expire'] = 0;
                    } else {
                        $res['renew_trade_info']['expire'] = intval($renew_apply['deadline'] - time());
                    }
                }
                unset($res['renew_trade_info']['deadline']);
                if ($renew_apply['code']) {
                    $user_info = UserInfo::findOne(['user_id=' . $renew_apply['code_owner'], 'columns' => 'user_id as uid,username,avatar']);//用户信息;
                    $res['renew_trade_info']['user_info'] = $user_info;
                }
            }
        }

        $last_paid_order = ShopApply::findOne(['status=' . self::pay_status_has_paid . " and user_id=" . $to_uid, 'order' => 'created desc', 'columns' => 'combo_deadline,combo_status']);
        if ($last_paid_order) {
            $res['combo_deadline'] = $last_paid_order['combo_deadline'];
            $res['combo_status'] = $last_paid_order['combo_status'];
        } else {
            if ($shop) {
                $res['combo_deadline'] = $shop[0]['combo_deadline'];
                $res['combo_status'] = $shop[0]['combo_status'];
            }
        }
        if ($shop) {
            unset($res['data_list'][0]['combo_deadline']);
            unset($res['data_list'][0]['combo_status']);
        }
        $agent = Agent::findOne(['user_id=' . $to_uid . " and is_merchant=1", 'columns' => 'code']);
        if ($agent) {
            $res['code'] = $agent['code'];
        }

        self::$ajax->outRight($res);
    }

    /**
     * @param $is_detail
     * @param $lng
     * @param $lat
     * @param $page
     * @param $limit
     * @param $area_code
     * 附近店铺
     */
    public function near($is_detail, $lng, $lat, $page, $limit, $area_code = '')
    {
        $where = "status = " . ShopManager::status_normal . " and combo_status=" . self::combo_status_normal;
        /*if ($area_code) {
            $where .= " and area_code='" . $area_code . "'";
        }*/
        if (!$is_detail) {//首页店铺列表
            $list = Shop::findList([$where, 'columns' => "id as shop_id,user_id as uid,brief,name,images,GetDistances(lat,lng,$lat,$lng) as distance", 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => ' fav_cnt desc,distance desc,created desc']);
            if ($list) {
                $shop_ids = array_column($list, 'shop_id');
                $goods_num = ShopGoods::getColumn(['shop_id in (' . implode(',', $shop_ids) . ') and status=' . ShopManager::status_normal, 'columns' => 'shop_id,count(*) as num', 'group' => 'shop_id'], 'num', 'shop_id');
                foreach ($list as $k => $item) {
                    if (empty($goods_num[$item['shop_id']])) {
                        unset($list[$k]);
                    } else {
                        $list[$k]['goods_num'] = $goods_num[$item['shop_id']];
                    }
                }
            }
        } else {
            $list = Shop::findList([$where, 'columns' => "id as shop_id,user_id as uid,brief,address,lng,lat,images,fav_cnt,name,is_hot,category_name,GetDistances(lat,lng,$lat,$lng) as distance", 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'fav_cnt desc,distance desc,created desc']);
            if ($list) {
                $shop_ids = array_column($list, 'shop_id');
                $goods_num = ShopGoods::getColumn(['shop_id in (' . implode(',', $shop_ids) . ') and status=' . ShopManager::status_normal, 'columns' => 'shop_id,count(*) as num', 'group' => 'shop_id'], 'num', 'shop_id');
                foreach ($list as $k => $item) {
                    $goods = ShopGoods::findList(['shop_id=' . $item['shop_id'] . " and status=" . GoodManager::status_normal, 'columns' => 'id,name,images,price,forward_cnt', 'limit' => 3, 'order' => 'forward_cnt desc']);
                    if (!$goods) {
                        unset($list[$k]);
                    } else {
                        $list[$k]['goods_list'] = $goods;
                        $list[$k]['goods_num'] = $goods_num[$item['shop_id']];
                    }
                }
            }
        }
        if ($list) {
            $list = array_values($list);
            $uids = array_column($list, 'uid');
            $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'is_auth,user_id as uid'], 'uid');
            foreach ($list as $k => $v) {
                $list[$k]['user_info'] = $user_info[$v['uid']];
            }
        }
//      $sql = "select *,GetDistances(s.lat,s.lng,$lat,$lng) as distance from shop as s where " . $where . " order by fav_cnt desc,distance desc,created desc limit " . ($page - 1) * $limit . ",$limit";
//      $list = $this->original_mysql->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
//        if ($list) {
//            if (!$is_detail)//首页店铺列表
//            {
//                $res = [];
//                foreach ($list as $k => $v) {
//                    $shop_ids[] = $v['id'];
//                    $res[$v['id']]['shop_id'] = $v['id'];
//                    $res[$v['id']]['uid'] = $v['user_id'];
//                    $res[$v['id']]['name'] = $v['name'];
//                    $res[$v['id']]['images'] = $v['images'];
//                }
//                $goods_nums = $this->original_mysql->query("select shop_id,COUNT(*) as sum from shop_goods where shop_id in ( " . implode(',', $shop_ids) . ") and status =  " . ShopManager::status_normal . " group by shop_id ")->fetchAll(\PDO::FETCH_ASSOC);
//
//                foreach ($goods_nums as $goods_num) {
//                    $tmp[$goods_num['shop_id']] = $goods_num;
//                }
//                foreach ($res as $v) {
//                    //$res[$v['shop_id']]['goods_num'] = $tmp[$v['shop_id']]['sum'] ?  $tmp[$v['shop_id']]['sum'] : "0";
//                    if (!$tmp[$v['shop_id']]['sum'])
//                        unset($res[$v['shop_id']]);
//                    else
//                        $res[$v['shop_id']]['goods_num'] = $tmp[$v['shop_id']]['sum'];
//                }
//                $res = array_values($res);//重建数组索引
//
//            } else //详细店铺列表
//            {
//                $res = [];
//                foreach ($list as $k => $v) {
//                    $shop_ids[] = $v['id'];
//                    $res[$v['id']]['shop_id'] = $v['id'];
//                    $res[$v['id']]['uid'] = $v['user_id'];
//                    $res[$v['id']]['name'] = $v['name'];
//                    $res[$v['id']]['brief'] = $v['brief'];
//                    $res[$v['id']]['images'] = $v['images'];
//                    $res[$v['id']]['category_name'] = $v['category_name'];
//                    $res[$v['id']]['is_hot'] = $v['is_hot'];
//                    $res[$v['id']]['distance'] = $v['distance'];
//                    $res[$v['id']]['address'] = $v['address'];
//                    $res[$v['id']]['lng'] = $v['lng'];
//                    $res[$v['id']]['lat'] = $v['lat'];
//                    $res[$v['id']]['fav_cnt'] = $v['fav_cnt'];//店铺收藏数
//                    $goods_list_per_shop = $this->original_mysql->query("select id,name,images,price from shop_goods where shop_id = " . $v['id'] . ' and status = ' . ShopManager::status_normal . ' order by forward_cnt desc limit 3')->fetchAll(\PDO::FETCH_ASSOC);
//                    $res[$v['id']]['goods_list'] = $goods_list_per_shop ? $goods_list_per_shop : [];
//                }
//                $goods_nums = $this->original_mysql->query("select shop_id,COUNT(*) as sum from shop_goods where shop_id in ( " . implode(',', $shop_ids) . ") and status =  " . ShopManager::status_normal . " group by shop_id ")->fetchAll(\PDO::FETCH_ASSOC);
//                foreach ($goods_nums as $goods_num) {
//                    $tmp[$goods_num['shop_id']] = $goods_num;
//                }
//                $uids = array_column($res, 'uid');
//                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'is_auth,user_id as uid'], 'uid');
//                foreach ($res as $v) {
//                    //$res[$v['shop_id']]['goods_num'] = $tmp[$v['shop_id']]['sum'] ?  $tmp[$v['shop_id']]['sum'] : "0";
//                    $res[$v['shop_id']]['user_info'] = $user_info[$v['uid']];
//                    if (!$tmp[$v['shop_id']]['sum'])
//                        unset($res[$v['shop_id']]);
//                    else
//                        $res[$v['shop_id']]['goods_num'] = $tmp[$v['shop_id']]['sum'];
//                }
//
//                $res = array_values($res);//重建数组索引
//
//            }
//        } else {
//            $res = [];
//        }
        Ajax::outRight($list ? $list : []);
    }

    //支付完成
    /**
     * @param $uid --用户ID
     * @param $paid_number --支付流水号
     * @param $trade_no --订单号
     * @return bool
     */
    public function paySuccess($uid, $paid_number, $trade_no)
    {
        $apply = ShopApply::findOne(["trade_no='" . $trade_no . "' and user_id=" . $uid . " and status=" . self::pay_status_wait_pay, 'columns' => 'money,code_owner,bonus,id,user_id,bonus_detail,is_renew,combo_deadline']);
        if ($apply) {
            $money = $apply['money'];
            try {
                $this->di->getShared("original_mysql")->begin();
                $income_id = [];//收益

                //更新订单状态
                $shop_apply_update_data = [
                    'paid_number' => $paid_number,
                    'paid_time' => time(),
                    'combo_status' => self::combo_status_normal,
                    'is_paid' => 1,
                    'status' => self::pay_status_has_paid,
                    'modify' => time()
                ];
                //续费订单
                if ($apply['is_renew']) {
                    $shop_apply_update_data['combo_status'] = self::combo_status_normal;
                    //更新店铺状态
                    Shop::updateOne(['combo_status' => self::combo_status_normal, 'combo_deadline' => $apply['combo_deadline'], 'modify' => time()], 'user_id=' . $uid);
                    //更新当前有效的订单为续费成功状态
                    ShopApply::updateOne(['combo_status' => self::combo_status_renew], 'user_id=' . $uid . " and status=" . self::combo_status_normal);
                }
                //更改订单状态
                if (ShopApply::updateOne($shop_apply_update_data, "trade_no='" . $trade_no . "'")
                ) {
                    if ($money > 0) {
                        if ($apply['bonus_detail']) {
                            $bonus_detail = json_decode($apply['bonus_detail'], true);
                            $level = 1;
                            foreach ($bonus_detail as $u => $bo) {
                                $tmp_income_id = AgentManager::init()->income($u, $uid, $bo, $trade_no, AgentManager::income_income_type_shop, 0, $level);
                                if (!$tmp_income_id) {
                                    throw new \Exception("上级合伙人入账记录失败:$u:$bo");
                                }
                                $income_id[] = $tmp_income_id;
                                $level++;
                            }
                        }

                        //扣钱
                        $transfer_data = [
                            'uid' => intval($uid),
                            'type' => 1,
                            'sub_type' => 10,
                            'money' => intval($money),
                            "transferway" => "",
                            "description" => "开店",
                            "created" => time(),
                            "out_payid" => '',
                            'payid' => $trade_no
                        ];
                        $res = Request::getPost(Request::WALLET_BALANCE_TRANSFER, [
                            'to_uid' => Request::$system_money_account,
                            'uid' => $uid,
                            'money' => $money,
                            'record' => json_encode($transfer_data, JSON_UNESCAPED_UNICODE)
                        ], false);
                        if ($res && $res['curl_is_success']) {
                            $content = json_decode($res['data'], true);
                            if ($content['code'] && $content['code'] == 501) {
                                throw new \Exception("余额不足");
                            } else if (!($content['code'] && $content['code'] == 200)) {
                                throw new \Exception("余额扣钱失败:" . var_export($res, true));
                            }
                        } else {
                            throw new \Exception("余额扣钱失败:" . var_export($res, true));
                        }
                    }
                } else {
                    //  Debug::log("更新订单状态失败:" . var_export($_REQUEST, true), 'order');
                    throw new \Exception("更新订单状态失败:");
                }
                if ($agent = Agent::findOne(["user_id=" . $apply['user_id'], 'columns' => 'is_merchant,parent_merchant'])) {
                    // 合作伙伴信息更新
                    Agent::updateOne([
                        'is_merchant' => 1,
                        'parent_merchant' => $apply['code_owner'] == $apply['user_id'] ? 0 : $apply['code_owner']
                    ], 'user_id=' . $apply['user_id']);
                } else {
                    // 生成合作伙伴
                    Agent::insertOne([
                        "user_id" => $apply['user_id'],
                        'is_merchant' => 1,
                        'is_partner' => 0,
                        'parent_merchant' => $apply['code_owner'] == $apply['user_id'] ? 0 : $apply['code_owner'],
                        'code' => AgentManager::init()->createCode(),
                        'created' => time()
                    ]);
                }

                $this->di->getShared("original_mysql")->commit();
                //关闭计时任务
                $res = OrderManager::init()->cancelTask($trade_no);
                Debug::log("取消定时任务:" . var_export($res, true), 'task');
                if (!TEST_SERVER) {
                    //发送消息给审核人员
                    ServerAPI::init()->sendBatchMsg(ImManager::ACCOUNT_SYSTEM, json_encode([50000, 50037, 60034]), 0, json_encode(['msg' => (!$apply['is_renew'] ? "有人开店缴费成功:" : "有人开店续费成功:") . (sprintf('%.2f', $money / 100)) . ",赶紧登录后台查看吧"]));
                }
                return ['income_id' => $income_id];
            } catch (\Exception $e) {
                $this->di->getShared("original_mysql")->rollback();
                Debug::log("支付处理失败:" . var_export($e->getMessage(), true), 'payment');
                $this->setErrorMsg(var_export($e->getMessage(), true));
                return false;
            }
        } else {
            Debug::log("订单不存在:" . var_export($_REQUEST, true), 'payment');
            $this->setErrorMsg("订单不存在");
        }
        return false;
    }


    //提交订单
    public function createOrder($uid, $code = '')
    {
        $apply = ShopApply::findOne(['user_id=' . $uid . " and (status=" . self::pay_status_has_paid . " or status=" . self::pay_status_wait_pay . ")", 'order' => 'created desc', 'columns' => 'status,deadline']);
        $is_renew = 0; //是否属于续费订单
        $time = time();
        $combo_deadline = strtotime(date('Ymd')) + self::$combo_deadline + 86400 * 2; //套餐到期时间

        //生成过订单
        if ($apply) {
            //待支付
            if ($apply['status'] == self::pay_status_wait_pay && $apply['deadline'] > time()) {
                self::$ajax->outError(Ajax::ERROR_SUBMIT_REPEAT);
            }

            //有订单已经支付过  说明是续费的
            if ($old_apply = ShopApply::findOne(["user_id=" . $uid . " and status=" . self::pay_status_has_paid, 'order' => 'created desc', 'columns' => 'combo_deadline,code,combo_status'])) {
                $code = $old_apply['code'];
                $is_renew = 1;
                //老订单还没有到合同套餐期时间
                if ($old_apply['combo_status'] == self::combo_status_normal) {
                    $combo_deadline = $old_apply['combo_deadline'] + self::$combo_deadline + 86400;
                } else {

                }
            }
        }
        //已提交审核 并且等待付款或者等待审核
//        if ($apply && (($apply['status'] == self::pay_status_wait_pay && $apply['deadline'] > time()) || $apply['status'] == self::pay_status_has_paid)) {
//            self::$ajax->outError(Ajax::ERROR_SUBMIT_REPEAT);
//        }

        //      $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'is_auth,true_name']);
//        //没有实名认证
//        if ($user_info['is_auth'] != 1) {
//            self::$ajax->outError(Ajax::ERROR_NO_AUTH);
//        }
        //配置
        $setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
        $setting = $setting['shop'];

        $favorable_money = 0;//优惠金额
        $total_money = 0;//总金额
        $bonus = 0;//总奖金
        $bonus_detail = [];//奖金详情
        $level_second = 0;//二级推荐人
        $level_third = 0;//三级推荐人

        if ($code) {
            if (!$agent = Agent::findOne(["code='" . $code . "'", 'columns' => 'user_id,is_merchant,is_offline,parent_merchant'])) {
                self::$ajax->outError(Ajax::INVALID_INVITE_CODE);
            }
            $money = $setting['has_code'];
            $favorable_money = ($setting['no_code'] - $money);
            $total_money = $setting['no_code'];
            //推荐人不是线下推荐人
            if (!$agent['is_offline']) {
                if ($setting['base']) {
                    $bonus_detail[$agent['user_id']] = $setting['base'];
                    $bonus = $setting['base'];
                }
                //店铺的上级邀请人 拿提成
                if ($agent['parent_merchant'] && $setting['second_base']) {

                    $second_inviter = Agent::findOne(["user_id=" . $agent['parent_merchant'] . " and is_merchant=1 and is_offline=0", 'columns' => 'user_id,parent_merchant']);
                    //二级提成
                    if ($second_inviter) {
                        $bonus_detail[$second_inviter['user_id']] = $setting['second_base'];
                        $bonus += $setting['second_base'];
                        $level_second = $second_inviter['user_id'];
                        //三级提成
                        if ($setting['third_base'] && $second_inviter['parent_merchant'] && $third_inviter = Agent::findOne(["user_id=" . $second_inviter['parent_merchant'] . " and is_merchant=1 and is_offline=0", 'columns' => 'user_id'])) {
                            $bonus_detail[$third_inviter['user_id']] = $setting['third_base'];
                            $bonus += $setting['third_base'];
                            $level_third = $third_inviter['user_id'];
                        }
                    }

                }
            }

        } else {
            $money = $setting['no_code'];
            $total_money = $money;
        }
        $user_info = [];//邀请码所属用户
        $data = [
            "user_id" => $uid,
            "code" => $code,
            "money" => $money,
            "favorable_money" => $favorable_money,
            "total_money" => $total_money,
            "created" => $time,
            'trade_no' => "OS" . OrderManager::init()->generateOrderNumber(),
            'bonus' => $bonus,
            'bonus_detail' => json_encode($bonus_detail),
            'deadline' => $time + self::$pay_expire_time,
            'level_second' => $level_second,
            'level_third' => $level_third,
            'combo_deadline' => $combo_deadline, //套餐结束日期，
            'is_renew' => $is_renew
        ];
        if ($code) {
            $data['code_owner'] = $agent['user_id'];
            $user_info = UserInfo::findOne(['user_id=' . $agent['user_id'], 'columns' => 'user_id as uid,username,avatar']);
        }
        //免费
        if ($money == 0) {
            $data['paid_time'] = $time;
            $data['is_paid'] = 1;
            $data['status'] = self::pay_status_has_paid;
        }
        if (ShopApply::insertOne($data)) {
            if ($data['status'] == self::pay_status_wait_pay) {
                //开启计时任务
                OrderManager::init()->startTask($data['trade_no'], date('Y-m-d H:i:s', $data['deadline']));
            }
            return [
                "money" => intval($money),
                'favorable_money' => intval($favorable_money),
                'trade_no' => $data['trade_no'],
                'expire' => $data['deadline'] - time(),
                'user_info' => $user_info ? $user_info : (object)[]
            ];
        }
        return false;
    }

    //审核
    public function check($shop_id, $is_success, $check_user, $reason = '')
    {
        $info = Shop::findOne(['id=' . $shop_id . " and status=" . self::status_checking]);
        if (!$info) {
            return false;
        }
        //审核通过
        if ($is_success) {
            //插入审核日志
            ShopCheckLog::insertOne([
                'shop_id' => $shop_id,
                'check_user' => $check_user,
                'check_time' => time(),
                'status' => 1,
                'info' => json_decode($info)]);
            //更新审核状态
            Shop::updateOne(['status' => self::status_normal, "modify" => time()], 'id=' . $shop_id);

            //发送im消息

            //

        } else {
            ShopCheckLog::insertOne([
                'apply_id' => $shop_id,
                'check_user' => $check_user,
                'reason' => $reason,
                'check_time' => time(),
                'status' => 0,
                'info' => json_decode($info)]);
            //更新审核状态
            AgentApply::updateOne(['status' => self::status_normal, "modify" => time()], 'id=' . $shop_id);
            //发送im消息

        }
    }

    //店铺概要信息：主要是店铺商品列表
    public function outlineInfo($uid, $shop_id)
    {
        $shop = Shop::findOne(['id = ' . $shop_id, 'columns' => 'id,user_id,name,images,brief']);
        $shop['owner_info'] = UserInfo::findOne(['user_id = ' . $shop['user_id'], 'columns' => 'user_id as uid,is_auth']);
        unset($shop['user_id']);
        $goods = ShopGoods::findList(['shop_id = ' . $shop['id'] . ' and status = ' . GoodManager::status_normal, 'columns' => 'id,name,price,unit,images', 'order' => 'like_cnt desc', 'limit' => 3]);
        if ($goods)
            $shop['goods_list'] = $goods;
        else
            $shop['goods_list'] = [];
        return $shop;
    }

    //到期时间检测
    public function checkDeadline()
    {
        $p = 1;
        $limit = 1000;
        $list = ShopApply::findList(['combo_status=' . self::combo_status_normal . " and combo_deadline<=" . time(), 'columns' => 'id,user_id', 'offset' => ($p - 1), 'limit' => $limit]);
        while ($list) {
            $uids = array_column($list, 'user_id');
            $ids = array_column($list, 'id');
            try {
                $this->original_mysql->begin();
                if (ShopApply::updateOne(['combo_status' => self::combo_status_deadline], 'id in (' . implode(',', $ids) . ')')) {
                    //更新店铺信息
                    Shop::updateOne(["combo_status" => self::combo_status_deadline], 'user_id in (' . implode(',', $uids) . ")");
                }
                // todo 发系统消息
                $this->original_mysql->commit();

            } catch (\Exception $e) {
                $this->original_mysql->rollback();
                Debug::log("店铺到期更新失败：" . var_export($e->getMessage(), true), 'shop_error');
                break;
            }
            $p++;
            $list = ShopApply::findList(['combo_status=' . self::combo_status_normal . " and combo_deadline<=" . time(), 'columns' => 'id,user_id', 'offset' => ($p - 1), 'limit' => $limit]);
        }
    }

}