<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/1
 * Time: 17:48
 */
class OrderTask extends \Phalcon\CLI\Task
{
    protected function onConstruct()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
    }

    //订单支付超时
    public function notifyAction($args)
    {
        $trade_no = @$args[0];
        \Util\Debug::log("orderTask notify->trade_no:" . $trade_no, 'order');
        if ($trade_no) {
            \Services\User\OrderManager::init(true)->cancelOrder($trade_no);
        }
    }

    //合伙人 收益
    public function agentIncomeAction($args)
    {
        set_time_limit(0);
        $date = @$args[0];
        $date = $date ? $date : date('Ym', (time() - 86400));
        \Services\Agent\AgentManager::init(true)->incomeToAccount($date);
        exit;
    }

    public function testAction()
    {
        var_dump(\Services\User\OrderManager::init()->list(50000));
        exit;
    }
}