<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/23
 * Time: 10:15
 */
class DiscussTask extends \Phalcon\CLI\Task
{
    protected function onConstruct()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
    }

    //清除榜单数据【仅保存7天】
    public function clearBillboardAction($args)
    {
        set_time_limit(0);
        $date = @$args[0];
        $date = $date ? $date : date('Ymd', strtotime("-8 days"));
        \Services\Discuss\BillboardManager::getInstance()->clearData($date);
        exit();
    }

    //生成榜单数据
    public function createBillboardAction()
    {
        set_time_limit(0);
        \Services\Discuss\BillboardManager::getInstance()->createBillboard();
        exit();
    }

}