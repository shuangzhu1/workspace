<?php
namespace Multiple\Callback;

use Phalcon\Di;
use Phalcon\Loader;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Mvc\View;

class Module
{
    public function registerAutoloaders()
    {

        $loader = new Loader();

        $loader->registerNamespaces(array(
            'Multiple\Callback\Controllers' => 'Apps/Callback/Controllers/'
        ));


        $loader->register();
    }

    /**
     * Register the services here to make them general or register in the ModuleDefinition to make them module-specific
     */
    public function registerServices($di)
    {
        $di->set(
            'dispatcher',
            function () use ($di) {
                $dispatcher = new Dispatcher();
                $dispatcher->setDefaultNamespace("Multiple\\Callback\\Controllers\\");
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

        $di->set('view', function () {

            $view = new View();
            $view->registerEngines(array(
                '.phtml' => "volt"
            ));


            $view->setViewsDir('Apps/Callback/Views');

            return $view;
        });
    }

}
