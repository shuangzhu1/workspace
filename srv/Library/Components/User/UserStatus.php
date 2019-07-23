<?php
/**
 * Created by PhpStorm.
 * User: ykuang
 * Date: 15-4-3
 * Time: 下午12:43
 */

namespace Components\User;

use Components\Rules\Point\PointRule;
use Components\Yunxin\ServerAPI;
use Models\Social\SocialDiscuss;
use Models\Social\SocialFav;
use Models\User\UserAttention;
use Models\User\UserAuthApply;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserInterview;
use Models\User\UserLocation;
use Models\User\UserPersonalSetting;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserSetting;
use Phalcon\Mvc\User\Plugin;
use Services\Discuss\DiscussManager;
use Services\Im\SysMessage;
use Services\Site\CacheSetting;
use Util\Debug;
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

    /*--用户状态--*/
    const STATUS_DELETED = 0;//被永久锁定
    const STATUS_NORMAL = 1;//正常
    const STATUS_LOCKED = 2;//被锁定，需解封

    const NEAR_DISTANCE_LIMIT = 1000;//附近的人距离限制
    const NEAR_TIME_LIMIT = 3600000;//附近的人时间限制 一小时内


    /*用户类型*/
    const USER_TYPE_NORMAL = 1;//普通用户
    const USER_TYPE_ROBOT = 2;//机器人

    /*--第三方登录类型----*/
    static $third_login_type = array(
        self::LOGIN_QQ => 'QQ',
        self::LOGIN_WEICHAT => '微信'
    );

    /*--用户状态定义--*/
    static $user_status = array(
        self::STATUS_DELETED => '被删除',
        self::STATUS_NORMAL => '正常',
        self::STATUS_LOCKED => '被锁定，需解封',
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
        $data['is_attention'] = 0; //是否已关注
        $data['is_contact'] = 0; //是否好友、联系人
        $data['contact_remark'] = "";//联系人备注
        $data['is_star'] = 0; //是否星标好友
        $data['is_blacklist'] = 0; //是否黑名单用户
        $data['fans_count'] = UserStatus::getFansCnt($to_uid); //粉丝数
        $data['attention_count'] = UserStatus::getAttentionCnt($to_uid); //关注数
        $data['discuss_count'] = SocialDiscuss::count('user_id=' . $to_uid . ' and status=' . DiscussManager::STATUS_NORMAL);//动态数
        $data['collect_count'] = 0; //收藏数
        $data['look_fans'] = 1; //是否允许查看粉丝数
        $data['look_my_discuss'] = 1; //是否允许查看我的动态
        $data['look_his_discuss'] = 1; //是否查看他/她的动态
        $data['newest_discuss_pic'] = []; //最新的动态图片/视频
        if ($uid !== $to_uid) {
            $contact = UserContactMember::findFirst(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'is_star,mark']);
            //是否联系人
            if ($contact) {
                $data['is_contact'] = 1;
                $data['is_attention'] = 1;
                $data['contact_remark'] = $contact->mark;
                $data['is_star'] = $contact->is_star;
            } else {
                //是否黑名单
                if (UserBlacklist::findFirst(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id'])) {
                    $data['is_blacklist'] = 1;
                } //是否关注
                elseif (UserAttention::findFirst(['owner_id=' . $uid . ' and user_id=' . $to_uid . ' and enable=1', 'columns' => 'id'])) {
                    $data['is_attention'] = 1;
                }
            }
            //个人设置
            $setting = UserSetting::findFirst(['user_id=' . $to_uid, 'columns' => 'look_fans']);
            $data['look_fans'] = ($setting && $setting->look_fans == 0) ? 0 : 1;

            $personal_setting = UserPersonalSetting::findFirst(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'scan_his_discuss,scan_my_discuss']);
            if ($personal_setting) {
                $data['look_my_discuss'] = $personal_setting->scan_my_discuss == 0 ? 0 : 1;
                $data['look_his_discuss'] = $personal_setting->scan_his_discuss == 0 ? 0 : 1;
            }

        } //查看自己的信息 多了收藏数
        else {
            $data['collect_count'] = SocialFav::count('user_id=' . $uid . ' and enable=1');
            $password = Users::findFirst(['id=' . $uid, 'columns' => 'password']);
            $setting = UserSetting::findFirst(['user_id=' . $uid, 'columns' => 'login_protect,look_fans']);
            $data['is_set_password'] = $password->password ? 1 : 0;
            $data['is_login_protect'] = $setting && $setting->login_protect ? 1 : 0;
            $data['look_fans'] = ($setting && $setting->look_fans == 0) ? 0 : 1;


        }
        $user_info = $this->getBaseUserInfo($to_uid);

        $data = array_merge($data, $user_info);

        //认证状态
        if ($uid == $to_uid && !$is_search) {
            //已认证
            if ($data['is_auth'] == 1) {
                $auth = UserAuthApply::findFirst(['user_id=' . $to_uid . ' and (status=1 or status=2)', 'columns' => 'status', 'order' => 'created desc']);
                if ($auth && $auth->status == 2) {
                    $data['is_auth'] = '2';//认证中
                }
            } else {
                $auth = UserAuthApply::findFirst(['user_id=' . $to_uid . ' and (status=2 or status=3)', 'columns' => 'status', 'order' => 'created desc']);
                if ($auth) {
                    $data['is_auth'] = $auth->status;//认证中/失败
                }
            }
        }
        if ($data['newest_discuss_pic']) {
            //查看别人 判断是否对方是否设置了不允许查看其动态
            if ($uid != $to_uid) {
                //对方给自己的设置
                $personal_setting = UserPersonalSetting::findFirst(['owner_id=' . $to_uid . ' and user_id=' . $uid, 'columns' => 'scan_my_discuss']);
                if ($personal_setting && $personal_setting['scan_my_discuss'] == 0) {
                    $data['newest_discuss_pic'] = [];
                } else {
                    $data['newest_discuss_pic'] = json_decode($data['newest_discuss_pic'], true);
                    foreach ($data['newest_discuss_pic'] as &$item) {
                        unset($item['id']);
                    }
                }
            } else {
                $data['newest_discuss_pic'] = json_decode($data['newest_discuss_pic'], true);
                foreach ($data['newest_discuss_pic'] as &$item) {
                    unset($item['id']);
                }
            }

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
            $columns = 'user_id as uid,status,username,birthday,true_name,phone,avatar,photos,coins,grade,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,auth_desc,introduce,job,industry,company,signature,newest_discuss_pic,created,voice_introduce';
        }
        $user_info = UserInfo::findFirst(['user_id=' . $uid, 'columns' => $columns]);
        $data = $user_info ? $user_info->toArray() : [];
        return $data;
    }

    /**缓存用户基本信息【变动较小】
     * @param $uid
     * @param bool $refresh
     * @return array|static
     */
    public function getCacheUserInfo($uid, $refresh = false)
    {
        $cache = new CacheSetting();
        $data = $cache->get($cache::PREFIX_USER_BASE_INFO, $uid);
        if (!$data || $refresh) {
            $columns = 'user_id as uid,status,username,true_name,phone,avatar,photos,coins,grade,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,auth_desc,introduce,job,industry,company,signature,voice_introduce';
            $data = UserInfo::findFirst(['user_id=' . $uid, 'columns' => $columns]);
            $data = $data->toArray();
            $cache->set($cache::PREFIX_USER_BASE_INFO, $uid, $data);
        }
        return $data;
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
        return UserAttention::count('user_id=' . $uid /*. ' and enable=1'*/);
    }

    /**获取关注数
     * @param $uid
     * @return mixed
     */
    public function getAttentionCnt($uid)
    {
        return UserAttention::count('owner_id=' . $uid /*. ' and enable=1'*/);

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
            $user = Users::findFirst('id=' . $uid);
            if (!$user->save($data)) {
                return false;
            }
            //云信接口调用
            $yx = ServerAPI::init()->updateUinfo($uid, $user->username, $user->avatar);

            //更新默认备注
            if (!empty($data['username'])) {
                $this->db->query("update user_contact_member set default_mark='" . $data['username'] . "' where user_id=" . $uid);
                $this->db->query("update group_member set default_nick='" . $data['username'] . "' where user_id=" . $uid);
            }
            unset($data['avatar']);
            unset($data['username']);
        }
        //更新user_profile表数据
        if ($data) {
            $user = UserProfile::findFirst('user_id=' . $uid);

            //送经验值
            $signature = ($user->signature == '' && !empty($data['signature'])) ? 1 : 0; //完善个性签名送经验值
            $area = ($user->city_id == 0 && !empty($data['city_id'])) ? 1 : 0; //完善地区信息送经验值


            if (!$user->save($data)) {
                return false;
            }
            if ($signature) {
                PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_FINISH_INFO_SIGNATURE);
            }
            if ($area) {
                PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_FINISH_INFO_AREA);
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
            $user_location = $this->db->query("select l.user_id,l.created,lng,lat from user_location as l left join user_profile as p on l.user_id=p.user_id where " . $where . $limit_str)->fetchAll(\PDO::FETCH_ASSOC);
        } //列表模式
        else {
            $user_location = $this->db->query("select GetDistances(lat,lng,$lat,$lng) as distance,l.user_id,l.created,lng,lat from user_location as l left join user_profile as p on l.user_id=p.user_id where " . $where . ' order by distance asc' . $limit_str)->fetchAll(\PDO::FETCH_ASSOC);
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
            $users = UserInfo::find(['user_id in(' . $uids . ')', 'columns' => 'user_id as uid,username,status,avatar,sex,signature,is_auth,auth_type,auth_desc,job,company,industry,grade'])->toArray();
            $blackList = UserBlacklist::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid'], 'uid');//黑名单列表
            $contactList = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人列表
            $attentionList = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid'], 'uid');//关注列表

            $hide_users = UserSetting::getColumn(['user_id in (' . $uids . ') and hide_location=1', 'columns' => 'user_id'], 'user_id');
            foreach ($users as &$item) {
                //去除隐藏自己位置的用户
                if (in_array($item['uid'], $hide_users)) {
                    continue;
                }
                $item['username'] = isset($contactList[$item['uid']]) && $contactList[$item['uid']]['mark'] ? $contactList[$item['uid']]['mark'] : $item['username'];
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
                $res['data_list'][] = $item;
                $distance_order[] = $user_location_distance[$item['uid']];
            }
            array_multisort($distance_order, SORT_ASC, $res['data_list']);
        }
        return $res;
    }

    /*更新访客*/
    public function updateVisitor($uid, $to_uid)
    {
        //访问者数据入库
        $data = UserInterview::findFirst(['user_id=' . $to_uid]);
        $add_cnt = false;//是否添加访客数
        $im_push = false;//是否推送消息 同一个有用户 5分钟内不推送

        //该用户已经存在一条数据
        if ($data) {
            //之前有人访问过
            if ($data->interview != '') {
                $interview = json_decode($data->interview);
                //没有访问过该用户
                if (!$interview->$uid) {
                    $add_cnt = true;
                } else {
                    $im_push = time() - $interview->$uid >= 300 ? true : false;
                }

                $interview->$uid = time();
                $interview = json_encode($interview, true);
            } //之前没有人访问过
            else {
                $interview = json_encode([$uid => time()], true);
                $add_cnt = true;
                $im_push = true;
            }
            $data->interview = $interview;
            //更新访客数
            if ($data->save() && $add_cnt) {
                $this->db->execute("update user_profile set visitor=visitor+1 where user_id=" . $to_uid);
            }
        } //该用户没有存在数据-没有访问过别人，也没有被访问过
        else {
            $data = new UserInterview();
            $interview = json_encode([$uid => time()], true);
            $data->user_id = $to_uid;
            $data->interview = $interview;

            //更新访客数
            if ($data->save()) {
                $this->db->execute("update user_profile set visitor=visitor+1 where user_id=" . $to_uid);
            }
            $im_push = true;
        }

        //被访问者数据入库 -类似访问者入库
        $data = UserInterview::findFirst(['user_id=' . $uid]);
        if ($data) {
            if ($data->interviewee != '') {
                $interviewee = json_decode($data->interviewee);
                $interviewee->$to_uid = time();
                $interviewee = json_encode($interviewee, true);
            } else {
                $interviewee = json_encode([$to_uid => time()], true);
            }
            $data->interviewee = $interviewee;
            $data->save();
        } else {
            $data = new UserInterview();
            $interviewee = json_encode([$to_uid => time()], true);
            $data->user_id = $uid;
            $data->interviewee = $interviewee;
            $data->save();
        }


        //消息推送
        if ($im_push) {
            SysMessage::init()->initMsg(SysMessage::TYPE_NEW_VISITOR, ["to_user_id" => $to_uid]);
        }
    }


}