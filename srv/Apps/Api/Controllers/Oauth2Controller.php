<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/30
 * Time: 14:39
 */

namespace Multiple\Api\Controllers;

use Models\OAuth2\Oauth2VerifyToken;
use Models\OAuth2\RequestAuthorizeUsers;
use Util\Ajax;

/**
 *
 * Class Oauth2Controller
 * @package Multiple\Api\Controllers
 */
class Oauth2Controller extends ControllerBase
{

    public function getVerifyTokenAction()
    {
        $appid = $this->request->get('appid','string','');
        $uid = $this->request->get('uid','string',0);
        if( $appid ==='' || $uid === 0)
            $this->ajax->outError(Ajax::INVALID_PARAM);
        $token = md5($appid . $uid . self::getSalt());
        /*if( Oauth2VerifyToken::findOne(['appid' => $appid, 'uid' => $uid, 'enable' => 1]) )
        {
            $res = $this->db_open->execute("update oauth2_verify_token set enable = 0 where appid = " . $appid . " and uid = " . $uid . " and enable = 1");
            if( !$res )
                $this->ajax->outError(Ajax::ERROR_OAUTH2_FAIL_GET_VERIFY_TOKEN,'获取票据失败，请稍后重试');
        }*/
        //$this->ajax->outError(Ajax::ERROR_OAUTH2_FAIL_GET_VERIFY_TOKEN,'');
        //todo 校验appid合法性
        /*if( $appid !== '23fb6ee1bfb4fd901e6db8e94cf28b87')
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'非法appid');*/
        if( RequestAuthorizeUsers::exist(['client_id' => $appid, 'user_id' => $uid]))
            $res = RequestAuthorizeUsers::updateOne(['openid' => md5($appid . $uid),'verify_token' => $token,'updated_at' => date('Y-m-d H:i:s',time())],['client_id' => $appid, 'user_id' => $uid]);
        else
            $res = RequestAuthorizeUsers::insertOne(['openid' => md5($appid . $uid),'client_id' => $appid, 'user_id' => $uid, 'verify_token' => $token,'created_at' => date('Y-m-d H:i:s',time())]);
        if( !$res )
            $this->ajax->outError(Ajax::ERROR_OAUTH2_FAIL_GET_VERIFY_TOKEN,'');
        $this->ajax->outRight(['uid' => $uid ,'token' => $token]);

    }

    private static function getSalt()
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
}