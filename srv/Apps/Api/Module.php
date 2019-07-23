<?php

namespace Multiple\Api;

use Phalcon\Loader;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\View;

class Module
{

    public function registerAutoloaders()
    {
        $loader = new Loader();

        $loader->registerNamespaces(array(
            'Multiple\Api\Controllers' => 'Apps/Api/Controllers/',
            'Window' => 'Apps/Api/Window/',
            'Merchant' => 'Apps/Api/Merchant/',
            'Community' => 'Apps/Api/Community/',
            'Multiple\Api\Merchant\Helper' => 'Apps/Api/Merchant/Helper/',
        ));

        $loader->register();
    }

    /**
     * Register the services here to make them general or register in the ModuleDefinition to make them module-specific
     */
    public function registerServices($di)
    {
        //Registering a dispatcher
        /*   $di->set('dispatcher', function () {
               $dispatcher = new Dispatcher();

               $dispatcher->setDefaultNamespace("Multiple\\Api\\Controllers\\");
               return $dispatcher;
           });*/
        $di->set(
            'dispatcher',
            function () use ($di) {
                $dispatcher = new Dispatcher();
                $dispatcher->setDefaultNamespace("Multiple\\Api\\Controllers\\");
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

        //Registering the view component
        $di->set('view', function () {
            $view = new View();
            $view->setViewsDir('Apps/Api/Views/');
            return $view;
        });

    }

}