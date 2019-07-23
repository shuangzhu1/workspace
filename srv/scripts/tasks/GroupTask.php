<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/13
 * Time: 11:36
 */
class GroupTask extends \Phalcon\CLI\Task
{
    //进程执行函数
    function doWeekProcess(swoole_process $worker)
    {
        $recv = $worker->pop();//默认是8192个长度
        $recv = json_decode($recv, true);
        \Services\Stat\GroupManager::getInstance()->statisticsWeek($recv['date'], $recv['data']['start'], $recv['data']['end']);
        /*  var_dump($recv);
          echo "从主进程获取到的数据: " . $recv . "---true pid" . $worker->pid . PHP_EOL;*/
        // echo "从主进程获取到的数据: " . "---true pid" . $worker->pid . PHP_EOL;
        echo "子进程完成:" . $recv['data']['start'] . "-" . $recv['data']['end'];
        /* sleep(20);*/
        $worker->exit(0);
    }

    //一周内统计
    public function sWeekAction($args)
    {
        set_time_limit(0);

        $date = @$args[0];
        $date = $date ? $date : date('Y-m-d', (time() - 86400));

        $start_gid = \Models\Group\Group::findOne(['status=' . \Services\User\GroupManager::GROUP_STATUS_NORMAL, 'order' => 'id asc', 'columns' => 'id']);
        $end_gid = \Models\Group\Group::findOne(['status=' . \Services\User\GroupManager::GROUP_STATUS_NORMAL . " and created<=" . (strtotime($date) + 86400), 'order' => 'id desc', 'columns' => 'id']);
        // $this->db->close();
        $res = self::chunk($start_gid['id'], $end_gid['id'], 100);
        for ($i = 0; $i < $res['count']; $i++) {
            echo "开始:" . $res['list'][$i]['start'] . "-" . $res['list'][$i]['end'] . "\r\n";
            \Services\Stat\GroupManager::getInstance()->statisticsWeek($date, $res['list'][$i]['start'], $res['list'][$i]['end']);
            echo "完成:" . $res['list'][$i]['start'] . "-" . $res['list'][$i]['end'] . "\r\n";
        }
        echo "完成";
//        exit();
//        $manager = new \Services\pcntl\taskManager();
//        for ($i = 0; $i < $res['count']; $i++)
//            $manager->add_task(new \Services\pcntl\taskMain(function () use ($date, $res, $i) {
//                $this->db->connect();
//                echo "开始:" . $res['list'][$i]['start'] . "-" . $res['list'][$i]['end'] . "\r\n";
//                \Services\Stat\GroupManager::getInstance()->statisticsWeek($date, $res['list'][$i]['start'], $res['list'][$i]['end']);
//                echo "完成:" . $res['list'][$i]['start'] . "-" . $res['list'][$i]['end'] . "\r\n";
//                //   sleep(rand(1, 10));
//                //  echo "time:" . $i . "\n\r";
//            }));
//
//        $manager->run();

        exit;
//        $workers = [];//进程仓库
//        $worker_num = $res['count'];//最大进程数
//        for ($i = 0; $i < $worker_num; $i++) {
//            //第三个参数改为false，才能实现进程通讯
//            //创建子进程
//            // $process = new swoole_process(array($this, 'doWeekProcess'), false, false);
//            //开启队列,类似于全局函数
//            // $process->useQueue();
//            // $pid = $process->start();
//            //$workers[$pid] = ['process' => $process, 'data' => $res['list'][$i]];
//
//        }
//        echo "完成";
//        exit;
        //主进程 向子进程添加
        /*  foreach ($workers as $pid => $process) {
              $process['process']->push(json_encode(['data' => $process['data'], 'date' => $date]));
          }

          //等待子进程结束回收资源
          for ($i = 0; $i < $worker_num; $i++) {
              $ret = swoole_process::wait();//等待执行完成
              $pid = $ret['pid'];
              unset($workers[$pid]);
              echo "子进程退出 $pid" . PHP_EOL;
          }

          echo "this is the end" . PHP_EOL;*/
        //  exit;

    }

    public function testAction($args)
    {

        set_time_limit(0);

        $date = @$args[0];
        $date = $date ? $date : date('Y-m-d', (time() - 86400));

        $start_gid = \Models\Group\Group::findOne(['status=' . \Services\User\GroupManager::GROUP_STATUS_NORMAL, 'order' => 'id asc', 'columns' => 'id']);
        $end_gid = \Models\Group\Group::findOne(['status=' . \Services\User\GroupManager::GROUP_STATUS_NORMAL . " and created<=" . (strtotime($date) + 86400), 'order' => 'id desc', 'columns' => 'id']);
        $res = self::chunk($start_gid['id'], $end_gid['id'], 100);

        /* for ($i = 0; $i < $res['count']; $i++) {
             echo "开始:" . $res['list'][$i]['start'] . "-" . $res['list'][$i]['end'] . "\r\n";
             \Services\Stat\GroupManager::getInstance()->statisticsWeek($date, $res['list'][$i]['start'], $res['list'][$i]['end']);
             echo "完成:" . $res['list'][$i]['start'] . "-" . $res['list'][$i]['end'] . "\r\n";
         }
         echo "完成";
         exit();*/

        $manager = new \Services\pcntl\taskManager();
        for ($i = 0; $i < 10; $i++)
            $manager->add_task(new \Services\pcntl\taskMain(function () use ($date, $i, $res) {
                try {

                    \Models\Group\Group::getColumn(['id>=' . $res['list'][$i]['start'] . ' and id<=' . $res['list'][$i]['end'] . " and status=" . \Services\User\GroupManager::GROUP_STATUS_NORMAL . " and created<=" . time(), 'columns' => 'id'], 'id', '');
                } catch (\Exception $e) {
                    var_dump($e->getMessage());
                }
                //\Models\Shop\ShopGoods::getColumn(['id>=' . $res['list'][$i]['start'] . ' and id<=' . $res['list'][$i]['end'] . " and status=" . \Services\User\GroupManager::GROUP_STATUS_NORMAL . " and created<=" . time(), 'columns' => 'id'], 'id', '');
                //$this->db->query("");
                //   sleep(rand(1, 10));
                //  echo "time:" . $i . "\n\r";
            }));
        $manager->run();
    }

    public function chunk($start, $end, $chunk_number = 1000)
    {
        $res = ['count' => 0, 'list' => []];
        $count = $end - $start;
        //总共数量小于一片的数量
        if ($count <= $chunk_number) {
            $res = ['count' => 1, 'list' => ['start' => $start, 'end' => $end]];
        } else {
            $offset = $start;
            while ($offset < $end) {
                $res['count'] += 1;
                if ($offset + $chunk_number <= $end) {
                    $res['list'][] = ['start' => $offset, 'end' => $offset + $chunk_number];
                } else {
                    $res['list'][] = ['start' => $offset, 'end' => $end];
                }
                $offset = $offset + $chunk_number + 1;
            }
        }
        return $res;
    }

    public function asyncConsumerAction()
    {
        set_time_limit(0);
        \Components\Kafka\Consumer::getInstance($this->di->get("config")->kafka->host)
            ->setGroup("group")
            ->setTopic(['community_create_group'])
            ->consume([$this, 'asyncCallbackProgress']);
    }

    /**创建群聊进程
     * @param $result
     * @param object $message
     */
    public function asyncCallbackProgress($result, $message = null)
    {
        if ($result == 1) {
            //社群推送
            if ($message->topic_name == Services\Kafka\TopicDefine::TOPIC_COMMUNITY_GROUP_CREATE) {
                $data = (array)json_decode($message->payload);
                //创建群聊流程
                \Services\Community\CommunityGroupManager::getInstance(true)->checkGroupCreateApplyAsync($data);
            }
            //\Util\Debug::log("consumer:result->" . $result . "message:" . var_export($data, true), 'kafka_consumer');
            #\Util\Debug::log("consumer:result->" . $result . "message:" . $data['type'], 'kafka_consumer');
        }
    }

}