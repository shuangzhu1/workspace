<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(array(__DIR__ . '/../../../Library/', APPLICATION_PATH . '/tasks/'))
    ->registerNamespaces(array(
        "Components" => __DIR__ . '/../../../Library/Components',
        "Library" => __DIR__ . '/../../../Library',
        "Services" => __DIR__ . '/../../../Services',
        "Language" => __DIR__ . '/../../../Language',
        "Util" => __DIR__ . '/../../../Library/Util',
        "Download" => __DIR__ . '/../../../Library/Download',
        "Models" => __DIR__ . '/../../../Models',
        "Upload" => __DIR__ . '/../../../Library/Upload',
        "PHPMailer" => __DIR__ . '/../../../Library/PHPMailer',
        "Phalcon" => __DIR__ . '/../../../Library/incubator/Library/Phalcon',
        "JPush" => __DIR__ . '/../../../Library/Components/JPush',
        "OSS" => __DIR__ . '/../../../Library/Components/Oss',
        "Green" => __DIR__ . '/../../../Library/Components/LvWang',
    ))->register();

$loader->registerClasses(
    array(
        "Phalcon\\Db\\Adapter\\Pdo\\Mssql" => __DIR__ . '/../../../Library/Components/Phalcon/Db/Adapter/Pdo/Mssql.php',
        "Phalcon\\Db\\Adapter\\Dialect\\Mssql" => __DIR__ . '/../../../Library/Components/Phalcon/Db/Dialect/Mssql.php',
    )
)->register();
