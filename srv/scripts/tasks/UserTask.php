<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/23
 * Time: 18:32
 */
class UserTask extends \Phalcon\CLI\Task
{
    protected function onConstruct()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
    }

    //在线统计
    public function onlineStatAction()
    {
        \Services\Stat\UserManager::getInstance()->onlineStat();
        exit;
    }

    //vip到期检测
    public function vipCheckAction()
    {
        set_time_limit(0);
        $vipCore = \Services\Vip\VipCore::getInstance();
        $vipCore->checkDeadline();
        exit;
    }

    //vip统计入库
    public function vipStatInsertDbAction($args)
    {
        set_time_limit(0);
        $date = @$args[0];
        $date = $date ? $date : date('Ymd', (time() - 86400));
        \Services\Vip\VipCore::getInstance()->statInsertDb($date);
        exit;
    }

    //vip快到期的提醒
    public function vipDeadlineTipAction()
    {
        set_time_limit(0);
        \Services\Vip\VipCore::getInstance()->deadlineTip();
        exit;
    }
}