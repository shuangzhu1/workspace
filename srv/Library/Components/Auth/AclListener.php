<?php
namespace Components\Auth;

use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class AclListener extends \Phalcon\Mvc\User\Component
{

    protected $_module;

    public function __construct($module)
    {
        $this->_module = $module;
    }

    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        $resource = $this->_module . '-' . $dispatcher->getControllerName(); // frontend-dashboard
        $access = $dispatcher->getActionName(); // null
    }

}