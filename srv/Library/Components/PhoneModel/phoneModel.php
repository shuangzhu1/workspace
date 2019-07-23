<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/12
 * Time: 16:08
 */

namespace Components\PhoneModel;


class phoneModel
{
    static $driver = null;
    static $instance = null;

    const os_android = 'android';
    const os_ios = 'ios';


    public function __construct($client_type)
    {
        if ($client_type == self::os_android) {
            self::$driver = new android();
        } else if ($client_type == self::os_ios) {
            self::$driver = new ios();
        }
    }

    public static function instance($client_type)
    {
        if (!self::$instance) {
            self::$instance = new self($client_type);
        }
        return self::$instance;
    }

    public function getName($model)
    {
        return self::$driver->getName($model);
    }

}