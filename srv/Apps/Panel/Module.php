<?php

namespace Multiple\Panel;

use Components\StaticFileManager;
use Multiple\Api\Merchant\Helper\Ajax;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Loader;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\View;

# 根据路径获取应用名称
$app_path = dirname(__FILE__);
$app_name = substr($app_path, strrpos($app_path, DIRECTORY_SEPARATOR) + 1);

define('MODULE_NAME', $app_name);
define("MODULE_PATH", __DIR__);

class Module
{
    public function registerAutoloaders()
    {

        $loader = new Loader();

        $loader->registerNamespaces(array(
            'Multiple\Panel\Controllers' => 'Apps/Panel/Controllers/',
            'Multiple\Panel\Plugins' => 'Apps/Panel/Plugins/',
            'Modules' => 'Apps/Panel/Modules/',
            'Modules\Api' => 'Apps/Panel/Modules/Api/',
            'Multiple\Panel\Api' => 'Apps/Panel/Api/',
            'Multiple\Wap\Helper' => 'Apps/Wap/Helper/',
        ));

        $loader->register();
    }

    /**
     * Register the services here to make them general or register in the ModuleDefinition to make them module-specific
     */
    public function registerServices($di)
    {
        //Registering a dispatcher
        $di->set(
            'dispatcher',
            function () use ($di) {
                $dispatcher = new Dispatcher();
                $dispatcher->setDefaultNamespace("Multiple\\Panel\\Controllers\\");
                $evManager = $di->getShared('eventsManager');
                $evManager->attach(
                    "dispatch:beforeException",
                    function ($event, $dispatcher, $exception) {
                        switch ($exception->getCode()) {
                            case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                            case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                                $dispatcher->forward(
                                    array(
                                        'namespace' => $dispatcher->getNamespaceName(),
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

        //Registering the view component
        $di->set('view', function () {
            //Create an event manager
            $eventsManager = new EventManager();
            $viewListener = new StaticFileManager();
            //Attach a listener for type "view"
            $eventsManager->attach("view:beforeRender", $viewListener);
            $view = new View();
            $view->registerEngines(array(
                '.phtml' => "volt"
            ));
            if (\Util\Ajax::isMobile()) {
                $view->setMainView("index");
            } else {
                $view->setMainView("layout");
            }
            $view->setLayout("main");
            $view->setViewsDir('Apps/Panel/Views');

            $view->setEventsManager($eventsManager);
            return $view;
        });

    }


}