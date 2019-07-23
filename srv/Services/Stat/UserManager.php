<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/15
 * Time: 15:19
 */

namespace Services\Stat;


use Components\Curl\CurlManager;
use Models\Statistics\UserLoginDay;
use Models\Statistics\UserOnlineDay;
use Models\Statistics\UserRegister;
use Models\User\UserLoginLog;
use Models\User\UserOnline;
use Phalcon\Mvc\User\Plugin;
use Services\User\UserStatus;
use Util\Ip;

class UserManager extends Plugin
{
    static $province = [
        "其他", "河北", "河南", "湖北", "湖南", "江苏",
        "江西", "辽宁", "吉林", "黑龙江", "陕西", "山西",
        "山东", "四川", "青海", "安徽", "海南", "广东",
        "贵州", "浙江", "福建", "台湾", "甘肃", "云南",
        "西藏", "宁夏", "广西", "新疆", "内蒙古", "香港",
        "澳门", "北京", "天津", "上海", "重庆"
    ];
    private static $instance = null;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //注册统计
    public function statistic($start_date)
    {
        set_time_limit(0);
        $where = 'u.user_type=' . UserStatus::USER_TYPE_NORMAL;
        if (!$start_date) {
            return;
        }
        $where .= " and u.created>=" . strtotime($start_date) . " and u.created<=" . (strtotime($start_date) + 86400);
        $data = ['total' => 0, 'uids' => [], 'province' => [], 'uid_province' => []];
        $res = $this->db->query("select p.register_ip,p.user_id from users as u left join user_profile as p on u.id=p.user_id where " . $where)->fetchAll(\PDO::FETCH_ASSOC);
        if ($res) {
            foreach ($res as $item) {
                $data['uids'][] = $item['user_id'];
                $data['total'] += 1;
                $province = "其他";
                if ($item['register_ip']) {
                    //获取地址
                    $res = CurlManager::init()->curl_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=" . $item['register_ip']);
                    $address = json_decode($res['data'], true);
                    if ($address && $address['province']) {
                        $province = $address['province'];
                    }
                }
                if (($k = array_search($province, self::$province)) !== false) {
                    if (!key_exists($k, $data['province'])) {
                        $data['province'][$k] = 1;
                        $data['uid_province'][$item['user_id']] = $k;
                    } else {
                        $data['province'][$k] += 1;
                    }
                } else {
                    if (!key_exists(0, $data['province'])) {
                        $data['province'][0] = 1;
                        $data['uid_province'][$item['user_id']] = 0;
                    } else {
                        $data['province'][0] += 1;
                    }
                }
            }
        }
        UserRegister::insertOne(['ymd' => $start_date, 'detail' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        exit;
    }

    //在线统计
    public function onlineStat($date = '')
    {
        $date = !$date ? date('Ymd', strtotime("-1 day")) : $date;
        if (UserOnlineDay::exist("ymd=" . $date)) {
            return;
        }
        $start_date = $date;
        $end_date = date('Ymd', strtotime($start_date) + 86400);

        set_time_limit(0);

        //清理下废数据
        UserOnline::remove("(login_time=0 and logout_date<" . $start_date . ") or (login_date<=$start_date and logout_time=0)");

        $page = 1;
        $limit = 5000;
        $where = "login_time<>0 and ((login_date<=$start_date and (logout_date>=$start_date or logout_date=0)) or (login_date>=$start_date and login_date<=$end_date))";

        $list = UserOnline::findList([$where, 'columns' => 'id,login_time,logout_time,user_id', 'limit' => $limit, 'offset' => 0]);
        $res = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'user_count' => 0];
        $online_users = [[], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], [], 'all' => []];
        $time_limit = [];

        //获取时间范围
        $i = 0;
        $date_time = strtotime($date);
        while ($i <= 23) {
            $start = $date_time * 1000 + 3600 * $i * 1000;
            $end = $start + 3600 * 1000;
            $time_limit[$i] = ['s' => $start, 'e' => $end];
            $i++;
        }
        //    var_dump($time_limit);exit;
        while ($list) {
            foreach ($list as $item) {
                foreach ($time_limit as $k => $l) {
                    if (in_array($item['user_id'], $online_users[$k])) {
                        continue;
                    }
                    if (
                        ($item['login_time'] <= $l['s'] && ($item['logout_time'] >= $l['s'] || $item['logout_time'] == 0))
                        || ($item['login_time'] >= $l['s'] && $item['login_time'] <= $l['e'])
                    ) {
                        $online_users[$k][] = $item['user_id'];
                        $res[$k] += 1;
                    }
                }
                if (!in_array($item['user_id'], $online_users['all'])) {
                    $online_users['all'][] = $item['user_id'];
                    $res['user_count'] += 1;
                }
            }
            $page++;
            $list = UserOnline::findList([$where, 'columns' => 'login_time,logout_time,user_id', 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
        }
        UserOnlineDay::insertOne(['ymd' => $date, 'detail' => json_encode($res, JSON_UNESCAPED_UNICODE)]);

    }

    //登录统计
    public function loginStat($date = '')
    {
        $date = !$date ? date('Ymd', strtotime("-1 day")) : $date;
        if (UserLoginDay::exist("ymd=" . $date)) {
            return;
        }
        $start_date = $date;
        $end_date = date('Ymd', strtotime($start_date) + 86400);
        set_time_limit(0);
        $id = UserLoginLog::getColumn(['ymd=' . $date, 'group' => 'user_id', 'columns' => 'user_id,max(id) as id'], 'id');
        if ($id) {
            $list = UserLoginLog::findList(['id in (' . implode(',', $id) . ')', 'columns' => 'user_id,client_ip']);
            foreach ($list as $item) {
                if ($item['client_ip']) {
                   var_dump(Ip::getAddress($item['client_ip']));
                }
            }

            // var_dump($list);exit;
        }
    }
}