<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/22
 * Time: 15:02
 */

namespace Services\Site;


use Phalcon\Mvc\User\Plugin;
use Services\Shop\ShopManager;
use Services\User\UserStatus;
use Util\Debug;

/**
 * @property \Phalcon\Db\AdapterInterface $original_mysql
 **/
class SearchManager extends Plugin
{
    private $k = '';//关键字
    private $page = 1;//第几页
    private $limit = 20;//每页显示的数量
    private $uid = 0;//用户id
    private $type = 0;//0-全部 1-用户 2-店铺
    private $lng = 0;//经度
    private $lat = 0;//纬度


    public function __construct($uid, $type = 0, $k = '', $lng, $lat, $page = 1, $limit = 20)
    {
        $this->uid = $uid;
        $this->k = trim($k);
        $this->page = $page;
        $this->limit = $limit;
        $this->type = $type;
        $this->lng = $lng;
        $this->lat = $lat;
    }

    //用户搜索
    public function user()
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $where = 'u.status=' . UserStatus::STATUS_NORMAL; //. " and  t.tags_name is not null ";
        if ($this->k) {
            $where .= " and (u.username like '%" . $this->k . "%' or t.tags_name like '%" . $this->k . "%')";
        }
        $list = $this->original_mysql->query("select GetDistances(lat,lng," . $this->lat . "," . $this->lng . ") as distance,l.user_id as uid,lng,lat,u.username,u.avatar,tags_name,u.id as uid from users as u left join  user_tags as t on t.user_id=u.id left join user_location as l on u.id=l.user_id   where " . $where . " order by distance asc limit " . ($this->page - 1) * ($this->limit) . ',' . ($this->limit))->fetchAll(\PDO::FETCH_ASSOC);
        if ($list) {
            foreach ($list as $item) {
                !$item['tags_name'] && $item['tags_name'] = '';
                !$item['distance'] && $item['distance'] = rand(100, 5000);
                !$item['lng'] && $item['lng'] = 0;
                !$item['lat'] && $item['lat'] = 0;

                $res['data_list'][] = $item;
            }
//            echo $where;
//            echo "select count(1) as count from user_tags as t left join  users as u on t.user_id=u.id left join user_location as l on u.id=l.user_id   where " . $where;
//            exit;
//            var_dump($this->original_mysql->query("select count(1) as count from user_tags as t left join  users as u on t.user_id=u.id left join user_location as l on u.id=l.user_id   where " . $where)->fetch());
//            exit;
            //  $res['data_list'] = $list;
            $res['data_count'] = intval($this->original_mysql->query("select count(1) as count from users as u left join user_tags as t on t.user_id=u.id left join user_location as l on u.id=l.user_id   where " . $where)->fetch()['count']);
        }
        return $res;

    }

    //店铺搜索
    public function shop()
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $where = 'status=' . ShopManager::status_normal;
        if ($this->k) {
            $where .= " and (name like '%" . $this->k . "%')";
        }
        $list = $this->original_mysql->query("select id as shop_id, GetDistances(lat,lng," . $this->lat . "," . $this->lng . ") as distance,user_id as uid,lng,lat,name,images,brief from shop as s  where " . $where . " order by distance asc limit " . ($this->page - 1) * ($this->limit) . ',' . ($this->limit))->fetchAll(\PDO::FETCH_ASSOC);
        if ($list) {
            $res['data_list'] = $list;
            $res['data_count'] = intval($this->original_mysql->query("select count(1) as count from shop   where " . $where)->fetch()['count']);
        }
        return $res;
    }

    //
    public function complex()
    {
        if ($this->k == '') {
            return ['data_list' => [], 'data_count' => 0];
        }
        if ($this->type == 1) {
            $this->log();
            return $this->user();
        }
        if ($this->type == 2) {
            $this->log();
            return $this->shop();
        } else {
            return ['data_list' => [], 'data_count' => 0];
        }
    }

    //搜索日志
    public function log()
    {
        if (!$this->k) {
            return false;
        }
        // 日志根路径
        $log_path = ROOT;
        $filepath = $log_path . '/Cache/search/' . date('Ym') . '/history.log';
        $message = date('YmdHis') . ' k:' . $this->k . " t:" . $this->type . "\n";
        $base = dirname($filepath);
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }

        if (!file_exists($filepath)) {
            $newfile = TRUE;
        }

        if (!$fp = fopen($filepath, 'a+')) {
            return FALSE;
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (isset($newfile) && $newfile === TRUE) {
            @chmod($filepath, 0777);
        }

        return TRUE;
    }
}