<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/23
 * Time: 16:32
 */
class ServerTask extends \Phalcon\CLI\Task
{
    /*服务器异常发消息*/
    public function abnormalAction($args)
    {
        $ip = "112.74.15.30";
        $phone = [18770090773];
        //服务器异常
        foreach ($phone as $p) {
            \Services\Site\VerifyCodeManager::init(true)->sendPhoneNormalMessage($p, \Services\Site\VerifyCodeManager::$codetype[\Services\Site\VerifyCodeManager::CODE_SERVER_ABNORMAL],
                ['ip' => $ip, 'detail' => $args[0]], '', '');
        }
        exit;
    }

    /**清除系统日志
     * @param $args
     */
    public function clearLogAction($args)
    {
        set_time_limit(0);
        $date = @$args[0];
        $date = $date ? $date : date('Y-m-d');
        //清除一个星期之前的
        \Util\Debug::clearLog(ROOT . "/Cache", $date, 7);
        exit;
    }

    /*redis数据删除缓存*/
    public function clearRedisAction($args)
    {
        set_time_limit(0);
        $start_date = @$args[0];
        $end_date = @$args[1];

        $start_date = $start_date ? $start_date : date('Y-m-d', strtotime("-20 days"));
        $end_date = $end_date ? $end_date : date('Y-m-d', strtotime("-7 days"));

        $days = \Services\Stat\StatManager::getLimitDay($start_date, $end_date, '');
        $keys = [
            \Services\Site\CacheSetting::KEY_PACKAGE_PICK_COUNT,
            \Services\Site\CacheSetting::KEY_RED_PACKAGE_PICK_LIST
        ];
        foreach ($days as $item) {
            foreach ($keys as $k) {
                ($this->di->getShared('redis')->del($k . $item));
            }
        }
        exit;
    }
}