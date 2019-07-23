<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/3
 * Time: 14:54
 */

namespace Multiple\Wap\Helper;


use Components\Rsa\lib\Sign;
use Components\Yunxin\ServerAPI;
use Models\Shop\Shop;
use Models\Site\SiteGift;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussMedia;
use Models\Social\SocialFav;
use Models\User\UserAttention;
use Models\User\UserAuthApply;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserCountStat;
use Models\User\UserGift;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserSetting;
use Models\User\UserTags;
use Models\User\UserThirdParty;
use Phalcon\Mvc\User\Component;
use Services\Discuss\DiscussManager;
use Services\Im\ImManager;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Util\Ajax;
use Util\Cookie;
use Util\Debug;
use Util\GetClient;
use Util\Validator;

class UserStatus extends Component
{
    const _logtype_cookie_name = "_APP_LOG_TYPE_klg";
    const _uid_cookie_name = '_APP_CUID_klg';
    const _openid_cookie_name = '_APP_OPENID_klg';
    const _isbind_cookie_name = '_APP_IS_BIND_klg';
    const _usr_cookie_name = '_APP_CUSR_ckg';
    const _point_cookie_name = '_APP_CUSR_POINT_klg';
    const _is_logged_cookie_name = '_APP_IS_LOGGED_klg';

    private $wechat_binded = false;
    private $weibo_binded = false;

    /**
     * @var Ajax
     */
    protected $ajax = null;

    public static function init()
    {
        return new self();
    }

    /**
     * ajax user login
     */
    public function ajaxLogin()
    {
        $phone = $this->request->getPost('phone');
        $password = $this->request->getPost('pass');
        if (!$phone) {
            Ajax::outError(Ajax::ERROR_PHONE_IS_INVALID);
        }

        if ((strlen($password) <= 0)) {
            Ajax::init()->outError(Ajax::ERROR_PASSWD_IS_INVALID);
        }

        $user = Users::findOne('phone = "' . $phone . '"');
        if (!$user) {
            Ajax::init()->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }

        if ($user['password'] != md5($user['password_salt'] . $password)) {
            Ajax::init()->outError(Ajax::ERROR_USER_OR_PASSWORD);
        }

        //被永久删除了
        if ($user['status'] == \Services\User\UserStatus::STATUS_DELETED) {
            Ajax::init()->outError(Ajax::ERROR_USER_DELETED);
        } //被临时锁定了
        else if ($user['status'] == \Services\User\UserStatus::STATUS_LOCKED) {
            Ajax::init()->outError(Ajax::ERROR_USER_HAS_BEING_LOCKED);
        }
        $this->saveStatus($user['username'], 'wap', $user['id']);
        // res
        $res = array(
            'uid' => $this->getUid(),
            'username' => $user['username'],
        );
        Ajax::init()->outRight($res);
    }

    //app登录
    public function appLogin()
    {
        $uid = $this->request->getPost('uid');
        $token = $this->request->getPost('token');
        if (!$uid) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }

        $user = Users::findOne(['id = "' . $uid . '"', 'columns' => 'id,username,status']);
        if (!$user) {
            Ajax::init()->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }
        if (!\Services\User\UserStatus::getInstance()->checkToken($uid, $token)) {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, "无效的token数据");
        }

        //被永久删除了
        if ($user['status'] == \Services\User\UserStatus::STATUS_DELETED) {
            Ajax::init()->outError(Ajax::ERROR_USER_DELETED);
        } //被临时锁定了
        else if ($user['status'] == \Services\User\UserStatus::STATUS_LOCKED) {
            Ajax::init()->outError(Ajax::ERROR_USER_HAS_BEING_LOCKED);
        }
        $this->saveStatus($user['username'], 'wap', $user['id']);
        // res
        $res = array(
            'uid' => $this->getUid(),
            'username' => $user['username'],
        );
        Ajax::init()->outRight($res);
    }

    public function registerThirdUser($open_id, $union_id, $open_type, $nick, $avatar, $sex)
    {
//        //微信登录
//        if ($open_type == \Services\User\UserStatus::LOGIN_WEICHAT) {
//
//        }
        try {

            $this->db->begin();
            $this->original_mysql->begin();
            $avatar = $avatar ? $avatar : \Services\User\UserStatus::$default_avatar; //头像初始化
            $nick = $nick ? $nick : ($open_type == 1 ? "QQ用户" : "微信用户"); //昵称初始化
            /**----users表插入数据------**/
            $user = new Users();

            $user_data = [
                "username" => $nick,
                "avatar" => $avatar,
                'true_name' => "",
                'points' => 0,
                'phone' => '',
                'coins' => 0,
                'grade' => 1,
                'created' => time(),
            ];

            if (!$user_id = $user->insertOne($user_data)) {
                $message = [];
                foreach ($user->getMessages() as $msg) {
                    $message[] = $msg;
                }
                throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
            }
            /**----云信注册---**/
            $res = ServerAPI::init()->createUserId($user_id, $nick, '', $avatar);
            if (!$res || $res['code'] != 200) {
                throw new \Exception('云信注册失败-' . $res['desc']);
            }

            /**----user_profile表插入数据------**/
            $user_profile = new UserProfile();
            $profile_data = [
                'user_id' => $user_id,
                'sex' => $sex == 0 ? 1 : $sex,
                'platform' => "pc",
                'yx_token' => $res['info']['token'],
                'register_type' => ($open_type == \Services\User\UserStatus::LOGIN_QQ ? \Services\User\UserStatus::REGISTER_TYPE_QQ : \Services\User\UserStatus::REGISTER_TYPE_WEIXIN),
                "register_ip" =>  GetClient::Getip(),
                'birthday' => ''// UserStatus::getInstance()->createRandBirthday($sex),
            ];
            // $profile_data['constellation'] = UserStatus::getInstance()->getConstellation($profile_data['birthday']);
            if (!$user_profile->insertOne($profile_data)) {
                $message = [];
                foreach ($user_profile->getMessages() as $msg) {
                    $message[] = $msg;
                }
                throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
            }
            /**---user_third_party表插入数据--**/
            $user_third_party = new UserThirdParty();
            $third_party_data = [
                'user_id' => $user_id,
                'open_id' => $open_id,
                'union_id' => $union_id,
                'nick' => $nick,
                'avatar' => $avatar,
                'type' => $open_type,
                'sex' => $sex
            ];
            if (!$user_third_party->insertOne($third_party_data)) {
                $message = [];
                foreach ($user_third_party->getMessages() as $msg) {
                    $message[] = $msg;
                }
                throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
            }


            //插入数据统计
            UserCountStat::insertOne(['user_id' => $user_id, 'created' => time()]);


            $this->db->commit();
            $this->original_mysql->commit();

            // 发送im系统消息
            ImManager::init()->initMsg(ImManager::TYPE_REGISTER, ['user_name' => $nick, 'to_user_id' => $user_id]);
            //发送推荐用户名片
            $recommend_users = Users::findList(['created<=' . $user_data['created'] . ' and status=' . \Services\User\UserStatus::STATUS_NORMAL . ' and id<>' . $user_id, 'limit' => 2, 'order' => 'created desc', 'columns' => 'id,username,avatar,created']);
            if ($recommend_users) {
                foreach ($recommend_users as $i) {
                    ImManager::init()->initMsg(ImManager::TYPE_USER, ['to_user_id' => $user_id, 'user_name' => $i['username'], 'avatar' => $i['avatar'], 'user_id' => $i['id']]);
                }
            }
            $user = Users::findOne('id="' . $user_id . '"');


            //开通钱包账户
            //Request::getPost(Base::OPEN_ACCOUNT, ['uid' => $user_id]);

            return $user_id;

        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            $this->db->rollback();
            $this->original_mysql->rollback();
            echo "注册失败";
            exit;
            return 0;
        }
    }

    /**
     * ajax user reg
     */
    public function ajaxReg()
    {

    }

    public function checkPhone($phone)
    {
        $user = Users::findOne("phone='{$phone}'");
        return $user;
    }

    /**
     * logout
     *
     */
    public static function logout()
    {
        Cookie::del(self::_uid_cookie_name);
        Cookie::del(self::_usr_cookie_name);
        Cookie::del(self::_logtype_cookie_name);
        Cookie::del(self::_isbind_cookie_name);
        Cookie::del(self::_openid_cookie_name);
        Cookie::del(self::_is_logged_cookie_name);
    }

    public static function isLogged()
    {
        if (Cookie::exists(self::_is_logged_cookie_name) && Cookie::get(self::_is_logged_cookie_name) == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function checkLogin()
    {
        $logType = Cookie::get(self::_logtype_cookie_name);
        $uid = $this->getUid();
        if (!$uid) {
            $cur_url = $this->uri->fullUrl();
            $redirect = 'user/login?go=' . urlencode($cur_url);
            return $this->response->redirect($redirect)->send();
        } else {
            return $uid;
        }
    }

    /**
     * get login user uid
     *
     * @return bool|mixed
     */
    public static function getUid()
    {
        if (!Cookie::exists(self::_uid_cookie_name)) {
            return false;
        }
        return Cookie::get(self::_uid_cookie_name);
    }


    /**
     * save login user id and name
     */
    public function saveStatus($name, $logtype, $uid = null, $open_id = null, $isbind = null)
    {

        Cookie::set(self::_logtype_cookie_name, $logtype, Cookie::oneMonth);
        Cookie::set(self::_isbind_cookie_name, $isbind, Cookie::oneMonth);
        Cookie::set(self::_openid_cookie_name, $open_id, Cookie::oneMonth);
        Cookie::set(self::_uid_cookie_name, $uid, Cookie::oneMonth);
        Cookie::set(self::_usr_cookie_name, $name, Cookie::oneMonth);
        Cookie::set(self::_is_logged_cookie_name, 1, Cookie::oneMonth);
    }


    public function isWeibo()
    {
        $userAgent = strtolower($this->request->getServer("HTTP_USER_AGENT"));
        if (strpos($userAgent, 'weibo') !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function isWechat()
    {
        $userAgent = strtolower($this->request->getServer("HTTP_USER_AGENT"));
        if (strpos($userAgent, 'micromessenger') !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserByUid($uid)
    {
        if (!$uid) {
            return false;
        }

        $user = Users::findOne('id = ' . $uid);
        return $user;
    }

    public function getLoggedOpenId()
    {
        return Cookie::get(self::_openid_cookie_name);
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

    /**获取用户基本信息
     * @param $uid
     * @param $columns
     * @return mixed
     */
    public function getBaseUserInfo($uid, $columns = '')
    {
        //星座
        $constellation = array(
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
        if ($columns == '') {
            $columns = 'user_id as uid,status,username,birthday,true_name,phone,avatar,coins,grade,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,auth_desc,introduce,job,industry,company,signature,newest_discuss_pic,created,voice_introduce,discuss_media_count as discuss_pic_count,website,charm,constellation,photos';
        }
        $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => $columns]);
        if (empty($user_info['photos']))
            $user_info['photos'] = $user_info['avatar'];
        //查询收到的礼物列表
        $res = UserGift::findList(['user_id=' . $uid, 'order' => 'id desc', 'columns' => 'gift_id,own_count']);
        $count = 0;
        if ($res) {
            $gift_ids = array_unique(array_column($res, 'gift_id'));
            $gift = SiteGift::getByColumnKeyList(['id in (' . implode(',', $gift_ids) . ')'], 'id');
            foreach ($res as &$item) {
                $item['thumb'] = $gift[$item['gift_id']]['thumb'];
                $item['is_vip'] = $gift[$item['gift_id']]['is_vip'];
                $item['enable'] = $gift[$item['gift_id']]['enable'];
                $item['coins'] = $gift[$item['gift_id']]['coins'];
                $item['name'] = $gift[$item['gift_id']]['name'];
                $item['charm'] = $gift[$item['gift_id']]['charm'];

                $count += $item['own_count'];
            }
        }
        //店铺
        $shops = Shop::findList(['user_id = ' . $uid . ' and status = 1', 'columns' => "id,name"]);
        !empty($shops) && $user_info['shops'] = $shops;
        //用户标签
        $tags = UserTags::findOne(['user_id = ' . $user_info['uid']]);
        !empty($tags) && $user_info['tags'] = $tags;
        $user_info['gift']['list'] = $res;
        $user_info['gift']['count'] = $count;
        $user_info['constellation'] = $constellation[$user_info['constellation']];
        return $user_info;
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
        $data['is_attention'] = 0; //是否已关注
        $data['is_contact'] = 0; //是否好友、联系人
        $data['contact_remark'] = "";//联系人备注
        $data['is_star'] = 0; //是否星标好友
        $data['is_blacklist'] = 0; //是否黑名单用户
        $data['fans_count'] = $this->getFansCnt($to_uid); //粉丝数
        $data['attention_count'] = $this->getAttentionCnt($to_uid); //关注数
        $data['discuss_count'] = SocialDiscuss::dataCount('user_id=' . $to_uid . ' and status=' . DiscussManager::STATUS_NORMAL);//动态数
        $data['collect_count'] = 0; //收藏数
        $data['look_fans'] = 1; //是否允许查看粉丝数
        $data['look_my_discuss'] = 1; //是否允许查看我的动态
        $data['look_his_discuss'] = 1; //是否查看他/她的动态
        $data['newest_discuss_pic'] = []; //最新的动态图片/视频
        $data['discuss_pic_count'] = 0; //动态图片/视频数量
        if ($uid) {
            if ($uid !== $to_uid) {
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

            } //查看自己的信息 多了收藏数
            else {
                $data['collect_count'] = SocialFav::dataCount('user_id=' . $uid . ' and enable=1');
                $password = Users::findOne(['id=' . $uid, 'columns' => 'password']);
                $setting = UserSetting::findOne(['user_id=' . $uid, 'columns' => 'login_protect,look_fans']);
                $data['is_set_password'] = $password['password'] ? 1 : 0;
                $data['is_login_protect'] = $setting && $setting['login_protect'] ? 1 : 0;
                $data['look_fans'] = ($setting && $setting['look_fans'] == 0) ? 0 : 1;

            }


            $data = array_merge($data, $user_info);
            $data['discuss_pic_count'] = intval($data['discuss_pic_count']);
            //认证状态
            if ($uid == $to_uid && !$is_search) {
                //已认证
                if ($data['is_auth'] == 1) {
                    $auth = UserAuthApply::findOne(['user_id=' . $to_uid . ' and (status=1 or status=2)', 'columns' => 'status', 'order' => 'created desc']);
                    if ($auth && $auth['status'] == 2) {
                        $data['is_auth'] = '2';//认证中
                    }
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
//                            foreach ($data['newest_discuss_pic'] as &$item) {
//                                unset($item['id']);
//                            }
                        }
                    } else {
                        $data['newest_discuss_pic'] = [];
                    }

                } else {
                    $data['newest_discuss_pic'] = json_decode($data['newest_discuss_pic'], true);
//                    foreach ($data['newest_discuss_pic'] as &$item) {
//                        unset($item['id']);
//                    }
                }

            } else {
                $data['newest_discuss_pic'] = [];
            }
        } else {
            $user_info = $this->getBaseUserInfo($to_uid);

            $data = array_merge($data, $user_info);
            $data['discuss_pic_count'] = intval($data['discuss_pic_count']);
            //认证状态
            if ($uid == $to_uid && !$is_search) {
                //已认证
                if ($data['is_auth'] == 1) {
                    $auth = UserAuthApply::findOne(['user_id=' . $to_uid . ' and (status=1 or status=2)', 'columns' => 'status', 'order' => 'created desc']);
                    if ($auth && $auth['status'] == 2) {
                        $data['is_auth'] = '2';//认证中
                    }
                } else {
                    $auth = UserAuthApply::findOne(['user_id=' . $to_uid . ' and (status=2 or status=3)', 'columns' => 'status', 'order' => 'created desc']);
                    if ($auth) {
                        $data['is_auth'] = $auth['status'];//认证中/失败
                    }
                }
            }
            if ($data['newest_discuss_pic']) {
                $data['newest_discuss_pic'] = json_decode($data['newest_discuss_pic'], true);
//                foreach ($data['newest_discuss_pic'] as &$item) {
//                    unset($item['id']);
//                }
            } else {
                $data['newest_discuss_pic'] = [];
            }
        }
        unset($data['password']);
        return $data;
    }

    /**获取个人相册 -动态图片/视频
     * @param $uid
     * @param $to_uid
     * @param int $page
     * @param int $limit
     * @param int $last_day
     * @return mixed
     */
    public static function myPhotos($uid, $to_uid, $page = 1, $limit = 10, $last_day)
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $where = 'user_id=' . $to_uid;
        if ($last_day) {
            $where .= 'time>' . $last_day;
        }
        $ids = SocialDiscussMedia::getColumn([$where, 'group' => 'time', 'order' => 'time desc', 'columns' => 'group_concat(id) as ids,time', 'offset' => ($page - 1) * $limit, 'limit' => $limit], 'ids', 'time');
        if ($ids) {
            $ids_all = [];
            //总数据量
            $res['data_count'] = SocialDiscussMedia::dataCount([$where, 'group' => 'time']);
            foreach ($ids as $key => $id) {
                $ids[$key] = explode(',', $id);
                $ids_all = array_merge($ids_all, $ids[$key]);
            }
            $where = 'user_id=' . $to_uid . " and id in (" . implode(',', $ids_all) . ')';
            //查看自己的
            if ($uid == $to_uid) {
                $list = SocialDiscussMedia::findList([$where, 'columns' => 'discuss_id,content,media,media_type,created,time', 'order' => 'created desc']);
            } //查看别人的
            else {
                if ($uid) {
                    if (UserBlacklist::exist('owner_id=' . $to_uid . ' and user_id=' . $uid)) {
                        return $res;
                    }
                    if (UserPersonalSetting::exist('owner_id=' . $to_uid . ' and user_id=' . $uid . ' and scan_my_discuss=0')) {
                        return $res;
                    }
                    //查看权限检测
                    //是好友
                    if (UserContactMember::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                        $where .= " and ((scan_type=" . DiscussManager::SCAN_TYPE_ALL . ") or (scan_type=" . DiscussManager::SCAN_TYPE_FRIEND . ") or (scan_type=" . DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0))";
                    } else {
                        $where .= " and ((scan_type=" . DiscussManager::SCAN_TYPE_ALL . ") or (scan_type=" . DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0)) and scan_type<>" . DiscussManager::SCAN_TYPE_FRIEND;
                    }
                } else {
                    $where .= " and ((scan_type=" . DiscussManager::SCAN_TYPE_ALL . ") or (scan_type=" . DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0)) and scan_type<>" . DiscussManager::SCAN_TYPE_FRIEND;
                }

                $list = SocialDiscussMedia::findList([$where, 'columns' => 'discuss_id,content,media,media_type,created,time', 'order' => 'created desc']);
            }
            if ($list) {
                $result = [];
                foreach ($list as $item) {
                    if (!isset($result[$item['time']])) {
                        $result[$item['time']] = [$item];
                    } else {
                        $result[$item['time']][] = $item;
                    }
                }
                $res['data_list'] = $result;
            }
        }
        return $res;
    }

    public static function getAge($birthday)
    {
        $age = strtotime($birthday);
        if ($age === false) {
            return false;
        }
        list($y1, $m1, $d1) = explode("-", date("Y-m-d", $age));
        $now = time();
        list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
        $age = $y2 - $y1;
        if ((int)($m2 . $d2) < (int)($m1 . $d1))
            $age -= 1;
        return $age;
    }


}