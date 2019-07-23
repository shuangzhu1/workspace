<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/17
 * Time: 11:49
 */
class ShopTask extends \Phalcon\Cli\Task
{
    protected function onConstruct()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
    }

    public function checkDeadlineAction()
    {
        set_time_limit(0);
        $shop = \Services\Shop\ShopManager::init(true);
        $shop->checkDeadline();
        exit;
    }
}