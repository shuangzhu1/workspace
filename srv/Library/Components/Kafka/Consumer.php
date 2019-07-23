<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/22
 * Time: 15:49
 *
 * 消费者
 */

namespace Components\Kafka;


use Components\Kafka\core\AbstractKafka;

class Consumer extends AbstractKafka
{
    static private $instance = null;

    public static function getInstance($host = '')
    {
        if (!self::$instance) {
            self::$instance = new self($host);
        }
        return self::$instance;
    }

    public function __construct($host)
    {
        parent::__construct($host);
    }

    public function consume($callback)
    {
        $this->consumer($callback);
    }

}