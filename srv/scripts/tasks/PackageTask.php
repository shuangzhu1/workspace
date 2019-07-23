<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/8
 * Time: 15:46
 */
class PackageTask extends \Phalcon\CLI\Task
{
    //抢红包等异步消息队列-消息接收
    public function notifyAction()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
        ini_set('default_socket_timeout', -1);
        set_time_limit(0);
        $conf = new RdKafka\Conf();

        // 当有新的消费进程加入或者退出消费组时，kafka 会自动重新分配分区给消费者进程，这里注册了一个回调函数，当分区被重新分配时触发
//        $conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
//            switch ($err) {
//                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
//                    echo "Assign: ";
//                    var_dump($partitions);
//                    $kafka->assign($partitions);
//                    break;
//
//                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
//                    echo "Revoke: ";
//                    var_dump($partitions);
//                    $kafka->assign(NULL);
//                    break;
//
//                default:
//                    throw new \Exception($err);
//            }
//        });

        // 配置groud.id 具有相同 group.id 的consumer 将会处理不同分区的消息，所以同一个组内的消费者数量如果订阅了一个topic， 那么消费者进程的数量多于 多于这个topic 分区的数量是没有意义的。
        $system_config = $this->di->get("config");

        $conf->set('group.id', 'php');
        //添加 kafka集群服务器地址
        $conf->set('metadata.broker.list', $system_config->kafka->host);
        $topicConf = new RdKafka\TopicConf();
        //当没有初始偏移量时，从哪里开始读取
        $topicConf->set('auto.offset.reset', 'smallest');
        // Set the configuration to use for subscribed/assigned topics
        $conf->setDefaultTopicConf($topicConf);
        $consumer = new RdKafka\KafkaConsumer($conf);
        //设置订阅的主题-领红包
        $consumer->subscribe(['grab_square_rb', 'new_activity']);

        while (true) {
            $message = $consumer->consume(120 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    \Util\Debug::log("topic name:" . $message->topic_name);
                    //抢红包成功
                    if ($message->topic_name == 'grab_square_rb') {
                        $data = $message->payload;
                        $data = json_decode($data, true);
                        $device_id = !empty($data['device_id']) ? $data['device_id'] : '';
                        \Services\User\SquareManager::init(true)->pickSuccess($data['uid'], $data['id'], $data['created'], $data['money'], $device_id);
                    } //发布新活动
                    else if ($message->topic_name == 'new_activity') {
                        $data = $message->payload;
                        $data = json_decode($data, true);
                        \Util\Debug::log("topic data:" . var_export($data, true));
                        if (!empty($data['money'])) {
                            $behavior = 0;
                            switch ($data['money']) {
                                case 100:
                                    $behavior = \Services\User\Square\SquareTask::TASK_SEND_ACTIVITY_1;
                                    break;
                                case 500:
                                    $behavior = \Services\User\Square\SquareTask::TASK_SEND_ACTIVITY_2;
                                    break;
                                case 1000:
                                    $behavior = \Services\User\Square\SquareTask::TASK_SEND_ACTIVITY_3;
                                    break;
                                case 5000:
                                    $behavior = \Services\User\Square\SquareTask::TASK_SEND_ACTIVITY_4;
                                    break;
                                case 10000:
                                    $behavior = \Services\User\Square\SquareTask::TASK_SEND_ACTIVITY_5;
                                    break;
                            }
                            if ($behavior) {
                                \Services\User\Square\SquareTask::init(true)->executeRule($data['uid'], $data['device_id'], $behavior);
                            }
                        }

                    }
                    //   echo $message->payload . "\n\r";
                    //var_dump($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    // echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    //  echo "Timed out\n";
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }



//    public function notify2Action()
//    {
//        ini_set('default_socket_timeout', -1);
//        set_time_limit(0);
//        $rk = new RdKafka\Consumer();
//        $rk->setLogLevel(LOG_DEBUG);
//        $rk->addBrokers("120.76.47.205:9092");
//        $topic = $rk->newTopic("grab_square_rb");
//        //  $topic->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);
//        // $topic->consumeStart(0, -1);
//         $topic->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);
//        while (true) {
//            echo "1\n\r";
//            //  $topic->consumeStart(0, -2);
//            $msg = $topic->consume(0, 1000);
//            echo "2\n\r";
//            switch ($msg->err) {
//                case RD_KAFKA_RESP_ERR_NO_ERROR:
//                    var_dump($msg);
//                    break;
//                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
//                    echo "No more messages; will wait for more\n";
//                    break;
//                case RD_KAFKA_RESP_ERR__TIMED_OUT:
//                    echo "Timed out\n";
//                    break;
//                default:
//                    throw new \Exception($msg->errstr(), $msg->err);
//                    break;
//            }
//
//            echo "5\n\r";
//        }
//    }

//    public function producerAction()
//    {
//        $rk = new RdKafka\Producer();
//        $rk->setLogLevel(LOG_DEBUG);
//        $rk->addBrokers("127.0.0.1:9092");
//        $topic = $rk->newTopic("test");
//        for ($i = 0; $i < 10; $i++) {
//            ($topic->produce(RD_KAFKA_PARTITION_UA, 0, "good Message $i"));
//        }
//    }
    //发布机器人红包
    public function sendPackageAction()
    {
        set_time_limit(0);
        $config = \Services\Site\SiteKeyValManager::init()->getCacheValByKey(\Services\Site\SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting');
        $keep_useful_count = intval($config['keep_count']);//保持多少个红包
        if ($keep_useful_count > 0) {
            $count = \Models\Square\RedPackage::dataCount("is_rob=1 and deadline>" . time() . " and created_ymd=" . date('Ymd') . " and status=" . \Services\User\SquareManager::STATUS_NORMAL);
            $total_money = \Models\Statistics\SiteCashRewardTotal::findOne(["ymd=" . date('Ymd') . " and type=" . \Services\Site\CashRewardManager::TYPE_ROBOT_SQUARE_PACKAGE, 'columns' => 'money']);
            //限制范围内
            if ($total_money['money'] >= intval($config['money_limit']) * 100) {
                exit;
            }
            if ($count < $keep_useful_count) {
                $need_add_count = $keep_useful_count - $count;
                for ($i = $need_add_count; $i > 0; $i--) {
                    \Services\User\SquareManager::init(true)->sendRobotPackage();
                }
            }
        }
        exit;
    }

    //发布节假日红包
    public function sendFestivalAction($args)
    {
        set_time_limit(0);
        $id = @$args[0];
        \Util\Debug::log("packageTask sendFestivalPackage->id:" . $id, 'package');

        if ($id) {
            \Services\User\SquareManager::init(true)->publishFestivalPackage($id);

        }
    }

    //每日凌晨统计上日数据
    public function dayStatAction($args)
    {
        set_time_limit(0);
        $date = @$args[0];
        $date = $date ? $date : date('Ymd', (time() - 86400));
        \Services\User\SquareManager::init(true)->dayStat($date);
        exit;
    }
}