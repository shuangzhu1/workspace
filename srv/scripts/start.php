<?php
/**
 * Created by PhpStorm.
 * User: Arimis
 * Date: 14-5-23
 * Time: 下午7:16
 */
use Phalcon\CLI\Console as ConsoleApp;

define('VERSION', '1.0.0');
define('ROOT', dirname(dirname(__FILE__)));
ini_set('date.timezone', 'Asia/Shanghai');
define('TEST_SERVER', file_exists(ROOT . "/test.txt") ? true : false);//是否测试服务器
//Using the CLI factory default services container
//$di = new CliDI();

// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', realpath(dirname(__FILE__)));

if (TEST_SERVER) {
    $config_path = APPLICATION_PATH . '/config/Test/';
} else {
    $config_path = APPLICATION_PATH . '/config/';
}

include $config_path . "/loader.php";
$config = include $config_path . "/config.php";
include $config_path . "/services.php";


//Create a console application
$console = new ConsoleApp();
$console->setDI($di);

/**
 * Process the console arguments
 */
$arguments = array();
foreach ($argv as $k => $arg) {
    if ($k == 1) {
        $arguments['task'] = $arg;
    } elseif ($k == 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        //$arguments[] = $arg;
        $arguments['params'][] = $arg;
    }
}

// define global constants for the current task and action
define('CURRENT_TASK', (isset($argv[1]) ? $argv[1] : null));
define('CURRENT_ACTION', (isset($argv[2]) ? $argv[2] : null));

try {
    // handle incoming arguments
    $console->handle($arguments);
} catch (\Phalcon\Exception $e) {
    echo date('Y-m-d H:i:s') . $e->getMessage();
    exit(255);
}