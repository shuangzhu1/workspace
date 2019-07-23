<?php
/**
 * Created by PhpStorm.
 * User: Arimis
 * Date: 14-7-22
 * Time: 下午5:33
 */

namespace Components\ShortMessenger\Adapter;


use Components\ShortMessenger\AbstractAdapter;
use Util\Validator;

class Emoi extends AbstractAdapter
{

    public $adapterName = 'emoi';

    public static $errorMessages = [
        -1 => "参数为空。信息、电话号码等有空指针，登陆失败",
        -2 => "电话号码个数超过100",
        -10 => "申请缓存空间失败",
        -11 => "电话号码中有非数字字符",
        -12 => "有异常电话号码",
        -13 => "电话号码个数与实际个数不相等",
        -14 => "实际号码个数超过100",
        -101 => "发送消息等待超时",
        -102 => "发送或接收消息失败",
        -103 => "接收消息超时",
        -200 => "其他错误",
        -999 => "web服务器内部错误",
        -10001 => "用户登陆不成功(帐号不存在/停用/密码错误)",
        -10002 => "提交格式不正确",
        -10003 => "用户余额不足",
        -10004 => "手机号码不正确",
        -10005 => "计费用户帐号错误",
        -10006 => "计费用户密码错",
        -10007 => "账号已经被停用",
        -10008 => "账号类型不支持该功能",
        -10009 => "其它错误",
        -10010 => "企业代码不正确",
        -10011 => "信息内容超长",
        -10012 => "不能发送联通号码",
        -10013 => "操作员权限不够",
        -10014 => "费率代码不正确",
        -10015 => "服务器繁忙",
        -10016 => "企业权限不够",
        -10017 => "此时间段不允许发送",
        -10018 => "经销商用户名或密码错",
        -10019 => "手机列表或规则错误",
        -10021 => "没有开停户权限",
        -10022 => "没有转换用户类型的权限",
        -10023 => "没有修改用户所属经销商的权限",
        -10024 => "经销商用户名或密码错",
        -10025 => "操作员登陆名或密码错误",
        -10026 => "操作员所充值的用户不存在",
        -10027 => "操作员没有充值商务版的权限",
        -10028 => "该用户没有转正不能充值",
        -10029 => "此用户没有权限从此通道发送信息(用户没有绑定该性质的通道，比如：用户发了小灵通的号码)",
        -10030 => "不能发送移动号码",
        -10031 => "手机号码(段)非法",
        -10032 => "用户使用的费率代码错误",
        -10033 => "非法关键词",
    ];

    public function config(array $config)
    {
        if (isset($config['user_name'])) {
            $this->user_name = $config['user_name'];
        }
        if (isset($config['pass_word'])) {
            $this->pass_word = $config['pass_word'];
        }
        return $this;
    }

    public function send($phone, $message)
    {
        /* 验证参数START */
        if (!Validator::validateNumeric($phone) || !Validator::validateLength($phone, 10, 12) || !Validator::validateCellPhone($phone)) {
            return array(
                'error' => array(
                    'code' => 11111,
                    'msg' => '号码格式不正确',
                    'desc' => '号码格式不合法'
                ),
                'result' => 0
            );
        }
        if (empty($message)) {
            //内容为空
            return array(
                'error' => array(
                    'code' => 22222,
                    'msg' => '内容不能为空',
                    'desc' => '内容不能为空'
                ),
                'result' => 0
            );
        }
        /* 验证参数END */

        $flag = 0;
        // 要post的数据
        $argv = array(
            'userId' => $this->user_name, // 序列号
            'password' => $this->pass_word, //密码
            'pszMobis' => $phone, // 手机号 多个用英文的逗号隔开 post理论没有长度限制.推荐群发一次小于等于10000个手机号
            'pszMsg' => $message, // 短信内容
            'iMobiCount' => 1,
            'pszSubPort' => '' // 默认空 如果空返回系统生成的标识串 如果传值保证值唯一 成功则返回传入的值
        );
//        $result = $this->postRequest("http://58.251.24.190:8082/MWGate/wmgw.asmx/MongateCsSpSendSmsNew", $argv, true);
        $result = file_get_contents("http://58.251.24.190:8082/MWGate/wmgw.asmx/MongateCsSpSendSmsNew?" . http_build_query($argv));
        $this->di->get("debugLogger")->info('request uri: ' . "http://58.251.24.190:8082/MWGate/wmgw.asmx/MongateCsSpSendSmsNew?" . http_build_query($argv) . " ---------- result: " . $result);

        // xml转数组
        $xml = simplexml_load_string($result);
        $mixArray = (array)$xml;
        if (abs($mixArray[0]) >= 20000) { // 发送成功
            return array(
                'error' => array(
                    'code' => 0,
                    'msg' => '成功',
                    'desc' => '短信发送成功'
                ),
                'result' => 1,
                'data' => array(
                    'rr' => $mixArray,
                    'rs' => $result
                )
            );
        } else { // 发送失败
            return array(
                'error' => array(
                    'code' => 33333,
                    'msg' => '失败',
                    'desc' => '短信发送失败'
                ),
                'result' => 0,
                'data' => array(
                    'ec' => $mixArray[0],
                    'rs' => $result
                )
            );
        }
    }

    public function massSend(array $phones, $message)
    {

    }

} 