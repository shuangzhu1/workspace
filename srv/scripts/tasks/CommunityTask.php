<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/10
 * Time: 11:09
 */
class CommunityTask extends \Phalcon\CLI\Task
{
    protected function onConstruct()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
    }

    /**
     * @param $result
     * @param object $message
     */
    public function callback($result, $message = null)
    {
        if ($result == 1) {
            //社群推送
            if ($message->topic_name == Services\Kafka\TopicDefine::TOPIC_COMMUNITY_GROUP_PUSH) {
                $data = (array)json_decode($message->payload);
                //社区新闻推送给群
                if ($data['type'] == \Services\Community\CommunityImManager::TYPE_COMMUNITY_NEWS) {
                    \Services\Community\CommunityGroupManager::getInstance(true)->pushNews($data['comm_id'], $data['item_id']);
                }
            } //社群解散
            else if ($message->topic_name == Services\Kafka\TopicDefine::TOPIC_COMMUNITY_GROUP_DISSOLVE) {
                $data = (array)json_decode($message->payload);
                \Services\Community\CommunityGroupManager::getInstance(true)->sendGroupDissolveMsg($data['gid']);
            }

            //\Util\Debug::log("consumer:result->" . $result . "message:" . var_export($data, true), 'kafka_consumer');
            #\Util\Debug::log("consumer:result->" . $result . "message:" . $data['type'], 'kafka_consumer');
        }
//        if ($result == 1) {
//            var_dump($message);
//        }
    }

    public function groupPushAction()
    {
        set_time_limit(0);
        \Components\Kafka\Consumer::getInstance($this->di->get("config")->kafka->host)
            ->setGroup("community")
            ->setTopic([\Services\Kafka\TopicDefine::TOPIC_COMMUNITY_GROUP_PUSH, \Services\Kafka\TopicDefine::TOPIC_COMMUNITY_GROUP_DISSOLVE])
            ->consume([$this, 'callback']);
    }
}