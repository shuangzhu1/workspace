<?php

namespace Multiple\Payment;

use Components\Auth\AclListener;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Loader;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\View;


define("MODULE_PATH", __DIR__);
define('CUR_APP_ID', 1);

class Module
{
    public function registerAutoloaders()
    {

        $loader = new Loader();

        $loader->registerNamespaces(array(
            'Multiple\Payment\Controllers' => 'Apps/Payment/Controllers/',
            'Multiple\Payment\Controllers\Main' => 'Apps/Payment/Controllers/main',
            'Multiple\Payment\Plugins' => 'Apps/Payment/Plugins/',
        ));

        $loader->register();
    }

    /**
     * Register the services here to make them general or register in the ModuleDefinition to make them module-specific
     */
    public function registerServices($di)
    {

        //Registering a dispatcher
        $di->set('dispatcher', function () {

            $dispatcher = new Dispatcher();

            //Attach a event listener to the dispatcher
            $eventManager = new EventManager();
            $eventManager->attach('dispatch', new AclListener('Payment'));

            $dispatcher->setEventsManager($eventManager);
            $dispatcher->setDefaultNamespace("Multiple\\Payment\\Controllers\\");
            return $dispatcher;
        });

        //Registering the view component
        $di->set('view', function () {
            //Create an event manager
            $view = new View();
            $view->registerEngines(array(
                '.phtml' => 'volt',
            ));
            $view->setMainView("index");
            $view->setLayout("main");
            $view->setViewsDir('Apps/Payment/Views/');
            return $view;
        });

    }


}
