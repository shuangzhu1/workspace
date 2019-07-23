<?php
// wap host rule
/**
 * 匹配域名 -> 微站三级域名中二级域名段
 * 如 1234067.m.main-domain.com 中'main-domain.com'=>'m'
 */

return new \Phalcon\Config(array(
    //中间件mycat
    'mycat_database' => array(
        'adapter' => 'Mysql',
//        "host" => "127.0.0.1:8866",
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
//        "host" => "127.0.0.1",
        "host" => "112.74.15.30",
        "username" => "root",
        /*  "password" => '',*/
        "password" => 'www.hn78.com',
        "dbname" => "dvalley",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    'database2' => array(
        'adapter' => 'Mysql',
//        "host" => "127.0.0.1",
        "host" => "112.74.15.30",
        "username" => "root",
        "password" => 'www.hn78.com',
        /* "password" => 'www.hn78.com',*/
        "dbname" => "dvalley_developer",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    'database_statistics' => array(
        'adapter' => 'Mysql',
//        "host" => "127.0.0.1",
        "host" => "112.74.15.30",
        "username" => "root",
        "password" => 'www.hn78.com',
        /* "password" => 'www.hn78.com',*/
        "dbname" => "dvalley_statistics",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    //红包发送记录
    'db_package' => array(
        'adapter' => 'Mysql',
        "host" => "120.76.47.205",
        "username" => "root",
        "password" => 'www.hn78.com',
        "dbname" => "redbag_new",
        "charset" => "utf8"
    ),
    //红包领取记录 kingshard 中间键
//    'db_package_pick' => array(
//        'adapter' => 'Mysql',
//        /* "host" => "127.0.0.1",*/
//        "host" => "120.76.47.205:9696",
//        "username" => "hn_ks",
//        "password" => 'www.hn78.com',
//        /* "password" => 'www.hn78.com',*/
//        "dbname" => "redbagresult_rid",
//        // "charset" =>"utf8",
//        "charset" => "utf8"
//    ),
//红包领取记录原生
    'db_package_pick' => array(
        'adapter' => 'Mysql',
        /* "host" => "127.0.0.1",*/
        "host" => "120.76.47.205",
        "username" => "root",
        "password" => 'www.hn78.com',
        /* "password" => 'www.hn78.com',*/
        "dbname" => "redbagresult_rid",
        // "charset" =>"utf8",
        "charset" => "utf8"
    ),
    'database_viewer' => array(
        'adapter' => 'Mysql',
//        "host" => "127.0.0.1",
        "host" => "120.76.47.205",
        "username" => "root",
        "password" => 'www.hn78test.com@2017',
        "dbname" => "dvalley_viewer",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),

    'redis' => array(
//        'host' => '127.0.0.1',//'',
        'host' => '112.74.15.30',//'',
        'port' => '6379',
        'name' => '',
        'auth' => 'www.hn78.com',
        'lifetime' => '17200',
        'cookie_lifetime' => 3600, // Cache data for 2 days
        'prefix' => 'klg_'
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
    'kafka' => array(
        'host' => '120.79.182.185:9092,120.78.176.91:9092,119.23.54.215:9092',
    ),
));
