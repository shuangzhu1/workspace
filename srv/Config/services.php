<?php

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\DI\FactoryDefault;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileLog;
use Phalcon\Session\Adapter\Files as Session;
use Phalcon\Cache\Frontend\Data as Data;
use Components\Redis\RedisComponent;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

$di->set('config', $config);

$di->set('router', function () {
    return require ROOT . '/Config/routes.php';
}, true);
$di->set('collectionManager', function () {
    return new Phalcon\Mvc\Collection\Manager();
}, true);

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () use ($config) {
    if (isset ($config->models->metadata)) {
        $metaDataConfig = $config->models->metadata;
        $metadataAdapter = 'Phalcon\Mvc\Model\Metadata\\' . $metaDataConfig->adapter;
        return new $metadataAdapter ();
    }
    return new \Phalcon\Mvc\Model\Metadata\Memory();
});

/**
 * Start the session the first time some component request the session service
 */
$di->set('session', function () use ($config) {
    $session = new Session();
    $session->start();
    return $session;
});
$di->set('cookies', function () {
    $cookies = new Phalcon\Http\Response\Cookies();
    $cookies->useEncryption(false);//禁用加密
    return $cookies;
});
/**
 * Register volt as one of the view template engines
 */
$di->set('volt', function ($view, $di) {
    $volt = new \Phalcon\Mvc\View\Engine\Volt ($view);

    $volt->setOptions(array(
        "compiledPath" => ROOT . "/Cache/tpl/",
        "compiledExtension" => '.php',
        'compiledSeparator' => '-',
        "compileAlways" => true
    ));
    return $volt;
});


/**
 * Register the flash service with custom CSS classes
 */
$di->set('flash', function () {
    return new Phalcon\Flash\Direct (array(
        'error' => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice' => 'alert alert-info'
    ));
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->mycat_database->adapter,
        "host" => $config->mycat_database->host,
        "username" => $config->mycat_database->username,
        "password" => $config->mycat_database->password,
        "dbname" => $config->mycat_database->dbname,
        "charset" => $config->mycat_database->charset,
    ));

    $adapter->setEventsManager($eventsManager);
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
$di->set('db2', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db2', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->database2->adapter,
        "host" => $config->database2->host,
        "username" => $config->database2->username,
        "password" => $config->database2->password,
        "dbname" => $config->database2->dbname,
        "charset" => $config->database2->charset,
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

$di->set('db_open', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db_open', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->database_open->adapter,
        "host" => $config->database_open->host,
        "username" => $config->database_open->username,
        "password" => $config->database_open->password,
        "dbname" => $config->database_open->dbname,
        "charset" => $config->database_open->charset,
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});

$di->set('forms', function () use ($config) {
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
        "adapter" => $config->forms->adapter,
        "host" => $config->forms->host,
        "username" => $config->forms->username,
        "password" => $config->forms->password,
        "dbname" => $config->forms->dbname,
        "charset" => $config->forms->charset,
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});

//卖家信息服务
$di->set('sellers', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->sellers->adapter,
        "host" => $config->sellers->host,
        "username" => $config->sellers->username,
        "password" => $config->sellers->password,
        "dbname" => $config->sellers->dbname,
        "charset" => $config->sellers->charset,
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});
//卖家信息服务
$di->set('question_bank', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->question_bank->adapter,
        "host" => $config->question_bank->host,
        "username" => $config->question_bank->username,
        "password" => $config->question_bank->password,
        "dbname" => $config->question_bank->dbname,
        "charset" => $config->question_bank->charset,
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});
//虚拟币
$di->set('virtual_coin', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->virtual_coin->adapter,
        "host" => $config->virtual_coin->host,
        "username" => $config->virtual_coin->username,
        "password" => $config->virtual_coin->password,
        "dbname" => 'virtual_coin',
        "port" => $config->virtual_coin->port
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});
//悬赏活动
$di->set('activity', function () use ($config) {
    $eventsManager = new EventManager();
    $logger = null;// new FileLog($filePath);

    //Listen all the database events
    $eventsManager->attach('db', function ($event, $connection) use ($logger) {
//        print_r($connection->getSQLStatement());
        if ($event->getType() == 'beforeQuery') {
            //   $logger->log($connection->getSQLStatement(), Logger::INFO);
        }
    });

    $adapter = new DbAdapter(array(
        "adapter" => $config->virtual_coin->adapter,
        "host" => $config->virtual_coin->host,
        "username" => $config->virtual_coin->username,
        "password" => $config->virtual_coin->password,
        "dbname" => 'activity',
        "port" => $config->virtual_coin->port
    ));

    $adapter->setEventsManager($eventsManager);
    return $adapter;
});
//Set a cache server for applications
//save,get,delete
$di->set('redis', function () use ($config) {
    // Cache data for 2 days
    $frontCache = new Data(array(
        "lifetime" => $config->redis->lifetime
    ));

    $cache = new RedisComponent($frontCache, array(
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
    $frontCache = new Data(array(//  "lifetime" => $config->redis->lifetime
    ));

    $cache = new RedisComponent($frontCache, array(
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
//发送 消息队列
$di->set('message_queue', function () use ($config) {
    $frontCache = new Data(array(//  "lifetime" => $config->redis->lifetime
    ));

    $cache = new RedisComponent($frontCache, array(
        'host' => $config->redis->host,
        'port' => $config->redis->port,
        'auth' => $config->redis->auth,
        'persistent' => false,
        'statsKey' => 'info',
        'prefix' => $config->redis->prefix,
        'index' => 2 /*选择redis数据库*/
    ));
    return $cache;
});
//消息订阅
$di->set('publish_queue', function () use ($config) {
    $frontCache = new Data(array(//  "lifetime" => $config->redis->lifetime
    ));

    $cache = new RedisComponent($frontCache, array(
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
//用户行为
$di->set('redis_behavior', function () use ($config) {
    $frontCache = new Data(array(//  "lifetime" => $config->redis->lifetime
    ));

    $cache = new RedisComponent($frontCache, array(
        'host' => $config->redis->host,
        'port' => $config->redis->port,
        'auth' => $config->redis->auth,
        'persistent' => false,
        'statsKey' => 'info',
        'prefix' => $config->redis->prefix,
        'index' => 4 /*选择redis数据库*/
    ));
    return $cache;
});
//敏感词过滤
$di->set('filter', function () {
    $filter = new \Phalcon\Filter();
    $filter->add('green', function ($content) {
        //$tmp = $filter->sanitize($content,'string');
        //$tmp = addslashes($content);
        return \Services\Site\SensitiveManager::filterContent($content);
    });
    return $filter;
}, true);
$di->set('platform_host', function () use ($config) {
    return 'main';
});

if (!defined('HOST_KEY')) define('HOST_KEY', $di->get('platform_host'));
$di->set('uri', function () use ($config) {
    return new \Util\Uri();
});

