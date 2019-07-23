<?php

namespace Multiple\Api\Controllers;

use Components\Passport\Identify;
use Models\Statistics\ApiCallTotalCount;
use Models\System\SystemApiError;
use Models\User\Users;
use Phalcon\Mvc\Controller;
use Services\Site\AppVersionManager;
use Services\Site\CacheSetting;
use Services\User\Behavior\Behavior;
use Services\User\UserStatus;
use Util\Ajax;
use Util\Debug;
use Util\GetClient;
use Util\Uri;

/**
 *  * @property \Util\Ajax $ajax
 *  * @property \Components\Redis\RedisComponent $redis
 */
class ControllerBase extends Controller
{
    protected $app = 0;
    protected $_check_login = false;
    public $ajax;
    protected $is_checkApi = true;
    public $redis = null;//redis
    //   public $db = null;//db
    public $client_type = null;//客户端类型ios/android
    public $app_version = null;//app版本号
    public $lang = 1;//语言 1-中文简体 2-中文繁体 3-英文
    public $uid = 0; //用户id
    protected $check_token = true; //检测令牌
    protected $is_r = false;//是否机器人服务器调用

    protected function onConstruct()
    {

        $this->view->disable();
        $sign = $this->request->get('sign', 'string', '');//签名字段
        $sign_type = strtoupper($this->request->get('sign_type', 'string', 'RSA'));//签名方式
        $time_stamp = $this->request->get('time_stamp', 'int', 0);//请求时间戳 10位
        $client_type = strtolower($this->request->get("client_type", 'string', ''));//ios、android
        $app_version = $this->request->get("app_version", 'string', '');//app sdk版本;
        $development = $this->request->get("development", 'string', '');//开发调试模式
        $device_id = $this->request->get("device_id", 'string', '');//设备号

        $this->uid = $this->request->get("uid", 'int', 0);//用户id
        $is_r = $this->request->get("is_r", 'int', 0);//是否机器人服务器调用
        $token = $this->request->get("token", 'string', '');//令牌

        $this->ajax = new Ajax();
        define("app_version", $app_version);
        define("client_type", strtolower($client_type));
        define("device_id", $device_id);
        define("is_r", $is_r);

        //检测ip黑名单
        if (Behavior::init(Behavior::TYPE_DISCUSS_LIKE, $this->uid)->checkIpBlacklist(GetClient::Getip())) {
            $this->ajax->outError(Ajax::ERROR_IP_ABNORMAL);
        }
        //$developer = parse_ini_file(ROOT . '/Data/site/developer.ini', true);
//        //开发者
//        if ($developer) {
//            if (!empty($developer['base']['environment']) && $developer['base']['environment'] == 'product') {
//                $environment = 'product';
//                define("environment", $environment);
//            }
//            if ($this->uid) {
//                if (!empty($developer['developer']) && !empty($developer['developer']['user'])) {
//                    if (strpos($developer['developer']['user'] . ",", $this->uid . ",") !== false) {
//                        $uri = new Uri();
//                        Debug::log(date('YmdHis') . ":" . $uri->actionUrl(), 'api_log/' . $this->uid);
//                    }
//                }
//            }
//
//        }


        //  var_dump($developer);exit;


        //记录 访问次数 过滤机器人

        $redis = new CacheSetting();
        $redis->incr(CacheSetting::PREFIX_API_CALL_COUNT, date('Ymd'));
        //   if (empty($_REQUEST['is_r'])) {}

        //去除需要检测令牌的情况
        $controller = $this->router->getControllerName();
        $action = $this->router->getActionName();
        if ($development) {
            $this->check_token = false;
        } else if ($is_r) {
            $this->check_token = false;
        } else if (($client_type == 'ios' && version_compare($app_version, '2.0.4', '<=')) ||
            ($client_type == 'android' && version_compare($app_version, '2.1.0101', '<='))
        ) {
            $this->check_token = false;
        } else if (
            in_array($controller, ['app', 'im', 'area', 'site', 'sms', 'rob', 'index', 'ads', 'upload']) ||
            ($controller == 'user' && (in_array($action, ['register', 'login', 'forgot', 'systemInfo']))) ||
            ($controller == 'system' && (in_array(strtolower($action), ['upgradeinfo'])))

        ) {
            $this->check_token = false;
        }


        //消息抄送的去掉接口验证身份
        if (($this->router->getControllerName() == 'im' && $this->router->getActionName() == 'notify') ||
            ($this->router->getControllerName() == 'app' && $this->router->getActionName() == 'apiError') ||
            ($this->router->getControllerName() == 'paidqa' && $this->router->getActionName() == 'chStat') ||
            ($this->router->getControllerName() == 'upload' && $this->router->getActionName() == 'callback')

        ) {
            $this->check_token = false;
            $this->is_checkApi = false;
        }
        //需要检测令牌
        if ($this->check_token) {
            if (!$token) {
                $this->ajax->outError(Ajax::ERROR_TOKEN_INVALID);
            }
            if (!$this->uid) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            /*//this
           if ($this->uid == 62194) {
                $this->ajax->outError(Ajax::ERROR_TOKEN_EXPIRES);
            }*/

            if (!$token_info = UserStatus::getInstance()->checkAccessToken($token, $this->uid)) {
                $this->ajax->outError(Ajax::ERROR_TOKEN_EXPIRES);
            }


        }
        $this->redis = $this->di->get('redis');
        $this->client_type = $client_type;
        $this->app_version = $app_version;
        $this->is_r = $is_r ? true : false;
        define("is_r", $is_r);
        //define("global_redis", $this->redis);
        global $global_redis;
        $global_redis = $this->redis;

        // var_dump(global_redis);
        if ($this->is_checkApi && !$development) {
            if (!$sign) {
                $this->ajax->outError(Ajax::INVALID_SIGN);
            }
            //app检测接口不检测版本号
            if (!($this->router->getControllerName() == 'app' && $this->router->getActionName() == 'checkVersion')) {
                if ($client_type == 'ios' && version_compare($app_version, '1.0.0', '<')) {
                    $this->ajax->outError(Ajax::ERROR_LOW_APP_VERSION);
                } else if ($client_type == 'android' && substr($app_version, 0, 1) == 1) {
                    $this->ajax->outError(Ajax::ERROR_LOW_APP_VERSION);
                }
                if (!AppVersionManager::getInstance()->setOs($client_type)->checkVersion($app_version)) {
                    $this->ajax->outError(Ajax::ERROR_LOW_APP_VERSION);
                }
            }
            if ($this->uid && $this->uid != 1) {
                $login_info = Users::findOne(["id=" . $this->uid, 'columns' => 'last_login_time,last_login_device'], false, true);
                if (!$login_info) {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "非法的请求");
                }
                //非机器人调用接口
                /*  if (!$is_r) {
                      if (!$login_info['last_login_time']) {
                          $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "非法的请求");
                      } elseif (time() - $login_info['last_login_time'] > 86400) {
                          $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "非法的请求");
                      } elseif ($client_type != $login_info['last_login_device']) {
                          $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "非法的请求");
                      }
                  }*/
            }
            if ((!$is_r && (!$client_type || !$app_version)) || !$time_stamp) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "非法的请求");
            }

            //请求的时间戳和当前时间戳相差了10分钟以上 请求已过期
            if (abs(time() - $time_stamp) > 600) {
                $this->ajax->outError(Ajax::INVALID_TIMESTAMP);
            }

            $params = $_REQUEST;
            $verifyResult = Identify::init()->getSignVeryfy($params, $sign, $sign_type, $client_type, $app_version);
            array_shift($params);//去除_url参数
            if (!$verifyResult) {
                $this->ajax->outError(Ajax::ERROR_SIGN);
            }
            /*  //请求的时间戳和当前时间戳相差了10分钟以上 请求已过期
              if (time() - $time_stamp > 600) {
                  $this->ajax->outError(Ajax::INVALID_REQUEST);
              }*/
            $md5_sign = md5($sign);

            //重定向的签名会和重定向之前的签名相同 会导致问题 过滤
            // Debug::log("md5_sign:" . $md5_sign, 'debug');
            //Debug::log("action:" . $action, 'debug');
            if ($this->dispatcher->getActionName() == 'paySuccess') {

            } else {
                $lock = $this->redis->setNX($md5_sign, 1, 30);
                if (!$lock) {
                    $this->ajax->outError(Ajax::INVALID_REQUEST);
                } else {
                    if ($this->redis->zScore(CacheSetting::KEY_SIGN_MD5, $md5_sign)) {
                        // Debug::log("sign:" . $sign, 'debug');
                        // Debug::log("md5_sign:" . $md5_sign, 'debug');
                        $this->ajax->outError(Ajax::INVALID_REQUEST);
                    } else {
                        $this->redis->zAdd(CacheSetting::KEY_SIGN_MD5, date('YmdHi'), md5($sign));
                    }
                    //去除并发锁
                    $this->redis->del($md5_sign);

                    // $this->redis->zAdd(CacheSetting::KEY_SIGN_MD5, date('YmdHi'), md5($sign));
                }
//                if ($this->redis->zScore(CacheSetting::KEY_SIGN_MD5, $md5_sign)) {
//                    // Debug::log("sign:" . $sign, 'debug');
//                    // Debug::log("md5_sign:" . $md5_sign, 'debug');
//                    $this->ajax->outError(Ajax::INVALID_REQUEST);
//                } else {
//                    $this->redis->zAdd(CacheSetting::KEY_SIGN_MD5, date('YmdHi'), md5($sign));
//                }
            }
        }/*else if ( !$this->is_checkApi && !empty($development))//开发模式下不校验签名和token，只校验$development参数是否合法
        {

        }*/
    }

}
