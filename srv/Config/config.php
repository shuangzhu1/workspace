<?php
// wap host rule
/**
 * 匹配域名 -> 微站三级域名中二级域名段
 * 如 1234067.m.main-domain.com 中'main-domain.com'=>'m'
 */
$host_keys = array();

if (!defined('MAIN_DOMAIN')) {
    define('MAIN_DOMAIN', "klgwl.com");
    define('UPLOAD_DIR', "");
    define('WAP_DOMAIN_DS', 'wap');
    define('FRONT_DOMAIN', 'www.klgwl.com');
    define('STATIC_DOMAIN', 'www.klgwl.com');
    define('HOST_BRAND', '恐龙谷');
}

if (!defined('MAIN_DOMAIN')) {
    die('该域名不在授权范围！！');
}

return new \Phalcon\Config(array(
    //中间件mycat
    'mycat_database' => array(
        'adapter' => 'Mysql',
        /* "host" => "127.0.0.1",*/
        "host" => "112.74.15.30:8866",
        /* "username" => "root",*/
        "username" => "mycat_klg",
        /*  "password" => '',*/
        "password" => 'www.hn78.com',
        /*  "dbname" => "dvalley",*/
        "dbname" => "dvalley2",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    //原生 mysql
    'database' => array(
        'adapter' => 'Mysql',
        //  "host" => "127.0.0.1",
        "host" => "112.74.15.30",
        "username" => "root",
        /*"password" => '',*/
        "password" => 'www.hn78.com',
        "dbname" => "dvalley",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    'database2' => array(
        'adapter' => 'Mysql',
        /*  "host" => "127.0.0.1",*/
        "host" => "112.74.15.30",
        "username" => "root",
        /*"password" => '',*/
        "password" => 'www.hn78.com',
        "dbname" => "dvalley_developer",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    'database_statistics' => array(
        'adapter' => 'Mysql',
        /*  "host" => "127.0.0.1",*/
        "host" => "112.74.15.30",
        "username" => "root",
        /*"password" => '',*/
        "password" => 'www.hn78.com',
        "dbname" => "dvalley_statistics",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    //queue server
//    'beanstalk' => array(
//        "host" => "",
//        "port" => "11300",
//    ),

//    'mongodb' => array(
//        "host" => "127.0.0.1",
//        "port" => "27017",
//    ),
//    'memcached' => array(
//        'host' => '127.0.0.1',
//        //'port' => '11211',
//        'port' => '6379',
//        'lifetime' => 172800, // Cache data for 2 days
//        'prefix' => 'dev4'
//    ),

    'redis' => array(
        'host' => '127.0.0.1',//'',
        //"host" => "112.74.15.30",
        'port' => '6379',
        'name' => '',
        'auth' => '',
        'lifetime' => '17200',
        'cookie_lifetime' => 3600, // Cache data for 2 days
        'prefix' => 'klg_',
    ),
    'yun_xin' => array(
        'app_key' => '98bc106cc840b07c869ec279a7d58d38',
        'app_secret' => '369b0699430e'
    ),
    'yun_pian' => array(
        'app_key' => '8c3f7282844ef24aa258d7b057eb8d97',
        'app_secret' => '',
        'sms_host' => 'https://sms.yunpian.com',
    ),
    'oss' => array(
        'app_key' => 'UuyoRLLDaiTyRYD5',
        'app_secret' => '06a8SRzXM0ELLnOluUMmkR9rLySFYh',
        "end_point" => "http://oss-cn-shenzhen.aliyuncs.com"
    ),
    'JPush' => array(
        'app_key' => '305214c2328aad0da32e9726',
        'master_secret' => 'f997e54ce2fcff6551fae386'
    ),
    //app下载地址
    'appDownload' => array(
        'android' => 'http://api.klgwl.com/download/ios/',#'http://bzsns.cn/uploads/app/ckg.apk',
        'ios' => 'https://itunes.apple.com/cn/app/kong-long-gu/id1208329358?mt=8'// 'http://api.klgwl.com/download/ios/'#'https://itunes.apple.com/cn/app/fang-yuan-jian/id1072631993?l=en&mt=8'
    ),

    'metadata' => array(
        "adapter" => "Apc",
        "suffix" => "my-suffix",
        "lifetime" => "86400"
    ),
    'uploadSaveType' => 'normal',//fdfs|normal
    'secret_key' => array(
        'sign_key' => 'www.hn78.com'
    ),
    'db_package' => array(
        'adapter' => 'Mysql',
        "host" => "120.76.47.205",
        "username" => "root",
        "password" => 'www.hn78.com',
        "dbname" => "redbag_new",
        "charset" => "utf8"
    ),
    //后台请求地址
    'api_domain' => [
        'service' => 'http://service.klgwl.com/'
    ],
    //统计服务器配置
    'forms' => [
        'adapter' => 'Mysql',
        "host" => "120.76.47.205",
        "username" => "klg_wallet",
        "password" => 'klgwl.com@wallet2017',
        "dbname" => "forms",
        "charset" => "utf8mb4"
    ],
    //卖家信息数据库
    'sellers' => [
        'adapter' => 'Mysql',
        "host" => "120.76.47.205",
        "username" => "klg_wallet",
        "password" => 'klgwl.com@wallet2017',
        "dbname" => "rent",
        "charset" => "utf8mb4"
    ],
    //题库
    'question_bank' => array(
        'adapter' => 'Mysql',
        "host" => "120.76.47.205",
        "username" => "root",
        "password" => 'www.hn78.com',
        "dbname" => "question_bank",
        "charset" => "utf8mb4"
    ),
    "wechat" => [
        "app_id" => "wx37e99724e3bfb8c4",
        "app_secret" => "b8fd50a25b1367e72483d6796213e3aa",
        "partnerkey" => "ac9d3bfe0eeb646166ee03d5541fadc7",
        "partnerid" => '1458939102'
    ]
    //
));
