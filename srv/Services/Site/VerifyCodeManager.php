<?php
namespace Services\Site;

use Components\YunPian\lib\VoiceOperator;
use Components\Yunxin\ServerAPI;
use Phalcon\Di;
use Phalcon\Logger;
use Phalcon\Mvc\User\Plugin;
use Util\Ajax;
use Util\Debug;
use Components\YunPian\lib\SmsOperator;
use Util\GetClient;

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/2
 * Time: 13:49
 */
class VerifyCodeManager extends Plugin
{
    #--**--验证码前缀定义--**--#
    const CODE_REGISTER = 1001; #注册  验证码
    const CODE_BIND = 1002; #绑定/手机验证 验证码
    const CODE_FORGOT = 1003; #找回密码时 验证码
    const CODE_CHANGE = 1004; #修改手机
    const CODE_AUTH = 1005; #认证
    const CODE_LOGIN_SAFE = 1006; #登录保护-认证
    const CODE_UNLOCK = 1007; #账号临时锁定-登录
    const CODE_MERGENCY_NEWS = 1008; #新闻抓取失败消息
    const CODE_PAY_PASSWORD = 1009; #找回支付密码
    const CODE_SET_PAY_PASSWORD = 1010; #设置支付密码
    const CODE_UNSUFFICIENT_REWARD = 1011; #资金奖励池不足
    const CODE_UNSUFFICIENT_PROMOTE = 1012; #推广资金不足
    const CODE_CASH_OUT = 1013; #申请提现
    const CODE_SERVER_ABNORMAL = 1014; #服务器异常
    const CODE_SEND_REDBAG_FROM_WEB = 1015;#后台发送红包君红包时短信模板

    //短信发送结果
    const SEND_FAIL_KLG = 1;//恐龙谷限制
    const SEND_FAIL_YP = 2;//云片限制
    const SEND_SUCCESS = 3;//成功

    public static $codetype = array(
        self::CODE_REGISTER => 'register',
        self::CODE_BIND => 'bind',
        self::CODE_CHANGE => 'change',
        self::CODE_FORGOT => 'forgot',
        self::CODE_AUTH => 'auth',
        self::CODE_LOGIN_SAFE => 'login_protect',
        self::CODE_UNLOCK => 'unlock',
        self::CODE_MERGENCY_NEWS => 'mergency_news',
        self::CODE_PAY_PASSWORD => 'pay_password',
        self::CODE_SET_PAY_PASSWORD => 'set_pay_password',
        self::CODE_UNSUFFICIENT_REWARD => 'unsufficient_reward',
        self::CODE_UNSUFFICIENT_PROMOTE => 'unsufficient_promote',
        self::CODE_CASH_OUT => 'cashout',
        self::CODE_SERVER_ABNORMAL => 'server_abnormal',
        self::CODE_SEND_REDBAG_FROM_WEB => 'code_send_redbag_from_web',

    );

    protected static $error_code = [
        "22" => "短信发送过于频繁[一小时内不超过3条]",
        "17" => "短信发送过于频繁[24小时内不超过10条]"
    ];

    #设置过期时间
    const EXPIRE = 1200; #20分钟
    public $redis = null;
    public $redis_sms = null;
    public $ajax = null;
    protected $is_cli = false;

    private static $instance = null;

    public function __construct($is_cli)
    {
        $this->redis = $this->di->get('redis');
        $this->redis_sms = $this->di->get('redis_queue');
        if (!$is_cli) {
            $this->ajax = new Ajax();
        }
        $this->is_cli = $is_cli;
    }

    /**
     * @param bool $is_cli
     * @return null|VerifyCodeManager
     */
    public static function init($is_cli = false)
    {
        if (!self::$instance) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    #发送手机验证码
    public function sendPhoneVerifyCode($phone, $type, $device, $uid = 0, $is_voice = 0, $need_code = true, $extra = [])
    {

        $cache_key = $type . '_' . $phone . '_' . $device . '_' . $uid;

        Debug::log("cache_key:" . $cache_key, 'sms');
        $cache = $this->redis->get($cache_key);
        /*一天发送的次数*/
        $send_count_cache_key = "sms_day_count_data:" . $phone;
        $send_count_cache = $this->redis->get($send_count_cache_key);


        $time = time(); #当前时间
        //1天就发送了10条 说明用户非法操作,一直在发送  .//记录的时间在1天之前，做清零操作
        if ($send_count_cache && time() - $send_count_cache['first_send_time'] <= 86400) {
            if ($send_count_cache['count'] >= 10) {
                $errCode = Ajax::ERROR_SEND_PHONE_CODE_TOO_FREQUENCY;
                self::smsSendRecords($phone, self::SEND_FAIL_KLG, $uid, $type, $device, Ajax::getErrorMsg($errCode), $is_voice, '');
                Ajax::outError($errCode);
            }
        } else {
            $send_count_cache['first_send_time'] = time();
            $send_count_cache['count'] = 0;
        }
        //一分钟内只允许发送一个验证码
        if ($cache && is_array($cache) && (time() - $cache ['last_send_time']) < 60) {
            $errCode = Ajax::ERROR_SEND_BYOND_ONE;
            self::smsSendRecords($phone, self::SEND_FAIL_KLG, $uid, $type, $device, Ajax::getErrorMsg($errCode), $is_voice, '');
            Ajax::outError($errCode);
        }
        $code = rand(100000, 999999); #验证码
        if (in_array($type, [self::$codetype[self::CODE_LOGIN_SAFE], self::$codetype[self::CODE_UNLOCK]])) {
            $msg = self::compileTemple(array('code' => $code), "normal");
        } else {
            $msg = self::compileTemple(array('code' => $code), $type);
        }
        $sms = new SmsOperator();

        if (!$is_voice) {
            $res = $sms->single_send(['mobile' => $phone, 'text' => $msg]);
        } //语音验证码
        else {
            $sms = new VoiceOperator();
            $res = $sms->send(['mobile' => $phone, 'code' => $code]);
        }
        //   $res=iconv('utf-8','gb2312',$res);
        /*  Debug::log('云片发短信:' . (var_export($res->responseData, true)), 'sms');*/
        Debug::log("phone:" . $phone . ",code:" . $code, 'sms');
        if ($res && $res->responseData['code'] == 0) {
            $cache ['last_send_code'] = $code;
            $cache ['last_send_phone'] = $phone;
            $cache ['last_send_time'] = $time;
            $cache ['last_send_count'] = isset($cache['last_send_count']) ? $cache['last_send_count'] + 1 : 1;
            $send_count_cache['count'] += 1;
            $this->redis->save($cache_key, $cache, self::EXPIRE);
            $this->redis->save($send_count_cache_key, $send_count_cache, 86400);//一天

            self::smsSendRecords($phone, self::SEND_SUCCESS, $uid, $type, $device, '', $is_voice, $msg);
            if ($need_code) {
                if ((client_type == 'android' && version_compare(app_version, '2.2.0', '<='))
                    || (client_type == 'ios' && version_compare(app_version, '2.1.0', '<='))
                ) {
                    $this->ajax->outRight($code);
                } elseif (is_r) {
                    $this->ajax->outRight($code);
                } else {
                    $this->ajax->outRight('');
                }
            } else {
                $this->ajax->outRight("发送成功");
            }
        } else {
            self::smsSendRecords($phone, self::SEND_FAIL_YP, $uid, $type, $device, $res->responseData['detail'], $is_voice, '');
            Debug::log('哇哦,短信发送出了点问题' . (var_export($res->responseData, true)), 'sms');
            Debug::log('哇哦,短信发送出了点问题' . ($res ? $res->responseData['detail'] : ''), 'sms');
            if (!key_exists($res->responseData['code'], self::$error_code)) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "短信发送失败[" . $res->responseData['code'] . ']');
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, self::$error_code[$res->responseData['code']]);
            }
            //$this->ajax->outError(Ajax::ERROR_SEND_VERIFY_CODE);
        }
    }

    /**发送普通短信
     * @param $phone
     * @param $type
     * @param $data
     * @param $device
     * @param int $uid
     * @return bool
     */
    public function sendPhoneNormalMessage($phone, $type, $data, $device, $uid = 0)
    {
        $msg = self::compileTemple($data, $type);
        $sms = new SmsOperator();
        $res = $sms->single_send(['mobile' => $phone, 'text' => $msg]);
        //   $res=iconv('utf-8','gb2312',$res);
        if ($res && $res->responseData['code'] == 0) {
            //  self::smsSendRecords($phone, self::SEND_SUCCESS, $uid, $type, $device, '', 0, $msg);
            if ($this->is_cli) {
                echo "发送成功";
                exit;
            } else {
                $this->ajax->outRight("发送成功");
            }
        } else {
            //   self::smsSendRecords($phone, self::SEND_FAIL_YP, $uid, $type, $device, $res->responseData['detail'], 0, '');
            Debug::log('哇哦,短信发送出了点问题' . (var_export($res->responseData, true)), 'sms');
            Debug::log('哇哦,短信发送出了点问题' . ($res ? $res->responseData['detail'] : ''), 'sms');
            if ($this->is_cli) {
                echo "发送失败";
                var_dump($res->responseData);
                exit;
            } else {
                $this->ajax->outError(Ajax::ERROR_SEND_VERIFY_CODE);
            }
        }
    }


    # 检测验证码
    public function checkVerifyCode($account, $type, $device, $uid = 0, $code)
    {
        $cache_key = $type . '_' . $account . '_' . $device . '_' . $uid;
        Debug::log("ip:" . var_export( GetClient::Getip(), true) . $code, 'sms');
        Debug::log("cache_key:" . $cache_key, 'sms');
        $cache = $this->redis->get($cache_key);
        //测试专用
        /*  if ($code == '888888') {
              return '1';
          }*/
        if (!$cache) {
            return Ajax::ERROR_VERIFY_CODE_OLD;
        }
        /*  //测试专用
          if ($code == '8888') {
              return '1';
          }*/
        if ($cache['last_send_code'] != $code) {
            return Ajax::ERROR_VERIFY_CODE;
        }
        return '1';
    }

    # 注销验证码
    public function clearVerifyCode($account, $type, $device, $uid = 0)
    {
        $cache_key = $type . '_' . $account . '_' . $device . '_' . $uid;
        $cache = $this->redis->get($cache_key);
        if ($cache) {
            $this->redis->delete($cache_key);
        }
        return true;
    }

    //编译模板
    public function compileTemple(array $data, $type)
    {
        $res = "";
        $template = SiteKeyValManager::init()->getOneByKey(SiteKeyValManager::KEY_PAGE_SMS_TPL, $type);
        if ($template) {
            $res = $template['val'];
            foreach ($data as $key => $val) {
                $res = str_replace('#' . $key . '#', $val, $res);
            }
        }

        return $res;
    }

    /**
     * @param $phone string 手机号
     * @param $status int 发送状态
     * @param $uid
     * @param $type
     * @param $device
     * @param $fail_reason
     * @param $is_voice
     * @param $content  string 短信内容
     */
    private static function smsSendRecords($phone, $status, $uid, $type, $device, $fail_reason, $is_voice, $content)
    {
        $data['phone'] = $phone;
        $data['status'] = $status;
        $data['uid'] = $uid;
        $data['type'] = $type;
        $data['device'] = $device;
        $data['fail_reason'] = $fail_reason;
        $data['is_voice'] = $is_voice;
        $data['content'] = $content;
        $data['send_time'] = time();
        Di::getDefault()->get('redis_queue')->rPush("sms_send_records", json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}