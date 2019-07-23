<?php

namespace Components\WeChat;

/**
 *
 */
use Components\WeChat\Requests\UserInfo;
use Phalcon\Mvc\User\Component;

class RequestFactory extends Component
{

    /**
     * @param $adapter
     * @param $customer
     * @return AbstractRequest
     */
    public static function create($adapter, $customer, $appId, $appSecret)
    {
        $adapter = "\\Components\\WeChat\\Requests\\" . ucwords($adapter);
        if (class_exists($adapter)) {
            $adapterClass = new $adapter($customer, $appId, $appSecret);
            $adapterClass->beforeRun();

            return $adapterClass;
        } else {
            return false;
        }
    }

    public static function createUserInfo($customer, $appId, $appSecret)
    {
        $adapter = new UserInfo($customer, $appId, $appSecret);
        $adapter->beforeRun();
        return $adapter;
    }
}

?>