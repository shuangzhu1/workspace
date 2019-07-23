<?php
/**
 * Created by PhpStorm.
 * User: wgwang
 * Date: 14-4-2
 * Time: 上午11:28
 */

namespace Components\WeChat;

use Models\WeChat\MessageSettingKeywords;
use Models\WeChat\MessageSettings;
use Phalcon\Mvc\User\Plugin;

class MessageManager extends Plugin
{

    /**
     * @var MessageManager
     */
    private static $instance = null;

    const PLATFORM_TYPE_WAP = 'wap';
    const PLATFORM_TYPE_WEIXIN = 'wx';
    const PLATFORM_TYPE_WEIBO = 'wb';

    const EVENT_TYPE_SUBSCRIBE = 'subscribe';
    const EVENT_TYPE_UN_SUBSCRIBE = 'unsubscribe';
    const EVENT_TYPE_NORMAL = 'normal';
    const EVENT_TYPE_KEYWORD = 'keyword';
    const EVENT_TYPE_ROBOT = 'robot';
    const EVENT_TYPE_SCAN = 'SCAN';
    const EVENT_TYPE_LOCATION = 'LOCATION';
    const EVENT_TYPE_CLICK = 'CLICK';
    const EVENT_TYPE_VIEW = 'VIEW';
    const EVENT_TYPE_MASS_SEND_JOB_FINISH = 'MASSSENDJOBFINISH';
    const EVENT_TYPE_FOLLOW = "follow";
    const EVENT_TYPE_UN_FOLLOW = 'unfollow';
    const EVENT_TYPE_MENTION = 'mention';
    const EVENT_TYPE_SCAN_FOLLOW = 'scan_follow';

    /**
     * @return MessageManager
     */
    public static function instance()
    {
        if (!self::$instance instanceof MessageManager) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $customer
     * @param string $eventType
     * @return array
     */
    public function getMessageSettings($platform, $customer, $eventType = null, $refresh = false)
    {
        $cache = $this->di->get('memcached');
        $cacheKey = "customer_message_setting_" . $platform;
        if ($eventType) {
            $cacheKey .= $eventType . '_' . $customer;
        } else {
            $cacheKey .= 'all_' . $customer;
        }
        $data = $cache->get($cacheKey);
        if (!$data || $refresh) {
            $data = array();
            if (is_null($eventType) || $eventType != self::EVENT_TYPE_KEYWORD) {
                $filter = "customer_id='{$customer}' AND platform='{$platform}'";
                if (is_string($eventType)) {
                    $filter .= " AND event_type='{$eventType}'";
                }
                $settings = MessageSettings::find(array($filter, 'group_by' => 'event_type'))->toArray();
                if (count($settings) > 0) {
                    foreach ($settings as $setting) {
                        $setting['values'] = ResourceManager::instance()->getMessage($setting['message_type'], $setting['value']);
                        $data[$setting['event_type']] = $setting;
                    }
                }
            } else {
                $keywordSettings = MessageSettingKeywords::find("customer_id='{$customer}' AND platform='{$platform}'")->toArray();
                if (count($keywordSettings) > 0) {
                    foreach ($keywordSettings as $setting) {
                        if ($setting['message_type'] == ResourceManager::MESSAGE_TYPE_TEXT) {
                            $data[MessageManager::EVENT_TYPE_KEYWORD][] = $setting;
                        } else {
                            $setting['message'] = ResourceManager::instance()->getMessage($setting['message_type'], $setting['respond_message']);
                            $data[MessageManager::EVENT_TYPE_KEYWORD][] = $setting;
                        }
                    }
                } else {
                    $data[self::EVENT_TYPE_KEYWORD] = array();
                }
            }
            if (is_string($eventType) && isset($data[$eventType])) {
                $data = $data[$eventType];
            }
            $cache->save($cacheKey, $data);
        }
        return $data;
    }

    public function sendMessage()
    {

    }
} 
