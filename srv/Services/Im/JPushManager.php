<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 17:16
 */

namespace Services\Im;


use JPush\Exceptions\APIConnectionException;
use JPush\Exceptions\APIRequestException;
use Phalcon\Mvc\User\Plugin;
use JPush\Client as JPush;
use Util\Debug;

class JPushManager extends Plugin
{

    private static $instance = null;

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //推送消息
    /**
     * @param $msg -消息内容
     * @param $platform -平台 all/array('ios','android')/array('android')/array('ios')
     * @param $registration_id -账号id 可以是数组
     * @param $extend_msg -扩展消息类型  array('title'=>'', 'content_type', 'extras')
     * @param $android_msg -通知栏样式展示  array('title', 'build_id', 'extras')
     * @param $ios_msg -通知栏样式展示  array('sound', 'badge', 'content-available', 'mutable-content', category', 'extras')
     * @return array|bool
     */
    public function pushMessage($msg, $platform, $registration_id = '', $extend_msg = '', $android_msg = '', $ios_msg = '')
    {
        $client = new JPush($this->di->get('config')->JPush->app_key, $this->di->get('config')->JPush->master_secret);
        try {
            $push_payload = $client->push();
            if ($registration_id) {
                $push_payload->setPlatform('all');
                $push_payload->addRegistrationId($registration_id);
            } else {
                $push_payload->setPlatform($platform);
                $push_payload->addAllAudience();
            }
            $push_payload->setNotificationAlert($msg);
            if ($extend_msg) {
                $push_payload->message($extend_msg);
            }
            if ($android_msg) {
                $push_payload->androidNotification($msg, $android_msg);
            }
            if ($ios_msg) {
                $push_payload->iosNotification($msg, $ios_msg);
            }
            $push_payload->options([]);//

            $response = $push_payload->send();
            return $response;
        } catch (APIConnectionException $e) {
            Debug::log(var_export($e->getMessage(), true), 'JPush');
            return false;
        } catch (APIRequestException $e) {
            Debug::log(var_export($e->getMessage(), true), 'JPush');
            return false;
        }
    }
}