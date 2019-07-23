<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 *
 *  * $ php /var/www/dvalley/scripts/start.php  daemon online
 * Date: 2017/7/19
 * Time: 15:13
 */
use Services\Social\SocialManager;
use Services\Site\CacheSetting;
use Util\Debug;
use Services\User\ContactManager;
use Services\User\RewardManager;

class DaemonTask extends \Phalcon\CLI\Task
{
    //点赞、取消赞
    public function callback($instance, $channelName, $message)
    {
        switch ($channelName) {
            //点赞 取消赞
            case  CacheSetting::KEY_LIKE:
                $message = json_decode($message, true);
                //   Debug::log("channelNAme:" . var_export($message, true), 'debug');
                if ($message['is_add']) {
                    $res = SocialManager::init(true)->like($message['uid'], $message['item_id'], $message['type']);
                } else {
                    $res = SocialManager::init(true)->dislike($message['uid'], $message['item_id'], $message['type']);
                }

                //  Debug::log("res:" . var_export($res, true), 'debug');
                break;
            //关注
            case  CacheSetting::KEY_ATTENTION:
                $message = json_decode($message, true);
                \Util\Debug::log("channelNAme:" . var_export($message, true), 'debug');
                $res = ContactManager::init(true)->attention($message['uid'], $message['to_uid'], $message['source']);
                //  Debug::log("res:" . var_export($res, true), 'debug');
                break;
            //打赏
            case  CacheSetting::KEY_REWARD:
                $message = json_decode($message, true);
                \Util\Debug::log("channelNAme:" . var_export($message, true), 'debug');
                $res = RewardManager::getInstance()->to($message['uid'], $message['to_uid'], $message['type'], $message['gift_info'], $message['package_info'], $message['item_type'], $message['item_id']);
                Debug::log("res:" . var_export($res, true), 'debug');
        }

        // \Util\Debug::log("message:" . $message, 'debug');
        //\Util\Debug::log("channelNAme:" . $channelName, 'debug');


        //  \Util\Debug::log("args:" . var_export(func_get_args(), true) . (date('Y-m-d H:i:s')), 'debug');
    }

    public function testAction()
    {
        ini_set('default_socket_timeout', -1);
        $redis = $this->di->get("publish_queue");
        global $global_redis;
        $global_redis = $this->di->get("redis");

        $redis->subscribe([
            \Services\Site\CacheSetting::KEY_LIKE,
            \Services\Site\CacheSetting::KEY_ATTENTION,
            \Services\Site\CacheSetting::KEY_REWARD,
        ],
            array($this, "callback"));

        /*  $this->db->begin();
          $detail = $this->db->query('select * from social_like where item_id=4466 and type="discuss" and user_id=61652 for update')->fetchAll(\PDO::FETCH_ASSOC);
          sleep(60);
          $this->db->commit();*/
        //\Util\Debug::log("test:" . time().(date('Y-m-d H:i:s')), 'debug');


        //处理业务代码

        /*  while (true) {
              \Util\Debug::log("test:" . time().(date('Y-m-d H:i:s')), 'debug');
              sleep(5);
          }*/
    }
}
