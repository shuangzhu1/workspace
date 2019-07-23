<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/19
 * Time: 18:32
 *
 *
 * https://github.com/apache/kafka/tree/0.8.2/core/src/main/scala/kafka
 * https://github.com/edenhill/librdkafka
 * https://github.com/arnaud-lb/php-rdkafka
 */
namespace Components\Kafka\core;

use \RdKafka;
use Util\Debug;

abstract class AbstractKafka
{
    protected $host = '127.0.0.1:9092';//集群服务器
    protected $group_id = '';
    protected $offset = 'smallest';//初始偏移量
    protected $topic = null;//主题
    protected $topic_config = null; //主题配置
    protected $conf = null;//配置


    protected function __construct($host = '')
    {
        if ($host) {
            $this->host = $host;
        }
    }

    public function setTopic($topic)
    {
        $this->topic = $topic;
        return $this;
    }

    /**设置消费者分组
     * @param $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group_id = $group;
        return $this;
    }

    /**消费者 设置偏移量
     * @param $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    protected function setTopicConfig()
    {
        $conf = new RdKafka\Conf();
        if ($this->group_id) {
            $conf->set('group.id', $this->group_id);
        }
        //添加 kafka集群服务器地址
        $conf->set('metadata.broker.list', $this->host);
//        $conf->set('socket.timeout.ms', 3000);
//        $conf->set('metadata.request.timeout.ms', 3000);
//        $conf->set('session.timeout.ms', 3000);

        $topicConf = new RdKafka\TopicConf();
        //当没有初始偏移量时，从哪里开始读取
        $topicConf->set('auto.offset.reset', $this->offset);
        //$topicConf->set('request.timeout.ms', 3000);
        $topicConf->set('message.timeout.ms', 3000);

        // $topicConf->set('auto.commit.enable', true);

        $conf->setDefaultTopicConf($topicConf);
        $this->topic_config = $topicConf;
        $this->conf = $conf;

    }

    /**
     * 消费者
     * @param $callback --回调函数
     */
    protected function consumer($callback)
    {
        set_time_limit(0);
        //   $start = microtime(true);
        // echo $start;
        // echo "\n\r";
        $this->setTopicConfig();
        // $end = microtime(true);

        //  echo $end - $start;
        //   echo "\n\r";
        $consumer = new RdKafka\KafkaConsumer($this->conf);
        // $end2 = microtime(true);

        // echo $end2 - $end;
        //  echo "\n\r";
        //设置订阅的主题-领红包
        $consumer->subscribe($this->topic);
        //    $end3 = microtime(true);

        // echo $end3 - $end2;
        //  echo "\n\r";

        while (true) {

            $message = $consumer->consume(5 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    call_user_func_array($callback, ['result' => 1, 'message' => $message]);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    //call_user_func_array($callback, ['result' => 0, 'message' => $message]);
                    // echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    //call_user_func_array($callback, ['result' => 0, 'message' => $message]);
                    //  echo "Timed out\n";
                    break;
                default:
                    //call_user_func_array($callback, ['result' => 0, 'message' => $message]);
                    //  throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }

    /**
     * 生产者
     * @param $data
     * @return boolean
     */
    protected function producer($data)
    {
        $res = true;
        if (is_array($data)) {
            $data = json_encode($data);
        }
//        $rk = new \RdKafka\Producer();
//        $rk->setLogLevel(LOG_DEBUG);
//        $rk->addBrokers($this->host);
//        $topic = $rk->newTopic($this->topic);
//        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $data);
        try {
            $this->setTopicConfig();
            $rk = new RdKafka\Producer($this->conf);
            $rk->setLogLevel(LOG_DEBUG);
            $topic = $rk->newTopic($this->topic, $this->topic_config);
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $data);
        } catch (\Exception $e) {
            $res = false;
            Debug::log("kafka生产者生产内容失败:" . $e->getMessage(), 'kafka');
        }
        return $res;
    }
}