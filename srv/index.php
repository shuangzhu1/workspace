<?php
//test
use Phalcon\Mvc\Application;

//error_reporting(E_ALL);
error_reporting(1);
ini_set('display_errors', 1);
define('ROOT', dirname(__FILE__));
define('LANG', isset($_REQUEST['lang']) ? $_REQUEST['lang'] : 1);//初始化语言 1-中文简体 2-中文繁体 3-英文
define('DS', DIRECTORY_SEPARATOR);
ini_set('date.timezone', 'Asia/Shanghai');
define("START_TIME", microtime(true));
define('TEST_SERVER', file_exists(ROOT . "/test.txt") ? true : false);//是否测试服务器
//initilize a error logger
function getLogger()
{
    $filePath = ROOT . "/Cache/phalcon_log/error/" . date('Y-m-d') . '/' . date('H') . ".log";
    $path = dirname($filePath);
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    if (file_exists($filePath)) {
        @chmod($filePath, FILE_WRITE_MODE);
    }
    $logger = new \Phalcon\Logger\Adapter\File($filePath);
    return $logger;
}

function writeLog($msg)
{
    $uri = new \Util\Uri();
    \Models\System\SystemApiError::insertOne([
        'state_code' => '',
        'params' => json_encode($_REQUEST, JSON_UNESCAPED_UNICODE),
        'url' => $uri->actionUrl(),
        'response' => $msg,
        'app_version' => isset($_REQUEST['app_version']) ? $_REQUEST['app_version'] : '',
        'client_type' => isset($_REQUEST['client_type']) ? $_REQUEST['client_type'] : '',
        'created' => time(),
        'ymd' => date('Ymd')
    ]);
}

try {
    //测试服务器
    $config_path = __DIR__ . "/Config/";
    if (TEST_SERVER) {
        $config_path = __DIR__ . "/Config/Test/";
    }
    /**
     * Read the configuration
     */
    $config = include $config_path . "config.php";

    /**
     * Read auto-loader
     */
    include $config_path . "loader.php";

    /**
     * Read services
     */
    include $config_path . "services.php";

    //output config
//    echo $config->redis->host, '<br>';
//    echo $config->redis->port,'<br>';
//    echo $config->redis->lifetime,'<br>';
//    echo $_SERVER['SERVER_PORT'];

    /**
     * Handle the request
     */
    $application = new Application();

    /**
     * Assign the DI
     */
    $application->setDI($di);

    /**
     * Include modules
     */
    require $config_path . 'modules.php';
    $app = $application->handle();
    echo $app->getContent();

} catch (\Phalcon\Exception $e) {
    $ajax = new \Util\Ajax();
    $logger = getLogger();
    $message = $e->getFile() . ' ' . $e->getTraceAsString() . ' ' . $e->getLine() . '行 ' . $e->getMessage() . ",数据：" . var_export($_REQUEST, true);
    $logger->error($message);
    if ($application->dispatcher->getModuleName() == 'api') {
        writeLog($message);
        $ajax::outError(\Util\Ajax::CUSTOM_ERROR_MSG, "服务器打了个盹");
    } else {
        echo $message;
    }
} catch (\PDOException $e) {
    $ajax = new \Util\Ajax();
    $logger = getLogger();
    $message = $e->getFile() . ' ' . $e->getTraceAsString() . ' ' . $e->getLine() . '行 ' . $e->getMessage() . ",数据：" . var_export($_REQUEST, true);
    $logger->error($message);
    if ($application->dispatcher->getModuleName() == 'api') {
        writeLog($message);
        $ajax::outError(\Util\Ajax::CUSTOM_ERROR_MSG, "服务器打了个盹");
    } else {
        echo $message;
    }
    $logger = getLogger();
    $message = $e->getFile() . ' ' . $e->getTraceAsString() . ' ' . $e->getLine() . '行 ' . $e->getMessage() . ",数据：" . var_export($_REQUEST, true);
    $logger->error($message);
    if ($application->dispatcher->getModuleName() == 'api') {
        writeLog($message);
        $ajax::outError(\Util\Ajax::CUSTOM_ERROR_MSG, "服务器打了个盹");
    } else {
        echo $message;
    }
}


