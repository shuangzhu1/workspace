<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/14
 * Time: 16:56
 */
class ViewerTask extends \Phalcon\CLI\Task
{
    protected function onConstruct()
    {
        global $global_redis;
        $global_redis = $this->di->get("redis");
    }

    //商品访客数据入库
    public function insertGoodDbAction()
    {
        set_time_limit(0);
        $redis = $this->di->get("redis_queue");
        $date = date('Ymd', (time() - 86400));
        $table = \Services\Site\CacheSetting::KEY_VIEWER . "good_" . $date;
        echo $table;
        $keys = $redis->hKeys($table);
        if ($keys) {
            $GoodViewer = new \Models\Viewer\GoodViewer();
            $sqlModal = new  \Models\CreateTable();
            foreach ($keys as $k) {
                $values = $redis->hGet($table, $k);
                $values = json_decode($values, true);
                $tb_name = "good_viewer_" . $k; //表名
                if (!\Models\Viewer\ViewerTb::exist("table_name='" . $tb_name . "'")) {
                    var_dump($sqlModal->createGoodViewTable($k));exit;
                }
                $source = $GoodViewer->setSource($tb_name);
                foreach ($values as $uid => $time) {
                    var_dump($source->insertOne(['user_id' => $uid, 'ymd' => $date, 'count' => count($time), 'times' => implode(',', $time), 'f_time' => current($time), 'l_time' => end($time)]));
                    exit;
                }
            }
        }
    }
    //店铺访客数据入库
    public function insertShopDbAction()
    {
        set_time_limit(0);
        $redis = $this->di->get("redis_queue");
        $date = date('Ymd', (time() - 86400));
        $table = \Services\Site\CacheSetting::KEY_VIEWER . "shop_" . $date;
        $keys = $redis->hKeys($table);
        if ($keys) {
            $GoodViewer = new \Models\Viewer\GoodViewer();
            $sqlModal = new  \Models\CreateTable();
            foreach ($keys as $k) {
                $values = $redis->hGet($table, $k);
                $values = json_decode($values, true);
                $tb_name = "shop_viewer_" . $k; //表名
                if (!\Models\Viewer\ViewerTb::exist("table_name='" . $tb_name . "'")) {
                    $sqlModal->createGoodViewTable($k);
                }
                $source = $GoodViewer->setSource($tb_name);
                foreach ($values as $uid => $time) {
                    $source->insertOne(['user_id' => $uid, 'ymd' => $date, 'count' => count($time), 'times' => implode(',', $time), 'f_time' => current($time), 'l_time' => end($time)]);
                }
            }
        }
    }
}