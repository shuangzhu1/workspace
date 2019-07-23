<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/20
 * Time: 19:26
 */
class RobotTask extends \Phalcon\CLI\Task
{
    //键值 过期回调
    public function callback($redis, $pattern, $chan, $msg)
    {
        $redis = $this->di->get("redis");
        if (strpos($msg, \Services\Site\CacheSetting::KEY_OPEN_ROBOT)!==false) {
            $robot_id = explode(":", $msg)[1];
            if ($robot_id) {
                $yx = \Components\Yunxin\ServerAPI::init()->updateUserToken($robot_id);
                if ($yx && $yx['code'] == 200) {
                    $token = $yx['info']['token'];
                    $base = \Services\Site\CacheSetting::$setting[\Services\Site\CacheSetting::KEY_OPEN_ROBOT];
                    //更新redis
                    $redis->originalSet($base['prefix'] . $robot_id, json_encode(['token' => $token, 'expire' => time() + $base['life_time'] - 5]), $base['life_time']);
                    //更新数据库
                    \Models\User\UserProfile::updateOne(['yx_token' => $token], 'user_id=' . $robot_id);
                }
            }
        }
        // echo "888\n\r";
    }

    public function updateTokenAction()
    {
        ini_set('default_socket_timeout', -1);
        set_time_limit(0);
        global $global_redis;
        $global_redis = $this->di->get("redis");
        $global_redis->pSubscribe(array('__keyevent@0__:expired'), array($this, 'callback'));

    }
}