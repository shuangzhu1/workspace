<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/29
 * Time: 13:59
 */

namespace Multiple\Api\Controllers;

use Components\Kafka\Producer;
use Components\Passport\Identify;
use Components\PhoneModel\phoneModel;
use Components\Redis\RedisComponent;
use Components\Rsa\lib\Sign;
use Components\Rules\Coin\PointRule;
use Models\Shop\Shop;
use Models\Social\SocialDiscussMedia;
use Models\User\MessagePushTime;
use Models\User\UserCountStat;
use Models\User\UserInfo;
use Models\User\UserNickUpdateLog;
use Models\User\UserTags;
use Services\Im\SysMessage;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Shop\ShopManager;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\User\Behavior\Behavior;
use Services\User\SystemPushManager;
use Services\User\UserStatus;
use Components\Yunxin\ServerAPI;
use Models\Site\AreaProvince;
use Models\User\UserInterview;
use Models\User\UserLocation;
use Models\User\UserLoginLog;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserSetting;
use Models\User\UserThirdParty;
use Services\Discuss\DiscussManager;
use Services\Im\ImManager;
use Services\Im\NotifyManager;
use Services\Site\AreaManager;
use Services\Site\VerifyCodeManager;
use Services\User\WelfareManager;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;
use Util\GetClient;
use Util\Validator;

class UserController extends ControllerBase
{
    public function indexAction()
    {

    }

    public function testAction()
    {

    }

    /*注册*/
    public function registerAction()
    {
        $phone = $this->request->get('phone', 'string', '');//手机号码
        $code = $this->request->get('code', 'string', '');//手机验证码
        $pwd = $this->request->get('pwd', 'string', '');//密码 RSA加密
        $nick = $this->request->get('username', 'green', '');//用户昵称
        $avatar = $this->request->get('avatar', 'string', UserStatus::$default_avatar);//用户头像
        $sex = $this->request->get('sex', 'int', 0);//1-男 2-女 0-未知
        $birthday = $this->request->get('birthday', 'string', '');//生日
        //  Debug::log(var_export($_REQUEST, true), 'debug');
        //无效的参数
        if (!$phone || !$code || !$pwd || !$nick) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //无效的手机号
        if (!Validator::validateCellPhone($phone)) {
            $this->ajax->outError(Ajax::INVALID_PHONE);
        }
        //无效的验证码
        if (Validator::validVerifyCode($code)) {
            $this->ajax->outError(Ajax::ERROR_VERIFY_CODE);
        }
        $msg = VerifyCodeManager::init()->checkVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_REGISTER], $this->client_type, 0, $code);
        if ($msg != '1') {
            $this->ajax->outError($msg);
        }
        if (!Validator::validateNick($nick)) {
            $this->ajax->outError(Ajax::ERROR_USERNAME_PREG);
        }
//        if (Users::exist("username='" . $nick . "'")) {
//            $this->ajax->outError(Ajax::ERROR_NICK_HAS_BEEN_USED);
//        }
        // $pwd = Sign::publicSign($pwd);
        // ** 检测密码
        $Sign = new Sign($this->client_type, $this->app_version);
        $pwd = $Sign->signStr($pwd);
        //  $pwd = Sign::signStr($pwd);
        if (!$pwd) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        if (!Validator::validPassword($pwd)) {
            $this->ajax->outError(Ajax::ERROR_PASSWD_IS_INVALID);
        }

        //保证并发同一手机号 多次调用注册多个账号问题
        $lock = $this->redis->setNX($phone, 1, 30);
        if (!$lock) {
            Debug::log("注册同一手机并发调用:" . $phone, 'error');
            $this->ajax->outError(Ajax::FAIL_REGISTER);
        }

        $user = Users::findOne(['phone="' . $phone . '"', 'columns' => 'id,status']);
        //该手机已注册
        if ($user) {
            $this->ajax->outError(Ajax::ERROR_PHONE_EXIST);
        }
        try {
            $avatar = $avatar ? $avatar : UserStatus::$default_avatar;//默认头像

            $this->db->begin();
            $this->original_mysql->begin();
            /**----users表插入数据------**/
            $user = new Users();
            $salt = UserStatus::getSalt();
            $user_data = [
                "username" => $nick,
                "phone" => $phone,
                "avatar" => $avatar,
                "password_salt" => $salt,
                "password" => md5($salt . $pwd),
                "created" => time(),

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
                "user_id" => $user_id,
                "platform" => $this->client_type,
                "sex" => $sex,
                "register_type" => UserStatus::REGISTER_TYPE_PHONE,
                "register_ip" => GetClient::Getip(),
                "birthday" => $birthday, //? $birthday : UserStatus::getInstance()->createRandBirthday($sex),
                "yx_token" => $res['info']['token'],
            ];
            if ($birthday) {
                // $profile_data['birthday'] = $birthday;
                $profile_data['constellation'] = UserStatus::getInstance()->getConstellation($birthday);
            }
            if (!$user_profile->insertOne($profile_data)) {
                $message = [];
                foreach ($user_profile->getMessages() as $msg) {
                    $message[] = $msg;
                }
                throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
            }
            //插入数据统计
            UserCountStat::insertOne(['user_id' => $user_id, 'created' => time()]);
            //送经验值
            if ($birthday) {
                PointRule::init()->executeRule($user_id, \Components\Rules\Point\PointRule::BEHAVIOR_USER_BIRTHDAY);
            }
            $this->db->commit();
            $this->original_mysql->commit();

            VerifyCodeManager::init()->clearVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_REGISTER], $this->client_type, 0);//清除验证码
            // 发送im系统消息
            ImManager::init()->initMsg(ImManager::TYPE_REGISTER, ['user_name' => $nick, 'to_user_id' => $user_id]);

            //发送推荐用户名片
            $recommend_users = $this->db->query("select user_id,avatar,username,birthday,grade,constellation from users as u  left join user_profile as p on u.id=p.user_id where " . ' u.status=' . UserStatus::STATUS_NORMAL . ' and u.id<>' . $user_id . " and u.last_login_time>=" . strtotime("-7 days") . " and u.avatar<>'" . UserStatus::$default_avatar . "' and p.sex=" . ($sex == 1 ? 2 : 1) . " and p.birthday<>'' and u.user_type=1 order by rand() limit 2")->fetchAll();
            // $recommend_users = UserInfo::findList(['status=' . UserStatus::STATUS_NORMAL . ' and user_id<>' . $user_id . " and sex=" . ($sex == 1 ? 2 : 1), 'order' => 'rand()', 'limit' => 2, 'columns' => 'user_id,username,avatar,sex,grade']);
            if ($recommend_users) {
                foreach ($recommend_users as $i) {
                    ImManager::init()->initMsg(ImManager::TYPE_USER,
                        [
                            'to_user_id' => $user_id,
                            'user_name' => $i['username'],
                            'constellation' => UserStatus::$constellation[$i["constellation"]],
                            'avatar' => $i['avatar'],
                            'user_id' => $i['user_id'],
                            'sex' => $i['sex'],
                            'grade' => $i['grade'],
                            'birthday' => $i['birthday'],
                        ]);
                }
            }
            //推送新手教程
            SystemPushManager::init()->NewbieTutorial($user_id);

            //开通钱包账户
            //   Request::asyncPost(Base::OPEN_ACCOUNT, ['uid' => $user_id]);
            /* if($phone=='13560487593'){
             }else{
                 Request::getPost(Base::OPEN_ACCOUNT, ['uid' => $user_id]);
             }*/
            //去除并发锁
            $this->redis->del($phone);

            $this->ajax->outRight('注册成功', Ajax::SUCCESS_REGISTER);
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();

            //去除并发锁
            $this->redis->del($phone);
            Debug::log($e->getMessage(), 'error');
            $this->ajax->outError(Ajax::FAIL_REGISTER);
        }
    }

    /*--登录--*/
    public function loginAction()
    {
        $open_id = $this->request->get('open_id', 'string', '');//第三方登录
        $union_id = $this->request->get('union_id', 'string', '');//第三方登录

        $open_type = $this->request->get('open_type', 'int', 0);//第三方类型,1-qq,2-微信【具体详见UserStatus】
        $nick = $this->request->get('open_nick', 'string', '');//昵称
        $avatar = $this->request->get('open_avatar', 'string', '');//用户头像
        $sex = $this->request->get('open_sex', 'int', 0);//性别 1-男，2-女，0-未知
        $push_device_id = $this->request->get('push_device_id', 'string', ''); // 推送设备id,jpush对应的registration_id
        $os_version = $this->request->get('os_version', 'string', ''); // 操作系统版本号 例如iOS10.1.1
        $phone_model = $this->request->get('phone_model', 'string', ''); // 手机型号如 华为荣耀6,iphone5s
        $device_id = $this->request->get('device_id', 'string', ''); // 手机设备号 手机唯一标识
        $code = $this->request->get('code', 'string', ''); // 登录保护/账号被锁定 认证验证码
        $welcome = $this->request->get('welcome', 'int', 0); // 登录发送欢迎语

        $phone_model = phoneModel::instance($this->client_type)->getName($phone_model);
        $phone = $this->request->get('phone', 'string', '');//手机号码
        $pwd = $this->request->get('pwd', 'string', '');//登录密码-RSA加密
        if ((!$open_id && !$phone) /*|| !$device_id*/) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //this
        /*if ($phone ==  18888888885) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }*/

        $user = '';
        /*--手机登录--*/
        if ($phone) {
            $error_cnt = $this->redis->hGet(CacheSetting::KEY_USER_PASSWORD_ERROR_CNT, $phone);
            if ($error_cnt >= 5) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "账号存在异常,请找回密码");
            }

            if (!$pwd) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            // ** 检测密码
            $Sign = new Sign($this->client_type, $this->app_version);
            $pwd = $Sign->signStr($pwd);
            //  $pwd = Sign::signStr($pwd);
            if (!$pwd) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }


            $user = Users::findOne('phone="' . $phone . '"');

        } /*--第三方登录--*/
        else {
            Debug::log("第三方登录信息:" . var_export($_REQUEST, true));
            if (!key_exists($open_type, UserStatus::$third_login_type)) {
                $this->ajax->outError(Ajax::INVALID_LOGIN_TYPE);
            }
            //微信登录
            if ($open_type == UserStatus::LOGIN_WEICHAT) {
                //新版本
                if ($union_id) {
                    //IOS
                    if ($this->client_type == 'ios') {
                        $third_user = UserThirdParty::findOne(['open_id="' . $union_id . '" and type=' . UserStatus::LOGIN_WEICHAT, 'columns' => 'user_id,union_id,id']);
                        if (!$third_user) {
                            $third_user = UserThirdParty::findOne(['open_id="' . $open_id . '" and type=' . UserStatus::LOGIN_WEICHAT, 'columns' => 'union_id,user_id,id']);
                            //旧账号 没有保存union_id
                            if ($third_user && !$third_user['union_id']) {
                                UserThirdParty::updateOne(['union_id' => $union_id], 'id=' . $third_user['id']);
                            }
                        } else {
                            //旧账号 并且没有存在两份【ios和安卓】  替换union_id和open_id
                            if (!UserThirdParty::exist('open_id="' . $open_id . '" and type=' . UserStatus::LOGIN_WEICHAT)) {
                                UserThirdParty::updateOne(['open_id' => $open_id, 'union_id' => $union_id], 'id=' . $third_user['id']);
                            }
                        }
                    } else {
                        //安卓
                        $third_user = UserThirdParty::findOne(['open_id="' . $open_id . '" and type=' . UserStatus::LOGIN_WEICHAT, 'columns' => 'user_id,union_id,id']);
                        if (!$third_user) {
                            $third_user = UserThirdParty::findOne(['open_id="' . $union_id . '" and type=' . UserStatus::LOGIN_WEICHAT, 'columns' => 'user_id,id']);
                            //替换open_id和union_id
                            if ($third_user) {
                                UserThirdParty::updateOne(['open_id' => $open_id, 'union_id' => $union_id], 'id=' . $third_user['id']);
                            }
                        } else {
                            if (!$third_user['union_id']) {
                                UserThirdParty::updateOne(['union_id' => $union_id], 'id=' . $third_user['id']);
                            }
                        }
                    }

                } else {
                    //老版本
                    //ios之前传的union_id 做兼容处理
                    if ($this->client_type == 'ios') {
                        strpos($open_id, "oV") === false && $union_id = $open_id;
                        $third_user = UserThirdParty::findOne(['open_id="' . $open_id . '" and type=' . UserStatus::LOGIN_WEICHAT, 'columns' => 'user_id,union_id']);
                        if (!$third_user) {
                            $third_user = UserThirdParty::findOne(['union_id="' . $open_id . '" and type=' . UserStatus::LOGIN_WEICHAT, 'columns' => 'user_id']);
                        }
                    } else {
                        $third_user = UserThirdParty::findOne(['open_id="' . $open_id . '" and type=' . UserStatus::LOGIN_WEICHAT, 'columns' => 'user_id']);
                    }
                }
            } else {
                //其他第三方
                $third_user = UserThirdParty::findOne(['open_id="' . $open_id . '"', 'columns' => 'user_id']);
            }
            /*该用户以前登录过*/
            if ($third_user) {
                $user = Users::findOne('id="' . $third_user['user_id'] . '"');
            } //该用户以前没有登录过,注册第三方账号
            else {
                if (empty($avatar))//第一次尝试查询第三方登录信息
                    $this->ajax->outError(Ajax::ERROR_USER_HAS_NO_OPEN_AVATAR);
                // $third_user = new UserThirdParty();
                try {

                    $this->db->begin();
                    $this->original_mysql->begin();
                    $avatar = $avatar ? $avatar : UserStatus::$default_avatar; //头像初始化
                    $nick = $nick ? $nick : ($open_type == 1 ? "QQ用户" : "微信用户"); //昵称初始化
                    $username = $nick; //($open_type == 1 ? "QQ用户" : "微信用户") . (UserStatus::getInstance()->getRegisterUniqueID());

                    /**----users表插入数据------**/
                    $user = new Users();

                    $user_data = [
                        "username" => $username,
                        "avatar" => $avatar,
                        'true_name' => "",
                        'points' => 0,
                        'phone' => '',
                        'coins' => 0,
                        'grade' => 1,
                        'created' => time(),
                        'phone_model' => $phone_model,
                    ];

                    if (!$user_id = $user->insertOne($user_data)) {
                        $message = [];
                        foreach ($user->getMessages() as $msg) {
                            $message[] = $msg;
                        }
                        throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
                    }
                    /**----云信注册---**/
                    $res = ServerAPI::init()->createUserId($user_id, $username, '', $avatar);
                    if (!$res || $res['code'] != 200) {
                        throw new \Exception('云信注册失败-' . $res['desc']);
                    }

                    /**----user_profile表插入数据------**/
                    $user_profile = new UserProfile();
                    $profile_data = [
                        'user_id' => $user_id,
                        'sex' => $sex == 0 ? 1 : $sex,
                        'platform' => $this->client_type,
                        'yx_token' => $res['info']['token'],
                        'register_type' => ($open_type == UserStatus::LOGIN_QQ ? UserStatus::REGISTER_TYPE_QQ : UserStatus::REGISTER_TYPE_WEIXIN),
                        "register_ip" => GetClient::Getip(),
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
                    $recommend_users = Users::findList(['status=' . UserStatus::STATUS_NORMAL . ' and id<>' . $user_id . " and user_type=" . UserStatus::USER_TYPE_NORMAL, 'limit' => 2, 'order' => 'rand() desc', 'columns' => 'id,username,avatar,created']);
                    if ($recommend_users) {
                        foreach ($recommend_users as $i) {
                            ImManager::init()->initMsg(ImManager::TYPE_USER, ['to_user_id' => $user_id, 'user_name' => $i['username'], 'avatar' => $i['avatar'], 'user_id' => $i['id']]);
                        }
                    }
                    $user = Users::findOne('id="' . $user_id . '"');


                    //开通钱包账户
                    // Request::getPost(Base::OPEN_ACCOUNT, ['uid' => $user_id]);


                } catch (\Exception $e) {
                    Debug::log($e->getMessage(), 'error');
                    $this->db->rollback();
                    $this->original_mysql->rollback();
                    $this->ajax->outError(Ajax::FAIL_LOGIN);
                }
            }
        }
        if ($phone) {
            if (!$user) {
                $this->ajax->outError(Ajax::ERROR_USER_OR_PASSWORD);
            }

            if ($user['password'] != md5($user['password_salt'] . $pwd)) {
                $this->redis->hIncrBy(CacheSetting::KEY_USER_PASSWORD_ERROR_CNT, $phone, 1);
                $this->ajax->outError(Ajax::ERROR_USER_OR_PASSWORD);
            }

        }
        $user_id = $user['id'];
        //  if ($user->status != UserStatus::STATUS_NORMAL) {

        /*--修改user表登录数据---*/
        $login_data = [
            'last_login_ip' => GetClient::Getip(),
            'last_login_device' => $this->client_type,
            'last_sdk_version' => $this->app_version,
            'last_os_version' => $os_version,
            'last_phone_model' => $phone_model,
            'last_device_id' => $device_id,
        ];

        //被永久删除了
        if ($user['status'] == UserStatus::STATUS_DELETED) {
            $key = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'official_info');
            $key = json_decode($key, true);
            $this->ajax->outError(Ajax::ERROR_USER_DELETED, $key['telephone']);
        } //被临时锁定了
        else if ($user['status'] == UserStatus::STATUS_LOCKED) {
            //   if (empty($open_id)) {
            //没有输入验证码
            if (!$code) {
                $key = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'official_info');
                $key = json_decode($key, true);
                //第三方登录的联系客服
                if (!$phone/*$user['phone']*/) {
                    $this->ajax->outError(Ajax::ERROR_USER_HAS_BEING_LOCKED_MANUAL, $key['telephone']);
                } else {
                    $this->ajax->outError(Ajax::ERROR_USER_HAS_BEING_LOCKED, $key['telephone']);
                }
                //  $this->ajax->outError(Ajax::ERROR_USER_HAS_BEING_LOCKED);
            } else {
                $msg = VerifyCodeManager::init()->checkVerifyCode($user['phone'], VerifyCodeManager::$codetype[VerifyCodeManager::CODE_UNLOCK], $this->client_type, 0, $code);
                if ($msg != '1') {
                    $this->ajax->outError($msg);
                }
                VerifyCodeManager::init()->clearVerifyCode($user['phone'], VerifyCodeManager::$codetype[VerifyCodeManager::CODE_UNLOCK], $this->client_type, 0); //清除验证码
                //更新用户状态
                $login_data['status'] = UserStatus::STATUS_NORMAL;
            }
            //}
        } //正常
        else {
            //登录保护 第三方暂时过滤
            /* if ($phone) {
                 $user_setting = UserSetting::findOne(['user_id=' . $user_id, 'columns' => 'login_protect']);
                 //上次的登录设备和这次不同
                 if ($user['last_device_id'] != '' && $device_id != $user['last_device_id']) {
                     //开启了登录保护
                     if ($user_setting && $user_setting['login_protect'] == 1) {
                         //没有输入验证码
                         if (!$code) {
                             $this->ajax->outError(Ajax::ERROR_LOGIN_DEVICE);
                         } else {
                             $msg = VerifyCodeManager::init()->checkVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_LOGIN_SAFE], $this->client_type, 0, $code);
                             if ($msg != '1') {
                                 $this->ajax->outError($msg);
                             }
                             VerifyCodeManager::init()->clearVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_LOGIN_SAFE], $this->client_type, 0); //清除验证码
                         }
                     }
                 }
             }*/

        }
        //   }

        $reward_coin_behavior = 0;//奖励龙豆类型


        $push_device_id && $login_data['push_device_id'] = $push_device_id;

        /*注册后第一次登陆,*/
        if ($user['last_login_time'] <= 0) {
            $login_data['phone_model'] = $phone_model;
            $login_data['login_count'] = 1;
            //周末
            if (date('w') == 6 || date('w') == 0) {
                $reward_coin_behavior = PointRule::BEHAVIOR_SIGN_IN_WEEKEND;
            } else {
                $reward_coin_behavior = PointRule::BEHAVIOR_SIGN_IN;
            }

        } //今天有登陆
        elseif (date("Ymd", $user['last_login_time']) == date("Ymd", time())) {

        } //今天没登录
        else {
            //昨天有登录
            if (date("Ymd", $user['last_login_time']) == date("Ymd", strtotime("-1 day"))) {
                $login_data['login_count'] = $user['login_count'] + 1;
            } else {
                $login_data['login_count'] = 1;
            }
            //周末
            if (date('w') == 6 || date('w') == 0) {
                $reward_coin_behavior = PointRule::BEHAVIOR_SIGN_IN_WEEKEND;
            } else {
                $reward_coin_behavior = PointRule::BEHAVIOR_SIGN_IN;
            }
        }


        $login_data['last_login_time'] = time();
        try {
            $this->original_mysql->begin();
            if (!Users::updateOne($login_data, ['id' => $user_id])) {
                throw new \Exception("更新登录信息失败");
            }
            $this->original_mysql->commit();
        } catch (\Exception $e) {
            $this->original_mysql->rollback();
            Debug::log($e->getMessage(), 'error');
            $this->ajax->outError(Ajax::FAIL_LOGIN);
        }
        /*--送龙豆--*/
        if ($reward_coin_behavior) {
            //  PointRule::init()->executeRule($user->id, $reward_coin_behavior);
        }

        //记录日志
        $log_data = [
            'login_time' => $login_data['last_login_time'],
            'user_id' => $user['id'],
            'client_ip' => $login_data['last_login_ip'],
            'client_type' => $this->client_type,
            'os' => $os_version,
            'app_version' => $this->app_version,
            'phone_model' => $phone_model,
            'device_id' => $device_id,
            'ymd' => date('Ymd', $login_data['last_login_time']),
            'login_type' => $phone ? UserStatus::LOGIN_PHONE : ($open_type)
        ];
        $log = new UserLoginLog();
        $log->insertOne($log_data);

        $token_info = UserStatus::getInstance()->createAccessToken($user['id']);

        /*--返回用户信息--*/
        $data = [
            'uid' => $user['id'],
            'username' => $user['username'],
            'truename' => $user['true_name'],
            'phone' => $user['phone'],
            'avatar' => $user['avatar'],
            'points' => $user['points'],
            'coins' => $user['coins'],
            'grade' => $user['grade'],
            'is_reward' => $reward_coin_behavior ? 1 : 0,
            'reward_coins' => $reward_coin_behavior ? PointRule::init()->getRulePoints($reward_coin_behavior) : 0,
            'constellation' => $user['constellation'] ? UserStatus::$constellation[$user['constellation']] : '',
            'birthday' => $user['birthday'],
            'token_info' => $token_info
        ];
        $user_profile = UserProfile::findOne(['user_id=' . $user_id, 'columns' => 'yx_token,photos,sex,company,province_id,city_id,county_id,province_name,city_name,county_name,is_auth,auth_type,introduce,job,industry,company,signature,is_merchant,discuss_bg,is_vip']);
        $data = array_merge($data, $user_profile);


        // $data['is_merchant'] = $data['is_merchant'] == 0 ? 0 : 1;
        $data['shop'] = '';
        if ($data['is_merchant']) {
            $shops = Shop::getColumn(['user_id=' . $user_id . ' and status=' . ShopManager::status_normal, 'columns' => 'id'], 'id');
            if ($shops) {
                $data['shop'] = implode(',', $shops);
            }
        }
        unset($data['is_merchant']);
        //距上次推送间隔一周，推送秀场消息
        /* try {
             $items = json_decode(MessagePushTime::init()->findOne(["user_id = " . $user['id'], 'columns' => 'items']), true);
             $last_push_time = isset($items['show_weekly']) ? $items['show_weekly'] : 0;
             if (time() - $last_push_time >= 60 || $user['last_login_time'] <= 0) {
                 $redis_queue = $this->di->get("redis_queue");
                 $redis_queue->rPush(CacheSetting::KEY_SYSTEM_MESSAGE_PUSH_LIST . ":" . 'show_weekly', json_encode(['user_id' => $user['id'], 'sex' => $user_profile['sex']]));
             }

         } catch (\Exception  $e) {
             $this->ajax->outRight($data);
         }*/
        //推送 再次使用恐龙谷消息
        /* if ($welcome) {
             ImManager::init()->initMsg(ImManager::TYPE_WELCOME_BACK, ['to_user_id' => $user_id]);
         }*/
        //全民推广 激活用户
        // Request::getPost(Base::RELATION_ACTIVATE, ['uid' => $user_id]);

        //公益
        WelfareManager::getInstance()->activate($user_id, WelfareManager::ACTIVE_LOGIN);

        $this->ajax->outRight($data);
    }

    /*--获取用户信息--*/
    public function infoAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
//        if ($to_uid < 50000) {
//            $this->ajax->outError(Ajax::INVALID_PARAM);
//        }
        if (!Users::exist('id=' . $to_uid)) {
            $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }
        $user_info = UserStatus::getInstance()->getUserInfo($uid, $to_uid);
        $this->ajax->outRight($user_info);
    }

    //用户简短信息
    public function baseInfoAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $res = [];
        $user = UserInfo::findOne(['user_id=' . $to_uid, 'columns' => 'user_id as uid,username,avatar,sex,phone,constellation,user_type']);
        if ($user) {
            $res = $user;
            $tags = UserTags::findOne(['user_id=' . $to_uid, 'columns' => 'tags_name']);
            if ($tags) {
                $res['tags'] = $tags['tags_name'];
            } else {
                $res['tags'] = '';
            }
            if ($res['constellation']) {
                $res['constellation'] = UserStatus::$constellation[$res['constellation']]; //星座
            }
        }
        $this->ajax->outRight($res);
    }

    /*--编辑个人信息--*/
    public function editInfoAction()
    {
        $uid = $this->uid;
        $nick = trim($this->request->get('username', 'green', ''));//昵称
        $sex = $this->request->get('sex', 'int', 0);//性别
        $signature = trim($this->request->get('signature', 'green', ''));//个性签名
        $province_id = $this->request->get('province_id', 'int', '');//省
        $city_id = $this->request->get('city_id', 'int', '');//市
        $avatar = $this->request->get('avatar', 'string', '');//头像
        $photos = $this->request->get('photos', 'string', '');//照片
        $voice = $this->request->get('voice', 'string', '');//语音介绍
        $birthday = $this->request->get('birthday', 'string', '');//生日
        $cover = $this->request->get('cover', 'string', '');//背景图片


        if (!$uid || (!$nick && !$sex && !$signature && !$province_id && !$city_id && !$avatar && !$photos && !$birthday && !$voice && !$cover)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = [];
        if ($nick) {
            $user = Users::findOne(['id=' . $uid, 'columns' => 'username']);
            //用户昵称只能修改一次
            //
            $time = time();
            $y = date('Y');
//            if ($user['username'] != $nick && $this->redis->hGet(CacheSetting::KEY_UPDATE_NICK_COUNT . $y, $uid)) {
//                $this->ajax->outError(Ajax::ERROR_NICK_UPDATE_ONLY_ONCE);
//           }
            if (!Validator::validateNick($nick)) {
                $this->ajax->outError(Ajax::ERROR_USERNAME_PREG);
            }
            if (Users::exist("username='" . $nick . "' and id<>" . $uid)) {
                $this->ajax->outError(Ajax::ERROR_NICK_HAS_BEEN_USED);
            }

            $data['username'] = $nick;
        }
        if ($sex) {
            $data['sex'] = $sex;
        }
        //清空个性签名
        if ($signature == 'empty') {
            $data['signature'] = '';
        } elseif ($signature) {
            $data['signature'] = $signature;
        }
        if ($province_id) {
            $data['province_id'] = $province_id;
            $data['province_name'] = AreaManager::getProvince($province_id, 'short_name');
            // AreaManager::
        }
        if ($city_id) {
            $data['city_id'] = $city_id;
            $data['city_name'] = AreaManager::getCity($city_id, 'short_name');
            // AreaManager::
        }
        if ($avatar) {
            $data['avatar'] = $avatar;
        }
        if ($birthday) {
            $data['birthday'] = $birthday;
            $data['constellation'] = UserStatus::getInstance()->getConstellation($birthday);
        }
        if ($cover) {

            if (!Validator::validateUrl($cover)) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            $data['cover'] = $cover;
        }

        //清空所有照片墙
        if ($photos == 'empty') {
            $data['photos'] = '';
        } elseif ($photos) {
            $data['photos'] = $photos;
        }
        //清空语音
        if ($voice == 'empty') {
            $data['voice_introduce'] = '';
        } elseif ($voice) {
            $data['voice_introduce'] = $voice;
        }

        //  Debug::log(var_export($data, true));
        if (!UserStatus::getInstance()->editInfo($uid, $data)) {
            $this->ajax->outError(Ajax::FAIL_EDIT);
        }
        //头像(包括照片墙)鉴黄入列
        $images = $photos;//$avatar . ',' . $photos;
        Debug::log(var_export($images,true),'debug');
        if ($images && !strpos($images, "gif/")) {
            $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "img_check");
            $setting = json_decode($setting, true);
            Debug::log(var_export($setting,true),'debug');
            if ($setting && $setting['enable'] == 1 && $setting['enable_avatar'] == 1) {
                $redis = $this->di->get("redis_queue");
                $media = array_filter(explode(',', $images));
                foreach ($media as $k => $item) {
                    $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_AVATAR_LIST, $uid . "|" . $item);
                }
            }
        }
        //更新缓存
        if ($avatar || $nick || $sex || $birthday) {
            $data = ['uid' => $uid];
            if ($nick) {
                $data['username'] = $nick;
            }
            if ($avatar) {
                $data['avatar'] = $avatar;
            }
            if ($sex) {
                $data['sex'] = $sex;
            }
            if ($birthday) {
                $data['birthday'] = $birthday;
            }
            Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_uums_update)
                ->produce($data);
            // Request::getPost(Base::USER_INFO_UPDATE, ['username' => $nick, 'avatar' => $avatar, 'sex' => $sex, 'uid' => $uid]);
        }
        // 用户昵称编辑过增加次数
        if ($nick) {
            if ($user['username'] != $nick) {
                if ($log = UserNickUpdateLog::findOne(["user_id=" . $uid . " and y=" . $y, 'columns' => 'id,user_id,detail,total_cnt'])) {
                    $detail = json_decode($log['detail'], true);
                    $detail[] = ['n' => $nick, 't' => $time];
                    $data = ['total_cnt' => $log['total_cnt'] + 1, 'detail' => json_encode($detail, JSON_UNESCAPED_UNICODE), 'modify' => $time];
                    UserNickUpdateLog::updateOne($data, 'id=' . $log['id']);
                } else {
                    $data = ['user_id' => $uid, 'y' => $y, 'created' => $time, 'total_cnt' => 1, 'detail' => json_encode([['t' => $time, 'n' => $nick]], JSON_UNESCAPED_UNICODE)];
                    UserNickUpdateLog::insertOne($data);
                }
                $this->redis->hIncrBy(CacheSetting::KEY_UPDATE_NICK_COUNT . $y, $uid, 1);
            }
        }


        $this->ajax->outRight(UserStatus::getInstance()->getUserInfo($uid, $uid));
    }

    /*上报位置*/
    public function locationAction()
    {
        $lng = $this->request->get('lng', 'string', ''); //经度
        $lat = $this->request->get('lat', 'string', ''); //纬度
        $type = $this->request->get('type', 'string', 'gps'); //定位类型 gps/ip
        $uid = $this->request->get('uid', 'int', 0); //用户id
        $device = $this->client_type; //设备类型
        $province = $this->request->get('province', 'string', ''); //省
        $city = $this->request->get('city', 'string', ''); //市
        $town = $this->request->get('town', 'string', ''); //区
        $street = $this->request->get('street', 'string', ''); //街道
        $street_number = $this->request->get('street_number', 'string', ''); //门牌号
        $address = $this->request->get('address', 'string', ''); //具体地址

        if (!$uid || !$lat || !$lng) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = array(
            "user_id" => $uid,
            "lng" => $lng,
            "lat" => $lat,
            /* "type" => $type,*/
            "province" => '',
            "city" => '',
            "town" => '',
            "street" => '',
            "street_number" => '',
            /*   "device" => $device,*/
            "created" => time()
        );

        if ($address) {
            $data['province'] = $province;
            $data['city'] = $city;
            $data['town'] = $town;
            $data['street'] = $street;
            $data['street_number'] = $street_number;
            $data['address'] = $address;

            if ($area_code = AreaManager::getInstance()->getCityByName($data['city'], 'area_code')) {
                $data['area_code'] = $area_code;
            };
        }
        $position = UserLocation::findOne(["user_id=" . $uid, 'columns' => 'id']);
        if (!$position) {
            $position = new UserLocation();
            $res = $position->insertOne($data);
        } else {
            $res = UserLocation::updateOne($data, ['id' => $position['id']]);
        }
        if ($res) {

            // Request::getPost(Base::USER_INFO_UPDATE, ['uid' => $uid, 'lng' => floatval($lng), 'lat' => floatval($lat)]);
            Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_uums_update)
                ->produce(['uid' => $uid, 'lng' => floatval($lng), 'lat' => floatval($lat)]);
            $this->ajax->outRight("");

        } else {
            $this->ajax->outError(Ajax::FAIL_LOCATION);
        }
    }

    /*--绑定消息推送设备号--*/
    public function bindPushDeviceIdAction()
    {
        $uid = $this->uid;
        $push_device_id = $this->request->get('push_device_id', 'string', ''); // 推送设备id,jpush对应的registration_id
        if (!$uid || !$push_device_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $user = Users::exist('id=' . $uid);
        if (!$user) {
            $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }
        Users::updateOne(['push_device_id' => $push_device_id], ['id' => $uid]);
        $this->ajax->outRight('');
    }

    /*--绑定/修改手机号--*/
    public function bindPhoneAction()
    {
        $uid = $this->uid;
        $phone = $this->request->get('phone', 'string', '');
        $is_bind = $this->request->get('is_bind', 'int', 1); //是否绑定手机号
        $code = $this->request->get('code', 'string', '');
        $password = $this->request->get('pwd', 'string', ''); //密码【rsa加密】

        if (!$uid || !$phone || !$code) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //无效的手机号
        if (!Validator::validateCellPhone($phone)) {
            $this->ajax->outError(Ajax::INVALID_PHONE);
        }
        //无效的验证码
        if (Validator::validVerifyCode($code)) {
            $this->ajax->outError(Ajax::ERROR_VERIFY_CODE);
        }
        $type = $is_bind ? VerifyCodeManager::CODE_BIND : VerifyCodeManager::CODE_CHANGE;
        $msg = VerifyCodeManager::init()->checkVerifyCode($phone, VerifyCodeManager::$codetype[$type], $this->client_type, $uid, $code);
        if ($msg != '1') {
            $this->ajax->outError($msg);
        }
        $user = Users::exist("phone='" . $phone . "'");
        if ($user) {
            $this->ajax->outError(Ajax::ERROR_PHONE_HAS_BEING_USED);
        }
        $data = ['phone' => $phone];
        //绑定手机并设置登录密码
        if ($password) {

            // ** 检测密码
            $Sign = new Sign($this->client_type, $this->app_version);
            $pwd = $Sign->signStr($password);
            //$pwd = Sign::signStr($password);
            if (!$pwd) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            if (!Validator::validPassword($pwd)) {
                $this->ajax->outError(Ajax::ERROR_PASSWD_IS_INVALID);
            }

            $salt = UserStatus::getSalt();
            $data['password_salt'] = $salt;
            $data['password'] = md5($salt . $pwd);
        }

        if (!Users::init()->updateOne($data, ['id' => $uid])) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        //绑定手机 --送经验值
        \Components\Rules\Point\PointRule::init()->executeRule($uid, \Components\Rules\Point\PointRule::BEHAVIOR_BIND_PHONE);

        UserStatus::getInstance()->removeCacheUserInfo($uid);//清除缓存
        VerifyCodeManager::init()->clearVerifyCode($phone, VerifyCodeManager::$codetype[$type], $this->client_type, $uid);//清除验证码

        //更新缓存
        // Request::getPost(Base::USER_INFO_UPDATE, ['phone' => $phone, 'uid' => $uid]);
        Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_uums_update)
            ->produce(['phone' => $phone, 'uid' => $uid]);

        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);

    }

    /*--找回密码--*/
    public function forgotAction()
    {
        $phone = $this->request->get('phone', 'string', '');
        $code = $this->request->get('code', 'int', 0); //验证码
        $password = $this->request->get('pwd', 'string', ''); //新密码 rsa加密
        if (!$phone || !$code || !$password) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $msg = VerifyCodeManager::init()->checkVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_FORGOT], $this->client_type, 0, $code);
        if ($msg != '1') {
            $this->ajax->outError($msg);
        }
        $user = Users::findOne(["phone='" . $phone . "'", 'columns' => 'id']);
        if (!$user) {
            $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }
        $Sign = new Sign($this->client_type, $this->app_version);
        //  $pwd = $Sign->signArr($password);
        $pwd = $Sign->signStr($password);
        // ** 检测密码
        // $pwd = Sign::signStr($password);
        if (!$pwd) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!Validator::validPassword($pwd)) {
            $this->ajax->outError(Ajax::ERROR_PASSWD_IS_INVALID);
        }
        $salt = UserStatus::getSalt();
        if (Users::updateOne(['password_salt' => $salt, 'password' => md5($salt . $pwd)], ['id' => $user['id']])) {
            VerifyCodeManager::init()->clearVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_FORGOT], $this->client_type, 0);//清除验证码
            $this->redis->hDel(CacheSetting::KEY_USER_PASSWORD_ERROR_CNT, $phone);

            $this->ajax->outRight("设置成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    /*--修改/设置密码--*/
    public function resetPasswordAction()
    {
        $uid = $this->uid; //用户id
        $old_password = $this->request->get('old_pwd', 'string', ''); //旧密码【rsa加密】 修改密码用到
        $password = $this->request->get('pwd', 'string', ''); //新密码【rsa加密】
        if (!$uid || !$password) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        // ** 检测密码
        $Sign = new Sign($this->client_type, $this->app_version);
        $pwd = $Sign->signStr($password);
        //$pwd = Sign::signStr($password);
        if (!$pwd) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!Validator::validPassword($pwd)) {
            $this->ajax->outError(Ajax::ERROR_PASSWD_IS_INVALID);
        }
        $user = Users::findOne(["id='" . $uid . "'", 'columns' => 'id,password_salt,password']);
        if (!$user) {
            $this->ajax->outError(Ajax::ERROR_USER_IS_NOT_EXISTS);
        }
        // 有旧密码 为修改密码
        if ($old_password) {
            $old_pwd = $Sign->signStr($old_password);
            //   $old_pwd = Sign::signStr($old_password);
            if (!$old_password) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            if ($user['password'] != md5($user['password_salt'] . $old_pwd)) {
                $this->ajax->outError(Ajax::ERROR_PASSWD_IS_NOT_CORRECT);
            }
        }
        $salt = UserStatus::getSalt();
        if (Users::updateOne(['password_salt' => $salt, 'password' => md5($salt . $pwd)], ['id' => $user['id']])
        ) {
            $this->ajax->outRight("设置成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    /*获取附近的人*/
    public function nearUserAction()
    {
        $uid = $this->uid;
        $lng = $this->request->get('lng', 'string', '');//精度
        $lat = $this->request->get('lat', 'string', '');//纬度
        $sex = $this->request->get('sex', 'int', 0);//性别
        $distance = $this->request->get('distance', 'int', 0);//多大范围内的用户

        $page = $this->request->get('page', 'int', 0);//第几页
        $limit = $this->request->get('limit', 'int', 20);//每页数量

        if (!$lng || !$lat) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $users = UserStatus::getInstance()->getNearUser($uid, $lng, $lat, $distance, $sex, $page, $limit);
        $this->ajax->outRight($users);
    }

    /*访问用户-访客*/
    public function visitAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //自己访问自己
        if ($uid == $to_uid) {
            $this->ajax->outRight("");
        }
        UserStatus::getInstance()->updateVisitor($uid, $to_uid);
        $this->ajax->outRight("");
    }

    /*访客列表*/
    public function visitorListAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $page = $this->request->get('page', 'int', 0);//第几页
        $limit = $this->request->get('limit', 'int', 20);//每页数量
        $res = UserStatus::getInstance()->visitorList($uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    /*我的相册/他的相册*/
    public function myPhotosAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);;
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $res = UserStatus::getInstance()->myPhotos($uid, $to_uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    //获取恐龙谷官方信息
    public function systemInfoAction()
    {
        /* $uid = $this->uid;
         if (!$uid) {
             Ajax::outError(Ajax::INVALID_PARAM);
         }*/
        $account = $this->request->get("account", 'int', 13);
        $res = [];
        if (!$account || $account == 13) {
            $key = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'official_info');
        } else {
            $key = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'official_info_' . $account);
        }
        if ($key) {
            $res = json_decode($key, true);
        }
        $this->ajax->outRight($res);
    }

    //邀请上传照片墙
    public function inviteUploadPhotosAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_INVITE_UPLOAD_PHOTOS, $uid)->checkBehavior();
        $user_info = UserStatus::getInstance()->getCacheUserInfo($uid, false, $to_uid, true);
        ImManager::init()->initMsg(ImManager::TYPE_INVITE_UPLOAD_PHOTOS, ['to_user_id' => $to_uid, 'user_id' => $uid, 'user_name' => $user_info['username']]);
        $this->ajax->outRight("邀请成功", Ajax::SUCCESS_SEND);
    }

    /*获取一次性登录token*/
    public function getTokenAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        Ajax::outRight(UserStatus::getInstance()->createToken($uid));
    }
}
