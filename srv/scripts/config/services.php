<?php

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\DI\FactoryDefault;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Logger\Adapter\File as FileLog;
use Phalcon\Logger;
use Phalcon\DI\FactoryDefault\CLI as CliDI;
use Components\Redis\RedisComponent as RedisComponent;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new CliDi();

$config = include APPLICATION_PATH . "/config/config.php";

$di->set('config', $config);

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config) {
    /*$eventsManager = new EventManager();
    //$filePath = APPLICATION_PATH . "/Cache/log/sql/" . date('Ymd') . '/' . date('H') . ".log";
    $filePath = "/var/www/dvalley" . "/Cache/log/sql/" . date('Ymd') . '/' . date('H') . ".log";
    $path = dirname($filePath);
    if (!is_dir($path)) {
        @mkdir($path, 0777, true);
    }
    if (file_exists($filePath)) {
        @chmod($filePath, 0777);
    }

    $logger = new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db', function ($event, $connection) use ($logger) {
//   /     print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            // $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });*/
    $adapter = new DbAdapter(array(
        "adapter" => $config->mycat_database->adapter,
        "host" => $config->mycat_database->host,
        "username" => $config->mycat_database->username,
        "password" => $config->mycat_database->password,
        "dbname" => $config->mycat_database->dbname,
        "charset" => $config->mycat_database->charset,
      /*  "options" => [ //这里加上此附加参数
            PDO::ATTR_PERSISTENT => true,//长连接
            PDO::ATTR_EMULATE_PREPARES => true
        ]*/
    ));
    //$adapter->setEventsManager($eventsManager);
    return $adapter;
});
$di->set('original_mysql', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);
    //Listen all the database events
    $eventsManager->attach('mysql', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->database->adapter,
        "host" => $config->database->host,
        "username" => $config->database->username,
        "password" => $config->database->password,
        "dbname" => $config->database->dbname,
        "charset" => $config->database->charset,
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});

$di->set('db_statistics', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db_statistics', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->database_statistics->adapter,
        "host" => $config->database_statistics->host,
        "username" => $config->database_statistics->username,
        "password" => $config->database_statistics->password,
        "dbname" => $config->database_statistics->dbname,
        "charset" => $config->database_statistics->charset,
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});
$di->set('db_viewer', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('mysql', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //  $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->database_viewer->adapter,
        "host" => $config->database_viewer->host,
        "username" => $config->database_viewer->username,
        "password" => $config->database_viewer->password,
        "dbname" => $config->database_viewer->dbname,
        "charset" => $config->database_viewer->charset,
    ));


    $adapter->setEventsManager($eventsManager);
    return $adapter;
});
$di->set('db_package', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('mysql', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->db_package->adapter,
        "host" => $config->db_package->host,
        "username" => $config->db_package->username,
        "password" => $config->db_package->password,
        "dbname" => $config->db_package->dbname,
        "charset" => $config->db_package->charset,
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});
$di->set('db_package_pick', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('mysql', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->db_package_pick->adapter,
        "host" => $config->db_package_pick->host,
        "username" => $config->db_package_pick->username,
        "password" => $config->db_package_pick->password,
        "dbname" => $config->db_package_pick->dbname,
        "charset" => $config->db_package_pick->charset,
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});
//$di->set('modelsMetadata', function () use ($config) {
//   if (isset ($config->models->metadata)) {
//       $metaDataConfig = $config->models->metadata;
//        $metadataAdapter = 'Phalcon\Mvc\Model\Metadata\\' . $metaDataConfig->adapter;
//        return new $metadataAdapter ();
//    }
//    return new \Phalcon\Mvc\Model\Metadata\Memory();
//});
$di->set('debugLogger', function () {
    $filePath = ROOT . "/Cache/phalcon_log/debug/" . date('Ymd') . '/' . date('H') . ".log";
    $path = dirname($filePath);
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    if (file_exists($filePath)) {
        @chmod($filePath, FILE_WRITE_MODE);
    }
    $logger = new \Phalcon\Logger\Adapter\File($filePath);
    return $logger;
});
$di->set('errorLogger', function () {
    $filePath = ROOT . "/Cache/phalcon_log/error/" . date('Ymd') . '/' . date('H') . ".log";
    $path = dirname($filePath);
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    if (file_exists($filePath)) {
        @chmod($filePath, FILE_WRITE_MODE);
    }
    $logger = new \Phalcon\Logger\Adapter\File($filePath);
    return $logger;
});
$di->set('redis', function () use ($config) {
    // Cache data for 2 days
    $frontCache = new \Phalcon\Cache\Frontend\Data(array(
        "lifetime" => $config->redis->lifetime
    ));
    $cache = new \Components\Redis\RedisComponent($frontCache, array(
        'host' => $config->redis->host,
        'port' => $config->redis->port,
        'auth' => $config->redis->auth,
        'persistent' => false,
        'statsKey' => 'info',
        'prefix' => $config->redis->prefix,
        'index' => 0 /*选择redis数据库*/
    ));
    return $cache;
});
$di->set('redis_queue', function () use ($config) {
    // Cache data for 2 days
    $frontCache = new \Phalcon\Cache\Frontend\Data(array(
        "lifetime" => $config->redis->lifetime
    ));

    $cache = new \Components\Redis\RedisComponent($frontCache, array(
        'host' => $config->redis->host,
        'port' => $config->redis->port,
        'auth' => $config->redis->auth,
        'persistent' => false,
        'statsKey' => 'info',
        'prefix' => $config->redis->prefix,
        'index' => 1 /*选择redis数据库*/
    ));
    return $cache;
});
//消息订阅
$di->set('publish_queue', function () use ($config) {
    $frontCache = new \Phalcon\Cache\Frontend\Data(array(//  "lifetime" => $config->redis->lifetime
    ));

    $cache = new \Components\Redis\RedisComponent($frontCache, array(
        'host' => $config->redis->host,
        'port' => $config->redis->port,
        'auth' => $config->redis->auth,
        'persistent' => false,
        'statsKey' => 'info',
        'prefix' => $config->redis->prefix,
        'index' => 3 /*选择redis数据库*/
    ));
    return $cache;
});