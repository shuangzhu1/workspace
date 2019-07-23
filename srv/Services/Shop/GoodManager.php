<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/16
 * Time: 13:49
 */

namespace Services\Shop;


use Models\Shop\Shop;
use Models\Shop\ShopGoods;
use Models\Social\SocialFav;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Services\Site\CacheSetting;
use Services\Social\SocialManager;
use Util\Ajax;
use Util\Validator;

class GoodManager extends Plugin
{
    private static $instance = null;

    const status_system_deleted = 0;//系统删除
    const status_normal = 1; //正常
    const status_checking = 2; //审核中
    const status_check_fail = 3; //审核失败
    const status_user_deleted = 4; //用户删除
    const status_off = 5; //用户下架

    private static $ajax = null;

    static $status = [
        self::status_system_deleted => '系统删除',
        self::status_normal => "正常",
        self::status_checking => "审核中",
        self::status_check_fail => "审核失败",
        self::status_user_deleted => "用户删除",
        self::status_off => "还未上架",
    ];

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        self::$ajax = new Ajax();
    }

    /**添加商品
     * @param $uid
     * @param $shop_id
     * @param $name
     * @param $price
     * @param string $brief
     * @param string $imgs
     * @param string $url
     * @param string $unit
     * @param int $sale
     * @return int
     */
    public function add($uid, $shop_id, $name, $price, $brief = '', $imgs = '', $url = '', $unit = '', $sale = 1)
    {
        //商品数量限制 todo

        $shop = Shop::exist("id=" . $shop_id . " and user_id=" . $uid . " and (status=" . self::status_normal . " or status=" . self::status_checking . ")");
        if (!$shop) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if ($url) {
            //清空
            if ($url == 'empty') {
                $url = '';
            } else {
                if (!Validator::validateUrl($url)) {
                    self::$ajax->outError(Ajax::INVALID_WEBSITE);
                }
            }
        }
        $time = time();
        $data = [
            'user_id' => $uid,
            'name' => $name,
            'brief' => $brief ? $brief : '',
            'url' => $url,
            'price' => $price,
            'unit' => $unit,
            'images' => $imgs,
            "created" => $time,
            'modify' => $time,
            "shop_id" => $shop_id
        ];
        if ($sale == 1) {
            $data['status'] = self::status_normal;
        } else {
            $data['status'] = self::status_off;
        }
        $good_id = ShopGoods::insertOne($data);
        if ($good_id) {
            //添加
            $data['id'] = $good_id;
            $cache = new CacheSetting();
            $cache->set(CacheSetting::PREFIX_GOOD_DETAIL, $good_id, $data);
            return true;
        }
        return false;
    }

    /**编辑商品
     * @param $uid
     * @param $good_id
     * @param $name
     * @param $price
     * @param string $brief
     * @param string $imgs
     * @param string $url
     * @param string $unit
     * @param int $sale
     * @return bool
     */
    public function edit($uid, $good_id, $name, $price, $brief = '', $imgs = '', $url = '', $unit = '', $sale = 1)
    {
        $good = ShopGoods::exist("id=" . $good_id . " and user_id=" . $uid . " and (status=" . self::status_normal . " or status=" . self::status_checking . " or status=" . self::status_off . ")");
        if (!$good) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $data = [];
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
        if ($name) {
            $data['name'] = $name;
        }
        if ($price) {
            $data['price'] = $price;
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
        if ($unit) {
            $data['unit'] = $unit;
        }
        if ($sale == 1) {
            $data['status'] = self::status_normal;
        } else if ($sale == 2) {
            $data['status'] = self::status_off;
        } else if ($sale == 3) {
            $data['status'] = self::status_user_deleted;
        }
        if ($data) {
            $data['modify'] = time();
            if (ShopGoods::updateOne($data, 'id=' . $good_id)) {
                if ($sale == 3) {
                    //删除缓存
                    $cache = new CacheSetting();
                    $cache->remove(CacheSetting::PREFIX_GOOD_DETAIL, $good_id);
                } else {
                    //更新缓存
                    $cache = new CacheSetting();
                    $cache->set(CacheSetting::PREFIX_GOOD_DETAIL, $good_id, ShopGoods::findOne(['id=' . $good_id]));
                }
                return true;
            }
            return false;
        }
        return true;
    }

    /**商品详情
     * @param $uid
     * @param $good_id
     */
    public function detail($uid, $good_id)
    {
        $cache = new CacheSetting();
        $data = $cache->get(CacheSetting::PREFIX_GOOD_DETAIL, $good_id);
        if (!$data) {
            $good = ShopGoods::findOne(["id=" . $good_id]);
            if (!$good) {
                self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $data = $good;
            $cache->set(CacheSetting::PREFIX_GOOD_DETAIL, $good_id, $good);
            // $good = ShopGoods::findOne(["id=" . $good_id . " and (status=" . self::status_normal . " or status=" . self::status_checking . " or status=" . self::status_off . ")", 'columns' => 'id as good_id,shop_id,images,brief,user_id as uid,name,price,unit,url,status,created']);
        }
        $shop = Shop::findOne(['id=' . $data['shop_id'], 'columns' => 'status']);
        /*  if ($shop['status'] == ShopManager::status_system_deleted) {
              self::$ajax->outError(Ajax::ERROR_SHOP_HAS_BEEN_SHIELD);
          } else if ($shop['status'] == ShopManager::status_user_deleted) {
              self::$ajax->outError(Ajax::ERROR_SHOP_HAS_BEEN_DELETED);
          }*/
        $users = Users::findOne(['id=' . $data['user_id'], 'columns' => 'username,avatar']);


        $good = [
            'good_id' => $data['id'],
            'shop_id' => $data['shop_id'],
            'shop_status' => $shop['status'],
            'images' => $data['images'],
            'brief' => $data['brief'],
            'uid' => $data['user_id'],
            'avatar' => $users['avatar'],
            'username' => $users['username'],
            'name' => $data['name'],
            'price' => $data['price'],
            'unit' => $data['unit'],
            'fav_cnt' => $data['fav_cnt'],
            'url' => $data['url'],
            'status' => $data['status'],
            'created' => $data['created'],
        ];
        //是否收藏
        if (SocialFav::exist('user_id = ' . $uid . ' and item_id = ' . $good_id . ' and enable = 1 and type="' . SocialManager::TYPE_GOOD . '"'))
            $good['is_collect'] = "1";
        else
            $good['is_collect'] = "0";

        self::$ajax->outRight($good);
    }

    /**商品列表
     * @param $shop_id
     * @param $uid
     * @param $status
     * @param int $page
     * @param int $limit
     */
    public function list($shop_id, $uid, $status, $page = 1, $limit = 20)
    {
        if (!$shop = Shop::findOne(['id=' . $shop_id . ' and status=' . self::status_normal, 'columns' => 'user_id'])) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $res = ['data_count' => 0, 'data_list' => []];
        $where = 'shop_id=' . $shop_id;

        //查看别人的只能查看展示的商品
        if ($uid != $shop['user_id']) {
            $where .= " and status=" . self::status_normal;
        } else {
            if ($status == 1) {
                $where .= " and status=" . self::status_normal;
            } elseif ($status == 2) {
                $where .= " and status=" . self::status_off;
            }
        }
        $res['data_count'] = ShopGoods::dataCount($where);
        $list = ShopGoods::findList([$where, 'columns' => 'id as good_id,fav_cnt,shop_id,images,brief,user_id as uid,name,price,unit,url,status,created', 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
        if ($list) {
            $res['data_list'] = $list;
        }
        self::$ajax->outRight($res);
    }
}