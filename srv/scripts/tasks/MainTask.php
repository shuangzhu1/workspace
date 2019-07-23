<?php

use Models\User\UserOnline as UserOnline;
use Models\System\SystemApiCallLog as SystemApiCallLog;
use Util\Debug;
use Models\Social\SocialDiscuss;

/**
 * Created by PhpStorm.
 *
 *
 * $ php /var/www/dvalley/scripts/start.php  main online
 * User: ykuang
 * Date: 17-3-10
 * Time: 下午7:17
 */
class MainTask extends \Phalcon\CLI\Task
{
    protected function onConstruct()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
    }

//    public function callback($result, $message = null)
//    {
//        Debug::log("message:" . var_export($message, true), 'debug');
//    }

    public function testAction()
    {
//        \Components\Kafka\Consumer::getInstance($this->di->get("config")->kafka->host)
//            ->setGroup("test")
//            ->setTopic(['test'])
//            ->consume([$this, 'callback']);
    }

    // * /$ * */1 * * * /usr/local/php/bin/php -f /var/www/dvalley/scripts/start.php main online
    /*******登录登出事件队列处理***********/
    public function onlineAction()
    {
        set_time_limit(0);
        $redis = $this->di->get("redis_queue");
        while ($data = $redis->lPop(\Services\Site\CacheSetting::KEY_USER__ONLINE)) {
            $data = json_decode($data, true);

            if ($data) {
                $time = $data['time'];
                switch ($data['act']) {
                    //登录
                    case "login":
                        $userLogin = new UserOnline();
                        //之前登录没退出 又登录 则认为一直在线
//                        if ($online = UserOnline::exist('user_id=' . $data['uid'] . ' and logout_time=0')) {
//                            /* $online->save(['logout_date' => date('Ymd', $time), 'logout_time' => $time]);
//                             $data = ['logout_date' => date('Ymd', $time), 'logout_time' => $time];
//                             $online->save($data);*/
//                        } else {
//                            //\Services\Site\CurlManager::init()->curl_get_contents("http://gc.ditu.aliyun.com/regeocoding?l=22.566415,113.862747");
//                            $data = ['user_id' => $data['uid'], 'login_date' => date('Ymd', $time), 'login_time' => $time, 'created' => time(), 'login_ip' => $data['ip'] ? $data['ip'] : ''];
//                            $userLogin->insertOne($data);
//                        }
                        if ($online = UserOnline::findOne(['user_id=' . $data['uid'] . ' and login_time=0 and logout_time>' . $time, 'order' => 'logout_time asc', 'columns' => 'logout_time,id'], false, true)) {
                            $data = ['login_date' => date('Ymd', intval($time / 1000)), 'login_time' => $time, 'login_ip' => $data['ip'] ? $data['ip'] : ''];
                            UserOnline::updateOne($data, ['id' => $online['id']]);
                        } else {
                            $data = ['user_id' => $data['uid'], 'login_date' => date('Ymd', intval($time / 1000)), 'login_time' => $time, 'created' => time(), 'login_ip' => $data['ip'] ? $data['ip'] : ''];
                            $userLogin->insertOne($data);
                        }
                        break;
                    //退出
                    case "logout":
                        if ($online = UserOnline::findOne(['user_id=' . $data['uid'] . ' and logout_time=0 and login_time<' . $time, 'order' => 'login_time asc', 'columns' => 'id,created,login_time'], false, true)) {
                            $data = ['logout_date' => date('Ymd', intval($time / 1000)), 'logout_time' => $time, 'logout_ip' => $data['ip'] ? $data['ip'] : ''];
                            UserOnline::updateOne($data, ['id' => $online['id']]);
                        } else {
                            $data = ['user_id' => $data['uid'], 'created' => time(), 'logout_date' => date('Ymd', intval($time / 1000)), 'logout_time' => $time, 'logout_ip' => $data['ip'] ? $data['ip'] : ''];
                            UserOnline::insertOne($data);
                        }
                        break;
                }
            }
        }
    }

    /*******api日志记录***********/
    public function apiLogAction()
    {
        set_time_limit(0);
        $redis = $this->di->get("redis_queue");
        while ($data = $redis->lPop(\Services\Site\CacheSetting::KEY_API_CALL_LOG)) {
            $data = json_decode($data, true);
            if ($data) {
                try {
                    $log = new SystemApiCallLog();
                    $log->insertOne($data);
                } catch (Exception $e) {
                    Debug::log(var_export($e->getMessage(), true), 'back_error');
                    Debug::log(var_export($data, true), 'back_error');
                }

            }
        }
    }

    /*******群活跃度统计***********/
    public function groupStatisticsAction()
    {
        set_time_limit(0);
        \Services\Stat\GroupManager::getInstance()->syncDb(date('Y-m-d'));
    }

    /*******用户注册统计***********/
    //凌晨统计昨天的
    public function userRegisterAction()
    {
        set_time_limit(0);
        $date = date('Ymd', (time() - 86400));
        \Services\Stat\UserManager::getInstance()->statistic($date);
    }

    //云信聊天消息 接收队列处理
    public function messageNotifyAction()
    {
        set_time_limit(0);
        $redis_queue = $this->di->get("redis_queue");
        $redis = $this->di->get('redis');

        while ($data = $redis_queue->lPop(\Services\Site\CacheSetting::KEY_MESSAGE_PUSH_LIST)) {
            $message = new \Models\User\Message();
            $data = json_decode($data, true);
            $redis->hDel(\Services\Site\CacheSetting::KEY_MESSAGE_NOTIFY_LIST, $data['message_id']);
            //发给恐龙君的 未读消息数加1
            if ($data['to_uid'] == \Services\Im\ImManager::ACCOUNT_SYSTEM) {
                $redis->hIncrBy(\Services\Site\CacheSetting::KEY_UNREAD_MESSAGE, $data['from_uid'], 1);
                $redis->hIncrBy(\Services\Site\CacheSetting::KEY_UNREAD_MESSAGE, \Services\Site\CacheSetting::KEY_UNREAD_MESSAGE_TOTAL, 1);
            }
            empty($data['device_id']) && $data['device_id'] = '';
            empty($data['client_type']) && $data['client_type'] = '';
            empty($data['extend_json']) && $data['extend_json'] = '';

            if (!$message->insertOne($data)) {
                Debug::log('消息抄送入库失败' . var_export($data, true) . var_export($message->getMessages(), true), 'im');
                return false;
            }
        }
    }

    //图片鉴黄
    public function checkImgAction()
    {
        \Services\Aliyun\GreenManager::getInstance()->discussCheck();
        \Services\Aliyun\GreenManager::getInstance()->commentCheck();
        \Services\Aliyun\GreenManager::getInstance()->replyCheck();
        \Services\Aliyun\GreenManager::getInstance()->avatarCheck();
    }

    //秀场统计
    public function showStatsAction()
    {
        \Services\User\Show\ShowManager::init()->statistics();
    }

    public function apiCallCountAction()
    {
        $redis = new \Services\Site\CacheSetting();
        $count = $redis->get(\Services\Site\CacheSetting::PREFIX_API_CALL_COUNT, date('Ymd'));
        \Models\Statistics\ApiCallTotalCount::insertOne(['count' => $count, 'ymd' => date('Ymd')]);
    }

    //更新阅读数
    public function updateReadCntAction()
    {
        $redis = $this->di->get('redis');

        //更新数量
        $result = $redis->hGetAll(\Services\Site\CacheSetting::$setting[\Services\Site\CacheSetting::PREFIX_READ_COUNT]['prefix']);
        if ($result) {
            foreach ($result as $key => $item) {
                if ($item > 0) {
                    $arr = explode('#', $key);
                    if (count($arr) != 2) {
                        continue;
                    }
                    $item_id = $arr[1];
                    $type = $arr[0];
                    switch ($type) {
                        //动态
                        case \Services\Social\SocialManager::TYPE_DISCUSS:
                            SocialDiscuss::updateOne('view_cnt=view_cnt+' . $item, 'id=' . $item_id);
                            break;
                        //社区动态
                        case \Services\Social\SocialManager::TYPE_COMMUNITY_DISCUSS:
                            \Models\Community\CommunityDiscuss::updateOne('view_cnt=view_cnt+' . $item, 'id=' . $item_id);
                            break;

                    }

                    $redis->hIncrBy(\Services\Site\CacheSetting::$setting[\Services\Site\CacheSetting::PREFIX_READ_COUNT]['prefix'], $key, '-' . $item);
                } else {
                    $redis->hDel(\Services\Site\CacheSetting::$setting[\Services\Site\CacheSetting::PREFIX_READ_COUNT]['prefix'], $key);
                }
            }
        }

        //插入列表
        $result = $redis->hGetAll(\Services\Site\CacheSetting::$setting[\Services\Site\CacheSetting::PREFIX_READ_LIST]['prefix']);
        $redis->del(\Services\Site\CacheSetting::$setting[\Services\Site\CacheSetting::PREFIX_READ_LIST]['prefix']);
        if ($result) {
            foreach ($result as $key => $item) {
                $arr = explode('#', $key);
                if (count($arr) != 2) {
                    continue;
                } else {
                    $item_id = $arr[1];
                    $type = $arr[0];
                    if ($item) {
                        switch ($type) {
                            //动态
                            case \Services\Social\SocialManager::TYPE_DISCUSS:
                                $log = \Models\Social\SocialDiscussViewLog::findOne(['discuss_id=' . $item_id . " and type=1", 'columns' => 'id,detail']);
                                $item_type = 1;
                                break;
                            //社区动态
                            case \Services\Social\SocialManager::TYPE_COMMUNITY_DISCUSS:
                                $log = \Models\Social\SocialDiscussViewLog::findOne(['discuss_id=' . $item_id . " and type=2", 'columns' => 'id,detail']);
                                $item_type = 2;
                                break;
                        }
                        if (empty($item_type)) {
                            continue;
                        }
                        if (!$log) {
                            $log = new \Models\Social\SocialDiscussViewLog();
                            $log_data = ['discuss_id' => $item_id, 'type' => $item_type, 'detail' => $item];
                            if (!$log->insertOne($log_data)) {
                            }
                        } else {
                            if ($log['detail']) {
                                $detail = json_decode($log['detail'], true);
                                $item = json_decode($item, true);
                                $order_columns = [];
                                foreach ($item as $k => $i) {
                                    $detail[$k] = $i;
                                }
                                foreach ($detail as $d) {
                                    $order_columns[] = $d;
                                }
                                $keys = array_keys($detail);
                                array_multisort(
                                    $order_columns, SORT_DESC, SORT_NUMERIC, $detail, $keys
                                );
                                $detail = array_combine($keys, $detail);
                                if (count($detail) > 200) {
                                    $tmp = [];
                                    $i = 0;
                                    foreach ($detail as $k => $val) {
                                        if ($i < 200) {
                                            $tmp[$k] = $val;
                                        }
                                        $i++;
                                    }
                                    $detail = $tmp;
                                }
                                $detail = json_encode($detail);
                            } else {
                                $detail = $item;
                            }

                            \Models\Social\SocialDiscussViewLog::updateOne(['detail' => $detail], ['id' => $log['id']]);
                        }
                    }
                }


            }
        }


    }


    //凌晨 统计上一天的奖励数据
    public function rewardStatisticAction()
    {
        $date = date('Ymd', (time() - 86400));
        \Services\Site\CashRewardManager::statistic($date);
    }

    //清除接口请求时的记录
    public function clearSignMd5Action()
    {
        $end = date('YmdHi', (time() - 1800));
        $start = date('YmdHi', (time() - 60000));
        $redis = $this->di->get('redis');
        $redis->zRemRangeByScore(\Services\Site\CacheSetting::KEY_SIGN_MD5, $start, $end);
    }

    //短信发送记录入库
    public function smsSendRecordsToMysqlAction()
    {
        $redis = $this->di->get('redis_queue');
        try {
            while ($record = $redis->lPop('sms_send_records')) {
                $record = json_decode($record, true);
                $record['created'] = time();
                \Models\System\SystemSmsSendRecords::insertOne($record);
            }
        } catch (\Exception $e) {
            Debug::log('短信发送记录入库失败：' . $e->getMessage() . "\n数据：" . var_export($record, true));
        }

    }

    // 在线用户清理
    public function userOnlineClearAction()
    {
        $redis = $this->di->get('redis');
        $list = $redis->hGetAll(\Services\Site\CacheSetting::KEY_USER_ONLINE_LIST);
        if ($list) {
            foreach ($list as $item) {
                $item = json_decode($item);
                //超过6小时在线 清除
                if (time() - intval($item->time / 1000) >= 3600 * 6) {
                    $redis->hDel(\Services\Site\CacheSetting::KEY_USER_ONLINE_LIST, $item->uid);
                }
            }
        }
    }

    //每天登录用户个数统计
    public function loginCountPerDayAction()
    {
        $theDay = date('Ymd', strtotime(date('Ymd', time()) . ' -1 day'));//待统计日期
        $login_users = $this->db->query("select user_id,client_ip,ymd from user_login_log where id in(select MAX(id) from user_login_log where ymd = " . $theDay . " group by user_id) order by user_id")->fetchAll(\PDO::FETCH_ASSOC);
        $ips = array_column($login_users, 'client_ip');
        $ipLocation = new \Util\Ip();
        $ipInfo = [];
        //获取ip地理位置
        foreach ($ips as $ip) {
            $ipInfo[$ip] = $ipLocation->find($ip);
        }
        $res = ['users_per_province' => [], 'proportion_of_users' => []];
        foreach ($login_users as $login_user) {
            if (isset($res['users_per_province'][$ipInfo[$login_user['client_ip']]['province']]))
                $res['users_per_province'][$ipInfo[$login_user['client_ip']]['province']] += 1;
            else
                $res['users_per_province'][$ipInfo[$login_user['client_ip']]['province']] = 1;


        }
        //新老用户占比
        $new_user_count = $this->db->query("select count(1) as sum from users where id in (" . implode(',', array_column($login_users, 'user_id')) . ") and  created >= " . strtotime($theDay) . " and created <=" . (strtotime($theDay) + 86400))->fetch(\PDO::FETCH_ASSOC)['sum'];
        $total_user_count = count($login_users);
        $old_user_count = $total_user_count - $new_user_count;
        $res['proportion_of_users']['new'] = (int)$new_user_count;
        $res['proportion_of_users']['old'] = (int)$old_user_count;
        $res['day'] = $theDay;
        $resToJson = json_encode($res, JSON_UNESCAPED_UNICODE);
        $this->db_statistics->execute("insert into user_login_day value(null,$theDay,'$resToJson')");
    }

    //站内文档访问记录入库
    public function articleViewLogToMysqlAction()
    {
        $redis = $this->di->get('redis');
        //访问次数入库
        $records = $redis->hGetAll(\Services\Site\CacheSetting::KEY_SITE_ARTICLE_VIEW_LOG . ':count');
        foreach ($records as $k1 => $v1) {
            $res = \Models\Site\SiteArticle::updateOne(['view_cnt' => 'view_cnt+' . $v1], ['param' => $k1]);
            if ($res)
                $redis->hIncrBy(\Services\Site\CacheSetting::KEY_SITE_ARTICLE_VIEW_LOG . ':count', $k1, (int)(-$v1));
        }
        //访问者记录入库
        $viewers = $redis->hGetAll(\Services\Site\CacheSetting::KEY_SITE_ARTICLE_VIEW_LOG . ':list');
        foreach ($viewers as $k2 => $v2) {
            $time = (int)$k2 / 1000;
            $arr = json_decode($v2, true);
            $res = \Models\Site\SiteArticleViewLog::insertOne(['uid' => $arr['viewer'], 'article' => $arr['article'], 'view_time' => $time, 'created' => time()]);
            if ($res)
                $redis->hDel(\Services\Site\CacheSetting::KEY_SITE_ARTICLE_VIEW_LOG . ':list', $k2);
        }
    }

    //店铺访问记录入库
    public function shopVisitLogPersistentAction()
    {
        $redis_queue = $this->di->get('redis_queue');
        while ($log = $redis_queue->rPop(\Services\Site\CacheSetting::KEY_SHOP_VISIT_LOG)) {
            $log = json_decode($log, true);
            $res = \Models\Shop\ShopVisitLog::insertOne(['shop_id' => $log['shop_id'], 'uid' => $log['uid'], 'visit_time' => $log['visit_time'], 'created' => time()]);
            if (!$res)
                $redis_queue->lPush(\Services\Site\CacheSetting::KEY_SHOP_VISIT_LOG, json_encode($log));
        }
    }


}
