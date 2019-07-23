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
        "host" => "127.0.0.1",
        "username" => "root",
        /*  "password" => '',*/
        "password" => 'www.hn78test.com@2017',
        "dbname" => "dvalley",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    //原生 mysql
    'database' => array(
        'adapter' => 'Mysql',
        "host" => "127.0.0.1",
        "username" => "root",
        "password" => 'www.hn78test.com@2017',
        "dbname" => "dvalley",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    'database2' => array(
        'adapter' => 'Mysql',
        "host" => "127.0.0.1",
        "username" => "root",
        "password" => 'www.hn78test.com@2017',
        "dbname" => "dvalley_developer",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    'database_statistics' => array(
        'adapter' => 'Mysql',
        "host" => "127.0.0.1",
        "username" => "root",
        "password" => 'www.hn78test.com@2017',
        "dbname" => "dvalley_statistics",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    'database_viewer' => array(
        'adapter' => 'Mysql',
        "host" => "127.0.0.1",
        "username" => "root",
        "password" => 'www.hn78test.com@2017',
        "dbname" => "dvalley_viewer",
        // "charset" =>"utf8",
        "charset" => "utf8mb4"
    ),
    'redis' => array(
        'host' => '127.0.0.1',//'',
        /*    'host' => '112.74.15.30',//'',*/
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
    'kafka' => array(
        'host' => '127.0.0.1:9092',
    ),
));
