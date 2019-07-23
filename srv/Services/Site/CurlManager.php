<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/2
 * Time: 14:00
 */

namespace Services\Site;

class CurlManager
{
    /*curl 错误 CODE*/
    const CURLE_UNSUPPORTED_PROTOCOL = 1;
    const CURLE_FAILED_INIT = 2;
    const CURLE_URL_MALFORMAT = 3;
    const CURLE_NOT_BUILT_IN = 4;
    const CURLE_COULDNT_RESOLVE_PROXY = 5;
    const CURLE_COULDNT_RESOLVE_HOST = 6;
    const CURLE_COULDNT_CONNECT = 7;
    const CURLE_FTP_WEIRD_SERVER_REPLY = 8;
    const CURLE_REMOTE_ACCESS_DENIED = 9;
    const CURLE_FTP_ACCEPT_FAILED = 11;
    const CURLE_FTP_WEIRD_PASS_REPLY = 11;
    const CURLE_FTP_ACCEPT_TIMEOUT = 12;
    const CURLE_FTP_WEIRD_PASV_REPLY = 13;
    const CURLE_FTP_WEIRD_227_FORMAT = 14;
    const CURLE_FTP_CANT_GET_HOST = 15;
    const CURLE_HTTP2 = 16;
    const CURLE_FTP_COULDNT_SET_TYPE = 17;
    const CURLE_PARTIAL_FILE = 18;
    const CURLE_FTP_COULDNT_RETR_FILE = 19;
    const CURLE_QUOTE_ERROR = 21;
    const CURLE_HTTP_RETURNED_ERROR = 22;
    const CURLE_WRITE_ERROR = 23;
    const CURLE_UPLOAD_FAILED = 25;
    const CURLE_READ_ERROR = 26;
    const CURLE_OUT_OF_MEMORY = 27;
    const CURLE_OPERATION_TIMEDOUT = 28;
    const CURLE_FTP_PORT_FAILED = 30;
    const CURLE_FTP_COULDNT_USE_REST = 31;
    const CURLE_RANGE_ERROR = 33;
    const CURLE_HTTP_POST_ERROR = 34;
    const CURLE_SSL_CONNECT_ERROR = 35;
    const CURLE_BAD_DOWNLOAD_RESUME = 36;
    const CURLE_FILE_COULDNT_READ_FILE = 37;
    const CURLE_LDAP_CANNOT_BIND = 38;
    const CURLE_LDAP_SEARCH_FAILED = 39;
    const CURLE_FUNCTION_NOT_FOUND = 41;
    const CURLE_ABORTED_BY_CALLBACK = 42;
    const CURLE_BAD_FUNCTION_ARGUMENT = 43;
    const CURLE_INTERFACE_FAILED = 45;
    const CURLE_TOO_MANY_REDIRECTS = 47;
    const CURLE_UNKNOWN_OPTION = 48;
    const CURLE_TELNET_OPTION_SYNTAX = 49;
    const CURLE_PEER_FAILED_VERIFICATION = 51;
    const CURLE_GOT_NOTHING = 52;
    const CURLE_SSL_ENGINE_NOTFOUND = 53;
    const CURLE_SSL_ENGINE_SETFAILED = 54;
    const CURLE_SEND_ERROR = 55;
    const CURLE_RECV_ERROR = 56;
    const CURLE_SSL_CERTPROBLEM = 58;
    const CURLE_SSL_CIPHER = 59;
    const CURLE_SSL_CACERT = 60;
    const CURLE_BAD_CONTENT_ENCODING = 61;
    const CURLE_LDAP_INVALID_URL = 62;
    const CURLE_FILESIZE_EXCEEDED = 63;
    const CURLE_USE_SSL_FAILED = 64;
    const CURLE_SEND_FAIL_REWIND = 65;
    const CURLE_SSL_ENGINE_INITFAILED = 66;
    const CURLE_LOGIN_DENIED = 67;
    const CURLE_TFTP_NOTFOUND = 68;
    const CURLE_TFTP_PERM = 69;
    const CURLE_REMOTE_DISK_FULL = 70;
    const CURLE_TFTP_ILLEGAL = 71;
    const CURLE_TFTP_UNKNOWNID = 72;
    const CURLE_REMOTE_FILE_EXISTS = 73;
    const CURLE_TFTP_NOSUCHUSER = 74;
    const CURLE_CONV_FAILED = 75;
    const CURLE_CONV_REQD = 76;
    const CURLE_SSL_CACERT_BADFILE = 77;
    const CURLE_REMOTE_FILE_NOT_FOUND = 78;
    const CURLE_SSH = 79;
    const CURLE_SSL_SHUTDOWN_FAILED = 80;
    const CURLE_AGAIN = 81;
    const CURLE_SSL_CRL_BADFILE = 82;
    const CURLE_SSL_ISSUER_ERROR = 83;
    const CURLE_FTP_PRET_FAILED = 84;
    const CURLE_RTSP_CSEQ_ERROR = 85;
    const CURLE_RTSP_SESSION_ERROR = 86;
    const CURLE_FTP_BAD_FILE_LIST = 87;
    const CURLE_CHUNK_FAILED = 88;
    const CURLE_NO_CONNECTION_AVAILABLE = 89;
    const CURLE_SSL_PINNEDPUBKEYNOTMATCH = 90;
    const CURLE_SSL_INVALIDCERTSTATUS = 91;
    const CURLE_HTTP2_STREAM = 92;

    /*http 错误 CODE*/
    const HTTP_CONTINUE = 100;
    const HTTP_WITCHING_PROTOCOLS = 101;
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIME_OUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUIRED_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LARGE = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_INTERNAL_SWEVER_EEEOR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIME_OUT = 504;
    const HTTP_VERSION_NOR_SUPPORTED = 505;

    public static $errmsg_arr = [
        self::CURLE_UNSUPPORTED_PROTOCOL => '错误的协议',//1
        self:: CURLE_FAILED_INIT => '初始化代码失败',//2
        self:: CURLE_URL_MALFORMAT => 'URL格式不正确',//3
        self::CURLE_NOT_BUILT_IN => '请求协议错误',//4
        self::CURLE_COULDNT_RESOLVE_PROXY => '无法解析代理',//5
        self::CURLE_COULDNT_RESOLVE_HOST => '无法解析主机地址',//6
        self:: CURLE_COULDNT_CONNECT => '无法连接到主机',//7
        self::CURLE_FTP_WEIRD_SERVER_REPLY => '远程服务器不可用',//8
        self:: CURLE_REMOTE_ACCESS_DENIED => '访问资源错误',//9
        self::CURLE_FTP_ACCEPT_FAILED => '在等待服务器的连接时,一个主动FTP会话使用,被送到控制连接或类似的错误代码',//10
        self::CURLE_FTP_WEIRD_PASS_REPLY => 'FTP密码错误',//11
        self::CURLE_FTP_ACCEPT_TIMEOUT => 'FTP接收数据超时',//12
        self::CURLE_FTP_WEIRD_PASV_REPLY => '结果错误',//13
        self::CURLE_FTP_WEIRD_227_FORMAT => 'FTP回应PASV命令',//14
        self::CURLE_FTP_CANT_GET_HOST => '内部故障',//15
        self::CURLE_HTTP2 => '',//16
        self::CURLE_FTP_COULDNT_SET_TYPE => '设置传输模式为二进制',//17
        self::CURLE_PARTIAL_FILE => '文件传输短或大于预期',//18
        self:: CURLE_FTP_COULDNT_RETR_FILE => 'RETR命令传输完成',//19
        self:: CURLE_QUOTE_ERROR => '命令成功完成',//21
        self:: CURLE_HTTP_RETURNED_ERROR => '返回正常',//22
        self::CURLE_WRITE_ERROR => '数据写入失败',//23
        self::CURLE_UPLOAD_FAILED => '无法启动上传',//25
        self::CURLE_READ_ERROR => '回调错误',//26
        self::CURLE_OUT_OF_MEMORY => '内存分配请求失败',//27
        self::CURLE_OPERATION_TIMEDOUT => '访问超时',//28
        self::CURLE_FTP_PORT_FAILED => 'FTP端口错误',//30
        self:: CURLE_FTP_COULDNT_USE_REST => 'FTP错误',//31
        self::CURLE_RANGE_ERROR => '不支持请求',//33
        self::CURLE_HTTP_POST_ERROR => '内部发生错误',//34
        self:: CURLE_SSL_CONNECT_ERROR => 'SSL/TLS握手失败',//35
        self::CURLE_BAD_DOWNLOAD_RESUME => '下载无法恢复',//36
        self::CURLE_FILE_COULDNT_READ_FILE => '文件权限错误',//37
        self::CURLE_LDAP_CANNOT_BIND => 'LDAP可没有约束力',//38
        self:: CURLE_LDAP_SEARCH_FAILED => 'LDAP搜索失败',//39
        self::CURLE_FUNCTION_NOT_FOUND => '函数没有找到',//41
        self::CURLE_ABORTED_BY_CALLBACK => '中止的回调',//42
        self::CURLE_BAD_FUNCTION_ARGUMENT => '内部错误',//43
        self:: CURLE_INTERFACE_FAILED => '接口错误',//45
        self::CURLE_TOO_MANY_REDIRECTS => '过多的重定向',//47
        self::CURLE_UNKNOWN_OPTION => '无法识别选项',//48
        self:: CURLE_TELNET_OPTION_SYNTAX => 'TELNET格式错误',//49
        self:: CURLE_PEER_FAILED_VERIFICATION => '远程服务器的SSL证书',//51
        self:: CURLE_GOT_NOTHING => '服务器无返回内容',//52
        self:: CURLE_SSL_ENGINE_NOTFOUND => '加密引擎未找到',//53
        self::CURLE_SSL_ENGINE_SETFAILED => '设定默认SSL加密失败',//54
        self:: CURLE_SEND_ERROR => '无法发送网络数据',//55
        self:: CURLE_RECV_ERROR => '衰竭接收网络数据',//56
        self::CURLE_SSL_CERTPROBLEM => '本地客户端证书',//58
        self::CURLE_SSL_CIPHER => '无法使用密码',//59
        self::CURLE_SSL_CACERT => '凭证无法验证',//60
        self:: CURLE_BAD_CONTENT_ENCODING => '无法识别的传输编码',//61
        self:: CURLE_LDAP_INVALID_URL => '无效的LDAP URL',//62
        self::CURLE_FILESIZE_EXCEEDED => '文件超过最大大小',//63
        self::CURLE_USE_SSL_FAILED => 'FTP失败',//64
        self:: CURLE_SEND_FAIL_REWIND => '倒带操作失败',//65
        self:: CURLE_SSL_ENGINE_INITFAILED => 'SSL引擎失败',//66
        self::CURLE_LOGIN_DENIED => '服务器拒绝登录',//67
        self:: CURLE_TFTP_NOTFOUND => '未找到文件',//68
        self::CURLE_TFTP_PERM => '无权限',//69
        self:: CURLE_REMOTE_DISK_FULL => '超出服务器磁盘空间',//70
        self::CURLE_TFTP_ILLEGAL => '非法TFTP操作',//71
        self::CURLE_TFTP_UNKNOWNID => '未知TFTP传输的ID',//72
        self::CURLE_REMOTE_FILE_EXISTS => '文件已经存在',//73
        self:: CURLE_TFTP_NOSUCHUSER => '错误TFTP服务器',//74
        self::CURLE_CONV_FAILED => '字符转换失败',//75
        self:: CURLE_CONV_REQD => '必须记录回调',//76
        self:: CURLE_SSL_CACERT_BADFILE => 'CA证书权限',//77
        self:: CURLE_REMOTE_FILE_NOT_FOUND => 'URL中引用资源不存在',//78
        self:: CURLE_SSH => '错误发生在SSH会话',//79
        self::CURLE_SSL_SHUTDOWN_FAILED => '无法关闭SSL连接',//80
        self::CURLE_AGAIN => '服务未准备',//81
        self:: CURLE_SSL_ISSUER_ERROR => '发行人检查失败',//83
        self:: CURLE_FTP_PRET_FAILED => 'FTP服务器不理解的PRET命令',//84
        self:: CURLE_RTSP_CSEQ_ERROR => 'RTSP的Cseq号码不匹配',//85
        self:: CURLE_RTSP_SESSION_ERROR => 'RTSP会话标识符不匹配',//86
        self::CURLE_FTP_BAD_FILE_LIST => '无法，解析FTP文件列表（在FTP通配符下载）',//87
        self::CURLE_CHUNK_FAILED => '块回调报告错误',//88
        self::CURLE_NO_CONNECTION_AVAILABLE => '连接不可用',//89
        /*   self:: CURLE_SSL_PINNEDPUBKEYNOTMATCH => 90,//90
           self::CURLE_SSL_INVALIDCERTSTATUS => 91,//91
           self:: CURLE_HTTP2_STREAM => 92,//92*/
    ];
    public static $http_err = [
        self:: HTTP_CONTINUE => '(继续)请求者应当继续提出请求。服务器返回此代码表示已收到请求的第一部分，正在等待其余部分',//100
        self:: HTTP_WITCHING_PROTOCOLS => '(切换协议)请求者已要求服务器切换协议，服务器已确认并准备切换',//101
        self:: HTTP_OK => '(成功)服务器已成功处理了请求。通常，这表示服务器提供了请求的网页',//200
        self:: HTTP_CREATED => '(已创建)请求成功并且服务器创建了新的资源',//201
        self:: HTTP_ACCEPTED => '(已接受)服务器已接受请求，但尚未处理',//202
        self:: HTTP_NON_AUTHORITATIVE_INFORMATION => '(非授权信息)服务器已成功处理了请求，但返回的信息可能来自另一来源',//203
        self:: HTTP_NO_CONTENT => '(无内容)服务器成功处理了请求，但没有返回任何内容',//204
        self:: HTTP_RESET_CONTENT => '(重置内容)服务器成功处理了请求，但没有返回任何内容',//205
        self:: HTTP_PARTIAL_CONTENT => '(部分内容)服务器成功处理了部分 GET 请求',//206
        self:: HTTP_MULTIPLE_CHOICES => '(多种选择)针对请求，服务器可执行多种操作。服务器可根据请求者 (user agent) 选择一项操作，或提供操作列表供请求者选择',//300
        self:: HTTP_MOVED_PERMANENTLY => '(永久移动)请求的网页已永久移动到新位置。服务器返回此响应（对 GET 或 HEAD 请求的响应）时，会自动将请求者转到新位置',//301
        self:: HTTP_FOUND => '(临时移动)服务器目前从不同位置的网页响应请求，但请求者应继续使用原有位置来进行以后的请求',//302
        self:: HTTP_SEE_OTHER => '(查看其他位置)请求者应当对不同的位置使用单独的 GET 请求来检索响应时，服务器返回此代码',//303
        self:: HTTP_NOT_MODIFIED => '(未修改) 自从上次请求后，请求的网页未修改过。服务器返回此响应时，不会返回网页内容',//304
        self:: HTTP_USE_PROXY => '(使用代理)请求者只能使用代理访问请求的网页。如果服务器返回此响应，还表示请求者应使用代理',//305
        self:: HTTP_TEMPORARY_REDIRECT => '(临时重定向)服务器目前从不同位置的网页响应请求，但请求者应继续使用原有位置来进行以后的请求',//307
        self:: HTTP_BAD_REQUEST => '(错误请求)服务器不理解请求的语法',//400
        self:: HTTP_UNAUTHORIZED => '(未授权)请求要求身份验证。 对于需要登录的网页，服务器可能返回此响应',//401
        self:: HTTP_PAYMENT_REQUIRED => '',//402
        self:: HTTP_FORBIDDEN => '(禁止)服务器拒绝请求',//403
        self:: HTTP_NOT_FOUND => '(未找到)服务器找不到请求的网页',//404
        self:: HTTP_METHOD_NOT_ALLOWED => '(方法禁用) 禁用请求中指定的方法',//405
        self:: HTTP_NOT_ACCEPTABLE => '(不接受)无法使用请求的内容特性响应请求的网页',//406
        self:: HTTP_PROXY_AUTHENTICATION_REQUIRED => '(需要代理授权)此状态代码与 401（未授权）类似，但指定请求者应当授权使用代理',//407
        self:: HTTP_REQUEST_TIME_OUT => '(请求超时)服务器等候请求时发生超时',//408
        self:: HTTP_CONFLICT => '(冲突)服务器在完成请求时发生冲突。服务器必须在响应中包含有关冲突的信息',//409
        self:: HTTP_GONE => '(已删除)如果请求的资源已永久删除，服务器就会返回此响应',//410
        self:: HTTP_LENGTH_REQUIRED => '(需要有效长度)服务器不接受不含有效内容长度标头字段的请求',//411
        self:: HTTP_PRECONDITION_FAILED => '(未满足前提条件)服务器未满足请求者在请求中设置的其中一个前提条件',//412
        self:: HTTP_REQUIRED_ENTITY_TOO_LARGE => '(请求实体过大)服务器无法处理请求，因为请求实体过大，超出服务器的处理能力',//413
        self:: HTTP_REQUEST_URI_TOO_LARGE => '(请求的 URI 过长)请求的 URI（通常为网址）过长，服务器无法处理',//414
        self:: HTTP_UNSUPPORTED_MEDIA_TYPE => '(不支持的媒体类型)请求的格式不受请求页面的支持',//415
        self:: HTTP_REQUESTED_RANGE_NOT_SATISFIABLE => '(请求范围不符合要求)如果页面无法提供请求的范围，则服务器会返回此状态代码',//416
        self:: HTTP_EXPECTATION_FAILED => '(未满足期望值)服务器未满足"期望"请求标头字段的要求',//417
        self:: HTTP_INTERNAL_SWEVER_EEEOR => '(服务器内部错误)服务器遇到错误，无法完成请求',//500
        self:: HTTP_NOT_IMPLEMENTED => '(尚未实施) 服务器不具备完成请求的功能。例如，服务器无法识别请求方法时可能会返回此代码',//501
        self:: HTTP_BAD_GATEWAY => '(错误网关)服务器作为网关或代理，从上游服务器收到无效响应',//502
        self:: HTTP_SERVICE_UNAVAILABLE => '(服务不可用)服务器目前无法使用（由于超载或停机维护）。通常，这只是暂时状态',//503
        self:: HTTP_GATEWAY_TIME_OUT => '(网关超时)服务器作为网关或代理，但是没有及时从上游服务器收到请求',//504
        self:: HTTP_VERSION_NOR_SUPPORTED => '(HTTP 版本不受支持)服务器不支持请求中所用的 HTTP 协议版本',//505
    ];
    public static $instance = null;

    public function __construct()
    {

    }

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function CURL_POST($url, $data)
    {
        $ch = curl_init();
        $data = is_array($data) ? http_build_query($data) : $data;

        curl_setopt($ch, CURLOPT_URL, $url);
        $this_header = array(
            "content-type: application/x-www-form-urlencoded;
            charset=UTF-8"
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);

        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);//设置超时时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $r = curl_exec($ch);
        $info = curl_getinfo($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $result = ['curl_is_success' => 1, 'data' => [], 'curl_data' => $info, 'params' => $data ? base64_encode($data) : '', 'errno' => $errno, 'curl_self_err_msg' => $err, 'curl_parse_err_msg' => ''];
        if ($errno) {
            $result['curl_is_success'] = 0;
            $result['curl_parse_err_msg'] = self::getCurlErrorMsg($errno);
        } else if ($info['http_code'] != 200) {
            $result['curl_is_success'] = 0;
            $result['curl_parse_err_msg'] = self::getHttpErrorMsg($info['http_code']);
        }
        $result['data'] = $r;
        // Debug::log($result, 'curl', 'curl_result');

        curl_close($ch);
        return $result;
    }

    function curl_get_contents($url)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

// I changed UA here
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','charset=gb2312'));
        /*  $html = curl_exec($ch);
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url); //设置访问的url地址
          //curl_setopt($ch,CURLOPT_HEADER,1);            //是否显示头部信息
          curl_setopt($ch, CURLOPT_TIMEOUT, 5); //设置超时
          curl_setopt($ch, CURLOPT_USERAGENT, CURLOPT_USERAGENT); //用户访问代理 User-Agent
          curl_setopt($ch, CURLOPT_REFERER, CURLOPT_REFERER); //设置 referer
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //跟踪301
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //返回结果*/
        $r = curl_exec($ch);
        $info = curl_getinfo($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        // Debug::log("curl_error_info:" . var_export(curl_error($ch), true), "error");
        $result = ['curl_is_success' => 1, 'data' => [], 'curl_data' => $info, 'params' => '', 'errno' => $errno, 'curl_self_err_msg' => $err, 'curl_parse_err_msg' => ''];
        if ($errno) {
            $result['curl_is_success'] = 0;
            $result['curl_parse_err_msg'] = self::getCurlErrorMsg($errno);
        } else if ($info['http_code'] != 200) {
            $result['curl_is_success'] = 0;
            $result['curl_parse_err_msg'] = self::getHttpErrorMsg($info['http_code']);
        }
        $result['data'] = $r;
        // Debug::log($result, 'curl', 'curl_result');
        curl_close($ch);
        return $result;
    }

    /*根据curl返回的errno 获取错误信息
     * */
    public function getCurlErrorMsg($err_code)
    {
        if (!$err_code) {
            return '';
        }
        if (isset(self::$errmsg_arr[$err_code])) {
            return self::$errmsg_arr[$err_code];
        }
        return '未知的错误';
    }

    /*根据curl返回的http_code 获取错误信息
   * */
    public function getHttpErrorMsg($err_code)
    {
        if (!$err_code) {
            return '';
        }
        if (isset(self::$http_err[$err_code])) {
            return self::$http_err[$err_code];
        }
        return '未知的错误';
    }
}