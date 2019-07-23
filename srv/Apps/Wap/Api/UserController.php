<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/6
 * Time: 11:52
 */

namespace Multiple\Wap\Api;


use Components\Rules\Point\PointRule;
use Components\Yunxin\ServerAPI;
use Models\User\UserCountStat;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserShow;
use Models\User\UserVideo;
use Multiple\Wap\Helper\ContactManager;
use Multiple\Wap\Helper\UserStatus;
use Multiple\Wap\Helper\DiscussManager;
use Services\Im\ImManager;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\Site\VerifyCodeManager;
use Services\User\WelfareManager;
use Util\Ajax;
use Util\Debug;
use Util\Validator;

class UserController extends ControllerBase
{
    public function AjaxLoginAction()
    {
        UserStatus::init()->ajaxLogin();
    }

    public function attentionListAction()
    {
        $to = $this->request->get('to', 'int', 0);
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        if (!$to) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $list = ContactManager::followers(UserStatus::getUid(), $to, $page, $limit);
        $data = [];
        if ($list) {
            foreach ($list['data_list'] as $item) {
                $data[] = [$this->getFromOB('user/partial/item_attention', array('item' => $item))];
            }
        }
        $data = array('count' => $list['data_count'], "limit" => $limit, 'data_list' => $data);
        Ajax::outRight($data);
    }

    public function fansListAction()
    {
        $to = $this->request->get('to', 'int', 0);
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        if (!$to) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $list = ContactManager::fans(UserStatus::getUid(), $to, $page, $limit);
        $data = [];
        if ($list) {
            foreach ($list['data_list'] as $item) {
                $data[] = [$this->getFromOB('user/partial/item_fans', array('item' => $item))];
            }
        }
        $data = array('count' => $list['data_count'], "limit" => $limit, 'data_list' => $data);
        Ajax::outRight($data);
    }

    //我的、他的动态
    public function discussAction()
    {
        $to = $this->request->get('to', 'int', '');
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        if (!$to) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $list = DiscussManager::list(UserStatus::getUid(), $to, 0, $page, $limit);
        $data = [];
        if ($list) {
            foreach ($list['data_list'] as $item) {
                $data[] = [$this->getFromOB('user/user_center/partial/item_discuss', array('item' => $item))];
            }
        }
        $data = array('count' => $list['data_count'], "limit" => $limit, 'data_list' => $data);
        Ajax::outRight($data);
    }

    //我的、他的相册
    public function albumAction()
    {
        $to = $this->request->get('to', 'int', '');
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 5);
        $last_id = $this->request->get('last_id', 'int', 0);

        if (!$to) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $list = UserStatus::myPhotos(UserStatus::getUid(), $to, $page, $limit, $last_id);
        $data = [];
        if ($list) {
            foreach ($list['data_list'] as $key => $item) {
                $data[] = [$this->getFromOB('user/user_center/partial/item_album', array('item' => $item, 'key' => $key))];
            }
        }
        $data = array('count' => $list['data_count'], "limit" => $limit, 'data_list' => $data);
        if ($last_id) {
            $data['last_id'] = $last_id;
        }
        Ajax::outRight($data);
    }

    //秀场视频和照片
    public function showAction()
    {
        $uid = $this->request->get('uid');
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $page = $page > 0 ? $page : 1;
        //获取视频
//        $video = UserVideo::findList(['user_id=' . $uid . ' and status = 1', 'columns' => 'like_cnt,view_cnt,url', 'order' => 'created desc', 'limit' => 10]);
        $video = $this->original_mysql->query("select like_cnt,view_cnt,url from user_video where user_id = $uid and status = 1 order by created desc limit " . ($page - 1) * $limit . ",$limit")->fetchAll(\PDO::FETCH_ASSOC);
        //  $info = UserShow::init()->findOne(['user_id = ' . $uid, 'columns' => 'video,images,like_cnt,dislike_cnt,enable']);
        $data = [];
        /*$service = (Request::getPost(Base::SKILL_SERVICE, ['type' => 1, 'uid' => $uid, 'touid' => $uid]));
        $service = json_decode($service['data'], true)['data'];
        $skill = $service['skill'];*/
        /*if ($skill) {
            $data['data_list'][] = [$this->getFromOB('user/user_center/partial/item_rent', array('skill' => $skill, 'info' => ['avatar' => $service['avatar'], 'score' => $service['score'], 'username' => $service['username']]))];
        }*/
        if ($video) {
            foreach ($video as $item) {
                $data['data_list'][] = [$this->getFromOB('user/user_center/partial/item_show', array('video' => $item))];
            }
        } else {
            $data['data_list'] = [];
        }
        Ajax::outRight($data);
    }


    public function app_loginAction()
    {
        UserStatus::init()->appLogin();
    }

    //注册
    public function regAction()
    {
        $phone = $this->request->get("tel", 'string', '');
        $code = $this->request->get("code", 'string', '');
        $nick = $this->request->get("username", 'string', '');
        $birthday = $this->request->get("birthday", 'string', '');
        $pwd = $this->request->get("pwd", 'string', '');
        $avatar = $this->request->get("avatar", 'string', '');
        $sex = $this->request->get("sex", 'int', 1);
        $avatar = $avatar ? $avatar : \Services\User\UserStatus::$default_avatar;
        if (!$phone || !$code || !$pwd || !$nick || !$avatar || !$birthday) {
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
        $msg = VerifyCodeManager::init()->checkVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_REGISTER], "wap", 0, $code);
        if ($msg != '1') {
            $this->ajax->outError($msg);
        }
        if (!Validator::validateNick($nick)) {
            $this->ajax->outError(Ajax::ERROR_USERNAME_PREG);
        }
        if (Users::exist("username='" . $nick . "'")) {
            $this->ajax->outError(Ajax::ERROR_NICK_HAS_BEEN_USED);
        }
        if (!Validator::validPassword($pwd)) {
            $this->ajax->outError(Ajax::ERROR_PASSWD_IS_INVALID);
        }
        $user = Users::findOne(['phone="' . $phone . '"', 'columns' => 'id,status']);
        //该手机已注册
        if ($user) {
            $this->ajax->outError(Ajax::ERROR_PHONE_EXIST);
        }

        try {
            $this->db->begin();

            /**----users表插入数据------**/
            $user = new Users();
            $salt = \Services\User\UserStatus::getSalt();
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
                "platform" => 'wap',
                "sex" => $sex,
                "register_type" => \Services\User\UserStatus::REGISTER_TYPE_PHONE,
                "register_ip" => $this->request->getClientAddress(),
                "birthday" => $birthday, //? $birthday : \Services\User\UserStatus::getInstance()->createRandBirthday($sex),
                "yx_token" => $res['info']['token'],
            ];
            if ($birthday) {
                // $profile_data['birthday'] = $birthday;
                $profile_data['constellation'] = \Services\User\UserStatus::getInstance()->getConstellation($birthday);
            }
            if (!$user_profile->insertOne($profile_data)) {
                $message = [];
                foreach ($user_profile->getMessages() as $msg) {
                    $message[] = $msg;
                }
                throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
            }


            //送经验值
            PointRule::init()->executeRule($user_id, PointRule::BEHAVIOR_USER_BIRTHDAY);
            //插入数据统计
            UserCountStat::insertOne(['user_id' => $user_id, 'created' => time()]);

            $this->db->commit();

            VerifyCodeManager::init()->clearVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_REGISTER], 'wap', 0);//清除验证码
            // 发送im系统消息
            ImManager::init()->initMsg(ImManager::TYPE_REGISTER, ['user_name' => $nick, 'to_user_id' => $user_id]);

            //发送推荐用户名片
            $recommend_users = Users::findList(['status=' . \Services\User\UserStatus::STATUS_NORMAL . ' and id<>' . $user_id . " and user_type=" . \Services\User\UserStatus::USER_TYPE_NORMAL, 'limit' => 2, 'order' => 'rand() desc', 'columns' => 'id,username,avatar']);
            if ($recommend_users) {
                foreach ($recommend_users as $i) {
                    ImManager::init()->initMsg(ImManager::TYPE_USER, ['to_user_id' => $user_id, 'user_name' => $i['username'], 'avatar' => $i['avatar'], 'user_id' => $i['id']]);
                }
            }

            //开通钱包账户
            //  Request::getPost(Base::OPEN_ACCOUNT, ['uid' => $user_id]);
            //开通钱包账户
            //Request::asyncPost(Base::OPEN_ACCOUNT, ['uid' => $user_id]);


            //
            $from = $this->cookies->get("from")->getValue();//介绍人
            if ($from) {

                $redis = $this->di->get("publish_queue");
                $redis->publish(CacheSetting::KEY_ATTENTION, json_encode(['uid' => $from, 'to_uid' => $user_id, 'source' => 3]));
                $redis->publish(CacheSetting::KEY_ATTENTION, json_encode(['uid' => $user_id, 'to_uid' => $from, 'source' => 3]));

                // Request::asyncPost(Base::RELATION_MAKE, ['uid' => $user_id, 'introducer' => $from]);
                //公益
                $res = WelfareManager::getInstance()->add($from, $user_id);
                /* $res = Request::getPost(Base::RELATION_MAKE, ['uid' => $user_id, 'introducer' => $from]);
                 if (!$res || !$res['curl_is_success'] || empty($res['data'])) {
                     throw new \Exception("设置推荐人失败：" . var_export($res, true));
                 } else {
                     $res_data = json_decode($res['data'], true);
                     if (!(isset($res_data['code']) && $res_data['code'] == 200)) {
                         throw new \Exception("设置推荐人失败：" . var_export($res, true));
                     }
                 }*/
            }
            $this->ajax->outRight($user_id);
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log($e->getMessage(), 'error');
            $this->ajax->outError(Ajax::FAIL_REGISTER);
        }

    }

    //注册时校验验证码
    public function ValidateCodeAction()
    {
        $phone = $this->request->get('phone', 'string', '');
        $code = $this->request->get('code', 'string', '');
        //无效的验证码
        if (Validator::validVerifyCode($code)) {
            $this->ajax->outError(Ajax::ERROR_VERIFY_CODE);
        }
        $msg = VerifyCodeManager::init()->checkVerifyCode($phone, VerifyCodeManager::$codetype[VerifyCodeManager::CODE_REGISTER], "wap", 0, $code);
        if ($msg != '1') {
            $this->ajax->outError($msg);
        }
        $this->ajax->outRight('');
    }

}