<?php
/**
 * Created by PhpStorm.
 * User: ykuang
 * Date: 15-4-3
 * Time: 下午12:43
 */

namespace Services\User;

use Components\Rules\Point\PointRule;
use Components\Yunxin\ServerAPI;
use Models\Community\Community;
use Models\Shop\Shop;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussMedia;
use Models\Social\SocialFav;
use Models\User\UserAttention;
use Models\User\UserAuthApply;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserCountStat;
use Models\User\UserInfo;
use Models\User\UserInterview;
use Models\User\UserLocation;
use Models\User\UserPersonalSetting;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserSetting;
use Models\User\UserShow;
use Models\User\UserVisitor;
use Models\Vip\VipPrivileges;
use Phalcon\Mvc\User\Plugin;
use Services\Discuss\DiscussManager;
use Services\Im\SysMessage;
use Services\Shop\ShopManager;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\User\Square\SquareTask;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;
use Util\LatLng;
use Util\Time;

class UserStatus extends Plugin
{
    /*
       * infinite
       */
    static $instacne = null;

    /*---登录类型--*/
    const LOGIN_QQ = 1;
    const LOGIN_WEICHAT = 2;
    const LOGIN_PHONE = 3;
    const LOGIN_OTHER = 0;


    /*--用户状态--*/
    const STATUS_DELETED = 0;//被永久锁定
    const STATUS_NORMAL = 1;//正常
    const STATUS_LOCKED = 2;//被锁定，需解封

    const NEAR_DISTANCE_LIMIT = 1000;//附近的人距离限制
    const NEAR_TIME_LIMIT = 3600000;//附近的人时间限制 一小时内

    const ACCESS_TOKEN_TIMEOUT = 86400;//app 登录token过期时间 24小时
    const ACCESS_TOKEN_CACHE_TIME_OUT = 600;///app 登录 token缓冲时间 10分钟

    /*--注册方式----*/
    const REGISTER_TYPE_PHONE = 'phone';
    const REGISTER_TYPE_QQ = 'qq';
    const REGISTER_TYPE_WEIXIN = 'weixin';
    const REGISTER_TYPE_ROBOT = 'robot';


    /*用户类型*/
    const USER_TYPE_NORMAL = 1;//普通用户
    const USER_TYPE_ROBOT = 2;//机器人
    const USER_TYPE_OFFICIAL = 3;//官方账号


    /*--第三方登录类型----*/
    static $third_login_type = array(
        self::LOGIN_QQ => 'QQ',
        self::LOGIN_WEICHAT => '微信'
    );

    /*--登录类型----*/
    static $login_type = array(
        self::LOGIN_QQ => 'QQ',
        self::LOGIN_WEICHAT => '微信',
        self::LOGIN_PHONE => '手机',
        self::LOGIN_OTHER => '其他'
    );

    //默认头像地址
    static $default_avatar = 'http://avatorimg.klgwl.com/default/avatar.png';

    /*--用户状态定义--*/
    static $user_status = array(
        self::STATUS_DELETED => '被删除',
        self::STATUS_NORMAL => '正常',
        self::STATUS_LOCKED => '被锁定，需解封',
    );
    //星座
    static $constellation = array(
        "1" => '水瓶座',
        "2" => '双鱼座',
        "3" => '白羊座',
        "4" => '金牛座',
        "5" => '双子座',
        "6" => '巨蟹座',
        "7" => '狮子座',
        "8" => '处女座',
        "9" => '天秤座',
        "10" => '天蝎座',
        "11" => '射手座',
        "12" => '摩羯座',
    );

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!self::$instacne) {
            self::$instacne = new self();
        }
        return self::$instacne;
    }

    /**
     * 生成随机注册码
     * @return string
     */
    public static function getSalt()
    {
        $salt = '';
        $rand_str = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand_str_length = strlen($rand_str);
        $rand_count = rand(5, 10);//5-10位
        for ($i = 0; $i < $rand_count; $i++) {
            $salt .= $rand_str[rand(0, $rand_str_length - 1)];
        }
        return $salt;
    }

    /**获取用户信息
     * @param $uid
     * @param $to_uid
     * @param $is_search //是否来自搜索
     * @return mixed
     */
    public function getUserInfo($uid, $to_uid, $is_search = 0)
    {
        $user_info = $this->getBaseUserInfo($to_uid);
        if (!$user_info) {
            return false;
        }

        $attention_fans_cnt = $this->getAttentionFansCnt($to_uid);
        $user_info['constellation'] = $user_info['constellation'] ? UserStatus::$constellation[$user_info['constellation']] : ''; //星座
        $data['is_attention'] = 0; //是否已关注
        $data['is_contact'] = 0; //是否好友、联系人
        $data['contact_remark'] = "";//联系人备注
        $data['is_star'] = 0; //是否星标好友
        $data['is_blacklist'] = 0; //是否黑名单用户
        $data['fans_count'] = $attention_fans_cnt['fans_cnt'];// $this->getFansCnt($to_uid); //粉丝数
        $data['attention_count'] = $attention_fans_cnt['attention_cnt'];//$this->getAttentionCnt($to_uid); //关注数
        $data['discuss_count'] = SocialDiscuss::dataCount('user_id=' . $to_uid . ' and status=' . DiscussManager::STATUS_NORMAL);//动态数
        $data['collect_count'] = 0; //收藏数
        $data['look_fans'] = 1; //是否允许查看粉丝数
        $data['look_my_discuss'] = 1; //是否允许查看我的动态
        $data['look_his_discuss'] = 1; //是否查看他/她的动态
        $data['newest_discuss_pic'] = []; //最新的动态图片/视频
        $data['discuss_pic_count'] = 0; //动态图片/视频数量
        $data['comm_info'] = []; //创建的社区信息

        $data['relation'] = ['count' => 0, 'list' => []]; //关系-我关注的人也关注了他
        if ($uid != $to_uid) {
            $contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'is_star,mark']);
            //是否联系人
            if ($contact) {
                $data['is_contact'] = 1;
                $data['is_attention'] = 1;
                $data['contact_remark'] = $contact['mark'];
                $data['is_star'] = $contact['is_star'];
            } else {
                //是否黑名单
                if (UserBlacklist::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                    $data['is_blacklist'] = 1;
                } //是否关注
                elseif (UserAttention::exist('owner_id=' . $uid . ' and user_id=' . $to_uid . ' and enable=1')) {
                    $data['is_attention'] = 1;
                }
            }
            //个人设置
            $setting = UserSetting::findOne(['user_id=' . $to_uid, 'columns' => 'look_fans']);
            $data['look_fans'] = ($setting && $setting['look_fans'] == 0) ? 0 : 1;

            $personal_setting = UserPersonalSetting::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'scan_his_discuss,scan_my_discuss,mark']);
            if ($personal_setting) {
                $data['look_my_discuss'] = $personal_setting['scan_my_discuss'] == 0 ? 0 : 1;
                $data['look_his_discuss'] = $personal_setting['scan_his_discuss'] == 0 ? 0 : 1;
                $data['contact_remark'] = $personal_setting['mark'];
            }
            //关注交集
            $data['relation'] = ContactManager::init()->sameAttention($uid, $to_uid);


        } //查看自己的信息 多了收藏数
        else {
            $data['collect_count'] = SocialFav::dataCount('user_id=' . $uid . ' and enable=1');
            $password = Users::findOne(['id=' . $uid, 'columns' => 'password']);
            $setting = UserSetting::findOne(['user_id=' . $uid, 'columns' => 'login_protect,look_fans']);
            $data['is_set_password'] = $password['password'] ? 1 : 0;
            $data['is_login_protect'] = $setting && $setting['login_protect'] ? 1 : 0;
            $data['look_fans'] = ($setting && $setting['look_fans'] == 0) ? 0 : 1;

        }
        //社区信息
        $community = Community::findOne(['user_id=' . $to_uid, 'columns' => 'id,name']);
        if ($community) {
            $data['comm_info'] = ['comm_id' => $community['id'], 'comm_name' => $community['name']];
        } else {
            $data['comm_info'] = (object)[];
        }

        $data = array_merge($data, $user_info);
        //$data['is_merchant'] = $user_info['is_merchant'] == 0 ? 0 : 1;//是否商家

        $data['shop'] = '';
        $data['shop_name'] = '';
        if ($data['is_merchant']) {
            $shops = Shop::findList(['user_id=' . $to_uid . ' and status=' . ShopManager::status_normal . " and combo_status=" . ShopManager::combo_status_normal, 'columns' => 'id,name']);
            if ($shops) {
                $data['shop'] = implode(',', array_column($shops, 'id'));
                $data['shop_name'] = implode(',', array_column($shops, 'name'));
            }
        }
        unset($data['is_merchant']);

        /* $data['discuss_pic_count'] = intval($data['discuss_pic_count']);*/
        //认证状态
        if ($uid == $to_uid && !$is_search) {
            //已认证
            if ($data['is_auth'] == 1) {
                $auth = UserAuthApply::findOne(['user_id=' . $to_uid . ' and (status=1 or status=2)', 'columns' => 'status', 'order' => 'created desc']);
                if ($auth && $auth['status'] == 2) {
                    $data['is_auth'] = '2';//认证中
                }
            } //后台取消认证
            else if ($data['is_auth'] == 4) {
                $data['is_auth'] = '0';//未认证
            } else {
                $auth = UserAuthApply::findOne(['user_id=' . $to_uid . ' and (status=2 or status=3)', 'columns' => 'status', 'order' => 'created desc']);
                if ($auth) {
                    $data['is_auth'] = $auth['status'];//认证中/失败
                }
            }
        }
        //是否被对方拉黑

        if ($data['newest_discuss_pic']) {
            if ($uid != $to_uid) {
                //在对方黑名单中,看不到对方图册
                if (!UserBlacklist::findOne(['owner_id=' . $to_uid . ' and user_id=' . $uid, 'columns' => 'id'])) {
                    //对方给自己的设置
                    $personal_setting = UserPersonalSetting::findOne(['owner_id=' . $to_uid . ' and user_id=' . $uid, 'columns' => 'scan_my_discuss']);
                    if ($personal_setting && $personal_setting['scan_my_discuss'] == 0) {
                        $data['newest_discuss_pic'] = [];
                    } else {
                        $data['newest_discuss_pic'] = json_decode($data['newest_discuss_pic'], true);
                        foreach ($data['newest_discuss_pic'] as &$item) {
                            //  unset($item['id']);
                            $item['discuss_id'] = $item['id'];
                            unset($item['id']);
                        }
                    }
                } else {
                    $data['newest_discuss_pic'] = [];
                }

            } else {
                $data['newest_discuss_pic'] = json_decode($data['newest_discuss_pic'], true);
                foreach ($data['newest_discuss_pic'] as &$item) {
                    $item['discuss_id'] = $item['id'];
                    unset($item['id']);
                }
            }

        } else {
            $data['newest_discuss_pic'] = [];
        }

        unset($data['password']);
        return $data;
    }

    /**获取用户基本信息
     * @param $uid
     * @param $columns
     * @return mixed
     */
    public function getBaseUserInfo($uid, $columns = '')
    {
        if ($columns == '') {
            //  $columns = 'user_id as uid,status,username,birthday,true_name,phone,avatar,photos,coins,grade,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,auth_desc,introduce,job,industry,company,signature,newest_discuss_pic,created,voice_introduce,discuss_media_count as discuss_pic_count,cover,website';
            $columns = 'user_id as uid,status,username,birthday,true_name,phone,avatar,photos,coins,grade,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,auth_desc,introduce,job,industry,company,signature,created,voice_introduce,cover,website,charm,constellation,is_merchant,newest_discuss_pic,user_type,discuss_bg,is_vip';
        }

        return UserInfo::findOne(['user_id=' . $uid, 'columns' => $columns]);

    }

    /**缓存用户基本信息【变动较小】
     * @param $uid
     * @param bool $refresh
     * @param int $owner_id
     * @param bool $get_mark
     * @return array|static
     */
    public function getCacheUserInfo($uid, $refresh = false, $owner_id = 0, $get_mark = false)
    {
        if (!$uid) {
            return [];
        }
        $cache = new CacheSetting();
        $data = $cache->get($cache::PREFIX_USER_BASE_INFO, $uid);
        if (!$data || $refresh) {
            $columns = 'user_id as uid,status,username,true_name,phone,avatar,photos,coins,grade,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,auth_desc,introduce,job,industry,company,signature,voice_introduce';
            $data = UserInfo::findOne(['user_id=' . $uid, 'columns' => $columns]);
            $cache->set($cache::PREFIX_USER_BASE_INFO, $uid, $data);
        }
        if ($owner_id && $get_mark) {
            $personal_setting = UserPersonalSetting::findOne(["owner_id=" . $owner_id . ' and user_id=' . $uid, 'columns' => 'mark']);
            if ($personal_setting && $personal_setting['mark'] != '') {
                $data['username'] = $personal_setting['mark'];
            }
        }
        return $data;
    }

    /**缓存批量用户基本信息
     * @param $uids
     * @param array $uids
     * @param bool $refresh
     * @param int $owner_id
     * @param bool $get_mark
     * @return array|static
     */
    public function getCacheUsersInfo($uids, $refresh = false, $owner_id = 0, $get_mark = false)
    {
        $cache = new CacheSetting();
        if ($refresh) {
            $columns = 'user_id as uid,status,username,true_name,phone,avatar,photos,coins,grade,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,auth_desc,introduce,job,industry,company,signature,voice_introduce';
            $user_info = UserInfo::getByColumnKeyList(['user_id in(' . implode(',', $uids) . ')', 'columns' => $columns], 'uid');
            $cache->sets($user_info, "_PHCR", $this->di->get("redis")->prefix, $cache::PREFIX_USER_BASE_INFO);
            $res = $user_info;
        } else {
            $data = $cache->gets($uids, "_PHCR" . $this->di->get("config")->redis->prefix, $cache::PREFIX_USER_BASE_INFO);
            $need_get_id = [];
            foreach ($data as $k => $item) {
                if (!$item) {
                    $need_get_id[] = $uids[$k];
                }
            }
            $new_data = array_combine($uids, $data);
            if ($need_get_id) {
                $columns = 'user_id as uid,status,username,true_name,phone,avatar,photos,coins,grade,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,auth_desc,introduce,job,industry,company,signature,voice_introduce';
                $user_info = UserInfo::getByColumnKeyList(['user_id in(' . implode(',', $need_get_id) . ')', 'columns' => $columns], 'uid');
                $cache->sets($user_info, "_PHCR", $this->di->get("redis")->prefix, $cache::PREFIX_USER_BASE_INFO);
                $new_data = array_merge($new_data, $user_info);
            }
            $res = $new_data;
        }
        if ($owner_id && $get_mark) {
            $personal_setting = UserPersonalSetting::getByColumnKeyList(["owner_id=" . $owner_id . ' and user_id in(' . implode(',', $uids) . ')', 'columns' => 'mark,user_id'], 'user_id');
            if ($personal_setting) {
                foreach ($personal_setting as $k => $item) {
                    if ($item['mark'] != '') {
                        $res[$k]['username'] = $item['mark'];
                    }
                }
            }
        }
        return $res;
    }

    /**获取个人备注
     * @param $owner_id
     * @param $uid
     * @return \Phalcon\Mvc\Model\Resultset|\Phalcon\Mvc\Phalcon\Mvc\Model|string
     */
    public static function getMark($owner_id, $uid)
    {
        $personal_setting = UserPersonalSetting::findOne(['user_id=' . $owner_id . ' and owner_id=' . $uid, 'columns' => 'mark']);
        //有个人备注
        if ($personal_setting) {
            return $personal_setting['mark'];
        }
        return "";
    }

    /**获取用户名
     * @param $owner_id
     * @param $uid
     */
    public static function getUserName($owner_id, $uid)
    {
        $personal_setting = UserPersonalSetting::findOne(['owner_id=' . $owner_id . ' and user_id=' . $uid, 'columns' => 'mark']);
        //有个人备注
        if ($personal_setting && $personal_setting['mark']) {
            return $personal_setting['mark'];
        } else {
            $user = Users::findOne(['id=' . $uid, 'columns' => 'username']);
            return $user['username'];
        }
    }

    /**删除缓存用户基本信息【变动较小】
     * @param $uid
     * @return array|static
     */
    public function removeCacheUserInfo($uid)
    {
        $cache = new CacheSetting();
        $cache->remove($cache::PREFIX_USER_BASE_INFO, $uid);
    }

    /**获取粉丝数
     * @param $uid
     * @return mixed
     */
    public function getFansCnt($uid)
    {
        return UserAttention::dataCount('user_id=' . $uid /*. ' and enable=1'*/);
    }

    /**获取关注数
     * @param $uid
     * @return mixed
     */
    public function getAttentionCnt($uid)
    {
        return UserAttention::dataCount('owner_id=' . $uid /*. ' and enable=1'*/);
    }

    public function getAttentionFansCnt($uid)
    {
        $res = UserCountStat::findOne(['user_id=' . $uid, 'columns' => 'fans_cnt,attention_cnt']);
        if ($res) {
            return ['fans_cnt' => $res['fans_cnt'], 'attention_cnt' => $res['attention_cnt']];
        } else {
            return ['fans_cnt' => 0, 'attention_cnt' => 0];
        }
    }

    /**编辑用户信息
     * @param $uid
     * @param $data
     * @return bool
     */
    public function editInfo($uid, $data)
    {
        //更新user表数据
        if (!empty($data['avatar']) || !empty($data['username'])) {
            $user = Users::findOne(['id=' . $uid, 'columns' => 'username,avatar']);
            $user_data = [];
            !empty($data['avatar']) && $user_data['avatar'] = $data['avatar'];
            !empty($data['username']) && $user_data['username'] = $data['username'];

            if (!Users::updateOne($user_data, ['id' => $uid])) {
                return false;
            }
            //云信接口调用
            $yx = ServerAPI::init()->updateUinfo($uid, $user_data['username'] ? $user_data['username'] : $user['username'], $user_data['avatar'] ? $user_data['avatar'] : $user['avatar']);

            //更新默认备注
            if (!empty($data['username'])) {
                $this->db->query("update user_contact_member set default_mark='" . $data['username'] . "' where user_id=" . $uid);
                $this->db->query("update group_member set default_nick='" . $data['username'] . "' where user_id=" . $uid);
            }
            //红包广场增加次数
            if (!empty($data['avatar']) && $data['avatar'] != UserStatus::$default_avatar) {
                SquareTask::init()->executeRule($uid, device_id, SquareTask::TASK_UPLOAD_AVATAR);
            }
            unset($data['avatar']);
            unset($data['username']);


        }
        //更新user_profile表数据
        if ($data) {
            $user = UserProfile::findOne(['user_id=' . $uid, 'columns' => 'signature,city_id,photos,voice_introduce,birthday']);

            //送经验值
            $signature = ($user['signature'] == '' && !empty($data['signature'])) ? 1 : 0; //完善个性签名送经验值
            $area = ($user['city_id'] == 0 && !empty($data['city_id'])) ? 1 : 0; //完善地区信息送经验值
            $photos = ($user['photos'] == '' && !empty($data['photos'])) ? 1 : 0; //完善照片墙送经验值
            $voice = ($user['voice_introduce'] == '' && !empty($data['voice_introduce'])) ? 1 : 0; //完善语音送经验值
            $birthday = ($user['birthday'] == '' && !empty($data['birthday'])) ? 1 : 0; //完善生日送经验值

            if (!UserProfile::updateOne($data, ['user_id' => $uid])) {
                return false;
            }
            $rule = '';
            if ($signature) {
                $rule = PointRule::BEHAVIOR_FINISH_INFO_SIGNATURE;
            }
            if ($area) {
                $rule = PointRule::BEHAVIOR_FINISH_INFO_AREA;
            }
            if ($photos) {
                $rule = PointRule::BEHAVIOR_USER_PHOTOS;
            }
            if ($voice) {
                $rule = PointRule::BEHAVIOR_USER_VOICE;
            }
            if ($birthday) {
                $rule = PointRule::BEHAVIOR_USER_BIRTHDAY;
            }
            if ($rule) {
                PointRule::init()->executeRule($uid, $rule);
            }
        }
        self::removeCacheUserInfo($uid);
        return true;
    }

    /**获取附近的用户(好友和黑名单用户不需要出来)
     * @param $uid
     * @param $lng
     * @param $lat
     * @param $distance --多大距离米
     * @param $sex
     * @param int $page
     * @param int $limit
     * 参考 http://www.cnblogs.com/LBSer/p/3392491.html  经度或纬度每隔0.001度，距离相差约100米，由此推算出矩形左下角和右上角坐标
     * @return array
     */
    public function getNearUser($uid, $lng, $lat, $distance, $sex, $page = 1, $limit = 20)
    {

        $res = ['data_list' => []/*, 'data_count' => 0*/];

        //暂时不考虑 负值
        $new_distance = $distance ? $distance : self::NEAR_DISTANCE_LIMIT;
        $length = 0.001 * ($new_distance / 100);//跨越的长度
        $start_lng = ($lng - $length);
        $end_lng = ($lng + $length);

        $start_lat = ($lat - $length);
        $end_lat = ($lat + $length);
        $where = ' l.user_id <> ' . $uid . " and lng between $start_lng and $end_lng and lat between $start_lat and $end_lat and created>=" . (time() - self::NEAR_TIME_LIMIT) . ' and p.user_id>0 ';


        // $where = 'l.user_id <> ' . $uid . " and GetDistances(lat,lng,$lat,$lng)<=" . self::NEAR_DISTANCE_LIMIT . ' and created>=' . (time() - self::NEAR_TIME_LIMIT . ' and p.user_id>0');
        if ($sex) {
            $where .= " and p.sex=" . $sex;
        }
        // $contacts = UserContactMember::getColumn('owner_id=' . $uid, 'user_id');//联系人
        $blackList = UserBlacklist::getColumn('owner_id=' . $uid, 'user_id');//黑名单用户

        $not_user = []; //不需要展示的用户
        /* if ($contacts) {
             $not_user = $contacts;
         }*/
        if ($blackList) {
            $not_user = $blackList;// $not_user ? array_merge($not_user, $blackList) : $not_user;
        }
        if ($not_user) {
            $not_user = implode(',', array_unique($not_user));
            $where .= " and l.user_id not in (" . $not_user . ') ';
        }
        $limit_str = "";
        if ($page > 0) {
            $limit_str = " limit " . ($page - 1) * $limit . ',' . $limit;
        }

        //地图模式
        if ($distance) {
            $user_location = $this->di->get("original_mysql")->query("select l.user_id,l.created,lng,lat from user_location as l left join user_profile as p on l.user_id=p.user_id where " . $where . $limit_str)->fetchAll(\PDO::FETCH_ASSOC);
        } //列表模式
        else {
            $user_location = $this->di->get("original_mysql")->query("select GetDistances(lat,lng,$lat,$lng) as distance,l.user_id,l.created,lng,lat from user_location as l left join user_profile as p on l.user_id=p.user_id where " . $where . ' order by distance asc ' . $limit_str)->fetchAll(\PDO::FETCH_ASSOC);
        }
        // $user_location = UserLocation::getByColumnKeyList(['user_id <> ' . $uid . " and GetDistances(lat,lng,$lat,$lng)<=" . self::NEAR_DISTANCE_LIMIT . ' and created>=' . (time() - self::NEAR_TIME_LIMIT), 'columns' => "GetDistances(lat,lng,$lat,$lng) as distance,user_id,created,lng,lat", 'offset' => ($page - 1) * $limit, 'limit' => $limit], 'user_id');
        //数量
        //   $res['data_count'] = $this->db->query("select count(1) as count from user_location as l left join user_profile as p on l.user_id=p.user_id where " . $where)->fetch(\PDO::FETCH_ASSOC)['count'];
        if ($user_location) {
            $user_location_uids = array_column($user_location, 'user_id');

            //地图模式 不会分页 且无顺序
            if ($distance) {
                foreach ($user_location as &$l) {
                    $l['distance'] = LatLng::getDistance($lat, $lng, $l['lat'], $l['lng']);
                }
            }
            $distance_order = [];//最后排序规则
            $user_location_distance = array_column($user_location, 'distance', 'user_id');//排序规则

            $uids = implode(',', $user_location_uids);
            $user_location = array_combine($user_location_uids, $user_location);

            $users = UserInfo::findList(['user_id in(' . $uids . ') and status=' . UserStatus::STATUS_NORMAL, 'columns' => 'user_id as uid,username,status,avatar,sex,signature,is_auth,auth_type,auth_desc,job,company,industry,grade,voice_introduce,birthday,charm,constellation']);
            if ($users) {
                $blackList = UserBlacklist::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid'], 'uid');//黑名单列表
                $contactList = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人列表
                $attentionList = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid'], 'uid');//关注列表
                $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人设置列表

                $hide_users = UserSetting::getColumn(['user_id in (' . $uids . ') and hide_location=1', 'columns' => 'user_id'], 'user_id');
                $fans = UserAttention::getColumn(['user_id in (' . $uids . ')', 'columns' => 'count(1) as count,user_id', 'group' => 'user_id'], 'count', 'user_id');

                $shows = UserShow::getColumn(['user_id in (' . $uids . ') and enable=1', 'columns' => 'enable,user_id as uid'], 'enable', 'uid');

                foreach ($users as &$item) {
                    //去除隐藏自己位置的用户
                    if (in_array($item['uid'], $hide_users)) {
                        continue;
                    }
                    $item['username'] = isset($user_personal_setting[$item['uid']]) && $user_personal_setting[$item['uid']]['mark'] ? $user_personal_setting[$item['uid']]['mark'] : $item['username'];
                    $item['fans_count'] = isset($fans[$item['uid']]) ? intval($fans[$item['uid']]) : 0;

                    $item['is_contact'] = isset($contactList[$item['uid']]) ? 1 : 0;
                    $item['is_blacklist'] = isset($blackList[$item['uid']]) ? 1 : 0;
                    $item['is_attention'] = (isset($contactList[$item['uid']]) || isset($attentionList[$item['uid']])) ? 1 : 0;
                    $item['lng'] = $user_location[$item['uid']]['lng'];
                    $item['lat'] = $user_location[$item['uid']]['lat'];

                    $item['distance'] = $user_location[$item['uid']]['distance'];
                    $item['show_distance'] = LatLng::LongFormat($user_location[$item['uid']]['distance']);
                    $item['created'] = $user_location[$item['uid']]['created'];
                    $item['show_time'] = Time::formatHumaneTime($item['created']);

                    $item['is_attention'] = (isset($contactList[$item['uid']]) || isset($attentionList[$item['uid']])) ? 1 : 0;
                    $item['newest_dynamic'] = "";
                    $item['show_enable'] = (isset($shows[$item['uid']]) || isset($shows[$item['uid']])) ? 1 : 0;;
                    $item['constellation'] = $item['constellation'] ? self::$constellation[$item['constellation']] : '';
                    $res['data_list'][] = $item;
                    $distance_order[] = $user_location_distance[$item['uid']];

                }
                if ($res['data_list']) {
                    array_multisort($distance_order, SORT_ASC, $res['data_list']);

                    //最新动态

                    $discuss = ContactManager::init()->getNewestDynamic($uid, array_column($res['data_list'], 'uid'));
                    if ($discuss) {
                        foreach ($res['data_list'] as &$item) {
                            if (isset($discuss[$item['uid']])) {
                                if ($discuss[$item['uid']]['content'] == '') {
                                    $item['newest_dynamic'] = ($discuss[$item['share']]['share_original_item_id'] > 0 ? "转发" : '') . DiscussManager::$media_type[$discuss[$item['share']]['media_type']];
                                } else {
                                    $item['newest_dynamic'] = FilterUtil::unPackageContentTag($discuss[$item['uid']]['content'], $uid);
                                }
                            }
                        }
                    }
                }

            }

        }
        return $res;
    }

    //异性匹配
    public function matchUser($uid, $lng, $lat, $page = 1, $limit = 20)
    {
        $res = ['data_list' => []];
        $user = UserProfile::findOne(['user_id=' . $uid, 'columns' => 'sex']);
        $where = ' p.user_id>0  and p.sex=' . ($user['sex'] == 1 ? 2 : 1);

        $not_user = [$uid]; //不需要展示的用户
        /* if ($contacts) {
             $not_user = $contacts;
         }*/

        //黑名单
        $blackList = UserBlacklist::getColumn('owner_id=' . $uid, 'user_id');//黑名单用户
        if ($blackList) {
            $not_user = array_merge($not_user, $blackList);
        }
        //好友或者关注过的
        $attentionList = UserAttention::getColumn('owner_id=' . $uid, 'user_id');//关注过的或者好友
        if ($attentionList) {
            $not_user = array_merge($not_user, $attentionList);
        }


        if ($not_user) {
            $not_user = implode(',', array_unique($not_user));
            $where .= " and l.user_id not in (" . $not_user . ') ';
        }
        $limit_str = "";
        if ($page > 0) {
            $limit_str = " limit " . ($page - 1) * $limit . ',' . $limit;
        }
        $user_location = $this->di->get("original_mysql")->query("select GetDistances(lat,lng,$lat,$lng) as distance,l.user_id,l.created,lng,lat from user_location as l left join user_profile as p on l.user_id=p.user_id where " . $where . ' order by distance asc ' . $limit_str)->fetchAll(\PDO::FETCH_ASSOC);
        if ($user_location) {
            $user_location_uids = array_column($user_location, 'user_id');

            $distance_order = [];//最后排序规则
            $user_location_distance = array_column($user_location, 'distance', 'user_id');//排序规则

            $uids = implode(',', $user_location_uids);
            $user_location = array_combine($user_location_uids, $user_location);

            $users = UserInfo::findList(['user_id in(' . $uids . ') and status=' . UserStatus::STATUS_NORMAL, 'columns' => 'user_id as uid,username,avatar,sex,grade,voice_introduce,birthday,charm,constellation']);
            if ($users) {
                $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人设置列表

                $fans = UserAttention::getColumn(['user_id in (' . $uids . ')', 'columns' => 'count(1) as count,user_id', 'group' => 'user_id'], 'count', 'user_id');

                foreach ($users as &$item) {
                    $item['username'] = isset($user_personal_setting[$item['uid']]) && $user_personal_setting[$item['uid']]['mark'] ? $user_personal_setting[$item['uid']]['mark'] : $item['username'];
                    $item['fans_count'] = isset($fans[$item['uid']]) ? intval($fans[$item['uid']]) : 0;
                    $item['distance'] = $user_location[$item['uid']]['distance'];
                    $item['show_distance'] = LatLng::LongFormat($user_location[$item['uid']]['distance']);
                    $item['created'] = $user_location[$item['uid']]['created'];
                    $item['constellation'] = $item['constellation'] ? self::$constellation[$item['constellation']] : '';
                    $res['data_list'][] = $item;
                    $distance_order[] = $user_location_distance[$item['uid']];
                }
                array_multisort($distance_order, SORT_ASC, $res['data_list']);
            }
        }
        return $res;
    }

    /*更新访客*/
    public function updateVisitor($uid, $to_uid)
    {
        $node = 'dn' . (($to_uid % 10) + 1);
        $ymd = date('Ymd');
        $time = time();
        $visitor = UserVisitor::findOne(['owner_id=' . $to_uid . " and user_id=" . $uid . " and ymd=" . $ymd, 'columns' => 'modify,count,id'], false, true, $node);
        if ($visitor) {
            UserVisitor::updateOne(['modify' => $time, 'count' => $visitor['count'] + 1], 'id=' . $visitor['id']);
            //同一用户超过5分钟再次访问 推送消息
            if ($time - $visitor['modify'] >= 300) {
                SysMessage::init()->initMsg(SysMessage::TYPE_NEW_VISITOR, ["to_user_id" => $to_uid]);
            }
        } else {
            UserVisitor::insertOne(['created' => $time, 'modify' => $time, 'count' => 1, 'ymd' => $ymd, 'owner_id' => $to_uid, 'user_id' => $uid]);
            SysMessage::init()->initMsg(SysMessage::TYPE_NEW_VISITOR, ["to_user_id" => $to_uid]);
        }
//        //访问者数据入库
//        $data = UserInterview::findOne(['user_id=' . $to_uid, 'columns' => 'interview,id']);
//        $add_cnt = false;//是否添加访客数
//        $im_push = false;//是否推送消息 同一个有用户 5分钟内不推送
//
//        //该用户已经存在一条数据
//        if ($data) {
//            //之前有人访问过
//            if ($data['interview'] != '') {
//                $interview = json_decode($data['interview']);
//                //没有访问过该用户
//                if (!$interview->$uid) {
//                    $add_cnt = true;
//                } else {
//                    $im_push = (time() - $interview->$uid) >= 300 ? true : false;
//                }
//
//                $interview->$uid = time();
//                $interview = json_encode($interview, true);
//            } //之前没有人访问过
//            else {
//                $interview = json_encode([$uid => time()], true);
//                $add_cnt = true;
//                $im_push = true;
//            }
//            $data->interview = $interview;
//            UserInterview::updateOne(["interview" => $interview], ['id' => $data['id']]);
//
//            //更新访客数
//            if ($add_cnt) {
//                $this->db->execute("update user_profile set visitor=visitor+1 where user_id=" . $to_uid);
//            }
//        } //该用户没有存在数据-没有访问过别人，也没有被访问过
//        else {
//            $data = new UserInterview();
//            $interview = json_encode([$uid => time()], true);
//
//            //更新访客数
//            if ($data->insertOne(["user_id" => $to_uid, 'interview' => $interview])) {
//                $this->db->execute("update user_profile set visitor=visitor+1 where user_id=" . $to_uid);
//            }
//            $im_push = true;
//        }
//
//        //被访问者数据入库 -类似访问者入库
//        $data = UserInterview::findOne(['user_id=' . $uid, 'columns' => 'id,interviewee']);
//        if ($data) {
//            if ($data['interviewee'] != '') {
//                $interviewee = json_decode($data['interviewee']);
//                $interviewee->$to_uid = time();
//                $interviewee = json_encode($interviewee, true);
//            } else {
//                $interviewee = json_encode([$to_uid => time()], true);
//            }
//            UserInterview::updateOne(["interviewee" => $interviewee], ['id' => $data['id']]);
//        } else {
//            $data = new UserInterview();
//            $interviewee = json_encode([$to_uid => time()], true);
//            $data::insertOne(['user_id' => $uid, 'interviewee' => $interviewee]);
//        }
//        //消息推送
//        if ($im_push) {
//            SysMessage::init()->initMsg(SysMessage::TYPE_NEW_VISITOR, ["to_user_id" => $to_uid]);
//        }
    }

    /**获取访客列表
     * @param $uid
     * @param int $page 第几页
     * @param int $limit 每页显示的数量
     * @return array
     */
    public function visitorList($uid, $page = 1, $limit = 20)
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $node = 'dn' . (($uid % 10) + 1);
        $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'is_vip']);
        $normal_setting = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");
        //访客限制
        /*$user_visitor_limit = $normal_setting ? $normal_setting['user_visitor'] : 30;

        if ($user_info['is_vip']) {
            $vip_privileges = VipPrivileges::findOne(['user_id=' . $uid, 'columns' => 'user_visitor']);
            $user_visitor_limit = $vip_privileges ? $vip_privileges['user_visitor'] : $user_visitor_limit;
        }
        if ($user_visitor_limit == -1) {
            $visitor = UserVisitor::findList(['owner_id=' . $uid, 'order' => 'created desc', 'columns' => 'modify as created,user_id', 'offset' => ($page - 1) * $limit, 'limit' => $limit], false, $node);
        } else {
            $limit = $user_visitor_limit;
            if ($page > 1) {
                $visitor = [];
            } else {
                $visitor = UserVisitor::findList(['owner_id=' . $uid, 'order' => 'created desc', 'columns' => 'modify as created,user_id', 'offset' => 0, 'limit' => $limit], false, $node);
            }
        }*/
        $visitor = UserVisitor::findList(['owner_id=' . $uid, 'order' => 'created desc', 'columns' => 'modify as created,user_id', 'offset' => 0, 'limit' => $limit], false, $node);

        $res['data_count'] = UserVisitor::dataCount('owner_id=' . $uid);
        //  $interview = UserInterview::findOne(['user_id=' . $uid, 'columns' => 'interview']);
//        if ($interview) {
//            $detail = json_decode($interview['interview'], true);
//            arsort($detail);
//            $list = array_slice($detail, ($page - 1) * $limit, $limit, true);
//            if ($list) {
//                $order_date = [];//排序字段
//                $uids = implode(',', array_keys($list));
//                $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth,signature,company,job,industry'], 'uid');
//                $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
//                foreach ($list as $k => $i) {
//                    $item = $users[$k];
//                    $item['created'] = (string)($i);
//                    $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($item['true_name'] ? $item['true_name'] : $users[$item['uid']]['username']);
//                    $res['data_list'][] = $item;
//                    $order_date[] = $i;
//                }
//                array_multisort($order_date, SORT_DESC, $res['data_list']);
//            }
//        }
        // $res['data_count'] = UserAttention::count('user_id=' . $uid . ' and enable=1');
        // $user_ids = UserAttention::getColumn($params, 'owner_id');
        if ($visitor) {
            $order_date = [];//排序字段
            $uids = implode(',', array_column($visitor, 'user_id'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth,signature,company,job,industry'], 'uid');
            $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
            foreach ($visitor as $i) {
                $item = $users[$i['user_id']];
                $item['created'] = (string)($i['created']);
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($item['true_name'] ? $item['true_name'] : $users[$item['uid']]['username']);
                $res['data_list'][] = $item;
                $order_date[] = $i;
            }
            array_multisort($order_date, SORT_DESC, $res['data_list']);
        }
        return $res;
    }

    /**获取个人相册 -动态图片/视频
     * @param $uid
     * @param $to_uid
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function myPhotos($uid, $to_uid, $page = 1, $limit = 10)
    {
        $res = ['data_list' => []];
        $where = 'user_id=' . $to_uid;
        //查看自己的
        if ($uid == $to_uid) {
            $list = SocialDiscussMedia::findList([$where, 'columns' => 'discuss_id,content,media,media_type,created', 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        } //查看别人的
        else {
            if (UserBlacklist::exist('owner_id=' . $to_uid . ' and user_id=' . $uid)) {
                return $res['data_list'];
            }
            if (UserPersonalSetting::exist('owner_id=' . $to_uid . ' and user_id=' . $uid . ' and scan_my_discuss=0')) {
                return $res['data_list'];
            }
            //查看权限检测
            //是好友
            if (UserContactMember::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                $where .= " and ((scan_type=" . DiscussManager::SCAN_TYPE_ALL . ") or (scan_type=" . DiscussManager::SCAN_TYPE_FRIEND . ") or (scan_type=" . DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0))";
            } else {
                $where .= " and ((scan_type=" . DiscussManager::SCAN_TYPE_ALL . ") or (scan_type=" . DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0)) and scan_type<>" . DiscussManager::SCAN_TYPE_FRIEND;
            }
            $list = SocialDiscussMedia::findList([$where, 'columns' => 'discuss_id,content,media,media_type,created', 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        }
        if ($list) {
            foreach ($list as $item) {
                //视频/图片
                $res['data_list'][] = ['media_type' => $item['media_type'], 'content' => FilterUtil::unPackageContentTag($item['content'], $uid), 'media' => $item['media'], 'created' => $item['created']];

            }
        }
        return $res['data_list'];
    }

    //生成第三方、H5登录token
    public function createToken($uid, $app_id = '')
    {
        $rand_str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%";
        $rand_count = strlen($rand_str) - 1;
        $token = '';
        for ($i = 0; $i < 5; $i++) {
            $token .= $rand_str[rand(0, $rand_count)];
        }
        $token .= time();
        $token = md5($token);

        $cacheSetting = new CacheSetting();
        //删除旧的
        if ($old = $cacheSetting->get(CacheSetting::PREFIX_USER_TOKEN, $uid . "/" . $app_id)) {
            $cacheSetting->remove(CacheSetting::PREFIX_USER_TOKEN, $uid . "/" . $app_id);
            $cacheSetting->remove(CacheSetting::PREFIX_USER_TOKEN, $old);
        }
        $cacheSetting->set(CacheSetting::PREFIX_USER_TOKEN, $token, $uid . "/" . $app_id);
        $cacheSetting->set(CacheSetting::PREFIX_USER_TOKEN, $uid . "/" . $app_id, $token);

        return $token;
    }

    //验证第三方、H5登录token
    public function checkToken($uid, $check_token, $app_id = '')
    {
        $cacheSetting = new CacheSetting();
        $app_uid = $cacheSetting->get(CacheSetting::PREFIX_USER_TOKEN, $check_token);
        if ($app_uid && $uid . "/" . $app_id == $app_uid) {
            //  $cacheSetting->remove(CacheSetting::PREFIX_USER_TOKEN, $uid);//清除token
            return true;
        } else {
            return false;
        }
    }

    //生成App登录token
    public function createAccessToken($uid)
    {
        $redis = $this->di->get('redis');

        $token_user = $redis->hGet(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $uid);
        if ($token_user) {
            $token_user = json_decode($token_user, true);
            //已过期
            if ($token_user['expire'] <= time()) {

                //删除过期的
                $redis->hDel(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $token_user['token']);
                $redis->hDel(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $uid);

                //生成新的token
                $token = $this->getRandToken($uid);
                $expire = time() + self::ACCESS_TOKEN_TIMEOUT;
                $redis->hSet(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $token, json_encode(['uid' => $uid, 'expire' => $expire]));
                $redis->hSet(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $uid, json_encode(['token' => $token, 'expire' => $expire]));
            } else {
                $token = $token_user['token'];
                $expire = $token_user['expire'];
            }
        } else {

            //生成新的token
            $token = $this->getRandToken($uid);
            $expire = time() + self::ACCESS_TOKEN_TIMEOUT;
            $redis->hSet(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $token, json_encode(['uid' => $uid, 'expire' => $expire]));
            $redis->hSet(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $uid, json_encode(['token' => $token, 'expire' => $expire]));
        }
        return ['token' => $token, 'expire' => $expire];
    }

    //校验App登录token
    public function checkAccessToken($token, $uid = '')
    {
        $redis = $this->di->get('redis');
        $token_info = $redis->hGet(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $token);
        if (!$token_info) {
            return false;
        }
        $token_info = json_decode($token_info, true);
        if ($uid) {
            //uid 对不上
            if ($token_info['uid'] != $uid) {
                return false;
            }
        }
        //超时  缓冲时间
        if (time() >= ($token_info['expire'] + self::ACCESS_TOKEN_CACHE_TIME_OUT)) {
            $redis->hDel(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $token);
            $token_user = $redis->hGet(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $uid);
            if ($token_user) {
                $token_user = json_decode($token_user, true);
                if ($token_user['token'] == $token) {
                    $redis->hDel(CacheSetting::KEY_LOGIN_ACCESS_TOKEN, $uid);
                }
            }
            return false;
        }
        return ['token' => $token, 'expire' => $token_info['expire'], 'uid' => $token_info['uid']];
    }
    //获取星座
    /**
     * @param $date 2017-09-12
     * @return string
     */
    public function getConstellation($date)
    {
        $md = intval(str_replace('-', '', substr($date, 5)));
        $res = '';
        if (321 <= $md && $md <= 419) {
            $res = 3;
        } else if (420 <= $md && $md <= 520) {
            $res = 4;
        } else if (521 <= $md && $md <= 621) {
            $res = 5;
        } else if (622 <= $md && $md <= 722) {
            $res = 6;
        } else if (723 <= $md && $md <= 822) {
            $res = 7;
        } else if (823 <= $md && $md <= 922) {
            $res = 8;
        } else if (923 <= $md && $md <= 1023) {
            $res = 9;
        } else if (1024 <= $md && $md <= 1122) {
            $res = 10;
        } else if (1123 <= $md && $md <= 1221) {
            $res = 11;
        } else if (1222 <= $md || ($md <= 119)) {
            $res = 12;
        } else if (120 <= $md && $md <= 218) {
            $res = 1;
        } else if (219 <= $md && $md <= 320) {
            $res = 2;
        }
        return $res;
    }

    /**获取随机生日
     * @return string
     */
    public function createRandBirthday($sex = 1)
    {
        if ($sex == 1) {
            $start = 20;
            $end = 35;
        } else {
            $start = 18;
            $end = 26;
        }
        $year_start = date('Y') - $end; //年份随机起始点
        $year_end = date('Y') - $start; //年份随机结束点

        $month_start = 1; //月份随机起始点
        $month_end = 12; //月份随机结束点

        $day_start = 1;//日随机起始点

        $year = rand($year_start, $year_end);
        $month = rand($month_start, $month_end);
        if (in_array($month, [1, 3, 5, 7, 8, 10, 12])) {
            $day_end = 31;
        } elseif (in_array($month, [4, 6, 9, 11])) {
            $day_end = 30;
        } //2月
        else {
            if (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0) {
                $day_end = 28;
            } else {
                $day_end = 29;
            }
        }
        $day = rand($day_start, $day_end);
        return $year . '-' . ($month < 10 ? '0' . $month : $month) . '-' . ($day < 10 ? '0' . $day : $day);
    }

    //生成随机token
    public function getRandToken($key = '')
    {
        $rand_str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%";
        $rand_count = strlen($rand_str) - 1;
        $token = '';
        for ($i = 0; $i < 5; $i++) {
            $token .= $rand_str[rand(0, $rand_count)];
        }
        $token .= time();
        $token = md5($key . $token);
        return $token;
    }

    //QQ/微信等注册时生成的后缀唯一id
    public function getRegisterUniqueID()
    {
        return $this->di->get("redis")->increment(CacheSetting::KEY_USER_REGISTER_UNIQUE_ID, 1);
    }


}