<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/2
 * Time: 16:09
 */

namespace Services\User;


use Components\Yunxin\ServerAPI;
use Models\User\UserProfile;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Util\Ajax;
use Util\Debug;
use Util\GetClient;

/**
 *
 * 企业账号 管理
 *
 *
 * Class OfficialUser
 * @package Services\User
 */
class OfficialUser extends Plugin
{
    //注册官方账号
    public function register($uid, $username = '', $avatar = '')
    {
        $avatar = $avatar ? $avatar : UserStatus::$default_avatar;//默认头像
        $nick = $username ? $username : "恐龙君官方账号";//用户名
        try {
            $user = Users::findOne(['id="' . $uid . '"', 'columns' => 'id,status']);
            //已经注册过
            if ($user) {
                return false;
            }
            $avatar = $avatar ? $avatar : UserStatus::$default_avatar;//默认头像

            $this->db->begin();
            $this->original_mysql->begin();
            /**----users表插入数据------**/
            $user = new Users();
            $user_data = [
                "username" => $nick,
                "phone" => '',
                "avatar" => $avatar,
                "password_salt" => '',
                "password" => '',
                "created" => time(),
                'id' => $uid,
                'user_type' => UserStatus::USER_TYPE_OFFICIAL
            ];
            if (!$user_id = $user->insertOne($user_data)) {
                $message = [];
                foreach ($user->getMessages() as $msg) {
                    $message[] = $msg;
                }
                throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
            }
            /**----云信注册---**/
            $res = ServerAPI::init()->createUserId($uid, $nick, '', $avatar);
            if (!$res || $res['code'] != 200) {
                throw new \Exception('云信注册失败-' . $res['desc']);
            }

            /**----user_profile表插入数据------**/
            $user_profile = new UserProfile();
            $profile_data = [
                "user_id" => $uid,
                "platform" => 'pc',
                "sex" => 1,
                "register_type" => UserStatus::REGISTER_TYPE_PHONE,
                "register_ip" => GetClient::Getip(),
                "birthday" => '', //? $birthday : UserStatus::getInstance()->createRandBirthday($sex),
                "yx_token" => $res['info']['token'],
            ];
            if (!$user_profile->insertOne($profile_data)) {
                $message = [];
                foreach ($user_profile->getMessages() as $msg) {
                    $message[] = $msg;
                }
                throw new \Exception(json_encode($message, JSON_UNESCAPED_UNICODE));
            }
            $this->db->commit();
            $this->original_mysql->commit();

            //开通钱包账户
            //  Request::asyncPost(Base::OPEN_ACCOUNT, ['uid' => intval($uid)]);
            /* if($phone=='13560487593'){
             }else{
                 Request::getPost(Base::OPEN_ACCOUNT, ['uid' => $user_id]);
             }*/

            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();

            //去除并发锁
            Debug::log($e->getMessage(), 'error');
            return false;
        }

    }
}