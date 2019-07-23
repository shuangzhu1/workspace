<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/7
 * Time: 17:00
 */
class SwooleTask extends \Phalcon\CLI\Task
{
    public function websocketAction()
    {
        $serv = new Swoole\Websocket\Server("0.0.0.0", 9503);

        $serv->on('Open', function ($server, $req) {
            echo "connection open: " . $req->fd;
        });

        $serv->on('Message', function ($server, $frame) {
            echo "message: " . $frame->data;
            $server->push($frame->fd, json_encode(["hello", "world"]));
        });

        $serv->on('Close', function ($server, $fd) {
            echo "connection close: " . $fd;
        });

        $serv->start();
    }

    public function testAction()
    {
        ini_set('mysql.connect_timeout', 50);
        ini_set('default_socket_timeout', -1);
        // $data = \Models\User\Users::findOne(["id=50000", 'columns' => 'username']);
        // var_dump($this->db->query("show VARIABLES like '%time%'")->fetchAll(\PDO::FETCH_ASSOC));

        while (true) {
            $data = \Models\User\Users::findOne(['id=50000']);
            echo($data['username']);
            sleep(2);
        }
    }

    public function bAction()
    {
        $i = 0;
        $api = \Components\Yunxin\ServerAPI::init();
        while ($list = \Models\User\Users::findList(['offset' => ($i * 500), 'limit' => 500, 'columns' => 'id'])) {
            foreach ($list as $item) {
                $res = $api->listBlackFriend($item['id']);
                if ($res && $res['blacklist']) {
                    // print_r($res['blacklist']);
                    $blacklist = \Models\User\UserBlacklist::getColumn(['owner_id=' . $item['id'], 'user_id'], 'user_id');
                    if ($blacklist) {
                        foreach ($res['blacklist'] as $b) {
                            if (!in_array($b, $blacklist)) {
                                $tmp_res = $api->specializeFriend($item['id'], $b, 1, 0);
                                \Util\Debug::log("from:" . $item['id'] . ",to:" . $b . ',res:' . var_export($tmp_res, true), "debug");
                            }
                        }
                    } else {
                        foreach ($res['blacklist'] as $b) {
                            $tmp_res = $api->specializeFriend($item['id'], $b, 1, 0);
                            \Util\Debug::log("from:" . $item['id'] . ",to:" . $b . ',res:' . var_export($tmp_res, true), 'debug');
                        }
                    }

                }
            }
            $i++;
        }
    }

//进程执行函数
    function doProcess(swoole_process $worker)
    {
        //    $recv = $worker->pop();//默认是8192个长度
        //  echo "从主进程获取到的数据: " . $recv . "---true pid" . $worker->pid . PHP_EOL;
        echo "从主进程获取到的数据: " . "---true pid" . $worker->pid . PHP_EOL;

        /* sleep(20);*/
        $worker->exit(0);
    }

    public function processAction()
    {
        $workers = [];//进程仓库
        $worker_num = 10;//最大进程数

        for ($i = 0; $i < $worker_num; $i++) {
            //第三个参数改为false，才能实现进程通讯
            $process = new swoole_process(array($this, 'doProcess'), false, false);//创建子进程
            // $process->useQueue();//开启队列,类似于全局函数
            $pid = $process->start();
            $workers[$pid] = $process;
        }

//主进程 向子进程添加
        /*       foreach ($workers as $pid => $process) {
                   $process->push("hello 子进程 $pid");
               }*/

//等待子进程结束回收资源
        for ($i = 0; $i < $worker_num; $i++) {
            $ret = swoole_process::wait();//等待执行完成
            $pid = $ret['pid'];
            unset($workers[$pid]);
            echo "子进程退出 $pid" . PHP_EOL;
        }

        echo "this is the end" . PHP_EOL;
    }
}