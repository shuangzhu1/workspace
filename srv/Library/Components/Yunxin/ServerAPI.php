<?php
/**
 * 网易云信server API 接口 2.0
 * Class ServerAPI
 * ykuang
 * 2016-11-29
 ****
 *
 ***/
namespace Components\Yunxin;

use Phalcon\Mvc\User\Plugin;
use Util\Debug;

class ServerAPI extends Plugin
{
    private $AppKey;                //开发者平台分配的AppKey
    private $AppSecret;             //开发者平台分配的AppSecret
    private $Nonce;                    //随机数（最大长度128个字符）
    private $CurTime;                //当前UTC时间戳，从1970年1月1日0点0 分0 秒开始到现在的秒数(String)
    private $CheckSum;                //SHA1(AppSecret + Nonce + CurTime),三个参数拼接的字符串，进行SHA1哈希计算，转化成16进制字符(String，小写)
    const   HEX_DIGITS = "0123456789abcdef";
    public static $instance = null;
    private static $async_ip = 'http://119.23.54.215:5011';//内部异步发送接口地址

    /**
     * 参数初始化
     * @param $RequestType [选择php请求方式，fsockopen或curl,若为curl方式，请检查php配置是否开启]
     */
    public function __construct($RequestType = 'curl')
    {
        $config = $this->di->get('config')->yun_xin;
        $this->AppKey = $config->app_key;
        $this->AppSecret = $config->app_secret;
        $this->RequestType = $RequestType;
    }

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * API checksum校验生成
     * @param  void
     * @return $CheckSum(对象私有属性)
     */
    public function checkSumBuilder()
    {
        //此部分生成随机字符串
        $hex_digits = self::HEX_DIGITS;
        $this->Nonce = '';
        for ($i = 0; $i < 128; $i++) {            //随机字符串最大128个字符，也可以小于该数
            $this->Nonce .= $hex_digits[rand(0, 15)];
        }
        $this->CurTime = (string)(time());    //当前时间戳，以秒为单位

        $join_string = $this->AppSecret . $this->Nonce . $this->CurTime;
        $this->CheckSum = sha1($join_string);
        //print_r($this->CheckSum);
    }

    /**消息抄送 服务器验证身份
     * @param $MD5
     * @param $CurTime
     * @param $Checksum
     * @return bool
     */
    public function checkSum($MD5, $CurTime, $Checksum)
    {
        $join_string = $this->AppSecret . $MD5 . $CurTime;
        if (sha1($join_string) == $Checksum) {
            return true;
        }
        return false;
    }

    /**
     * 将json字符串转化成php数组
     * @param  $json_str
     * @return $json_arr
     */
    public function json_to_array($json_str)
    {
        if (is_array($json_str) || is_object($json_str)) {
            //  $json_str = $json_str;
        } else if (is_null(json_decode($json_str))) {
            //  $json_str = $json_str;
        } else {
            $json_str = strval($json_str);
            $json_str = json_decode($json_str, true);
        }
        $json_arr = array();
        if ($json_str && is_array($json_str)) {
            foreach ($json_str as $k => $w) {
                if (is_object($w)) {
                    $json_arr[$k] = $this->json_to_array($w); //判断类型是不是object
                } else if (is_array($w)) {
                    $json_arr[$k] = $this->json_to_array($w);
                } else {
                    $json_arr[$k] = $w;
                }
            }
        }
        return $json_arr;
    }

    /**
     * 使用CURL方式发送post请求
     * @param  $url [请求地址]
     * @param  $data [array格式数据]
     * @return $请求返回结果(array)
     */
    public function postDataCurl($url, $data)
    {
        $this->checkSumBuilder();       //发送请求前需先生成checkSum

        $timeout = 5000;
        $http_header = array(
            'AppKey:' . $this->AppKey,
            'Nonce:' . $this->Nonce,
            'CurTime:' . $this->CurTime,
            'CheckSum:' . $this->CheckSum,
            'Content-Type:application/x-www-form-urlencoded;charset=utf-8'
        );
        //print_r($http_header);

        // $postdata = '';
        $postdataArray = array();
        foreach ($data as $key => $value) {
            array_push($postdataArray, $key . '=' . urlencode($value));
            // $postdata.= ($key.'='.urlencode($value).'&');
        }
        $postdata = join('&', $postdataArray);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (false === $result) {
            $result = curl_errno($ch);
        }
        curl_close($ch);
        return $this->json_to_array($result);
    }

    /**
     * 使用FSOCKOPEN方式发送post请求
     * @param  $url [请求地址]
     * @param  $data [array格式数据]
     * @return $请求返回结果(array)
     */
    public function postDataFsockopen($url, $data)
    {
        $this->checkSumBuilder();       //发送请求前需先生成checkSum

        // $postdata = '';
        $postdataArray = array();
        foreach ($data as $key => $value) {
            array_push($postdataArray, $key . '=' . urlencode($value));
            // $postdata.= ($key.'='.urlencode($value).'&');
        }
        $postdata = join('&', $postdataArray);
        // building POST-request: 
        $URL_Info = parse_url($url);
        if (!isset($URL_Info["port"])) {
            $URL_Info["port"] = 80;
        }
        $request = '';
        $request .= "POST " . $URL_Info["path"] . " HTTP/1.1\r\n";
        $request .= "Host:" . $URL_Info["host"] . "\r\n";
        $request .= "Content-type: application/x-www-form-urlencoded;charset=utf-8\r\n";
        $request .= "Content-length: " . strlen($postdata) . "\r\n";
        $request .= "Connection: close\r\n";
        $request .= "AppKey: " . $this->AppKey . "\r\n";
        $request .= "Nonce: " . $this->Nonce . "\r\n";
        $request .= "CurTime: " . $this->CurTime . "\r\n";
        $request .= "CheckSum: " . $this->CheckSum . "\r\n";
        $request .= "\r\n";
        $request .= $postdata . "\r\n";

        // print_r($request);
        $fp = fsockopen($URL_Info["host"], $URL_Info["port"]);
        fputs($fp, $request);
        $result = '';
        while (!feof($fp)) {
            $result .= fgets($fp, 128);
        }
        fclose($fp);

        $str_s = strpos($result, '{');
        $str_e = strrpos($result, '}');
        $str = substr($result, $str_s, $str_e - $str_s + 1);
        return $this->json_to_array($str);
    }

    /**
     * 使用CURL方式发送post请求（JSON类型）
     * @param  $url [请求地址]
     * @param  $data [array格式数据]
     * @return $请求返回结果(array)
     */
    public function postJsonDataCurl($url, $data)
    {
        $this->checkSumBuilder();        //发送请求前需先生成checkSum

        $timeout = 5000;
        $http_header = array(
            'AppKey:' . $this->AppKey,
            'Nonce:' . $this->Nonce,
            'CurTime:' . $this->CurTime,
            'CheckSum:' . $this->CheckSum,
            'Content-Type:application/json;charset=utf-8'
        );
        //print_r($http_header);

        $postdata = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (false === $result) {
            $result = curl_errno($ch);
        }
        curl_close($ch);
        return $this->json_to_array($result);
    }

    /**
     * 使用FSOCKOPEN方式发送post请求（json）
     * @param  $url [请求地址]
     * @param  $data [array格式数据]
     * @return $请求返回结果(array)
     */
    public function postJsonDataFsockopen($url, $data)
    {
        $this->checkSumBuilder();       //发送请求前需先生成checkSum

        $postdata = json_encode($data);

        // building POST-request: 
        $URL_Info = parse_url($url);
        if (!isset($URL_Info["port"])) {
            $URL_Info["port"] = 80;
        }
        $request = '';
        $request .= "POST " . $URL_Info["path"] . " HTTP/1.1\r\n";
        $request .= "Host:" . $URL_Info["host"] . "\r\n";
        $request .= "Content-type: application/json;charset=utf-8\r\n";
        $request .= "Content-length: " . strlen($postdata) . "\r\n";
        $request .= "Connection: close\r\n";
        $request .= "AppKey: " . $this->AppKey . "\r\n";
        $request .= "Nonce: " . $this->Nonce . "\r\n";
        $request .= "CurTime: " . $this->CurTime . "\r\n";
        $request .= "CheckSum: " . $this->CheckSum . "\r\n";
        $request .= "\r\n";
        $request .= $postdata . "\r\n";

        print_r($request);
        $fp = fsockopen($URL_Info["host"], $URL_Info["port"]);
        fputs($fp, $request);
        $result = '';
        while (!feof($fp)) {
            $result .= fgets($fp, 128);
        }
        fclose($fp);

        $str_s = strpos($result, '{');
        $str_e = strrpos($result, '}');
        $str = substr($result, $str_s, $str_e - $str_s + 1);
        return $this->json_to_array($str);
    }

    /**
     * 创建云信ID
     * 1.第三方帐号导入到云信平台；
     * 2.注意accid，name长度以及考虑管理秘钥token
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $name [云信ID昵称，最大长度64字节，用来PUSH推送时显示的昵称]
     * @param  $props [json属性，第三方可选填，最大长度1024字节]
     * @param  $icon [云信ID头像URL，第三方可选填，最大长度1024]
     * @param  $token [云信ID可以指定登录token值，最大长度128字节，并更新，如果未指定，会自动生成token，并在创建成功后返回]
     * @return $result    [返回array数组对象]
     */
    public function createUserId($accid, $name = '', $props = '{}', $icon = '', $token = '')
    {
        $url = 'https://api.netease.im/nimserver/user/create.action';
        $data = array(
            'accid' => $accid,
            'name' => $name,
            'props' => $props,
            'icon' => $icon,
            'token' => $token
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }


    /**
     * 更新云信ID
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $name [云信ID昵称，最大长度64字节，用来PUSH推送时显示的昵称]
     * @param  $props [json属性，第三方可选填，最大长度1024字节]
     * @param  $token [云信ID可以指定登录token值，最大长度128字节，并更新，如果未指定，会自动生成token，并在创建成功后返回]
     * @return $result    [返回array数组对象]
     */
    public function updateUserId($accid, $name = '', $props = '{}', $token = '')
    {
        $url = 'https://api.netease.im/nimserver/user/update.action';
        $data = array(
            'accid' => $accid,
            'name' => $name,
            'props' => $props,
            'token' => $token
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 更新并获取新token
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result    [返回array数组对象]
     */
    public function updateUserToken($accid)
    {
        $url = 'https://api.netease.im/nimserver/user/refreshToken.action';
        $data = array(
            'accid' => $accid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 封禁云信ID
     * 第三方禁用某个云信ID的IM功能,封禁云信ID后，此ID将不能登陆云信imserver
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result    [返回array数组对象]
     */
    public function blockUserId($accid)
    {
        $url = 'https://api.netease.im/nimserver/user/block.action';
        $data = array(
            'accid' => $accid,
            //  'needkick'=>false
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 解禁云信ID
     * 第三方禁用某个云信ID的IM功能,封禁云信ID后，此ID将不能登陆云信imserver
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result    [返回array数组对象]
     */
    public function unblockUserId($accid)
    {
        $url = 'https://api.netease.im/nimserver/user/unblock.action';
        $data = array(
            'accid' => $accid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }


    /**
     * 更新用户名片
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $name [云信ID昵称，最大长度64字节，用来PUSH推送时显示的昵称]
     * @param  $icon [用户icon，最大长度256字节]
     * @param  $sign [用户签名，最大长度256字节]
     * @param  $email [用户email，最大长度64字节]
     * @param  $birth [用户生日，最大长度16字节]
     * @param  $mobile [用户mobile，最大长度32字节]
     * @param  $ex [用户名片扩展字段，最大长度1024字节，用户可自行扩展，建议封装成JSON字符串]
     * @param  $gender [用户性别，0表示未知，1表示男，2女表示女，其它会报参数错误]
     * @return $result      [返回array数组对象]
     */
    public function updateUinfo($accid, $name = '', $icon = '', $sign = '', $email = '', $birth = '', $mobile = '', $gender = '0', $ex = '')
    {
        $data = ['accid' => $accid];
        $url = 'https://api.netease.im/nimserver/user/updateUinfo.action';
        if ($name) {
            $data['name'] = $name;
        }
        if ($icon) {
            $data['icon'] = $icon;
        }
        if ($sign) {
            $data['sign'] = $sign;
        }
        if ($email) {
            $data['email'] = $email;
        }
        if ($birth) {
            $data['birth'] = $birth;
        }
        if ($mobile) {
            $data['mobil'] = $mobile;
        }
        if ($gender) {
            $data['gender'] = $gender;
        }
        if ($ex) {
            $data['ex'] = $ex;
        }
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 获取用户名片，可批量
     * @param  $accids [用户帐号（例如：JSONArray对应的accid串，如："zhangsan"，如果解析出错，会报414）（一次查询最多为200）]
     * @return $result    [返回array数组对象]
     */
    public function getUinfos($accids)
    {
        $url = 'https://api.netease.im/nimserver/user/getUinfos.action';
        $data = array(
            'accids' => json_encode($accids)
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 好友关系-加好友
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $faccid [云信ID昵称，最大长度64字节，加好友接收者accid]
     * @param  $type [用户type，最大长度256字节 1直接加好友，2请求加好友，3同意加好友，4拒绝加好友]
     * @param  $msg [用户签名，最大长度256字节]
     * @return $result      [返回array数组对象]
     */
    public function addFriend($accid, $faccid, $type = '1', $msg = '')
    {
        $url = 'https://api.netease.im/nimserver/friend/add.action';
        $data = array(
            'accid' => $accid,
            'faccid' => $faccid,
            'type' => $type,
            'msg' => $msg
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 好友关系-更新好友信息
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $faccid [要修改朋友的accid]
     * @param  $alias [给好友增加备注名]
     * @return $result      [返回array数组对象]
     */
    public function updateFriend($accid, $faccid, $alias)
    {
        $url = 'https://api.netease.im/nimserver/friend/update.action';
        $data = array(
            'accid' => $accid,
            'faccid' => $faccid,
            'alias' => $alias
        );
        //  Debug::log("data:" . var_export($data, true), 'yunxin');
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 好友关系-获取好友关系
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result      [返回array数组对象]
     */
    public function getFriend($accid)
    {
        $url = 'https://api.netease.im/nimserver/friend/get.action';
        $data = array(
            'accid' => $accid,
            'createtime' => (string)(time() * 1000)
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 好友关系-删除好友信息
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $faccid [要修改朋友的accid]
     * @return $result      [返回array数组对象]
     */
    public function deleteFriend($accid, $faccid)
    {
        $url = 'https://api.netease.im/nimserver/friend/delete.action';
        $data = array(
            'accid' => $accid,
            'faccid' => $faccid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 好友关系-设置黑名单
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $targetAcc [被加黑或加静音的帐号]
     * @param  $relationType [本次操作的关系类型,1:黑名单操作，2:静音列表操作]
     * @param  $value [操作值，0:取消黑名单或静音；1:加入黑名单或静音]
     * @return $result      [返回array数组对象]
     */
    public function specializeFriend($accid, $targetAcc, $relationType = '1', $value = '1')
    {
        $url = 'https://api.netease.im/nimserver/user/setSpecialRelation.action';
        $data = array(
            'accid' => $accid,
            'targetAcc' => $targetAcc,
            'relationType' => $relationType,
            'value' => $value
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 好友关系-查看黑名单列表
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result      [返回array数组对象]
     */
    public function listBlackFriend($accid)
    {
        $url = 'https://api.netease.im/nimserver/user/listBlackAndMuteList.action';
        $data = array(
            'accid' => $accid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 消息功能-发送普通消息
     * @param  $from [发送者accid，用户帐号，最大32字节，APP内唯一]
     * @param  $ope [0：点对点个人消息，1：群消息，其他返回414]
     * @param  $to [ope==0是表示accid，ope==1表示tid]
     * @param  $type [0 表示文本消息,1 表示图片，2 表示语音，3 表示视频，4 表示地理位置信息，6 表示文件，100 自定义消息类型]
     * @param  $body [请参考下方消息示例说明中对应消息的body字段。最大长度5000字节，为一个json字段。]
     * @param  $option [发消息时特殊指定的行为选项,Json格式，可用于指定消息的漫游，存云端历史，发送方多端同步，推送，消息抄送等特殊行为;option中字段不填时表示默认值]
     * @param  $pushcontent [推送内容，发送消息（文本消息除外，type=0），option选项中允许推送（push=true），此字段可以指定推送内容。 最长200字节]
     * @param  $payload [ios 推送对应的payload,必须是JSON,不能超过2k字符]
     * @param $ext [开发者扩展字段，长度限制1024字符]
     * @return $result      [返回array数组对象]
     */
    public function sendMsg($from, $ope, $to, $type, $body, $ext = '', $pushcontent = '', $option = array("push" => true, "roam" => true, "history" => true, "sendersync" => true, "route" => false), $payload = '')
    {

        $url = self::$async_ip . '/msg/sendMsg';/*'https://api.netease.im/nimserver/msg/sendMsg.action'*/;
        $data = array(
            'from' => $from,
            'ope' => $ope,
            'to' => $to,
            'type' => $type,
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'option' => json_encode($option),
            'pushcontent' => $pushcontent,
            'payload' => $payload,
            'ext' => $type == 0 ? '' : $ext,
        );
        // Debug::log(var_export($data, true), 'im');
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 消息功能-发送普通消息
     * @param  $from [发送者accid，用户帐号，最大32字节，APP内唯一]
     * @param  $ope [0：点对点个人消息，1：群消息，其他返回414]
     * @param  $to [ope==0是表示accid，ope==1表示tid]
     * @param  $type [0 表示文本消息,1 表示图片，2 表示语音，3 表示视频，4 表示地理位置信息，6 表示文件，100 自定义消息类型]
     * @param  $body [请参考下方消息示例说明中对应消息的body字段。最大长度5000字节，为一个json字段。]
     * @param  $option [发消息时特殊指定的行为选项,Json格式，可用于指定消息的漫游，存云端历史，发送方多端同步，推送，消息抄送等特殊行为;option中字段不填时表示默认值]
     * @param  $pushcontent [推送内容，发送消息（文本消息除外，type=0），option选项中允许推送（push=true），此字段可以指定推送内容。 最长200字节]
     * @param  $payload [ios 推送对应的payload,必须是JSON,不能超过2k字符]
     * @param $ext [开发者扩展字段，长度限制1024字符]
     * @return $result      [返回array数组对象]
     */
    public function asyncSendMsg($from, $ope, $to, $type, $body, $ext = '', $pushcontent = '', $option = array("push" => true, "roam" => true, "history" => false, "sendersync" => true, "route" => false), $payload = '')
    {
        $url = 'https://api.netease.im/nimserver/msg/sendMsg.action';
        $data = array(
            'from' => $from,
            'ope' => $ope,
            'to' => $to,
            'type' => $type,
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'option' => json_encode($option),
            'pushcontent' => $pushcontent,
            'payload' => $payload,
            'ext' => $type == 0 ? '' : $ext,
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 消息功能-发送自定义系统消息
     * 1.自定义系统通知区别于普通消息，方便开发者进行业务逻辑的通知。
     * 2.目前支持两种类型：点对点类型和群类型（仅限高级群），根据msgType有所区别。
     * @param  $from [发送者accid，用户帐号，最大32字节，APP内唯一]
     * @param  $msgtype [0：点对点个人消息，1：群消息，其他返回414]
     * @param  $to [msgtype==0是表示accid，msgtype==1表示tid]
     * @param  $attach [自定义通知内容，第三方组装的字符串，建议是JSON串，最大长度1024字节]
     * @param  $pushcontent [ios推送内容，第三方自己组装的推送内容，如果此属性为空串，自定义通知将不会有推送（pushcontent + payload不能超过200字节）]
     * @param  $payload [ios 推送对应的payload,必须是JSON（pushcontent + payload不能超过200字节）]
     * @param  $sound [如果有指定推送，此属性指定为客户端本地的声音文件名，长度不要超过30个字节，如果不指定，会使用默认声音]
     * @return $result      [返回array数组对象]
     */
    public function sendAttachMsg($from, $msgtype, $to, $attach, $pushcontent = '', $payload = array(), $sound = '')
    {
        $url = self::$async_ip . '/msg/sendAttachMsg';
        //'https://api.netease.im/nimserver/msg/sendAttachMsg.action';
        $data = array(
            'from' => $from,
            'msgtype' => $msgtype,
            'to' => $to,
            'attach' => $attach,
            'pushcontent' => $pushcontent,
            'payload' => json_encode($payload),
            'sound' => $sound
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 消息功能-发送批量点对点自定义系统通知
     * 1.自定义系统通知区别于普通消息，方便开发者进行业务逻辑的通知。
     * 2.目前支持两种类型：点对点类型和群类型（仅限高级群），根据msgType有所区别。
     * @param  $from [发送者accid，用户帐号，最大32字符，APP内唯一]
     * @param  $msgtype [0：点对点个人消息，1：群消息，其他返回414]
     * @param  $to [["aaa","bbb"]（JSONArray对应的accid，如果解析出错，会报414错误），最大限500人]
     * @param  $attach [自定义通知内容，第三方组装的字符串，建议是JSON串，最大长度4096字符]
     * @param  $pushcontent [iOS推送内容，第三方自己组装的推送内容,不超过150字符]
     * @param  $payload [iOS推送对应的payload,必须是JSON,不能超过2k字符]
     * @param  $sound [如果有指定推送，此属性指定为客户端本地的声音文件名，长度不要超过30个字符，如果不指定，会使用默认声音]
     * @param  $save [1表示只发在线，2表示会存离线，其他会报414错误。默认会存离线]
     * @param  $option [发消息时特殊指定的行为选项,Json格式，可用于指定消息计数等特殊行为;option中字段不填时表示默认值。
     * option示例：
     * {"badge":false,"needPushNick":false,"route":false}
     * 字段说明：
     * 1. badge:该消息是否需要计入到未读计数中，默认true;
     * 2. needPushNick: 推送文案是否需要带上昵称，不设置该参数时默认false(ps:注意与sendBatchMsg.action接口有别)。
     * 3. route: 该消息是否需要抄送第三方；默认true (需要app开通消息抄送功能)]
     * @return $result      [返回array数组对象]
     */
    public function sendBatchAttachMsg($from, $to, $attach, $pushcontent = '', $payload = array(), $sound = '')
    {
        $url = self::$async_ip . '/msg/sendBatchAttachMsg';// 'https://api.netease.im/nimserver/msg/sendBatchAttachMsg.action';
        $data = array(
            'fromAccid' => $from,
            'toAccids' => json_encode($to),
            'attach' => $attach,
            'pushcontent' => $pushcontent,
            'payload' => json_encode($payload),
            'sound' => $sound
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 消息功能-批量发发送点对点普通消息
     * @param from - 发送者accid，用户帐号，最大32字符，必须保证一个APP内唯一
     * @param to - ["aaa","bbb"]（JSONArray对应的accid，如果解析出错，会报414错误），限500人
     * @param type - 0 表示文本消息,1 表示图片，2 表示语音，3 表示视频，4 表示地理位置信息， 6 表示文件，100 自定义消息类型
     * @param  body -请参考下方消息示例说明中对应消息的body字段，最大长度5000字符，为一个json串
     * @param option -发消息时特殊指定的行为选项,Json格式，可用于指定消息的漫游，存云端历史，发送方多端同步，推送，消息抄送等特殊行为;option中字段不填时表示默认值 option示例:
     *
     * {"push":false,"roam":true,"history":false,"sendersync":true,"route":false,"badge":false,"needPushNick":true}
     * 字段说明：
     * 1. roam: 该消息是否需要漫游，默认true（需要app开通漫游消息功能）；
     * 2. history: 该消息是否存云端历史，默认true；
     *
     * 3. sendersync: 该消息是否需要发送方多端同步，默认true；
     *
     * 4. push: 该消息是否需要APNS推送或安卓系统通知栏推送，默认true；
     *
     * 5. route: 该消息是否需要抄送第三方；默认true (需要app开通消息抄送功能);
     *
     * 6. badge:该消息是否需要计入到未读计数中，默认true;
     * 7. needPushNick: 推送文案是否需要带上昵称，不设置该参数时默认true;
     * @param pushcontent    ios推送内容，不超过150字符，option选项中允许推送（push=true），此字段可以指定推送内容
     * @param payload         ios 推送对应的payload,必须是JSON,不能超过2k字符
     * @param ext     开发者扩展字段，长度限制1024字符
     *
     * @return $result      [返回array数组对象]
     **/
    public function sendBatchMsg($from, $to, $type = 0, $body, $ext = '', $option = array("push" => false, "roam" => true, "history" => false, "sendersync" => true, "route" => false), $payload = array(), $pushcontent = '')
    {
        $url = self::$async_ip . '/msg/sendBatchMsg';//'https://api.netease.im/nimserver/msg/sendBatchMsg.action';
        $data = array(
            'fromAccid' => $from,
            'toAccids' => $to,
            'type' => $type,
            'body' => $body,
            'option' => json_encode($option),
            'payload' => $payload ? json_encode($payload) : '',
            'pushcontent' => $pushcontent,
            'ext' => $ext,
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 发送广播消息     --目前不会抄送
     *
     * 参数             类型    必须    说明
     * body            String    是    广播消息内容，最大4096字符
     * from            String    否    发送者accid, 用户帐号，最大长度32字符，必须保证一个APP内唯一
     * isOffline       String    否    是否存离线，true或false，默认false
     * ttl             int       否    存离线状态下的有效期，单位小时，默认7天
     * targetOs        String    否    目标客户端，默认所有客户端，jsonArray，格式：["ios","aos","pc","web","mac"]
     *
     * @param $from
     * @param $body
     * @param bool $isOffline
     * @param int $ttl
     * @param string $targetOs
     * @return array
     */
    public function sendBroadcastMsg($from, $body, $isOffline = false, $ttl = 168, $targetOs = "")
    {
        $url = 'https://api.netease.im/nimserver/msg/broadcastMsg.action';//self::$async_ip . '/msg/broadcastMsg';
        $data = array(
            'from' => $from,
            'body' => $body,
            "isOffline" => $isOffline,
            "ttl" => $ttl,
        );
        if ($targetOs) {
            $data["targetOs"] = $targetOs;
        }
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 撤回消息
     * @param $from -发消息的accid
     * @param $msgtype 7:表示点对点消息撤回，8:表示群消息撤回，其它为参数错误
     * @param $to -如果点对点消息，为接收消息的accid,如果群消息，为对应群的tid
     * @param $deleteMsgid -要撤回消息的msgid
     * @param $msg -可以带上对应的描述
     * @return array
     */
    public function reCall($from, $msgtype, $to, $deleteMsgid, $msg = '')
    {
        $url = 'https://api.netease.im/nimserver/msg/recall.action';
        $data = array(
            'from' => $from,
            'type' => $msgtype,
            'to' => $to,
            'msg' => $msg,
            'deleteMsgid' => $deleteMsgid,
            'timetag' => (time()) * 1000, //要撤回消息的创建时间
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 消息功能-文件上传
     * @param  $content [字节流base64串(Base64.encode(bytes)) ，最大15M的字节流]
     * @param  $type [上传文件类型]
     * @return $result      [返回array数组对象]
     */
    public function uploadMsg($content, $type = '0')
    {
        $url = 'https://api.netease.im/nimserver/msg/upload.action';
        $data = array(
            'content' => $content,
            'type' => $type
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 消息功能-文件上传（multipart方式）
     * @param  $content [字节流base64串(Base64.encode(bytes)) ，最大15M的字节流]
     * @param  $type [上传文件类型]
     * @return $result      [返回array数组对象]
     */
    public function uploadMultiMsg($content, $type = '0')
    {
        $url = 'https://api.netease.im/nimserver/msg/fileUpload.action';
        $data = array(
            'content' => $content,
            'type' => $type
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }


    /**
     * 群组功能（高级群）-创建群
     * @param  $tname [群名称，最大长度64字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @param  $members [["aaa","bbb"](JsonArray对应的accid，如果解析出错会报414)，长度最大1024字节]
     * @param  $announcement [群公告，最大长度1024字节]
     * @param  $intro [群描述，最大长度512字节]
     * @param  $msg [邀请发送的文字，最大长度150字节]
     * @param  $magree [管理后台建群时，0不需要被邀请人同意加入群，1需要被邀请人同意才可以加入群。其它会返回414。]
     * @param  $joinmode [群建好后，sdk操作时，0不用验证，1需要验证,2不允许任何人加入。其它返回414]
     * @param  $custom [自定义高级群扩展属性，第三方可以跟据此属性自定义扩展自己的群属性。（建议为json）,最大长度1024字节.]
     * @param  $icon [群头像，最大长度1024字符]
     * @param  $beinvitemode [被邀请人同意方式，0-需要同意(默认),1-不需要同意。其它返回414]
     * @param  $invitemode [谁可以邀请他人入群，0-管理员(默认),1-所有人。其它返回414]
     * @param  $uptinfomode [谁可以修改群资料，0-管理员(默认),1-所有人。其它返回414]
     * @param  $upcustommode [谁可以更新群自定义属性，0-管理员(默认),1-所有人。其它返回414]
     * @return $result      [返回array数组对象]
     *
     * {
     * "code": 200,
     * "tid": "13808863"
     * }
     */
    public function createGroup($tname, $owner, $members, $icon = '', $msg = '', $invitemode = 1, $joinmode = '0', $announcement = '', $intro = '', $magree = '0', $custom = '0', $beinvitemode = 1)
    {
        $url = 'https://api.netease.im/nimserver/team/create.action';
        $data = array(
            'tname' => $tname,
            'owner' => $owner,
            'members' => json_encode($members),
            'announcement' => $announcement,
            'intro' => $intro,
            'msg' => $msg,
            'magree' => $magree,
            'joinmode' => $joinmode,
            'custom' => $custom,
            'icon' => $icon,
            'beinvitemode' => $beinvitemode,
            'invitemode' => $invitemode
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-拉人入群
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @param  $members [["aaa","bbb"](JsonArray对应的accid，如果解析出错会报414)，长度最大1024字节]
     * @param  $magree [管理后台建群时，0不需要被邀请人同意加入群，1需要被邀请人同意才可以加入群。其它会返回414。]
     * @param  $attach [自定义扩展字段，最大长度512]
     * @return $result      [返回array数组对象]
     */
    public function addIntoGroup($tid, $owner, $members, $magree = '0', $msg = '请您入伙', $attach = '')
    {
        $url = 'https://api.netease.im/nimserver/team/add.action';
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
            'members' => json_encode($members),
            'magree' => $magree,
            'msg' => $msg,
            'attach' => $attach
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-踢人出群
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @param  $member [被移除人得accid，用户账号，最大长度字节]
     * @param  $attach [自定义扩展字段，最大长度512]
     * @return $result      [返回array数组对象]
     */
    public function kickFromGroup($tid, $owner, $member, $attach = '')
    {
        $url = 'https://api.netease.im/nimserver/team/kick.action';
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
            'member' => $member,
            'attach' => $attach,
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-退出群
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $accid [用户账号]
     * @return $result      [返回array数组对象]
     */
    public function leaveGroup($tid, $accid)
    {
        $url = 'https://api.netease.im/nimserver/team/leave.action';
        $data = array(
            'tid' => $tid,
            'accid' => $accid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-解散群
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @return $result      [返回array数组对象]
     */
    public function removeGroup($tid, $owner)
    {
        $url = 'https://api.netease.im/nimserver/team/remove.action';
        $data = array(
            'tid' => $tid,
            'owner' => $owner
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-更新群资料
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @param  $tname [群名称，最大长度32字节]
     * @param  $announcement [群公告，最大长度1024字节]
     * @param  $intro [群描述，最大长度512字节]
     * @param  $icon [群头像，最大长度1024字符]
     * @param  $joinmode [群建好后，sdk操作时，0不用验证，1需要验证,2不允许任何人加入。其它返回414]
     * @param  $custom [自定义高级群扩展属性，第三方可以跟据此属性自定义扩展自己的群属性。（建议为json）,最大长度1024字节.]
     * @param  $beinvitemode [被邀请人同意方式，0-需要同意(默认),1-不需要同意。其它返回414]
     * @param  $invitemode [谁可以邀请他人入群，0-管理员(默认),1-所有人。其它返回414]
     * @param  $uptinfomode [谁可以修改群资料，0-管理员(默认),1-所有人。其它返回414]
     * @param  $upcustommode [谁可以更新群自定义属性，0-管理员(默认),1-所有人。其它返回414]
     * @param  $data_arr [数据集合]
     * @return $result      [返回array数组对象]
     */
    public function updateGroup($tid, $owner, $data_arr)
    {
        $url = 'https://api.netease.im/nimserver/team/update.action';
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
        );
        $data = array_merge($data, $data_arr);
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-群信息与成员列表查询
     * @param  $tids [群tid列表，如[\"3083\",\"3084"]]
     * @param  $ope [1表示带上群成员列表，0表示不带群成员列表，只返回群信息]
     * @return $result      [返回array数组对象]
     */
    public function queryGroup($tids, $ope = '1')
    {
        $url = 'https://api.netease.im/nimserver/team/query.action';
        $data = array(
            'tids' => json_encode($tids),
            'ope' => $ope
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**群详情
     * @param  $tid
     * @return array
     */
    public function queryGroupDetail($tid)
    {
        $url = 'https://api.netease.im/nimserver/team/queryDetail.action';
        $data = array(
            'tid' => $tid,
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-移交群主
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @param  $newowner [新群主帐号，最大长度32字节]
     * @param  $leave [1:群主解除群主后离开群，2：群主解除群主后成为普通成员。其它414]
     * @return $result      [返回array数组对象]
     */
    public function changeGroupOwner($tid, $owner, $newowner, $leave = '2')
    {
        $url = 'https://api.netease.im/nimserver/team/changeOwner.action';
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
            'newowner' => $newowner,
            'leave' => $leave
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-任命管理员
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @param  $members [["aaa","bbb"](JsonArray对应的accid，如果解析出错会报414)，长度最大1024字节（群成员最多10个）]
     * @return $result      [返回array数组对象]
     */
    public function addGroupManager($tid, $owner, $members)
    {
        $url = 'https://api.netease.im/nimserver/team/addManager.action';
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
            'members' => json_encode($members)
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-移除管理员
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @param  $members [["aaa","bbb"](JsonArray对应的accid，如果解析出错会报414)，长度最大1024字节（群成员最多10个）]
     * @return $result      [返回array数组对象]
     */
    public function removeGroupManager($tid, $owner, $members)
    {
        $url = 'https://api.netease.im/nimserver/team/removeManager.action';
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
            'members' => json_encode($members)
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 群组功能（高级群）-获取某用户所加入的群信息
     * @param  $accid [要查询用户的accid]
     * @return $result      [返回array数组对象]
     */
    public function joinTeams($accid)
    {
        $url = 'https://api.netease.im/nimserver/team/joinTeams.action';
        $data = array(
            'accid' => $accid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }


    /**
     * 群组功能（高级群）-修改群昵称
     * @param  $tid [云信服务器产生，群唯一标识，创建群时会返回，最大长度128字节]
     * @param  $owner [群主用户帐号，最大长度32字节]
     * @param  $accid [要修改群昵称对应群成员的accid]
     * @param  $nick [accid对应的群昵称，最大长度32字节。]
     * @return $result      [返回array数组对象]
     */
    public function updateGroupNick($tid, $owner, $accid, $nick)
    {
        $url = 'https://api.netease.im/nimserver/team/updateTeamNick.action';
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
            'accid' => $accid,
            'nick' => $nick
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**禁言群成员
     * @param $tid -群id
     * @param $owner -群主id
     * @param $accid -群成员
     * @param $mute 0-解禁 1-禁言
     * @return array
     */
    public function muteTlist($tid, $owner, $accid, $mute)
    {
        $url = "https://api.netease.im/nimserver/team/muteTlist.action";
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
            'accid' => $accid,
            'mute' => $mute
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**将群组整体禁言
     * @param $tid
     * @param $owner
     * @param $mute 'false'-解禁 'true'-禁言
     * @return array
     */
    public function muteTlistAll($tid, $owner, $mute)
    {
        $url = "https://api.netease.im/nimserver/team/muteTlistAll.action";
        $data = array(
            'tid' => $tid,
            'owner' => $owner,
            'mute' => $mute
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 历史记录-单聊
     * @param  $from [发送者accid]
     * @param  $to [接收者accid]
     * @param  $begintime [开始时间，ms]
     * @param  $endtime [截止时间，ms]
     * @param  $limit [本次查询的消息条数上限(最多100条),小于等于0，或者大于100，会提示参数错误]
     * @param  $reverse [1按时间正序排列，2按时间降序排列。其它返回参数414.默认是按降序排列。]
     * @return $result      [返回array数组对象]
     */
    public function querySessionMsg($from, $to, $begintime, $endtime = '', $limit = '100', $reverse = '1')
    {
        $url = 'https://api.netease.im/nimserver/history/querySessionMsg.action';
        $data = array(
            'from' => $from,
            'to' => $to,
            'begintime' => $begintime,
            'endtime' => $endtime,
            'limit' => $limit,
            'reverse' => $reverse
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 历史记录-群聊
     * @param  $tid [群id]
     * @param  $accid [查询用户对应的accid.]
     * @param  $begintime [开始时间，ms]
     * @param  $endtime [截止时间，ms]
     * @param  $limit [本次查询的消息条数上限(最多100条),小于等于0，或者大于100，会提示参数错误]
     * @param  $reverse [1按时间正序排列，2按时间降序排列。其它返回参数414.默认是按降序排列。]
     * @return $result      [返回array数组对象]
     */
    public function queryGroupMsg($tid, $accid, $begintime, $endtime = '', $limit = '100', $reverse = '1')
    {
        $url = 'https://api.netease.im/nimserver/history/queryTeamMsg.action';
        $data = array(
            'tid' => $tid,
            'accid' => $accid,
            'begintime' => $begintime,
            'endtime' => $endtime,
            'limit' => $limit,
            'reverse' => $reverse
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 发送短信验证码
     * @param  $mobile [目标手机号]
     * @param  $deviceId [目标设备号，可选参数]
     * @return $result      [返回array数组对象]
     */
    public function sendSmsCode($mobile, $deviceId = '')
    {
        $url = 'https://api.netease.im/sms/sendcode.action';
        $data = array(
            'mobile' => $mobile,
            'deviceId' => $deviceId
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 校验验证码
     * @param  $mobile [目标手机号]
     * @param  $code [验证码]
     * @return $result      [返回array数组对象]
     */
    public function verifycode($mobile, $code = '')
    {
        $url = 'https://api.netease.im/sms/verifycode.action';
        $data = array(
            'mobile' => $mobile,
            'code' => $code
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 发送模板短信
     * @param  $templateid [模板编号(由客服配置之后告知开发者)]
     * @param  $mobiles [验证码]
     * @param  $params [短信参数列表，用于依次填充模板，JSONArray格式，如["xxx","yyy"];对于不包含变量的模板，不填此参数表示模板即短信全文内容]
     * @return $result      [返回array数组对象]
     */
    public function sendSMSTemplate($templateid, $mobiles = array(), $params = array())
    {
        $url = 'https://api.netease.im/sms/sendtemplate.action';
        $data = array(
            'templateid' => $templateid,
            'mobiles' => json_encode($mobiles),
            'params' => json_encode($params)
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 查询模板短信发送状态
     * @param  $sendid [发送短信的编号sendid]
     * @return $result      [返回array数组对象]
     */
    public function querySMSStatus($sendid)
    {
        $url = 'https://api.netease.im/sms/querystatus.action';
        $data = array(
            'sendid' => $sendid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 发起单人专线电话
     * @param  $callerAcc [发起本次请求的用户的accid]
     * @param  $caller [主叫方电话号码(不带+86这类国家码,下同)]
     * @param  $callee [被叫方电话号码]
     * @param  $maxDur [本通电话最大可持续时长,单位秒,超过该时长时通话会自动切断]
     * @return $result      [返回array数组对象]
     */
    public function startcall($callerAcc, $caller, $callee, $maxDur = '60')
    {
        $url = 'https://api.netease.im/call/ecp/startcall.action';
        $data = array(
            'callerAcc' => $callerAcc,
            'caller' => $caller,
            'callee' => $callee,
            'maxDur' => $maxDur
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 发起专线会议电话
     * @param  $callerAcc [发起本次请求的用户的accid]
     * @param  $caller [主叫方电话号码(不带+86这类国家码,下同)]
     * @param  $callee [所有被叫方电话号码,必须是json格式的字符串,如["13588888888","13699999999"]]
     * @param  $maxDur [本通电话最大可持续时长,单位秒,超过该时长时通话会自动切断]
     * @return $result      [返回array数组对象]
     */
    public function startconf($callerAcc, $caller, $callee, $maxDur = '60')
    {
        $url = 'https://api.netease.im/call/ecp/startconf.action';
        $data = array(
            'callerAcc' => $callerAcc,
            'caller' => $caller,
            'callee' => json_encode($callee),
            'maxDur' => $maxDur
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 查询单通专线电话或会议的详情
     * @param  $session [本次通话的id号]
     * @param  $type [通话类型,1:专线电话;2:专线会议]
     * @return $result      [返回array数组对象]
     */
    public function queryCallsBySession($session, $type)
    {
        $url = 'https://api.netease.im/call/ecp/queryBySession.action';
        $data = array(
            'session' => $session,
            'type' => $type
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /* 2016-06-15 新增php调用直播接口示例 */

    /**
     * 获取语音视频安全认证签名
     * @param  $uid [用户帐号唯一标识，必须是Long型]
     */
    public function getUserSignature($uid)
    {
        $url = 'https://api.netease.im/nimserver/user/getToken.action';
        $data = array(
            'uid' => $uid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postDataCurl($url, $data);
        } else {
            $result = $this->postDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 创建一个直播频道
     * @param  $name [频道名称, string]
     * @param  $type [频道类型（0:rtmp；1:hls；2:http）]
     */
    public function channelCreate($name, $type)
    {
        $url = 'https://vcloud.163.com/app/channel/create';
        $data = array(
            'name' => $name,
            'type' => $type
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postJsonDataCurl($url, $data);
        } else {
            $result = $this->postJsonDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 修改直播频道信息
     * @param  $name [频道名称, string]
     * @param  $cid [频道ID，32位字符串]
     * @param  $type [频道类型（0:rtmp；1:hls；2:http）]
     */
    public function channelUpdate($name, $cid, $type)
    {
        $url = 'https://vcloud.163.com/app/channel/update';
        $data = array(
            'name' => $name,
            'cid' => $cid,
            'type' => $type
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postJsonDataCurl($url, $data);
        } else {
            $result = $this->postJsonDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 删除一个直播频道
     * @param  $cid [频道ID，32位字符串]
     */
    public function channelDelete($cid)
    {
        $url = 'https://vcloud.163.com/app/channel/delete';
        $data = array(
            'cid' => $cid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postJsonDataCurl($url, $data);
        } else {
            $result = $this->postJsonDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 获取一个直播频道的信息
     * @param  $cid [频道ID，32位字符串]
     */
    public function channelStats($cid)
    {
        $url = 'https://vcloud.163.com/app/channelstats';
        $data = array(
            'cid' => $cid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postJsonDataCurl($url, $data);
        } else {
            $result = $this->postJsonDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 获取用户直播频道列表
     * @param  $records [单页记录数，默认值为10]
     * @param  $pnum = 1       [要取第几页，默认值为1]
     * @param  $ofield [排序的域，支持的排序域为：ctime（默认）]
     * @param  $sort [升序还是降序，1升序，0降序，默认为desc]
     */
    public function channelList($records = 10, $pnum = 1, $ofield = 'ctime', $sort = 0)
    {
        $url = 'https://vcloud.163.com/app/channellist';
        $data = array(
            'records' => $records,
            'pnum' => $pnum,
            'ofield' => $ofield,
            'sort' => $sort
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postJsonDataCurl($url, $data);
        } else {
            $result = $this->postJsonDataFsockopen($url, $data);
        }
        return $result;
    }

    /**
     * 重新获取推流地址
     * @param  $cid [频道ID，32位字符串]
     */
    public function channelRefreshAddr($cid)
    {
        $url = 'https://vcloud.163.com/app/address';
        $data = array(
            'cid' => $cid
        );
        if ($this->RequestType == 'curl') {
            $result = $this->postJsonDataCurl($url, $data);
        } else {
            $result = $this->postJsonDataFsockopen($url, $data);
        }
        return $result;
    }

}

?>