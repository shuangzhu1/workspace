<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/26
 * Time: 16:41
 */

namespace Services\User;


use Models\Shop\Shop;
use Models\Shop\ShopGoods;
use Models\Site\AreaCity;
use Models\Square\RedPackage;
use Models\Square\RedPackageFestival;
use Models\Square\RedPackagePickLog;
use Models\Square\RedPackageTaskLog;
use Models\Square\RedPackageTaskRules;
use Models\Statistics\PackageDayStat;
use Models\System\SystemRedPackageAds;
use Models\User\UserInfo;
use Models\User\Users;
use Models\User\UserTags;
use Models\Vip\VipPrivileges;
use Phalcon\Mvc\User\Plugin;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Shop\GoodManager;
use Services\Shop\ShopManager;
use Services\Site\CacheSetting;
use Services\Site\CashRewardManager;
use Services\Site\SiteKeyValManager;
use Services\Task\TaskManager;
use Services\User\Square\AbstractTask;
use Services\User\Square\SquareTask;
use Util\Ajax;
use Util\Debug;
use Util\LatLng;
use Util\Probability;
use Util\Time;

class SquareManager extends Plugin
{
    private static $instance = null;
    public $ajax = null;
    private static $task_url = 'http://127.0.0.1:4346/';//任务调用地址

    const TYPE_GOODS = 1;//商品
    const TYPE_ADS = 2;//普通广告

    const MEDIA_TYPE_TEXT = 1; //纯文本
    const MEDIA_TYPE_VIDEO = 2; //小视频
    const MEDIA_TYPE_PICTURE = 3; //图片

    //红包状态
    const STATUS_NORMAL = 1;//正常
    const STATUS_PICKED_OUT = 3;//已领完

    //红包显示范围
    const RANGE_DEFAULT = 0;//系统默认范围
    const RANGE_COUNTRY = 1;//全国
    const RANGE_CITY = 2;//全市


    //节假日红包状态
    const festival_deleted = 0; //被删除
    const festival_wait_publish = 1;//待发布
    const festival_has_published = 2;//已发布
    const festival_publish_fail = 3;//发布失败


    //红包广场每日领取龙钻上限
    const SQUARE_DIAMOND_PICK_LIMIT_PER_DAY = 8;

    //红包类型
    public static $type = [
        self::TYPE_GOODS,
        self::TYPE_ADS,
    ];
    public static $type_name = [
        self::TYPE_GOODS => '商品',
        self::TYPE_ADS => '普通',
    ];
    // 数据类型
    public static $media_type = [
        self::MEDIA_TYPE_TEXT,
        self::MEDIA_TYPE_PICTURE,
        self::MEDIA_TYPE_VIDEO,
    ];
    // 显示范围
    public static $range_type = [
        self::RANGE_DEFAULT,
        self::RANGE_COUNTRY,
        self::RANGE_CITY,
    ];
    public static $range_type_name = [
        self::RANGE_DEFAULT => "默认",
        self::RANGE_COUNTRY => "全国",
        self::RANGE_CITY => "全市",
    ];


    public function __construct($is_cli = false)
    {
        if (!$is_cli) {
            $this->ajax = new Ajax();
        }
    }

    //媒体类型
    public static function init($is_cli = false)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    //发红包
    /**
     * @param $uid -用户id
     * @param $type -类型 1-商品 2-普通
     * @param $item_id -数据id【如商品id】
     * @param $content -内容
     * @param $media_type -媒体类型【1-纯文本 2-视频 3-图片】
     * @param $media -媒体
     * @param $package_id -红包id
     * @param $package_info -红包信息{"id":"233434444","money":"2333","num":"4","deadline":"8888"}
     * @param $lng -经度
     * @param $lat -纬度
     * @param $area_code -区域码
     * @param $range_type -显示范围
     * @param $is_rob -是否机器人
     * @param $is_festival -是否节日红包
     * @return bool
     */
    public function addPackage($uid, $type, $item_id, $content, $media_type, $media, $package_id, $package_info, $lng, $lat, $area_code, $range_type, $is_rob = false, $is_festival = false)
    {
        $extra = '';
        $package_info = json_decode($package_info, true);
        if (!$package_info) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "红包信息有误");
        }
        //商品红包
        if ($type == self::TYPE_GOODS) {
            if (!$item_id) {
                $this->ajax->outError(Ajax::INVALID_PARAM, "缺少商品id");
            }
            $goods = ShopGoods::findOne(['id=' . $item_id, 'columns' => 'shop_id,user_id as uid,name,price,brief,url,images']);
            if (!$goods) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $shop = Shop::findOne(['id=' . $goods['shop_id'], 'columns' => 'name,lng,lat']);
//            $lng = $shop['lng'];
//            $lat = $shop['lat'];
            $goods['shop_name'] = $shop['name'];
            $extra = $goods;

        } //普通红包
        else {

        }
        if (!$lng || !$lat) {
            $lat = $lng = 0;
            // $this->ajax->outError(Ajax::INVALID_PARAM, "经纬度无效");
        }
        $data = [
            "package_id" => $package_id,
            'package_info' => json_encode($package_info),
            'lng' => $lng,
            'lat' => $lat,
            'type' => $type,
            'item_id' => $item_id ? intval($item_id) : 0,
            'content' => $content ? $content : '',
            'media_type' => $media_type,
            'media' => $media,
            'user_id' => $uid,
            'extra' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : '',
            'money' => $package_info['money'],
            'deadline' => $package_info['deadline'],
            'created' => Time::getMillisecond(),
            'created_ymd' => date('Ymd'),
            'area_code' => $area_code,
            'range_type' => $range_type ? intval($range_type) : 0,
            'num' => $package_info['num'],
            'is_festival' => $is_festival ? 1 : 0,
        ];
        if ($is_rob) {
            $data['is_rob'] = 1;
        }
        try {
            $res = RedPackage::insertOne($data);

            if ($res) {
                //设置缓存数据
                $r = $this->di->get("redis")->hSet(CacheSetting::KEY_RED_PACKAGE, $package_id, $data['package_info']);
                Debug::log("redis_set_key:table:" . CacheSetting::KEY_RED_PACKAGE . ",key:" . $package_id . ",package_info:" . $data['package_info'] . ",result:" . $r, "debug");


                //记录个人发出金额
                $this->di->get("redis")->hIncrBy(CacheSetting::KEY_RED_PACKAGE_EXPENSE, $uid, $package_info['money']);

                if (!$is_rob) {
                    //送红包领取次数
                    SquareTask::init()->executeRule($uid, device_id, SquareTask::TASK_ADD_RED_PACKAGE);
                }

                return true;


            }

            return false;
        } catch (\Exception $e) {
            Debug::log("发布红包失败:" . var_export($e->getMessage(), true), "error");
            return false;
        }
    }

    //发起抢红包请求
    public function pick($uid, $package_id, $extend, $is_pick)
    {
        $res = ['code' => -1, 'data' => ''];
        $redis = $this->di->get("redis");
        $count = $redis->hGet(CacheSetting::KEY_PACKAGE_PICK_COUNT . date('Ymd'), $uid);
        $count = $count ? $count : 0;
        $limit = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting');
        $limit_count = $limit['day_pick_limit'];

        //vip做相应处理
        $user = UserInfo::findOne(["user_id=" . $uid, 'columns' => 'is_vip']);
        if ($user['is_vip']) {
            $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'package_pick_count']);
            $limit_count = $vip_privileges['package_pick_count'];
        }

        //今天临时添加次数
        $extra_user_count = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_EXTRA_COUNT . date('Ymd'), $uid); //个人额外添加次数
        $extra_user_count = $extra_user_count ? $extra_user_count : 0;
        //$extra_device_count = device_id ? $redis->hGet(CacheSetting::KEY_RED_PACKAGE_EXTRA_COUNT . date('Ymd'), device_id) : 0; //设备额外添加次数
        // $extra_device_count = $extra_device_count ? $extra_device_count : 0;


        //$limit_count += ($extra_user_count > $extra_device_count ? $extra_device_count : $extra_user_count);
        $limit_count += $extra_user_count;

        //永久添加次数
        $extra_user_count = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, $uid); //个人额外添加永久次数
        $extra_user_count = $extra_user_count ? $extra_user_count : 0;
        //  $extra_device_count = device_id ? $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, device_id) : 0; //设备额外添加永久次数
        // $extra_device_count = $extra_device_count ? $extra_device_count : 0;
        //$limit_count += ($extra_user_count > $extra_device_count ? $extra_device_count : $extra_user_count);
        $limit_count += $extra_user_count;


        if ($count && $count >= $limit_count) {
            //  $this->ajax->outError(Ajax::ERROR_PACKAGE_BEYOND_LIMIT, "今日红包领取次数已达上限");
            Debug::log("红包领取次数已达上限【用户】:uid->$uid,count:$count,limit:" . $limit_count . "", 'log');
            $this->ajax->outRight(['code' => 100, 'data' => '今日红包领取次数已达上限']);
        }
        //有设备号 根据设备号来做限制
        if (device_id) {
            $device_count = $redis->hGet(CacheSetting::KEY_PACKAGE_PICK_COUNT . date('Ymd'), device_id);
            $device_count = $device_count ? $device_count : 0;
            if ($device_count && $device_count >= $limit_count) {
                //  $this->ajax->outError(Ajax::ERROR_PACKAGE_BEYOND_LIMIT, "今日红包领取次数已达上限");
                Debug::log("红包领取次数已达上限【设备】:uid->$uid,count:$device_count,limit:" . $limit_count . "", 'log');
                $this->ajax->outRight(['code' => 100, 'data' => '今日红包领取次数已达上限']);
            }
        }

        if ($is_pick) {
            $data = ['uid' => $uid, 'redid' => intval($package_id), 'device_id' => device_id, 'check_f' => 1];
            if ($extend) {
                $data['extend'] = $extend;
            }
            $result = Request::getPost(Base::PACKAGE_PICK, $data);
            if ($result && $result['curl_is_success']) {
                //有设备号 根据设备号来做限制
                // if (device_id) {
                // $redis->hIncrBy(CacheSetting::KEY_PACKAGE_PICK_COUNT . date('Ymd'), device_id, 1);
                //  }
                $content = json_decode($result['data'], true);
                $res['code'] = $content['code'];
                $res['data'] = $content['data'];
                $this->ajax->outRight($res);
            } else {
                $this->ajax->outError(Ajax::FAIL_SEND, "请求失败" . $result['data']);
            }
        } else {
            $status = Request::getPost(Base::PACKAGE_STATUS, ['uid' => $uid, 'redid' => $package_id]);

            if ($status && $status['curl_is_success']) {
                $content = json_decode($status['data'], true);
                $res['code'] = $content['code'];
                $res['data'] = $content['data'];
                $this->ajax->outRight($res);
            } else {
                $this->ajax->outError(Ajax::FAIL_SEND, "请求失败：" . $status['data']);
            }
        }
    }

    //抢红包成功
    /**
     * @param $uid -用户id
     * @param $package_id -红包id
     * @param $time -抢红包成功时间
     * @param $money -金额
     * @param $device_id -设备号
     * @return bool
     */
    public function pickSuccess($uid, $package_id, $time, $money, $device_id = '')
    {
        $redis = self::getDI()->get("redis");
        try {
            //获取红包总个数
            $package_info = $redis->hGet(CacheSetting::KEY_RED_PACKAGE, $package_id);
            $package_info = json_decode($package_info);
            //红包领取数量加1
            $num = $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_PICK_COUNT, $package_id, 1);
            //已经领完了 更新红包状态为已领完
            if ($num >= $package_info->num && $package_info) {
                RedPackage::updateOne(["status" => self::STATUS_PICKED_OUT], "package_id='" . $package_id . "'");
            }
            //个人领取红包数加1
            $redis->hIncrBy(CacheSetting::KEY_PACKAGE_PICK_COUNT . date('Ymd'), $uid, 1);
            //有设备号
            if ($device_id) {
                $redis->hIncrBy(CacheSetting::KEY_PACKAGE_PICK_COUNT . date('Ymd'), $device_id, 1);
            }


            //更新个人领取记录
            $pick_list = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PICK_LIST . date('Ymd'), $uid);
            //这个人这天没有领过记录
            if (!$pick_list) {
                $pick_list = [$package_id => $time];
            } else {
                $pick_list = json_decode($pick_list, true);
                $pick_list[$package_id] = $time;
            }
            $pick_list = json_encode($pick_list);
            $redis->hSet(CacheSetting::KEY_RED_PACKAGE_PICK_LIST . date('Ymd'), $uid, $pick_list);


            //记录个人领取金额
            $redis->hIncrBy(CacheSetting::KEY_RED_PACKAGE_INCOME, $uid, $money);

            //记录日志
            $res = RedPackagePickLog::insertOne(['user_id' => $uid, 'package_id' => $package_id, 'money' => $money, 'created' => $time, 'device_id' => $device_id]);

            //记录最后一次的领取记录
            $last_record = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_USER_LAST_PICK, $uid);
            $config = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting');
            $time = time();//当前时间戳
            $flush_time = 0;//下次刷新时间
            $count = 0;//迭代的次数

            //之前有记录
            if ($last_record) {
                $last_record = json_decode($last_record, true);

                //最后一次刷新的广场红包记录
                $last_show_list = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_SHOW_LIST, $uid);
                if ($last_show_list) {
                    $last_show_list = json_decode($last_show_list, true);
                    //和上次领取的红包在同一次 刷新次数不加1 刷新时间不变
                    if (in_array($last_record['id'], $last_show_list)) {
                        $flush_time = $last_record['flush_time'];
                        $count = $last_record['count'];
                    } else {

                        //到了清零时间
                        if ((time() - $last_record['last_time']) >= intval($config['clear_time']) * 60) {
                            $flush_time = $time + $config['increase_time'];
                            $count = 1;
                        } else {
                            $count = $last_record['count'] + 1;
                            $flush_time = $time + pow(2, $count) * intval($config['increase_time']);

                        }
                    }
                }
            } //之前没有记录
            else {
                $last_record = [];
                $flush_time = $time + intval($config['increase_time']);
                $count = 1;
            }
            $last_record['count'] = $count;
            $last_record['flush_time'] = $flush_time;
            $last_record['last_time'] = $time;
            $last_record['id'] = $package_id;
            // Debug::log("record:" . var_export($last_record, true), 'debug');
            $redis->hSet(CacheSetting::KEY_RED_PACKAGE_USER_LAST_PICK, $uid, json_encode($last_record));

            return true;
        } catch (\Exception $e) {
            Debug::log("请红包记录插入失败:" . var_export($e->getMessage(), true), 'error');
            return false;
        }
    }

    /**红包列表
     * @param $uid -用户id
     * @param $lng -经度
     * @param $lat -纬度
     * @param $area_code -区域码
     * @return array
     */
    public function packageList($uid, $lng, $lat, $area_code)
    {

        $res = ['data_list' => [], 'data_count' => 0, 'enable' => 1];
        $redis = $this->di->get("redis");
//        //暂时不考虑 负值
//        $new_distance = 1000;
//        $length = 0.001 * ($new_distance / 100);//跨越的长度
//        $start_lng = ($lng - $length);
//        $end_lng = ($lng + $length);


        $enable_count = 0;//可领红包个数
        $disable_count = 0;//不可领红包个数
        $limit = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting');
        $total_count = !empty($limit['total_package']) ? $limit['total_package'] : 30;//显示的总红包个数

        //在系统黑名单中
        $blacklist = $redis->hKeys(CacheSetting::KEY_RED_PACKAGE_BLACKLIST);
        if ($blacklist && in_array($uid, $blacklist)) {
            $enable_count = 0;
            //  $disable_count = $total_count;
            $res['enable'] = 0;
        } else {


            $pick_count = $redis->hGet(CacheSetting::KEY_PACKAGE_PICK_COUNT . date('Ymd'), $uid);
            $pick_count = $pick_count ? $pick_count : 0;
            if (device_id) {
                $device_pick_count = $redis->hGet(CacheSetting::KEY_PACKAGE_PICK_COUNT . date('Ymd'), device_id);
                $device_pick_count = $device_pick_count ? $device_pick_count : 0;
                $pick_count = $device_pick_count > $pick_count ? $device_pick_count : $pick_count;
            }

            $limit_count = $limit['day_pick_limit'];
            $user = UserInfo::findOne(["user_id=" . $uid, 'columns' => 'is_vip']);
            if ($user['is_vip']) {
                $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'package_pick_count']);
                $limit_count = $vip_privileges['package_pick_count'];
            }


            //今天临时添加次数
            $extra_user_count = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_EXTRA_COUNT . date('Ymd'), $uid); //个人额外添加次数
            $extra_user_count = $extra_user_count ? $extra_user_count : 0;

//            if (device_id) {
//                $extra_device_count = device_id ? $redis->hGet(CacheSetting::KEY_RED_PACKAGE_EXTRA_COUNT . date('Ymd'), device_id) : 0; //设备额外添加次数
//                $extra_device_count = $extra_device_count ? $extra_device_count : 0;
//                $limit_count += ($extra_user_count > $extra_device_count ? $extra_device_count : $extra_user_count);
//            } else {
//                $limit_count += $extra_user_count;
//            }
            $limit_count += $extra_user_count;

            //永久添加次数
            $extra_user_count = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, $uid); //个人额外永久添加次数
            $extra_user_count = $extra_user_count ? $extra_user_count : 0;
//            if (device_id) {
//                $extra_device_count = device_id ? $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, device_id) : 0; //设备额外永久添加次数
//                $extra_device_count = $extra_device_count ? $extra_device_count : 0;
//                $limit_count += ($extra_user_count > $extra_device_count ? $extra_device_count : $extra_user_count);
//            } else {
//                $limit_count += $extra_user_count;
//            }
            $limit_count += $extra_user_count;

            if ($pick_count >= $limit_count) {
                $res['enable'] = 0;
                $enable_count = mt_rand(!empty($limit['limit_top_start']) ? $limit['limit_top_start'] : 0, !empty($limit['limit_top_end']) ? $limit['limit_top_end'] : 0);
            } else {
                //最后一次的领取记录
                $last_record = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_USER_LAST_PICK, $uid);
                $last_record = $last_record ? json_decode($last_record, true) : [];

                //还没到刷新时间
                if ($last_record && (time() - $last_record['flush_time']) < 0) {
                    $enable_count = 0;
                } else {
//                    //注册3天内 返回多点可领红包
//                    if (time() - $user['created'] <= 86400 * 1) {
//                        $probability = ["3" => 900, "4" => 100];
//                        $mt = Probability::get_rand(array_values($probability));
//                        $enable_count = $mt == 0 ? 3 : 4;
//                    } else {
//                        //出现概率
//                        $probability = ["2" => 900, "3" => 100];
//                        $mt = Probability::get_rand(array_values($probability));
//                        $enable_count = $mt == 0 ? 2 : 3;
//                    }
                    //出现概率
                    if (!empty($limit['enable_limit'])) {
                        $probability = array_column($limit['enable_limit'], 'rate');
                        $mt = Probability::get_rand(array_values($probability));
                        $enable_count = $limit['enable_limit'][$mt]['num'];
                    } else {
                        $probability = ["2" => 900, "3" => 100];
                        $mt = Probability::get_rand(array_values($probability));
                        $enable_count = $mt == 0 ? 2 : 3;
                    }

                }
            }
        }

        //---1.先获取可领取的红包----

        //1.1 获取三天内已经领取过的红包
        $has_picked = [];
        $first_day = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PICK_LIST . date('Ymd', time() - 86400 * 3), $uid);
        $second_day = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PICK_LIST . date('Ymd', time() - 86400 * 2), $uid);
        $third_day = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PICK_LIST . date('Ymd', time() - 86400), $uid);
        $today = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PICK_LIST . date('Ymd'), $uid);

        $first_day && $has_picked = array_keys(json_decode($first_day, true));
        $second_day && $has_picked = array_merge($has_picked, array_keys(json_decode($second_day, true)));
        $third_day && $has_picked = array_merge($has_picked, array_keys(json_decode($third_day, true)));
        $today && $has_picked = array_merge($has_picked, array_keys(json_decode($today, true)));

        //机器人红包
        $where = "deadline>" . time() . " and status=" . self::STATUS_NORMAL . " and is_festival=0";
        $festival_where = "deadline>" . time() . " and status=" . self::STATUS_NORMAL . " and is_festival=1";

        $disable_where = "deadline>" . time();
        //机器人
        if (is_r) {
            $where .= " and is_rob=0 ";
        }
        if ($has_picked) {
            $where .= " and package_id not in (" . implode(',', $has_picked) . ") ";
            $festival_where .= " and package_id not in (" . implode(',', $has_picked) . ") ";
            $disable_where .= " and (status=" . self::STATUS_PICKED_OUT . " or (status=" . self::STATUS_NORMAL . " and package_id in(" . implode(',', $has_picked) . ")))";
        } else {
            $disable_where .= " and status=" . self::STATUS_PICKED_OUT;
        }

        if ($area_code) {
            //1.全国范围内的 2.全市范围,并且你在市区内 3.默认距离,你在指定距离内
            $where .= " and (range_type=" . self::RANGE_COUNTRY . " or (range_type=" . self::RANGE_CITY . " and area_code='" . $area_code . "') or (range_type=" . self::RANGE_DEFAULT . " and GetDistances(lat,lng,$lat,$lng)<=" . $limit['distance_limit'] . "))";

            $disable_where .= " and (range_type=" . self::RANGE_COUNTRY . " or (range_type=" . self::RANGE_CITY . " and area_code='" . $area_code . "') or (range_type=" . self::RANGE_DEFAULT . " and GetDistances(lat,lng,$lat,$lng)<=" . $limit['distance_limit'] . "))";
        } else {
            //1.全国范围内的 3.默认距离,你在指定距离内
            $where .= " and (range_type=" . self::RANGE_COUNTRY . " or (range_type=" . self::RANGE_DEFAULT . " and GetDistances(lat,lng,$lat,$lng)<=" . $limit['distance_limit'] . "))";

            $disable_where .= " and (range_type=" . self::RANGE_COUNTRY . " or (range_type=" . self::RANGE_DEFAULT . " and GetDistances(lat,lng,$lat,$lng)<=" . $limit['distance_limit'] . "))";
        }
        // echo $disable_where;
        // exit;
        if ($enable_count > 0) {
            $enable_list = RedPackage::findList([$where, 'limit' => $enable_count, 'columns' => "user_id as uid,package_id,package_info,deadline,lng,lat,GetDistances(lat,lng,$lat,$lng) as distance,area_code,range_type", 'order' => 'is_rob asc,rand() desc']);
            $disable_count = $total_count - count($enable_list);
        } else {
            $enable_list = [];
            $disable_count = $total_count;
        }


        //--2.再获取被领光的红包
        if (!is_r) {
            $disable_list = RedPackage::findList([$disable_where, 'limit' => $disable_count, 'columns' => "user_id as uid,package_id,package_info,deadline,lng,lat,GetDistances(lat,lng,$lat,$lng) as distance,area_code,range_type", 'order' => 'is_rob asc,rand() desc']);
            $list = array_merge($enable_list, $disable_list);
        } else {
            $list = $enable_list;
        }

        //--3. 获取没被领取的节假日红包
        $festival_list = [];
//        $festival_list = RedPackage::findList([$festival_where, 'limit' => 20, 'columns' => "user_id as uid,package_id,package_info,deadline,lng,lat,GetDistances(lat,lng,$lat,$lng) as distance,area_code,range_type", 'order' => 'created desc']);
//        if ($festival_list) {
//            $list = array_merge($festival_list, $list);
//        }

        if ($list) {
            $res['data_count'] = count($list);
            //用户id集合
            $uids = array_unique(array_column($list, 'uid'));
            //红包id集合
            $package_ids = array_column($list, 'package_id');

            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id as uid,username,avatar,sex,is_vip'], 'uid');
            $enable_package_ids = array_column($enable_list, 'package_id');//可领取红包

            if ($festival_list) {
                $enable_package_ids = array_merge($enable_package_ids, array_column($festival_list, 'package_id'));//可领取红包
            }
            //  $disable_package_ids = array_column($enable_list, 'package_id');//不可领取红包

            //已经领过的红包集合
            // $package_info = Request::getPost(Base::PACKAGE_IS_CAN_PICK, ['uid' => $uid, 'redids' => implode(',', $package_ids)]);
//            if ($package_info && $package_info['curl_is_success']) {
//                $content = json_decode($package_info['data'], true);
//                $package_info = $content['data'];
//                // var_dump($package_info);exit;
//            } else {
//                return $res;
//            }
//            var_dump(array_column($enable_list, 'package_id'));
//            var_dump(array_column($disable_list, 'package_id'));
//            var_dump($package_info);
//            exit;
            //$has_picked = RedPackagePickLog::getColumn(['user_id=' . $uid . " and package_id in (" . implode(',', $package_ids) . ')', 'columns' => 'package_id'], 'package_id');
            //记录最新一次的获取到的有效红包id
            $redis = $this->di->get("redis");
            if ($enable_package_ids && $res['enable'] == 1) {
                $redis->hSet(CacheSetting::KEY_RED_PACKAGE_SHOW_LIST, $uid, json_encode($enable_package_ids));
            }
            foreach ($list as $item) {
                $tmp = $item;
                $package_info = json_decode($item['package_info'], true);
                //全国范围内 构造经纬度 || //全市范围内 构造经纬度
                if ($tmp['range_type'] == self::RANGE_COUNTRY || $tmp['range_type'] == self::RANGE_CITY) {
                    //大于了最大显示范围
                    if ($tmp['distance'] > $limit['distance_limit']) {
                        // $pos = LatLng::getRandPos($lat, $lng, 1);
                        $pos = LatLng::getStaticRandPos($lng, $lat, $item['lng'], $item['lat'], 1.5);
                        //  exit;
                        $tmp['lng'] = $pos['lng'];
                        $tmp['lat'] = $pos['lat'];
                    }
                }
                $tmp['enable'] = in_array($item['package_id'], $enable_package_ids) ? 1 : 0; //in_array($item['uid'], $has_picked) ? 1 : 0;
                $tmp['deadline'] = $item['deadline'];
                $tmp['username'] = $users[$item['uid']]['username'];
                $tmp['avatar'] = $users[$item['uid']]['avatar'];
                $tmp['is_vip'] = $users[$item['uid']]['is_vip'];
                $tmp['package_content'] = $package_info && !empty($package_info['content']) ? $package_info['content'] : '';
                unset($tmp['package_info']);
                $res['data_list'][] = $tmp;
            }

        }
        return $res;
    }

    /**红包详情
     * @param $uid
     * @param $package_id
     * @param bool $need_package_info 是否需要红包具体状态及被抢信息等
     * @param $is_pick
     * @param $new_user
     * @return array
     */
    public function packageDetail($uid, $package_id, $need_package_info = false, $is_pick, $new_user)
    {
        $redis = $this->di->get('redis');
        $res = ['package_status' => -1, 'package_info' => [], 'detail' => []];
        $package = RedPackage::findOne(['package_id="' . $package_id . '"', 'columns' => 'user_id as uid,type,item_id,media,media_type,content,extra,deadline,lng,lat,range_type,package_info']);
        if (!$package) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if ($need_package_info) {
            //红包状态
//            $status = Request::getPost(Base::PACKAGE_STATUS, ['uid' => $uid, 'redid' => intval($package_id)]);
//            if ($status && $status['curl_is_success']) {
//                $content = json_decode($status['data'], true);
//                $res['package_status'] = $content['code'];
//            } else {
//                $this->ajax->outError(Ajax::FAIL_SEND, "红包状态查询失败：" . $status['data']);
//            }

            //红包详情
            $detail = Request::getPost(Base::PACKAGE_DETAIL, ['uid' => $uid, 'redid' => $package_id]);
            if ($detail && $detail['curl_is_success']) {
                $content = json_decode($detail['data'], true);
                $res['package_info'] = $content['data'];
                //红包已过期
                if ($res['package_info']['deadline'] < time()) {
                    $res['package_status'] = 101;
                } else {
                    //派发中
                    if ($res['package_info']['grabnum'] < $res['package_info']['num']) {
                        $res['package_status'] = 102;
                    }
                }
            } else {
                $this->ajax->outError(Ajax::FAIL_SEND, "红包详情查询失败：" . $detail['data']);
            }
        }
        if ($package['extra']) {
            $package['extra'] = json_decode($package['extra'], true);
            if ($package['type'] == self::TYPE_GOODS) {
                $shop = Shop::findOne(['id="' . $package['extra']['shop_id'] . '"', 'columns' => 'lng,lat,province,city,county,address,address_title,address_detail']);
                $package['extra'] = array_merge($package['extra'], $shop);
            }
        } else {
            $package['extra'] = (object)[];
        }

        //随机送龙钻
        $rand_diamond_num = rand(1, 3);
        if (!$need_package_info)//进入详情页不随机送，只领取
        {
            if ($new_user === 1)
                $diamond = self::sendDiamond($uid, $package_id, false);//新用户必送3个
            else
                $diamond = self::sendDiamond($uid, $package_id);//老用户随机
            if ($diamond)
                $res['has_diamond'] = 1;
            else
                $res['has_diamond'] = 0;
        } else {
            //is_pick字段兼容安卓 不兼容安卓的话下面代码只需要两行
            //$diamond = $redis->hGet(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND,$uid.$package_id);
            //$res['has_diamond'] = $diamond ? 1 : 0;

            if ($is_pick)//领取操作
            {
                $diamond = $redis->hGet(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND, $uid . $package_id);
                $res['has_diamond'] = $diamond ? 1 : 0;
            } else {
                if ($new_user === 1)
                    $diamond = self::sendDiamond($uid, $package_id, false);//新用户必送3个
                else
                    $diamond = self::sendDiamond($uid, $package_id);//老用户随机
                if ($diamond)
                    $res['has_diamond'] = 1;
                else
                    $res['has_diamond'] = 0;

            }

        }

        $package['package_info'] = json_decode($package['package_info'], true);
        $package['package_content'] = !empty($package['package_info']['content']) ? $package['package_info']['content'] : '';
        unset($package['package_info']);
        $res['detail'] = $package;
        $res['package_info'] = $res['package_info'] ? $res['package_info'] : (object)[];

        //用户标签
        $user_tags = UserTags::findOne(['user_id=' . $package['uid'], 'columns' => 'tags_name']);
        $res['detail']['user_tags'] = $user_tags ? $user_tags['tags_name'] : '';
        return $res;
    }

    //发出的收到的红包记录
    /**
     * @param $uid
     * @param int $type 1-我发出的 2-我抢到的
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function packageHistory($uid, $type = 1, $page = 1, $limit = 20)
    {
        $data = ['income' => 0, 'expense' => 0, 'data_list' => []];
        $redis = $this->di->get('redis');
        $income = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_INCOME, $uid);
        $expense = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_EXPENSE, $uid);
        $income && $data['income'] = $income;
        $expense && $data['expense'] = $expense;
        //我发出的
        if ($type == 1) {
//            $count = RedPackage::dataCount('user_id=' . $uid);
//            $data['data_count'] = $count;
            $user = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'avatar,username']);
            $list = RedPackage::findList(['user_id=' . $uid, 'limit' => $limit, 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'columns' => 'extra,money,media,media_type,content,user_id as uid,type,item_id,media,media_type,content,created,status,package_id,deadline']);
            if ($list) {
                foreach ($list as $item) {
                    $tmp = $item;
                    if ($tmp['deadline'] > time() && $tmp['status'] == self::STATUS_NORMAL) {
                        $tmp['status'] = 102;
                    } else {
                        $tmp['status'] = 0;
                    }
                    $tmp['avatar'] = $user['avatar'];
                    $tmp['created'] = (string)(intval($item['created'] / 1000));
                    $tmp['username'] = $user['username'];
                    $tmp['extra'] = $tmp['extra'] ? json_decode($tmp['extra']) : (object)[];
                    $data['data_list'][] = $tmp;
                }
            }
        } elseif ($type == 2) {
            //我抢到的
            $node = 'dn' . (($uid % 10) + 1);
            $list = RedPackagePickLog::findList(['user_id=' . $uid, 'limit' => $limit, 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'columns' => 'money,package_id,created'], false, $node);
//            $count = RedPackagePickLog::dataCount('user_id=' . $uid);
//            $data['data_count'] = $count;
            if ($list) {

                $package_ids = array_column($list, 'package_id');
                $packages = RedPackage::getByColumnKeyList(['package_id in (' . implode(',', $package_ids) . ')', 'columns' => 'extra,money,media,media_type,content,user_id as uid,type,item_id,media,media_type,content,created,status,package_id,deadline'], 'package_id');
                $uids = array_unique(array_column($packages, 'uid'));
                $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id,avatar,username'], 'user_id');

                foreach ($list as $item) {
                    $tmp = $packages[$item['package_id']];
                    $tmp['status'] = 0;
                    $tmp['money'] = $item['money'];
                    $tmp['created'] = $item['created'];
                    $tmp['avatar'] = $users[$packages[$item['package_id']]['uid']]['avatar'];
                    $tmp['username'] = $users[$packages[$item['package_id']]['uid']]['username'];
                    $tmp['extra'] = $tmp['extra'] ? json_decode($tmp['extra']) : (object)[];
                    $data['data_list'][] = $tmp;
                }
            }

        }
        return $data;
    }

    //发布机器人广场红包
    public function sendRobotPackage()
    {
        $lat = LatLng::getRandFloat(30.860000, 40.000000);//随机纬度 3.86~53.55
        $lng = LatLng::getRandFloat(80.660000, 120.000000);//随机经度 73.66~135.05

        $app_uid = Users::findOne(['user_type=' . UserStatus::USER_TYPE_ROBOT . " and (id<71041 or id>71078)", 'columns' => 'id,rand() as rand', 'order' => 'rand desc']);// $this->request->get('app_uid', 'int', 0);//app_uid
        $app_uid = $app_uid['id'];

        $area = AreaCity::findOne(['area_code<>""', 'columns' => 'area_code', 'order' => 'rand()']);
        $area_code = $area['area_code'];//地区码
        $package_content = "";//红包祝福语
        $money = 100;//红包金额

        //红包配置
        $config = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting');
        $min_money = !empty($config['money_limit_one']) ? intval($config['money_limit_one']) : 1;//每个红包的最低金额
        $res = Request::getPost(Base::PACKAGE_CONFIG, ['timestamp' => time()], true);
        //$min_money = self::packageMinMoney($money, $res['seller_config']['section']);//每个红包最小金额

        $num = floor($money / $min_money);//红包个数
        $day = date("Ymd");//日期

        $default_content = $res['default_content'];
        $special_content = $res['special_content'];

        //特殊日子 特殊祝福语
        if (isset($special_content[$day])) {
            $package_content = $special_content[$day][mt_rand(0, count($special_content[$day]) - 1)];
        } else {
            $package_content = $default_content[mt_rand(0, count($default_content) - 1)];
        }

        $deadline = (time() + 86400 * 3);//红包过期时间

        //生成一个红包
        $post_data = ['uid' => $app_uid, 'num' => $num, 'money' => $money, 'random' => 1, 'to_square' => 2, 'agent' => 12, 'version' => 1, 'deadline' => $deadline];
        $package = Request::getPost(Base::SEND_RED_PACKAGE, $post_data, true);
        $package_id = @$package['redbagid'];
        if ($package_id) {
            $package_info = json_encode(['id' => $package_id, 'content' => $package_content, 'deadline' => $deadline, 'money' => $money, "num" => $num]);
            $ads = SystemRedPackageAds::findOne(['status=1 and created>=' . (strtotime("-10 days")), 'order' => 'rand()']);
            if (!$ads) {
                $ads = SystemRedPackageAds::findOne(['status=1', 'order' => 'rand()']);
            }

            $res = self::addPackage($app_uid, self::TYPE_ADS, 0, $ads['content'], self::MEDIA_TYPE_PICTURE, $ads['media'], $package_id, $package_info, $lng, $lat, $area_code, self::RANGE_COUNTRY, true);
            if ($res) {
                Debug::log("发送广场红包:" . $package_id, 'package');
                //统计金额
                $reward = new CashRewardManager();
                $reward->squarePackage($app_uid, $package_id, $money);
                //更新投放数
                SystemRedPackageAds::updateOne(["send_count" => "send_count+1"], "id=" . $ads["id"]);
                return true;
            } else {
                return false;
            }
        } else {
            Debug::log("发送广场红包失败", 'package');
            return false;
        }


    }

    //每个红包的最小金额
    private function packageMinMoney($money, $res)
    {
        foreach ($res as $item) {
            if ($money > $item['left'] && $money <= $item['right']) {
                return $item['suggest'];
            }
        }
        return 10;
    }

    //每日统计
    public function dayStat($day)
    {
        $redis = $this->di->get('redis');
        $robot_uids = $redis->originalGet(CacheSetting::KEY_ROBOT_UIDS);
        $start = strtotime($day) * 1000;
        $end = $start + 86400000;
        $start2 = strtotime($day);
        $end2 = $start2 + 86400;
        $ymd = str_replace(['/', '-'], ['', ''], $day);

        //获取发出的红包 总个数
        $package_count = RedPackage::dataCount('created_ymd=' . $ymd);
        $package_user_count = RedPackage::dataCount('created_ymd=' . $ymd . " and is_rob=0");
        $package_total_count = RedPackage::findOne(['created_ymd=' . $ymd, 'columns' => 'sum(num) as total']);
        $package_total_count = $package_total_count ? $package_total_count['total'] : 0;//红包总个数 包括每个红包包含的小红包个数

        $total_money = RedPackage::findOne(['created_ymd=' . $ymd, 'columns' => 'sum(money) as total']);
        $total_user_money = RedPackage::findOne(['created_ymd=' . $ymd . " and is_rob=0", 'columns' => 'sum(money) as total']);
        $user_send_money_rank = RedPackage::findList(['created_ymd=' . $ymd . " and is_rob=0", 'columns' => 'sum(money) as total,user_id as uid', 'limit' => 100, 'group' => 'uid', 'order' => 'total desc']);
        $user_pick_money_rank = RedPackagePickLog::findList(["created>=" . $start2 . " and created<" . $end2, 'columns' => 'sum(money) as total,user_id as uid', 'limit' => 100, 'group' => 'uid', 'order' => 'total desc']);

        $total_pick_money = 0;//今日所有用户领取总金额
        $user_pick_money = 0;//普通用户领取总金额
        $robot_pick_money = 0;//机器用户领取总金额
        $total_pick_count = 0;//今日所有用户领取红包个数
        $user_pick_count = 0;//普通用户领取总个数
        $robot_pick_count = 0;//机器用户领取总个数

        $pick_person_count = 0;//参与领红包人数
        $pick_person_real_count = 0;//参与领红包真实人数
        $pick_person_robot_count = 0;//参与领红包机器人数

        $send_person_count = 0;//参与发红包人数
        $send_person_real_count = 0;//参与发红包真实人数
        $send_person_robot_count = 0;//参与发红包机器人数


        $total_pick_money = RedPackagePickLog::findOne(["created>=" . $start2 . " and created<" . $end2, 'columns' => 'sum(money) as total']);
        $total_pick_money = $total_pick_money ? $total_pick_money['total'] : 0;

        $total_pick_count = RedPackagePickLog::dataCount("created>=" . $start2 . " and created<" . $end2);

        $pick_person_count = $this->db->query("select count(1) as count from (select 1 from red_package_pick_log where created>=$start2 and created<=$end2 GROUP BY user_id) as tb1")->fetch(\PDO::FETCH_ASSOC);
        $pick_person_count = $pick_person_count ? $pick_person_count['count'] : 0;
        $send_person_count = $this->original_mysql->query("select count(1) as count from (select 1 from red_package where created_ymd=$ymd GROUP BY user_id) as tb1")->fetch(\PDO::FETCH_ASSOC);
        $send_person_count = $send_person_count ? $send_person_count['count'] : 0;
        if ($robot_uids) {
            $robot_pick_money = RedPackagePickLog::findOne(["created>=" . $start2 . " and created<" . $end2 . " and user_id  in ($robot_uids)", 'columns' => 'sum(money) as total']);
            $robot_pick_money = $robot_pick_money ? $robot_pick_money['total'] : 0;
            $robot_pick_count = RedPackagePickLog::dataCount("created>=" . $start2 . " and created<" . $end2 . " and user_id in ($robot_uids)");

            $pick_person_robot_count = $this->db->query("select count(1) as count from (select 1 from red_package_pick_log where created>=$start2 and created<=$end2  and user_id  in ($robot_uids) GROUP BY user_id) as tb1")->fetch(\PDO::FETCH_ASSOC);
            $pick_person_robot_count = $pick_person_robot_count ? $pick_person_robot_count['count'] : 0;

            $send_person_robot_count = $this->original_mysql->query("select count(1) as count from (select 1 from red_package where created_ymd=$ymd and  is_rob=1 GROUP BY user_id) as tb1")->fetch(\PDO::FETCH_ASSOC);
            $send_person_robot_count = $send_person_robot_count ? $send_person_robot_count['count'] : 0;
        }
        $user_pick_money = $total_pick_money - $robot_pick_money;
        $user_pick_count = $total_pick_count - $robot_pick_count;
        $pick_person_real_count = $pick_person_count - $pick_person_robot_count;
        $send_person_real_count = $send_person_count - $send_person_robot_count;


        $total_money = $total_money ? $total_money['total'] : 0; //发出的总金额
        $total_user_money = $total_user_money ? $total_user_money['total'] : 0; //真人发红包金额
        $user_send_money_rank = $user_send_money_rank ? $user_send_money_rank : [];//发红包排行榜
        $user_pick_money_rank = $user_pick_money_rank ? $user_pick_money_rank : [];//领红包排行榜

        $send_count_top = RedPackage::findList(['created_ymd=' . $ymd . " and is_rob=0", 'group' => 'user_id', 'columns' => 'user_id as uid,count(1) as count', 'order' => 'count desc']);
        $pick_count_top = $redis->hGetAll(CacheSetting::KEY_PACKAGE_PICK_COUNT . $ymd);
        arsort($pick_count_top);
        $data = [
            "created" => time(),
            "ymd" => $ymd,
            "package" => json_encode([
                'send_package_total_count' => $package_total_count,//发红包总个数【例如1个红包有10个小红包则记为10】
                'send_total_count' => $package_count,//用户发红包总个数
                "send_real_user_count" => $package_user_count,//真实用户发红包个数
                "send_robot_user_count" => $package_count - $package_user_count,//机器人发红包个数
                "send_total_money" => intval($total_money),//发红包花的总金额
                "send_real_user_money" => intval($total_user_money),//发红包真实用户花的金额
                "send_robot_user_money" => intval($total_money - $total_user_money),//发红包机器人花的金额
                "send_person_count" => $send_person_count,//发红包用户人数
                "send_person_real_count" => $send_person_real_count,//发红包真实用户人数
                "send_person_robot_count" => $send_person_robot_count,//发红包机器用户人数
                'pick_total_money' => $total_pick_money,//领取总金额
                'pick_real_user_money' => $user_pick_money,//真实用户领取总金额
                'pick_robot_user_money' => $robot_pick_money,//机器人领取总金额
                'pick_total_count' => $total_pick_count,//领取红包总个数
                'pick_real_user_count' => $user_pick_count,//真实用户领取红包个数
                'pick_robot_user_count' => $robot_pick_count,//机器人领取红包个数
                "pick_person_count" => $pick_person_count,//领红包人数
                "pick_person_real_count" => $pick_person_real_count,//领红包真实用户人数
                "pick_person_robot_count" => $pick_person_robot_count,//领红包机器用户人数
            ]),
            'pick_top' => json_encode(array_values($user_pick_money_rank)),//领红包排行榜【含机器人】
            'send_top' => json_encode(array_values($user_send_money_rank)),//发红包金额排行榜【不包含机器人】
            'send_count_top' => json_encode(array_values($send_count_top)),//发红包个数排行榜【不包含机器人】
            'pick_count_top' => json_encode($pick_count_top),//领红包个数排行榜【不包含机器人】
            'send_uids' => implode(',', array_column($user_send_money_rank, 'uid')),//发红包用户id集合
            'pick_uids' => implode(',', array_column($user_pick_money_rank, 'uid'))//领红包用户id集合

        ];
        if (!PackageDayStat::exist("ymd=" . $ymd)) {
            return PackageDayStat::insertOne($data);
        }
        return PackageDayStat::updateOne($data, 'ymd=' . $ymd);
    }

    public function getTheDiamond($uid, $package_id)
    {
        $redis = $this->di->get('redis');
        $diamond = $redis->hget(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND, $uid . $package_id);
        if (!$diamond) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS, '数据不存在');
        }
        if (!$redis->originalExists(CacheSetting::KEY_RECORD_SQUARE_HAS_PICK_NUM))//存在领取记录表
        {
            $redis->hSet(CacheSetting::KEY_RECORD_SQUARE_HAS_PICK_NUM, $uid, $diamond);
            //该表每天23:59:59时清理
            $expire = mktime(23, 59, 59, date('m'), date("d"), date("Y")) - time();
            $redis->expire(CacheSetting::KEY_RECORD_SQUARE_HAS_PICK_NUM, $expire);
            $has_picked_num = 0;
        } else {
            $has_picked_num = $redis->hget(CacheSetting::KEY_RECORD_SQUARE_HAS_PICK_NUM, $uid);
        }

        if ($has_picked_num >= self::SQUARE_DIAMOND_PICK_LIMIT_PER_DAY) {
            $this->ajax->outError(Ajax::FAIL_PICK_HAS_REACHED_LIMIT, '已达今日领取上限');
        } else {
            if ($has_picked_num + $diamond > self::SQUARE_DIAMOND_PICK_LIMIT_PER_DAY)//如果已领个数加上本次领取个数超过上限，只领取剩余可领个数
                $diamond = self::SQUARE_DIAMOND_PICK_LIMIT_PER_DAY - $has_picked_num;
        }
        try {
            $record = [
                //"payid" => "Novice_" . $id,             // 交易id
                "coin_type" => 0,           // 虚拟币类型【0红包钻石...其他保留】
                "coin" => (int)$diamond,               // 本次记录变动的钻石
                "type" => 0,                // 【0收入、1支出】
                "desc" => "红包广场送龙钻",  // 流水描述
                "created" => time(),    // 时间
                "way" => 6,                 // 渠道，对于龙钻(coin_type=0)充值，1表示ios内购、2表示支付宝、3表示微信、4表示余额、5表示公众号、6表示系统赠送奖励；对于收益(coin_type=2)来源，1表示恐龙谷活动、2表示广场红包
                "extend" => ""              // 拓展
            ];
            $res = Request::getPost(Request::VIRTUAL_COIN_UPDATE, ['uid' => intval($uid), 'coin_type' => 0, 'coin_num' => $diamond, 'record' => json_encode($record, JSON_UNESCAPED_UNICODE)]);
            if ($res && $res['curl_is_success']) {
                $content = json_decode($res['data'], true);
                if (empty($content['code']) || $content['code'] != 200) {
                    throw new \Exception("更新虚拟币失败：" . var_export($content, true));
                }
            } else {
                throw new \Exception("更新虚拟币失败");
            }
            $redis->hSet(CacheSetting::KEY_RECORD_SQUARE_HAS_PICK_NUM, $uid, $has_picked_num + $diamond);//更新今日领取数量
            $redis->hDel(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND, $uid . $package_id);//删除待领取记录

        } catch (\Exception $e) {
            $this->ajax->outError(Ajax::FAIL_PICK, var_export($e->getMessage(), true));
        }
        return (int)$diamond;
    }

    //发布假节日红包
    public function publishFestivalPackage($id)
    {
        $festival = RedPackageFestival::findOne(['id=' . $id . " and status=" . self::festival_wait_publish]);
        if (!$festival) {
            return false;
        }
        $lat = LatLng::getRandFloat(30.860000, 40.000000);//随机纬度 3.86~53.55
        $lng = LatLng::getRandFloat(80.660000, 120.000000);//随机经度 73.66~135.05

        // $app_uid = Users::findOne(['user_type=' . UserStatus::USER_TYPE_OFFICIAL, 'columns' => 'id,rand() as rand', 'order' => 'rand desc']);// $this->request->get('app_uid', 'int', 0);//app_uid
        $app_uid = $festival['user_id'];
        if (!$app_uid) {
            $app_uid = Users::findOne(['user_type=' . UserStatus::USER_TYPE_OFFICIAL, 'columns' => 'id,rand() as rand', 'order' => 'rand desc']);
        }
        $area = AreaCity::findOne(['area_code<>""', 'columns' => 'area_code', 'order' => 'rand()']);
        $area_code = $area['area_code'];//地区码
        $package_content = "";//红包祝福语
        $money = $festival['money'];//红包金额

        //红包配置
        $res = Request::getPost(Base::PACKAGE_CONFIG, ['timestamp' => time()], true);

        $num = $festival['num'];//红包个数
        $day = date("Ymd");//日期

        $default_content = $res['default_content'];
        $special_content = $res['special_content'];

        //特殊日子 特殊祝福语
        if (isset($special_content[$day])) {
            $package_content = $special_content[$day][mt_rand(0, count($special_content[$day]) - 1)];
        } else {
            $package_content = $default_content[mt_rand(0, count($default_content) - 1)];
        }

        $deadline = (time() + 86400 * 3);//红包过期时间

        //生成一个红包
        $post_data = ['uid' => $app_uid, 'num' => $num, 'money' => $money, 'random' => 1, 'to_square' => 2, 'agent' => 12, 'version' => 1, 'deadline' => $deadline];
        $package = Request::getPost(Base::SEND_RED_PACKAGE, $post_data, true);
        $package_id = @$package['redbagid'];
        try {
            $this->di->getShared("original_mysql")->begin();
            if ($package_id) {
                $package_info = json_encode(['id' => $package_id, 'content' => $package_content, 'deadline' => $deadline, 'money' => $money, "num" => $num]);

                $res = self::addPackage($app_uid, self::TYPE_ADS, 0, $festival['content'], self::MEDIA_TYPE_PICTURE, $festival['media'], $package_id, $package_info, $lng, $lat, $area_code, self::RANGE_COUNTRY, true, true);
                if ($res) {
                    Debug::log("发送广场红包:" . $package_id, 'package');
                    //统计金额
                    //  $reward = new CashRewardManager();
                    //   $reward->squarePackage($app_uid, $package_id, $money);
                    //更新状态
                    RedPackageFestival::updateOne(["status" => self::festival_has_published, 'package_id' => $package_id, 'modify' => time()], "id=" . $id);
                } else {
                    throw new \Exception("发布失败");
                }
            } else {
                throw new \Exception("生成红包失败");
            }
            $this->di->getShared("original_mysql")->commit();
            return true;

        } catch (\Exception $e) {
            $this->di->getShared("original_mysql")->rollback();
            //发布失败
            RedPackageFestival::updateOne(["status" => self::festival_publish_fail, 'package_id' => $package_id ? $package_id : 0, 'modify' => time()], "id=" . $id);
            Debug::log("发送假节日广场红包失败" . $e->getMessage(), 'package');
            return false;
        }
    }

    //添加节假日红包任务
    public function addFestivalPackageTask($id, $date)
    {
        if (TEST_SERVER) {
            $cmd = "php -f /mnt/www/dvalley/scripts/start.php package sendFestival " . $id;
        } else {
            $cmd = "php -f /var/www/dvalley/scripts/start.php package sendFestival " . $id;
        }
        return TaskManager::init(self::$task_url)->add_job("date", ['run_date' => $date], "festival_package_" . $id, "节假日红包,id:" . $id . "计时器", '', '', $cmd);
    }

    //删除节假日红包任务
    public function removeFestivalPackageTask($id)
    {
        return TaskManager::init(self::$task_url)->remove_job("festival_package_" . $id);
    }

    //更新节假日红包任务
    public function updateFestivalPackageTask($id, $date)
    {
        if (TEST_SERVER) {
            $cmd = "php -f /mnt/www/dvalley/scripts/start.php package sendFestival " . $id;
        } else {
            $cmd = "php -f /var/www/dvalley/scripts/start.php package sendFestival " . $id;
        }
        return TaskManager::init(self::$task_url)->edit_job("festival_package_" . $id, "date", ['run_date' => $date], "节假日红包,id:" . $id . "计时器", '', '', $cmd);
    }

    /**获取每日任务列表
     * @param $uid
     * @return array
     */
    public function dailyTask($uid)
    {
        $limit = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting');
        $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'is_vip']);
        $base_limit_count = intval($limit['day_pick_limit']);//最基础的领取次数
        if ($user_info['is_vip'] == 1) {
            $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'package_pick_count']);
            $base_limit_count = intval($vip_privileges['package_pick_count']);
        }

        $data = [
            'base_count' => $base_limit_count, //最基础的领取次数
            'permanent_count' => 0, //增加的永久次数
            'temporary_count' => 0, //增加的临时次数
            'used_count' => 0, //今日已经使用的次数
            'task_list' => [] //任务列表
        ];
        $redis = $this->di->getShared("redis");

        //设备记录 个人记录 取小值
        $permanent_extra = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, $uid);
        $permanent_extra = $permanent_extra ? $permanent_extra : 0;
//        if (device_id) {
//            $permanent_extra_device = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_PERMANENT_COUNT, device_id);
//            $permanent_extra_device = $permanent_extra_device ? $permanent_extra_device : 0;
//            $permanent_extra = $permanent_extra > $permanent_extra_device ? $permanent_extra_device : $permanent_extra;
//        }
        $data['permanent_count'] = intval($permanent_extra);

        //设备记录 个人记录 取小值
        $temporary_extra = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_EXTRA_COUNT . date('Ymd'), $uid);
        $temporary_extra = $temporary_extra ? $temporary_extra : 0;
        if (device_id) {
            $temporary_extra_device = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_EXTRA_COUNT . date('Ymd'), device_id);
            $temporary_extra_device = $temporary_extra_device ? $temporary_extra_device : 0;
            $temporary_extra = $temporary_extra > $temporary_extra_device ? $temporary_extra_device : $temporary_extra;
        }
        $data['temporary_count'] = intval($temporary_extra);
        $pick_count = $redis->hGet(CacheSetting::KEY_PACKAGE_PICK_COUNT . date('Ymd'), $uid);
        $data['used_count'] = intval($pick_count ? $pick_count : 0);

        $task_list = RedPackageTaskRules::findList(['enable=1', 'order' => 'order_num desc', 'columns' => 'is_permanent,behavior,add_count,term,limit_count']);
        foreach ($task_list as $t) {
            $tmp = [
                'is_permanent' => intval($t['is_permanent']),//是否永久性
                'behavior' => $t['behavior'],//任务id
                'behavior_desc' => SquareTask::$behaviorNameMap[$t['behavior']],//任务id
                'add_count' => intval($t['add_count']),//任务添加的次数
                'is_finished' => 0, //是否已经完成
                'exec_count' => 0,//执行次数
            ];
            //一次性任务
            if ($t['term'] == AbstractTask::TERM_ONLY_ONE) {
                if (RedPackageTaskLog::exist("action='" . $t['behavior'] . "' and user_id=" . $uid)) {
                    $tmp['is_finished'] = 1;
                    $tmp['exec_count'] = 1;
                }
            } else {
                $tmp['exec_count'] = RedPackageTaskLog::dataCount("action='" . $t['behavior'] . "' and user_id=" . $uid . " and ymd=" . date('Ymd'));
                //每次
                if ($t['term'] == AbstractTask::TERM_EVERY_BEHAVIOR) {
                    $tmp['is_finished'] = 0;
                } else if ($t['term'] == AbstractTask::TERM_ONCE_A_DAY) {
                    $tmp['is_finished'] = $tmp['exec_count'] >= 1 ? 1 : 0;
                } else if ($t['term'] == AbstractTask::TERM_DAY_LIMIT) {
                    $tmp['is_finished'] = $tmp['exec_count'] >= $t['limit_count'] ? 1 : 0;
                }
            }
            $data['task_list'][] = $tmp;
        }
        return $data;
    }

    public function nearShopList($uid, $category, $lng, $lat, $area_code)
    {
        $where = "status = " . ShopManager::status_normal;
        if (!empty($category))//用户选择的分类
        {
            //$categorys = $this->original_mysql->query("select id from shop_category where filter = " . $category)->fetchAll(\PDO::FETCH_ASSOC);
            //获取当前筛选按钮配置
            $btns = json_decode(SiteKeyValManager::init()->getValByKey('other', 'shop_filter_btns'), true);
            $btns = array_combine(array_column($btns, 'id'), $btns);
            if (isset($btns[$category]))//客户端传过来的筛选id有效
            {
                $shop_cids = explode(',', $btns[$category]['shop_cids']);
                $where .= " and (";
                $orWhere = [];
                foreach ($shop_cids as $shop_cid) {
                    $orWhere[] = "find_in_set(" . $shop_cid . ",`category_ids`)";
                }
                $where .= implode(' or ', $orWhere);
                $where .= " ) ";
            }

        }


        $limit = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting');
        $distance = (int)$limit['distance_limit'];//附近店铺范围，与红包范围保持一致
        $sql = "select *,GetDistances(s.lat,s.lng,$lat,$lng) as distance from shop as s where " . $where . " having distance <=" . $distance . " order by distance,created desc limit " . $limit['total_package'];//店铺显示个数与红包保持一致
        //var_dump($sql);exit;
        $list = $this->original_mysql->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $res['data_list'] = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $res['data_list'][$k]['id'] = $v['id'];
                $res['data_list'][$k]['owner_uid'] = $v['user_id'];
                $res['data_list'][$k]['name'] = $v['name'];
                $res['data_list'][$k]['lng'] = $v['lng'];
                $res['data_list'][$k]['lat'] = $v['lat'];
                $res['data_list'][$k]['filter'] = (int)$v['category_ids'];
            }
        }
        $res['data_count'] = count($res['data_list']);

        return $res;
    }

    /**
     * @param int $uid uid
     * @param string $package_id 红包id
     * @param bool $random 随机送还是必送
     * @return bool
     * 随机送龙钻，如果送，记录个数和领取人，返回true;不送，返回false;
     */
    private function SendDiamond($uid, $package_id, $random = true)
    {
        $redis = $this->di->get('redis');
        if (empty($redis->hGet(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND, $uid . $package_id))) {//没有记录
            if ($random === false || Probability::get_rand([1 => 1000, 2 => 19000]) === 1)//按1:20概率，中奖
            {
                $num = rand(1, 2);
                if (!$redis->originalExists(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND))//表不存在,执行建表->设置有效期
                {
                    $result = $redis->hSet(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND, $uid . $package_id, $num);
                    //该表每天23:59:59时清理
                    $expire = mktime(23, 59, 59, date('m'), date("d"), date("Y")) - time();
                    $result && $redis->expire(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND, $expire);//每天清空未领取奖励
                } else {
                    $result = $redis->hSet(CacheSetting::KEY_RECORD_SQUARE_GIVE_DIAMOND, $uid . $package_id, $num);
                }
                if ($result)
                    return true;
                else
                    return false;

            } else {
                return false;
            }
        } else {//已经有相关记录
            return true;
        }
    }

}