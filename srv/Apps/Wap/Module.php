<?php
namespace Multiple\Wap;

use Phalcon\DI;
use Phalcon\Loader;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\View;

# 根据路径获取应用名称
$app_path = dirname(__FILE__);
$app_name = substr($app_path, strrpos($app_path, DIRECTORY_SEPARATOR) + 1);

define('MODULE_NAME', $app_name);
define("MODULE_PATH", __DIR__);
// 匹配子域名(包含本地域名如12345709.m.local)
define('CUR_APP_ID', 1);

// defined app

class Module
{
    public function registerAutoloaders()
    {

        $loader = new Loader();

        $loader->registerNamespaces(array(
            'Multiple\Wap\Controllers' => 'Apps/Wap/Controllers/',
            'Multiple\Wap\Helper' => 'Apps/Wap/Helper/',
            'Multiple\Wap\Api' => 'Apps/Wap/Api/',
            'Multiple\Wap\Module' => 'Apps/Wap/Module/',
            'Multiple\Home' => 'Apps/Home'
        ));


        $loader->register();
    }

    /**
     * Register the services here to make them general or register in the ModuleDefinition to make them module-specific
     */
    public function registerServices(DI $di)
    {
        /*  $di->set('dispatcher', function () {
              $dispatcher = new Dispatcher();
              $dispatcher->setDefaultNamespace("Multiple\\Wap\\Controllers\\");
              return $dispatcher;
          });*/
        $di->set(
            'dispatcher',
            function () use ($di) {
                $dispatcher = new Dispatcher();
                //var_dump(Di::getDefault()->getRouter()->getMatchedRoute());exit;
                $dispatcher->setDefaultNamespace("Multiple\\Wap\\Controllers\\");
                $evManager = $di->getShared('eventsManager');
                $evManager->attach(
                    "dispatch:beforeException",
                    function ($event, $dispatcher, $exception) {
                        switch ($exception->getCode()) {
                            case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                            case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                                $dispatcher->forward(
                                    array(
                                        'controller' => 'errors',
                                        'action' => 'show404',
                                    )
                                );
                                return false;
                        }
                    }
                );
                $dispatcher->setEventsManager($evManager);
                return $dispatcher;
            },
            true
        );
//
//        //Registering the view component
        $di->set('view', function () {
            $view = new View();
            $view->setViewsDir(MODULE_PATH . '/Views');
            $view->registerEngines(array(
                '.phtml' => 'Phalcon\Mvc\View\Engine\Php',
            ));
            return $view;
        });

    }

}
