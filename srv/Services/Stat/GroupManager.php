<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/17
 * Time: 13:46
 */

namespace Services\Stat;


use Models\Group\Group;
use Models\Group\GroupMember;
use Models\Statistics\StatisticsGroup;
use Models\Statistics\StatisticsGroupWeek;
use Models\User\Message;
use Models\User\UserContactHistory;
use Models\User\Users;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Mvc\User\Plugin;
use Util\Debug;

class GroupManager extends Plugin
{
    private static $instance = null;

    const limit_message_count = 5;//日聊天大于多少的记为活跃群组

    public static $db = null;

    /**
     * @return  GroupManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        self::$db = $this->di->get("db_statistics");
    }

    //同步到数据库
    public static function syncDb($day, $end_now = false)
    {
        $message = [];
        //时间段同步
        if ($end_now) {
            $days = StatManager::getLimitDay($day, date('Y-m-d'));
            if ($days) {
                $message = Message::findList(["created>=" . strtotime($days[0]) . ' and created <= ' . (strtotime($days[count($days) - 1]) + 86400) . ' and gid>0', 'group' => 'gid,year,month,day', 'columns' => "count(1) as count,gid,concat(year,'-',month,'-',day) as ymd,year,month,day", 'having' => 'count>=' . self::limit_message_count]);
            }
        } //当天
        else {
            $message = Message::findList(["created>=" . strtotime($day) . ' and created <= ' . (strtotime($day) + 86400) . ' and gid>0', 'group' => 'gid,year,month,day', 'columns' => "count(1) as count,gid,concat(year,'-',month,'-',day) as ymd,year,month,day", 'having' => 'count>=' . self::limit_message_count]);
        }
        if ($message) {
            $day_arr = [];
            foreach ($message as $k => $item) {
                if (key_exists($item['ymd'], $day_arr)) {
                    $day_arr[$item['ymd']]['active_count'] += 1;
                    $day_arr[$item['ymd']]['active_gids'][] = $item['gid'];
                } else {
                    $day_arr[$item['ymd']] = ['active_count' => 1, 'active_gids' => [$item['gid']]];
                }
            }
            ksort($day_arr, SORT_DESC);
            foreach ($day_arr as $k => $i) {
                $date_arr = explode('-', $k);
                $day = $date_arr[0] . (strlen($date_arr[1]) == 1 ? '0' . $date_arr[1] : $date_arr[1]) . (strlen($date_arr[2]) == 1 ? '0' . $date_arr[2] : $date_arr[2]);
                /*  StatisticsGroup::*/
                $sql = "replace into statistics_group(ymd,active_count,active_gids,created) values('$day'," . $i['active_count'] . ",'" . implode(',', $i['active_gids']) . "'," . time() . ")";
                self::$db->execute($sql);
            }
        }

    }

    //群聊统计
    public function statisticsWeek($date = '', $start_gid, $end_gid)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }
        $time = (strtotime($date));
        $start_s_time = ($time - 86400 * 6);//7天起始时间
        $end_s_time = ($time + 86400);//7天结束时间
        //  var_dump($db);
        //  return;
        $now = time();
        $group = Group::getColumn(['id>=' . $start_gid . ' and id<=' . $end_gid . " and status=" . \Services\User\GroupManager::GROUP_STATUS_NORMAL . " and created<=" . $end_s_time, 'columns' => 'id'], 'id', '');
        if ($group) {
            //统计
            //  $list = Message::getByColumnKeyList(['gid in (' . implode(',', $group) . ') and send_time>=' . $time . " and send_time<=" . $end_s_time, 'group' => 'gid', 'columns' => 'count(1) as count,gid'], 'gid');
            $weekData = StatisticsGroupWeek::getByColumnKeyList(['gid in (' . implode(',', $group) . ')', 'columns' => 'id,gid,message_cnt,speakers,member_cnt'], 'gid');
            foreach ($group as $item) {

                //7天内发言人及条数
                $message_uid = Message::getColumn(['gid=' . $item . " and created>=" . ($start_s_time) . " and created<=" . $end_s_time, 'columns' => 'from_uid,count(1) as count', 'group' => 'from_uid', 'order' => 'count desc', 'limit' => 20], 'count', 'from_uid');
                //消息数量
                $message_cnt = Message::dataCount('gid=' . $item . " and created>=" . $time . " and created<=" . $end_s_time);
                //发言人数量
                $speakers = Message::getColumn(['gid=' . $item . " and created>=" . $time . " and created<=" . $end_s_time, 'group' => 'from_uid', 'columns' => 'count(1) as count,from_uid'], 'from_uid');
                //群成员
                $member = GroupMember::getColumn(['gid=' . $item . " and created<=" . $end_s_time, 'columns' => 'user_id'], 'user_id');
                $members = [];
                foreach ($member as $m) {
                    if (isset($message_uid[$m])) {
                        $members[$m] = $message_uid[$m];
                    } else {
                        $members[$m] = 0;
                    }
                }
                arsort($members);
                $data = [
                    'created' => $now,
                    'ymd' => str_replace('-', '', $date),
                    'member_m_top' => json_encode($members),
                    'gid' => $item
                ];
                //之前有统计数据
                if (isset($weekData[$item])) {
                    $message_cnt_old = json_decode($weekData[$item]['message_cnt'], true);
                    $speakers_old = json_decode($weekData[$item]['speakers'], true);
                    $member_cnt_old = json_decode($weekData[$item]['member_cnt'], true);


                    $message_cnt_old[$date] = $message_cnt;
                    $speakers_old[$date] = count($speakers);
                    $member_cnt_old[$date] = count($member);

                    ksort($message_cnt_old);
                    ksort($speakers_old);
                    ksort($member_cnt_old);

                    if (count($message_cnt_old) == 8) {
                        array_shift($message_cnt_old);
                        array_shift($speakers_old);
                        array_shift($member_cnt_old);
                    }
                    $data['message_cnt'] = json_encode($message_cnt_old);
                    $data['speakers'] = json_encode($speakers_old);
                    $data['member_cnt'] = json_encode($member_cnt_old);

                    if (!StatisticsGroupWeek::updateOne($data, 'id=' . $weekData[$item]['id'])) {
                        Debug::log("统计群星期数据失败:" . var_export($data, true), 'error');
                    }
                } else {
                    //没有统计数据
                    $data['message_cnt'] = json_encode([$date => $message_cnt]);
                    $data['speakers'] = json_encode([$date => count($speakers)]);
                    $data['member_cnt'] = json_encode([$date => count($member)]);
                    if (!StatisticsGroupWeek::insertOne($data)) {
                        Debug::log("统计群星期数据失败:" . var_export($data, true), 'error');
                    }
                    //  $insert_data[] = [$item, $now];
                }
            }
        }


    }
}