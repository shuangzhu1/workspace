<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/19
 * Time: 18:31
 */

namespace Components\Kafka;


use Components\Kafka\core\AbstractKafka;

class Producer extends AbstractKafka
{
    static private $instance = null;

    public static function getInstance($host='')
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

    public function produce($data)
    {
        return $this->producer($data);
    }
}