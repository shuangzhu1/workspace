<?php
namespace Util;

use Language\Api\En;
use Language\Api\Zh_cn;
use Language\Api\Zh_hk;
use Models\System\SystemApiCallLog;
use Phalcon\Mvc\User\Plugin;

/**
 * ykuang
 * @time
 *  2016-12-06
 */
class Ajax extends Plugin
{
    const ERROR_USER_IS_NOT_EXISTS = 1001;
    const ERROR_USER_HAS_NOT_LOGIN = 1002;
    const ERROR_USER_HAS_NO_PERMISSION = 1003;
    const ERROR_USER_HAS_BEING_USED = 1004;
    const ERROR_USER_IS_INVALID = 1005;
    const ERROR_USER_BIND_FAILED = 1006;
    const ERROR_PASSWD_IS_INVALID = 1007;
    const ERROR_PASSWD_IS_NOT_CORRECT = 1008;
    const ERROR_EMAIL_IS_INVALID = 1009;
    const ERROR_EMAIL_HAS_BEING_USED = 1010;
    const ERROR_INVALID_REQUEST_PARAM = 1011;
    const ERROR_YOU_HAS_LIKED = 1041;
    const ERROR_YOU_HASNT_LIKE = 1042;
    const ERROR_ILLEGAL_API_SIGNATURE = 1012;
    const ERROR_NOTHING_HAS_CHANGED = 1013;
    const ERROR_RUN_TIME_ERROR_OCCURRED = 1014;
    const ERROR_PHONE_IS_INVALID = 1015;
    const ERROR_PHONE_HAS_BEING_USED = 1016;
    const ERROR_USER_IS_NOT_ACTIVE = 1017;
    const ERROR_TOKEN_INVALID = 1018;
    const ERROR_TOKEN_EXPIRES = 1019;
    const ERROR_USER_NOT_BINDED = 1020;
    const ERROR_REFRESH_TOKEN_INVALID = 1021;
    const ERROR_SIGN_EXPIRES = 1022;
    const ERROR_DATA_NOT_EXISTS = 1023;
    const ERROR_DATA_HAS_EXISTS = 1024;
    const ERROR_POINTS_NOT_ENOUGH = 1025;
    const ERROR_EXP_NOT_ENOUGH = 1026;
    const ERROR_USER_PROFILE_IMCOMPLETE = 1027;
    const ERROR_TOKEN_HAS_REFRESHED = 1028;
    const ERROR_USER_HAS_BEING_LOCKED = 1029;
    const ERROR_TITLE_IS_NOT_EXISTS = 1030;
    const ERROR_TITLE_IS_EXISTS = 1031;
    const ERROR_ACCOUNT_IS_NOT_EXISTS = 1031;
    const ERROR_ACCOUNT_HAS_BEING_USED = 1033;
    const ERROR_PHONE_EXIST = 1034;//该手机已注册
    const ERROR_SIGN = 1035; //签名验证失败
    const ERROR_VERIFY_CODE = 1036; //验证码错误
    const ERROR_SEND_PHONE_CODE_TOO_FREQUENCY = 1037;
    const ERROR_SEND_BYOND_ONE = 1038;
    const ERROR_VERIFY_CODE_OLD = 1039;
    const ERROR_USERNAME_PREG = 1040; //昵称必须为2-8字符之间
    const ERROR_USER_OR_PASSWORD = 1041; //手机或密码错误
    const ERROR_GROUP_MEMBER_LIMIT = 1042; //群聊成员数已达上限
    const ERROR_GROUP_MEMBER_EXIST = 1043; //你已经是群成员
    const ERROR_GROUP_MEMBER_NOT_ADMIN = 1044; //你不是群主
    const ERROR_GROUP_MEMBER_ADMIN = 1045; //群主无法退出,你可以转让权限后退出或者解散群
    const ERROR_GROUP_NOT_MEMBER = 1046; //你还不是群成员
    const ERROR_GROUP_MEMBER_LIMIT_200 = 1047; //一次最多只能添加200个成员
    const ERROR_USER_DELETED = 1048; //账号已被禁用
    const ERROR_NOT_GROUP_MEMBER = 1049; //转让的对象必须是群成员
    const ERROR_HANDLE_NOT_GROUP_MEMBER = 1050; //操作的对象不是群成员
    const ERROR_CANNOT_ATTENTION_SELF = 1051; //自己不能关注自己
    const ERROR_HAS_ATTENTION = 1052;//你已经关注过了
    const ERROR_IN_BLACKLIST = 1053;//对方已经在你的黑名单列表中
    const ERROR_REFUSE_YOU_REQUEST = 1054;//对方拒绝了你的请求
    const ERROR_REPORT_HAS_SENT = 1055;//请勿重复提交举报
    const ERROR_SEND_VERIFY_CODE = 1056;//哇哦,短信发送出了点问题
    const ERROR_NOT_FRIEND = 1057;//你们还不是好友
    const ERROR_ALREADY_CONTACT_MEMBER = 1058;//你们已经是联系人了
    const ERROR_COIN_NOT_ENOUGH = 1059;//龙豆不足
    const ERROR_HAS_NO_PRIVILEGE_DISCUSS = 1060;//主人设置了访问权限
    const ERROR_REQUEST_FREQUENCY = 1061;//请求太过频繁
    const ERROR_SUBMIT_REPEAT = 1062;//请勿重复提交申请
    const ERROR_LOGIN_DEVICE = 1063;//您已开启设备登录保护,需要进行手机验证
    const ERROR_SYSTEM_BLACKLIST = 1064;//该用户为系统黑名单用户
    const ERROR_INVITOR_NOT_GROUP_MEMBER = 1065;//邀请者不是群成员
    const ERROR_LOW_APP_VERSION = 1066;//版本过低
    const ERROR_USER_HAS_BEING_LOCKED_MANUAL = 1067;//需要人工解除锁定
    const ERROR_USER_HAS_NO_OPEN_AVATAR = 1068;//无第三方头像
    const ERROR_MEMBER_PRIVILEGE_NOT_ENOUGH = 1069;//目标成员权限不足【群成员限制/其他】
    const ERROR_GIFT_OFF = 1070;//礼物已下架
    const ERROR_GIFT_BEYOND_LIMIT = 1071;//礼物数已超出限制
    const ERROR_TARGET_CAN_NOT_YOURSELF = 1072;//目标用户不能是自己
    const ERROR_EMPTY_BIND_PHONE = 1073;//先绑定手机号
    const ERROR_EMPTY_BIRTHDAY = 1074;//请先完善生日信息
    const ERROR_SHOW_DISABLE = 1075;//对方已关闭秀场
    const ERROR_SHOW_HAS_LIKE = 1076;//一已经点过赞了
    const ERROR_SHOW_HAS_DISLIKE = 1077;//一已经踩过了
    const ERROR_DELETED_BY_SYSTEM = 1078;//已被系统屏蔽
    const ERROR_DELETED_BY_USER = 1079;//已被用户删除
    const ERROR_DELETED_NOT_EXPIRE = 1080;//视频问答视频14天内不能删除
    const ERROR_SHOP_HAS_EXISTS = 1081;//请勿重复添加店铺
    const ERROR_IP_ABNORMAL = 1082;//请求异常
    const ERROR_MEMBER_NOT_JOIN = 1083;//禁止加入群聊
    const ERROR_SHOP_HAS_BEEN_SHIELD = 1084;//店铺已被屏蔽
    const ERROR_SHOP_HAS_BEEN_DELETED = 1085;//店铺已被店主关闭
    const ERROR_MEMBER_IS_ADMIN = 1086;//对方已经是群主
    const ERROR_PACKAGE_BEYOND_LIMIT = 1087;//今日红包领取次数已达上限
    const ERROR_NO_AUTH = 1088;//还没有实名认证
    const ERROR_OAUTH2_FAIL_GET_VERIFY_TOKEN = 1089;//第三方授权时，获取登录票据失败
    const ERROR_INVITER_HAS_INVITER = 1090;//你已经设置过推荐人了
    const ERROR_INVITER_IS_LOWER = 1091;//推荐人不能为下级用户
    const ERROR_ID_CARD_HAS_BEEN_USED = 1092;//该身份证已被使用
    const ERROR_DRAGON_COIN_NOT_ENOUGH = 1093;//龙币不足
    const ERROR_MONEY_NOT_ENOUGH = 1094;//余额不足
    const ERROR_NOT_SHOP_OWNER = 1095;//不是店主
    const ERROR_NICK_HAS_BEEN_USED = 1096;//该用户昵称已被使用
    const ERROR_NICK_UPDATE_ONLY_ONCE = 1097;//用户昵称一年只能修改一次
    const ERROR_OWN_ONE_COMMUNITY = 1098;//每个用户只能创建一个社区
    const ERROR_COMMUNITY_NAME_UNIQUE = 1099;//相同名字的社区已经存在
    const ERROR_COMMUNITY_NOT_MEMBER = 1100;//对方还不是社区成员
    const ERROR_COMMUNITY_IS_MANAGER = 1101;//对方已经是社区管理员
    const ERROR_COMMUNITY_MANAGER_HAS_GROUP_NO_TRANSFER = 1102;//该管理员还有群未转让
    const ERROR_COMMUNITY_GROUP_LIMIT = 1103;//你的社区社群个数已达上限
    const ERROR_COMMUNITY_GROUP_NAME_HAS_EXISTS = 1104;//相同名字的社群已经存在
    const ERROR_COMMUNITY_GROUP_MEMBER_UNSUBSCRIBE = 1105;//社群成员不能取消关注
    const ERROR_COMMUNITY_OWNER_ADMIN_UNSUBSCRIBE = 1106;//区主或社区管理员不能取消关注
    const ERROR_COMMUNITY_PRIVATE_GROUP_NOT_JOIN = 1107;//非公开群禁止主动加入
    const ERROR_COMMUNITY_DISCUSS_NEED_ADMIN = 1108;//仅管理员能发布
    const ERROR_COMMUNITY_DISCUSS_NEED_OWNER = 1109;//仅区主能操作
    const ERROR_COMMUNITY_CHECKING = 1110;//申请正在审核中，请勿重复提交
    const ERROR_COMMUNITY_APPLY_LIMIT = 1111;//今日提交次数已达上限
    const ERROR_COMMUNITY_APPLY_HAS_BEEN_HANDLE = 1112;//申请已被处理


    #2 文件上传相关
    const UPLOAD_ERR_TMP_NAME_NOT_EXIST = 2011;
    const UPLOAD_ERR_FILE_FIELD_NOT_RECEIVED = 2012;
    const UPLOAD_ERR_FILE_EXT_ONLY_ALLOWED = 2013;
    const UPLOAD_ERR_UPLOAD_FILE_IS_TOO_LARGE = 2014;
    const UPLOAD_ERR_BATCH_IS_NOT_ALLOWED = 2015;
    const UPLOAD_ERR_ONLY_SUPPORT_BATCH_UPLOAD = 2016;
    const UPLOAD_ERR_FASTDFS_SAVE_ERROR_OCCURRED = 2017;
    const UPLOAD_ERR_MASTER_FILE_NOT_EXIST = 2018;

    # custom error msg
    const CUSTOM_ERROR_MSG = 3001;
    # 无效的操作

    const INVALID_SIGN = 3002; //无效的签名
    const INVALID_PARAM = 3003;//无效的参数
    const INVALID_PHONE = 3004;//无效的手机号
    const INVALID_LOGIN_TYPE = 3005;//无效的登录类型
    const INVALID_MSG_TYPE = 3006;//无效的短信类型
    const INVALID_ID_CARD_TYPE = 3007;//无效的身份证号
    const INVALID_WEBSITE = 3008;//无效的网址
    const INVALID_REQUEST = 3009; //无效的请求
    const INVALID_TIMESTAMP = 3010; //手机时间异常，请同步网络时间
    const INVALID_INVITE_CODE = 3011; //无效的邀请码


    #4 失败

    const FAIL_REGISTER = 4001;
    const FAIL_LOGIN = 4002;
    const FAIL_ADD_GROUP = 4003;
    const FAIL_JOIN_GROUP = 4004;
    const FAIL_DISSOLVE_GROUP = 4005;
    const FAIL_LEAVE_GROUP = 4006;
    const FAIL_INVITE_GROUP = 4007;
    const FAIL_TRANSFER_GROUP = 4008;
    const FAIL_KICK_MEMBER = 4009;
    const FAIL_EDIT = 4010;
    const FAIL_HANDLE = 4011;
    const FAIL_LOCATION = 4012;//位置上报失败
    const FAIL_SEND = 4013;//请求发送失败
    const FAIL_PUBLISH = 4014;//发布失败
    const FAIL_DELETE = 4015;//删除失败
    const FAIL_FORWARD = 4016;//转发失败
    const FAIL_SHARE = 4017;//分享失败
    const FAIL_SUBMIT = 4018;//提交失败
    const FAIL_ADD = 4019;//添加失败
    const FAIL_TOP = 4020;//置顶失败
    const FAIL_CANCEL = 4021;//取消失败
    const FAIL_CHARGE = 4022;//充值失败
    const FAIL_QUESTION = 4023;//提问失败
    const FAIL_PAY = 4024;//支付失败
    const FAIL_PICK = 4025;//领取失败
    const FAIL_PICK_HAS_REACHED_LIMIT = 4026;//已达领取上限
    const FAIL_GET_SHOP_CATEGORY = 4027;//获取店铺分类失败


    #5 成功
    const SUCCESS_REGISTER = 5001;//注册成功
    const SUCCESS_JOIN = 5002;//加入成功
    const SUCCESS_DISSOLVE = 5003;//解散成功
    const SUCCESS_LEAVE = 5004;//退出成功
    const SUCCESS_INVITE = 5005;//邀请成功
    const SUCCESS_TRANSFER = 5006;//转让成功
    const SUCCESS_DELETE = 5007;//删除成功
    const SUCCESS_EDIT = 5008;//编辑成功
    const SUCCESS_ATTENTION = 5009;//关注成功
    const SUCCESS_CANCEL = 5010;// 取消成功
    const SUCCESS_REPORT = 5011;// 举报成功
    const SUCCESS_HANDLE = 5012;// 操作成功
    const SUCCESS_SEND = 5013;// 发送成功
    const SUCCESS_PUBLISH = 5014;// 发布成功
    const SUCCESS_FORWARD = 5015;// 转发成功
    const SUCCESS_SHARE = 5016;//分享成功
    const SUCCESS_SUBMIT = 5017;//提交成功
    const SUCCESS_REMOVE = 5018;//移除成功
    const SUCCESS_ADD = 5019;//添加成功
    const SUCCESS_TOP = 5020;//置顶成功
    const SUCCESS_QUESTION = 5021;//提问成功

    #custom

    const GROUP_CREATE = "group_create";//您当前等级只支持创建${1}人群聊;
    const GROUP_INVITE = "group_invite";//最多还能添加${1}成员;


    public static $instance = null;

    public $lang = '1';//1-中文简体，2-中文繁体，3-英文

    public static $api_request = null;
    public static $api_redis = null;

    public static function init()
    {
        return new self();
    }

    public function __construct()
    {
        self::$api_request = $this->request;
        self::$api_redis = $this->di->get("redis_queue");

    }

    public static function errorResult()
    {

    }

    public static function outRight($data = '', $code = '')
    {
        self::setHead();
        if ($code) {
            $result = array(
                'result' => 1,
                'data' => self::getSuccessMsg($code),
            );
        } else {
            $result = array(
                'result' => 1,
                'data' => $data
            );
        }

        //日志
        //self::recordLog(1, '');
        if (isset($_REQUEST['callback']) && $_REQUEST['callback']) {
            echo $_REQUEST["callback"] . '(' . json_encode($result, JSON_UNESCAPED_UNICODE) . ')'; // php 5.4
        } else {
            echo json_encode($result, JSON_UNESCAPED_UNICODE); // php 5.4
        }
        exit;

    }

    public static function outError($code, $msg = '')
    {
        self::setHead();
        $result = array(
            'error' => array('code' => $code, 'msg' => self::getErrorMsg($code), 'more' => $msg),
            'result' => 0,
        );
        if (!$result['error']['msg']) {
            $result['error']['msg'] = $result['error']['more'];
        }
        //日志
        self::recordLog(0, $result['error']['msg'], $code);

        if (isset($_REQUEST['callback']) && $_REQUEST['callback']) {
            echo $_REQUEST["callback"] . '(' . json_encode($result, JSON_UNESCAPED_UNICODE) . ')';
        } else {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * 设置ajax跨域head
     */
    public static function setHead()
    {
        header("content-type: text/javascript; charset=utf-8");
        header("Access-Control-Allow-Origin: *"); # 跨域处理
        header("Access-Control-Allow-Headers: content-disposition, origin, content-type, accept");
        header("Access-Control-Allow-Credentials: true");

        // Make sure file is not cached (as it happens for example on iOS devices)
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    public static function recordLog($status, $msg, $code = '')
    {
        //机器人请求过滤
//        if ($_REQUEST['is_r']) {
//            return;
//        }
        //过滤部分错误
        if (in_array($code, [self::ERROR_HAS_ATTENTION])) {
            return;
        }
        if (defined("IS_APP")) {
            $time = ceil((microtime(true) - START_TIME) * 1000);
            $now = time();
            $uri = new Uri();
            if (!self::$api_request->isPost()) {
                $data = [
                    'user_id' => isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0,
                    'api' => $uri->actionUrl(),
                    'params' => json_encode(self::$api_request->getQuery(), JSON_UNESCAPED_UNICODE),
                    'full_url' => $uri->fullUrl(),
                    'ymd' => date('Ymd', $now),
                    'h' => date('H', $now),
                    'created' => $now,
                    'time' => $time,
                    'app_version' => isset($_REQUEST['app_version']) ? $_REQUEST['app_version'] : '',
                    'client_type' => isset($_REQUEST['client_type']) ? $_REQUEST['client_type'] : '',
                    'status' => $status,
                    'msg' => $msg,
                    'ip' => self::$api_request->getClientAddress(),
                    'code' => $code
                ];
            } else {
                $query = self::$api_request->getPost();
                $query_str = "";
                if ($query) {
                    foreach ($query as $k => $item) {
                        $query_str .= "&$k=$item";
                    }
                    $query_str = substr($query_str, 1);
                }
                $data = [
                    'user_id' => isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0,
                    'api' => $uri->actionUrl(),
                    'params' => json_encode(array_merge(self::$api_request->getQuery(), $query), JSON_UNESCAPED_UNICODE),
                    'full_url' => $uri->fullUrl() . ($query_str ? "?" . $query_str : ''),
                    'ymd' => date('Ymd', $now),
                    'h' => date('H', $now),
                    'created' => $now,
                    'time' => $time,
                    'app_version' => isset($_REQUEST['app_version']) ? $_REQUEST['app_version'] : '',
                    'client_type' => isset($_REQUEST['client_type']) ? $_REQUEST['client_type'] : '',
                    'status' => $status,
                    'msg' => $msg,
                    'ip' => self::$api_request->getClientAddress(),
                    'code' => $code
                ];
            }
            self::$api_redis->rPush("api_call_log", json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * get error msg by defined code
     * @param $code
     * @return string
     */
    public static function getErrorMsg($code)
    {
        switch (LANG) {
            case 1:  //中文简体
                $msg = Zh_cn::getErrorMsg($code);
                break;
            case 2://中文繁体
                $msg = Zh_hk::getErrorMsg($code);
                break;
            case 3: //英文
                $msg = En::getErrorMsg($code);
                break;
            default:
                $msg = Zh_cn::getErrorMsg($code);
        }
        return $msg;
    }

    /**
     * get success msg by defined code
     * @param $code
     * @return string
     */
    public static function getSuccessMsg($code)
    {
        //   Debug::log("lang:" . LANG);
        switch (LANG) {
            case 1:  //中文简体
                $msg = Zh_cn::getSuccessMsg($code);
                break;
            case 2://中文繁体
                $msg = Zh_hk::getSuccessMsg($code);
                break;
            case 3: //英文
                $msg = En::getSuccessMsg($code);
                break;
            default:
                $msg = Zh_cn::getSuccessMsg($code);
        }
        return $msg;
    }

    /**
     * get success msg by defined code
     * like getCustomMsg(50001,4,4)
     * @return string
     */
    public static function getCustomMsg()
    {
        switch (LANG) {
            case 1:  //中文简体
                $msg = Zh_cn::getCustomMsg(func_get_args());
                break;
            case 2://中文繁体
                $msg = Zh_hk::getCustomMsg(func_get_args());
                break;
            case 3: //英文
                $msg = En::getCustomMsg(func_get_args());
                break;
            default:
                $msg = Zh_cn::getCustomMsg(func_get_args());
        }
        return $msg;
    }

    /*编译模板消息*/
    public static function compileTemplate(&$msg, $args)
    {
        array_shift($args);
        foreach ($args as $k => $item) {
            $msg = str_replace('${' . ($k + 1) . '}', $item, $msg);
        }
    }


    // 来自微博
    public static function isWeibo()
    {
        $userAgent = strtolower($_SERVER["HTTP_USER_AGENT"]);
        if (strpos($userAgent, 'weibo') !== false) {
            return true;
        }
        return false;
    }

    // 是否来自微信
    public static function isWechat()
    {
        $userAgent = strtolower($_SERVER["HTTP_USER_AGENT"]);
        if (strpos($userAgent, 'micromessenger') !== false) {
            return true;
        }
        return false;
    }

    public static function isMobile()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/AppleWebKit.*Mobile.*/', $user_agent)) {
            return true;
        }

        $mobile_agents = Array("240x320", "acer", "acoon", "acs-", "abacho", "ahong", "airness", "alcatel", "amoi", "android", "anywhereyougo.com", "applewebkit/525", "applewebkit/532", "asus", "audio", "au-mic", "avantogo", "becker", "benq", "bilbo", "bird", "blackberry", "blazer", "bleu", "cdm-", "compal", "coolpad", "danger", "dbtel", "dopod", "elaine", "eric", "etouch", "fly ", "fly_", "fly-", "go.web", "goodaccess", "gradiente", "grundig", "haier", "hedy", "hitachi", "htc", "huawei", "hutchison", "inno", "ipad", "ipaq", "ipod", "jbrowser", "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2", "lg3", "lg4", "lg5", "lg7", "lg8", "lg9", "lg-", "lge-", "lge9", "longcos", "maemo", "mercator", "meridian", "micromax", "midp", "mini", "mitsu", "mmm", "mmp", "mobi", "mot-", "moto", "nec-", "netfront", "newgen", "nexian", "nf-browser", "nintendo", "nitro", "nokia", "nook", "novarra", "obigo", "palm", "panasonic", "pantech", "philips", "phone", "pg-", "playstation", "pocket", "pt-", "qc-", "qtek", "rover", "sagem", "sama", "samu", "sanyo", "samsung", "sch-", "scooter", "sec-", "sendo", "sgh-", "sharp", "siemens", "sie-", "softbank", "sony", "spice", "sprint", "spv", "symbian", "tablet", "talkabout", "tcl-", "teleca", "telit", "tianyu", "tim-", "toshiba", "tsm", "up.browser", "utec", "utstar", "verykool", "virgin", "vk-", "voda", "voxtel", "vx", "wap", "wellco", "wig browser", "wii", "windows ce", "wireless", "xda", "xde", "zte");
        $is_mobile = false;
        foreach ($mobile_agents as $device) {
            if (stristr($user_agent, $device)) {
                $is_mobile = true;
                break;
            }
        }
        return $is_mobile;
    }

}
