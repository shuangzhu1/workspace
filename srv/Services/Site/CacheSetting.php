<?php

namespace Services\Site;

use Phalcon\Mvc\User\Plugin;
use Services\Stat\StatManager;

/** 缓存设置
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/2
 * Time: 11:43
 * @property  \Components\Redis\RedisComponent $cache
 *
 **
 */
class CacheSetting extends Plugin
{
    const PREFIX_REPORT_REASON = 1000; //举报原因
    const PREFIX_PROVINCE_LIST = 1001; //所有省份列表
    const PREFIX_PROVINCE_DETAIL = 1002; //省份详情
    const PREFIX_CITY_LIST = 1003; //城市列表
    const PREFIX_CITY_DETAIL = 1009; //城市详情
    const PREFIX_TAGS = 1010; //标签
    const PREFIX_USER_BASE_INFO = 1011; //用户基本信息
    const PREFIX_INDUSTRY = 1012; //行业
    const PREFIX_COUNTY_LIST = 1013; //区域列表
    const PREFIX_USER_ONLINE_COUNT = 1014; //在线人数
    const PREFIX_USER_ONLINE_LIST = 1015; //在线人列表
    const PREFIX_USER_TOKEN = 1016; //token 一次性token
    const PREFIX_SITE_SENSITIVE = 1017; //敏感词
    const PREFIX_ADS_LIST = 1018; //广告列表
    const PREFIX_REWARD_COUNT = 1019; //奖励次数
    const PREFIX_API_CALL_COUNT = 1020; //接口调用次数
    const PREFIX_READ_COUNT = 1021; //阅读数 hash
    const PREFIX_READ_LIST = 1022; //阅读列表
    const PREFIX_SHOP_DETAIL = 1023; //店铺详情
    const PREFIX_GOOD_DETAIL = 1024; //商品详情


    const KEY_IP_BLACKLIST = "ip_blacklist";//ip黑名单 redis tableName  hset
    const KEY_IP_FREQUENCY = "ip_frequency";//ip超频次数 redis tableName  hset

    const KEY_USER_BEHAVIOR = "user_behavior:";//用户行为记录 redis tableName  hset
    const KEY_UNREAD_MESSAGE = "unread_message";//恐龙君收到用户的未读消息数 redis tableName  hset
    const KEY_UNREAD_MESSAGE_TOTAL = "total";//恐龙君收到总的未读消息数 redis keyName  hset

    const KEY_USER_ATTENTION_UID = "attention";//用户关注uid数据 redis keyName  hset
    const KEY_USER_FOLLOWERS_UID = "followers";//粉丝uid数据 redis keyName  hset

    const KEY_SITE_ARTICLE_VIEW_LOG = "site_article_view_log";//站内文章查看记录 redis keyName  hset
    const KEY_SHOP_VISIT_LOG = "shop_visit_log";//店铺访客 redis_queue keyName  list


    const KEY_API_CALL_LOG = "api_call_log";//api调用记录入队列 redis_queue keyName list
    const KEY_USER_ONLINE_LIST = "user_online_list";//在线用户 redis tableName hset
    const KEY_USER_ONLINE = "user_online";//在线用户 redis  tableName  hset
    const KEY_USER_ONLINE_COUNT = "user_online_count";//在线用户 redis keyName hset
    const KEY_USER__ONLINE = "user:online";//在线用户队列 redis_queue  keyName list
    const KEY_MESSAGE_NOTIFY_LIST = "message_notify_list";// 云信消息抄送消息id存储 redis  keyName list
    const KEY_MESSAGE_PUSH_LIST = "message_push:list";//  云信消息抄送消息详情入队列 redis_queue  keyName list
    const KEY_IMAGE_CHECK_DISCUSS_LIST = "image_check_list:discuss";//  动态图片鉴黄入队列 redis_queue  keyName list
    const KEY_IMAGE_CHECK_USER_LIST = "image_check_list:user";//  头像鉴黄入队列 redis_queue  keyName list
    const KEY_IMAGE_CHECK_COMMENT_LIST = "image_check_list:comment";//  评论鉴黄入队列 redis_queue  keyName list
    const KEY_IMAGE_CHECK_REPLY_LIST = "image_check_list:reply";//  回复鉴黄入队列 redis_queue  keyName list
    const KEY_IMAGE_CHECK_AVATAR_LIST = "image_check_list:avatar";//  头像鉴黄入队列 redis_queue  keyName list

    const KEY_SYSTEM_MESSAGE_PUSH_LIST = "system_message_push";//  系统发送消息队列 message_queue  keyName list
    const KEY_LOGIN_ACCESS_TOKEN = "access_token";//  app登录返回的access_token redis  tableName  hset
    const KEY_SIGN_MD5 = "sign";//  接口的hash redis  tableName  hset
    const KEY_DEVELOPER_DEBUG_TOKEN = "debug_token";//  开发人员调试令牌 debug_token redis  tableName  hset

    const KEY_MYSQL_GLOBAL_SEQUENCE = "mysql_global_sequence";//  mysql表全局序列号 mysql_global_sequence redis  tableName  hset
    const KEY_GROUP_ACTIVE = "group_active";//  群聊表最后活跃时间  group_active redis  tableName  hset
    const KEY_CONVERSATION_LIST = "conversation_user_list:";//  单聊会话列表  conversation_list redis  tableName  hset
    const KEY_GROUP_CONVERSATION_LIST = "conversation_group_list";//  群聊会话列表  conversation_list redis  tableName  hset

    const KEY_GROUP_MEMBER_MUTE = "group_member_mute:";//  群成员禁言列表  group_member_mute redis  tableName  hset
    const KEY_URL_SHIELD = "shield_url";//禁止访问网址   shield_url redis  tableName  hset
    const KEY_OPEN_ROBOT = "open_robot:";//开放给棋牌的机器人   open_robot  tableName  hset
    const KEY_VIEWER = "viewer:";//访问记录   viewer  tableName  hset
    const KEY_PACKAGE_PICK_COUNT = "user_package_pick_count:";//抢红包次数记录  viewer  tableName  hset
    const KEY_SITE_KEY_VAL = "site_key_val";//site_key_val    tableName  hset //site_key_val表数据缓存
    const KEY_RED_PACKAGE = "red_package";//red_package    tableName  hset //红包基础缓存信息
    const KEY_RED_PACKAGE_PICK_COUNT = "red_package_pick_count";//red_package_pick_count   tableName  hset //红包领取次数
    const KEY_RED_PACKAGE_PICK_LIST = "user_red_package_pick_list:";//red_package_pick_count   tableName  hset //每个人每天领取的记录 只会保留一段时间
    const KEY_RED_PACKAGE_INCOME = "user_red_package_income";//user_red_package_income   tableName  hset //广场红包个人收入
    const KEY_RED_PACKAGE_EXPENSE = "user_red_package_expenses";//user_red_package_expenses   tableName  hset //广场红包个人支出
    const KEY_RED_PACKAGE_EXTRA_COUNT = "user_red_package_extra_count:";//user_red_package_extra_count   tableName  hset //广场红包每日增加额外红包领取次数
    const KEY_RED_PACKAGE_PERMANENT_COUNT = "user_red_package_permanent_count";//user_red_package_permanent_count   tableName  hset //广场红包增加额外红包领取次数


    const KEY_PAY_ORDER_USER = "no_pay_order_user:";//pay_order  //未支付订单-用户
    const KEY_PAY_ORDER_LIST = "no_pay_order_list:";//no_pay_order key //未支付订单
    const KEY_QR_CODE = "qrcode:";//qrcode key //二维码
    const KEY_RED_PACKAGE_USER_LAST_PICK = "user_red_package_last_pick";//user_red_package_last_pick   tableName  hset //广场红包个人最后一次领取记录
    const KEY_RED_PACKAGE_SHOW_LIST = "user_red_package_show_list";//user_red_package_show_list   tableName  hset //广场红包最后刷出的有效红包
    const KEY_ROBOT_UIDS = "robot_uids";//机器人用户id集合
    const KEY_RECORD_SQUARE_GIVE_DIAMOND = "record_square_give_diamond";//红包广场随机送龙钻待领取记录表  有效期一天
    const KEY_RECORD_SQUARE_HAS_PICK_NUM = "record_square_has_pick_num";//红包广场随机送龙钻已当天已领取个数记录表 有效期一天
    const KEY_THE_TIME_GET_NEW_YEAR_AD = "the_time_get_new_year_ad";//红包广场随机送龙钻已当天已领取个数记录表 有效期一天
    const KEY_THE_INTERVAL_APPEAR_NEW_YEAR_AD = "the_interval_appear_new_year_ad";//红包广场随机送龙钻已当天已领取个数记录表 有效期一天
    const KEY_RED_PACKAGE_BLACKLIST = "package_blacklist";//红包广场黑名单
    const KEY_EXTRA_GROUP_COUNT_PERMANENT = "extra_add_group_count";//额外增加的创建群聊个数-vip等 tableName  hset
    const KEY_VIP_STAT = "vip_stat:";//vip统计 tableName  hset
    const KEY_UPDATE_NICK_COUNT = "user_update_nick_count:";//用户编辑昵称次数 tableName  hset
    const KEY_USER_REGISTER_UNIQUE_ID = "user_register_unique_id";//注册时微信/QQ类型的后面标记的唯一id tableName  hset
    const KEY_USER_PASSWORD_ERROR_CNT = "user_password_error_cnt";//用户重试密码错误次数 tableName  hset


    //订阅发布
    const KEY_LIKE = 'like';
    const KEY_ATTENTION = 'attention';
    const KEY_REWARD = 'reward';

    private static $driver = 'redis';
    private static $cache = null;
    public static $setting = [
        self::PREFIX_REPORT_REASON => [
            'prefix' => 'report_reason:',
            'life_time' => 2592000, //1个月
            'name' => '举报原因',
        ],
        self::PREFIX_PROVINCE_LIST => [
            'prefix' => 'province_list',
            'life_time' => 2592000, //一个月
            'name' => '省份列表',

        ],
        self::PREFIX_PROVINCE_DETAIL => [
            'prefix' => 'province:',
            'life_time' => 2592000, //一个月
            'name' => '省份详情'
        ],
        self::PREFIX_CITY_LIST => [
            'prefix' => 'city_list:',
            'life_time' => 2592000, //一个月
            'name' => '城市列表',
        ],
        self::PREFIX_COUNTY_LIST => [
            'prefix' => 'county_list:',
            'life_time' => 2592000, //一个月
            'name' => '区域列表',
        ],
        self::PREFIX_CITY_DETAIL => [
            'prefix' => 'city:',
            'life_time' => 2592000, //一个月
            'name' => '城市详情',
        ],
        self::PREFIX_TAGS => [
            'prefix' => 'tags:',
            'life_time' => 2592000, //一个月
            'name' => '标签',
        ],
        self::PREFIX_USER_BASE_INFO => [
            'prefix' => 'user_base_info:',
            'life_time' => 2592000, //一个月
            'name' => '用户基本信息',
        ],
        self::PREFIX_INDUSTRY => [
            'prefix' => 'industry:',
            'life_time' => 2592000, //一个月
            'name' => '行业',
        ],
        self::PREFIX_USER_TOKEN => [
            'prefix' => 'token:',
            'life_time' => 2592000, //30天
            'name' => '周期性登录token',
        ],
        self::PREFIX_SITE_SENSITIVE => [
            'prefix' => 'sensitive:',
            'life_time' => 0, //不过期
            'name' => '敏感词',
        ],
        self::PREFIX_ADS_LIST => [
            'prefix' => 'advertise_list:',
            'life_time' => 0, //不过期
            'name' => '广告列表',
        ],
        self::PREFIX_REWARD_COUNT => [
            'prefix' => 'system_reward:',
            'life_time' => 86400, //一天
            'name' => '系统奖励',
        ],
        self::PREFIX_API_CALL_COUNT => [
            'prefix' => 'api_call_count:',
            'life_time' => 86400, //一天
            'name' => '接口调用次数',
        ],
        self::PREFIX_READ_COUNT => [
            'prefix' => 'read_cnt',
            'life_time' => 120, //两分钟
            'name' => '阅读数',
        ],
        self::PREFIX_READ_LIST => [
            'prefix' => 'read_list',
            'life_time' => 0, //不过期
            'name' => '阅读列表',
        ],
        self::PREFIX_GOOD_DETAIL => [
            'prefix' => 'good_detail:',
            'life_time' => 2592000, //30天
            'name' => '商品详情',
        ],
        self::PREFIX_SHOP_DETAIL => [
            'prefix' => 'shop_detail:',
            'life_time' => 2592000, //30天
            'name' => '店铺详情',
        ],
        self::KEY_OPEN_ROBOT => [
            'prefix' => 'open_robot:',
            'life_time' => 86400, //
            'name' => '棋牌机器人',
        ]

    ];

    public function __construct($driver = 'redis')
    {
        self::$driver = $driver;
        self::$cache = $this->di->get(self::$driver);
    }

    public function getRedis()
    {
        return self::$cache;
    }

    /**获取
     * @param $index
     * @param $key
     * @return array
     */
    public function get($index, $key = '')
    {
        if (empty(self::$setting[$index])) {
            return false;
        }
        $key = self::$setting[$index]['prefix'] . $key;
        return self::$cache->get($key);
    }

    /**设置
     * @param $key
     * @param $val
     * @param $index
     * @return bool
     */
    public function set($index, $key, $val)
    {
        /* if (empty(self::$setting[$index])) {
             return false;
         }*/
        $key = self::$setting[$index]['prefix'] . $key;
        $lifetime = self::$setting[$index]['life_time'];
        return self::$cache->save($key, $val, $lifetime);
    }

    /**加1 操作
     * @param $index
     * @param $key
     * @return bool
     */
    public function incr($index, $key)
    {
        if (empty(self::$setting[$index])) {
            return false;
        }
        $key = self::$setting[$index]['prefix'] . $key;
        return self::$cache->increment($key);
    }

    /**减1 操作
     * @param $index
     * @param $key
     * @return bool
     */
    public function decr($index, $key)
    {
        if (empty(self::$setting[$index])) {
            return false;
        }
        $key = self::$setting[$index]['prefix'] . $key;
        return self::$cache->decrement($key);
    }

    /**加法 操作
     * @param $index
     * @param $key
     * @param $num
     * @return bool
     */
    public function incrBy($index, $key, $num)
    {
        if (empty(self::$setting[$index])) {
            return false;
        }
        $key = self::$setting[$index]['prefix'] . $key;
        return self::$cache->increment($key, $num);
    }

    /**减法 操作
     * @param $index
     * @param $key
     * @param $num
     * @return bool
     */
    public function decrBy($index, $key, $num)
    {
        if (empty(self::$setting[$index])) {
            return false;
        }
        $key = self::$setting[$index]['prefix'] . $key;
        return self::$cache->decrement($key, $num);
    }

    /**设置过期时间
     * @param string $index
     * @param string $key
     * @return bool
     */
    public function expire($index = '', $key = '')
    {
        if (empty(self::$setting[$index])) {
            return false;
        }
        $key = self::$setting[$index]['prefix'] . $key;
        return self::$cache->expire('_PHCRklg_' . $key, self::$setting[$index]['life_time']);
    }

    /**设置过期时间
     * @param $key
     * @param $life_time
     * @return bool
     */
    public function expire2($key, $life_time)
    {
        if (empty($key)) {
            return false;
        }
        return self::$cache->expire($key, $life_time);
    }

    /**批量获取
     * @param $keys
     * 模糊查找：order*
     * @return array
     */
    public function keys($keys)
    {
        return self::$cache->queryKeys($keys);
    }

    /**删除
     * @param $index
     * @param $key
     * @return bool
     */

    public function remove($index, $key)
    {
        if ($index && !empty(self::$setting[$index])) {
            $key = self::$setting[$index]['prefix'] . $key;
        } else if ($key != '') {
        } else {
            return false;
        }
        return self::$cache->delete($key);
    }

    /**批量删除
     * @param $index
     * @param $key
     *
     */

    public function batchRemove($index, $key = '')
    {
        $config_prefix = $this->di->get('config')->redis->prefix;
        if ($index && !empty(self::$setting[$index])) {
            $keys = self::keys($config_prefix . self::$setting[$index]['prefix']);
        } else if ($key) {
            $keys = self::keys($config_prefix . $key);
        } else {
            $keys = [];
        }
        if ($keys) {
            foreach ($keys as $k) {
                $k = preg_replace('/' . $config_prefix . '/', '', $k);
                self::remove('', $k);
            }
        }
    }

    /**检测是否存在
     * @param $index
     * @param $key
     * @return bool
     */
    public function exists($index, $key)
    {
        if ($index && !empty(self::$setting[$index])) {
            $key = self::$setting[$index]['prefix'] . $key;
        } else if ($key) {

        } else {
            return false;
        }
        return self::$cache->exists($key);
    }

    /**删除所有的缓存
     */
    public function flush()
    {
        self::$cache->flush();
    }

}