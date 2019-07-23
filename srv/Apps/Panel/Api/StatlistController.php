<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/3
 * Time: 13:40
 */

namespace Multiple\Panel\Api;


use Models\Group\Group;
use Models\Group\GroupMember;
use Models\Site\SiteRewardLog;
use Models\Square\RedPackagePickLog;
use Models\Statistics\PackageDayStat;
use Models\Statistics\StatisticsGroup;
use Models\User\UserInfo;
use Models\User\Users;
use Services\Social\SocialManager;
use Services\Stat\StatManager;
use Util\Ajax;
use Util\Pagination;

class StatlistController extends ApiBase
{
    //总群聊统计数据列表
    public function groupAction()
    {
        $type = $this->request->get("type", 'string', 'total');
        $day = $this->request->get("day", 'string', 'today');
        $time = $this->request->get("time", 'string', '');//时间点

        $start_day = $this->request->get("start", 'string', '');
        $end_day = $this->request->get("end", 'string', '');
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 10);


        $params['order'] = 'created desc';
        $list = [];
        $count = 0;

        //建群统计
        if ($type == 'total') {
            $params[0] = '1=1';
            $params['offset'] = (($page - 1) * $limit);
            $params['limit'] = $limit;
            $params['columns'] = 'id,name,default_name,avatar,created,default_avatar';

            //今天
            if ($day == 'today') {
                $params[0] .= " and created>=" . strtotime(date('Y-m-d'));
                if ($time) {
                    $params[0] .= " and FROM_UNIXTIME(created,'%H')=" . substr($time, 0, 2);
                }
            } //昨天
            else if ($day == 'yesterday') {
                $params[0] .= " and created>=" . strtotime(date('Y-m-d', strtotime('-1 day'))) . " and created <=" . strtotime(date('Y-m-d'));
                if ($time) {
                    $params[0] .= " and FROM_UNIXTIME(created,'%H')=" . substr($time, 0, 2);
                }

            } //时间段
            elseif ($day == 'custom') {

                if ($time) {
                    if ($time == '今天') {
                        $start = date('Ymd');
                    } else if ($time == '昨天') {
                        $start = date('Ymd', strtotime("-1 day"));
                    } else if ($time == '前天') {
                        $start = date('Ymd', strtotime("-2 day"));
                    } else {
                        $start = str_replace('-', '', $time);
                    }
                    $start = strtotime($start);
                    $end = $start + 86400;

                    $params[0] .= " and created>=" . $start . " and created <=" . $end;
                } else {
                    $start = strtotime($start_day);
                    $end = strtotime($end_day) + 86400;
                    $params[0] .= " and created>=" . $start . " and created <=" . $end;
                }

            } //固定多少天
            else {
                if ($time) {
                    if ($time == '今天') {
                        $start = date('Ymd');
                    } else if ($time == '昨天') {
                        $start = date('Ymd', strtotime("-1 day"));
                    } else if ($time == '前天') {
                        $start = date('Ymd', strtotime("-2 day"));
                    } else {
                        $start = str_replace('-', '', $time);
                    }
                    $start = strtotime($start);
                    $end = $start + 86400;

                    $params[0] .= " and created>=" . $start . " and created <=" . $end;
                } else {
                    $start = strtotime(date('Y-m-d', strtotime('-' . ($day - 1) . ' days')));
                    $params[0] .= " and created>=" . $start;
                }

            }
            $list = Group::findList($params);
            $count = Group::dataCount($params[0]);
            if ($list) {
                $gids = array_column($list, 'id');
                $member_count = GroupMember::getColumn(['gid in (' . implode(',', $gids) . ')', 'group' => 'gid', 'columns' => 'gid,count(*) as count'], 'count', 'gid');
                foreach ($list as &$item) {
                    $item['member_count'] = $member_count[$item['id']];
                }
            }
        } //活跃度统计
        else if ($type == 'active') {
            $params[0] = '';
            $params['columns'] = 'id,active_gids,created';
            //  $params['offset'] = (($page - 1) * $limit);
            // $params['limit'] = $limit;

            //具体到某一天
            if ($time) {
                if ($time == '今天') {
                    $start = date('Ymd');
                } else if ($time == '昨天') {
                    $start = date('Ymd', strtotime("-1 day"));
                } else if ($time == '前天') {
                    $start = date('Ymd', strtotime("-2 day"));
                } else {
                    $start = str_replace('-', '', $time);
                }
                $params[0] .= "ymd=" . $start;
            } else {
                //时间段
                if ($day == 'custom') {
                    $start = str_replace('-', '', $start_day);
                    $end = str_replace('-', '', $end_day);
                    $params[0] .= "ymd>=" . $start . " and ymd<=" . $end;
                } //固定多少天
                else {
                    $start = date('Ymd', strtotime('-' . ($day - 1) . ' days'));
                    $params[0] .= "ymd>=" . $start;
                }
            }
            $list = StatisticsGroup::findList($params);
            if ($list) {
                $gids = '';
                foreach ($list as $item) {
                    $gids .= ',' . $item['active_gids'];
                }
                $gids = array_unique(explode(',', substr($gids, 1)));
                $count = count($gids);
                $gids = array_slice($gids, ($page - 1) * $limit, $limit);
                $list = Group::findList(['id in (' . implode(',', $gids) . ')', 'columns' => 'id,name,default_name,avatar,created,default_avatar', 'order' => 'created desc']);
                $member_count = GroupMember::getColumn(['gid in (' . implode(',', $gids) . ')', 'group' => 'gid', 'columns' => 'gid,count(*) as count'], 'count', 'gid');
                foreach ($list as &$item) {
                    $item['member_count'] = $member_count[$item['id']];
                }
            }
        }
        $bar = Pagination::getAjaxPageBar($count, $page, $limit);
        $data = $this->getFromOB('stat/partial/index', array('list' => $list, 'bar' => $bar, 'component' => 'group'));
        $this->ajax->outRight($data);
    }

    //奖励统计数据列表
    public function rewardAction()
    {
        $type = $this->request->get("type", 'string', 'total');
        $day = $this->request->get("day", 'string', 'today');
        $time = $this->request->get("time", 'string', '');//时间点
        $reward_type = $this->request->get("reward_type", 'string', 'cash');//奖励类型

        $start_day = $this->request->get("start", 'string', '');
        $end_day = $this->request->get("end", 'string', '');

        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 10);


        $params['order'] = 'created desc';
        $list = [];
        $count = 0;

        //日趋势图
        if ($type == 'total') {
            $params[0] = 'reward_type=' . ($reward_type == 'cash' ? 1 : 2);
            $params['offset'] = (($page - 1) * $limit);
            $params['limit'] = $limit;
            $params['columns'] = 'platform,type,money,created,user_id,type,reward_type';

            //时间段
            if ($day == 'custom') {
                if ($time) {
                    if ($time == '今天') {
                        $start = date('Ymd');
                    } else if ($time == '昨天') {
                        $start = date('Ymd', strtotime("-1 day"));
                    } else if ($time == '前天') {
                        $start = date('Ymd', strtotime("-2 day"));
                    } else {
                        $start = str_replace('-', '', $time);
                    }
                    $start = strtotime($start);
                    $end = $start + 86400;

                    $params[0] .= " and created>=" . $start . " and created <=" . $end;
                } else {
                    $start = strtotime($start_day);
                    $end = strtotime($end_day) + 86400;
                    $params[0] .= " and created>=" . $start . " and created <=" . $end;
                }

            } //固定多少天
            else {
                if ($time) {
                    if ($time == '昨天') {
                        $start = date('Ymd', strtotime("-1 day"));
                    } else if ($time == '前天') {
                        $start = date('Ymd', strtotime("-2 day"));
                    } else {
                        $start = str_replace('-', '', $time);
                    }
                    $start = strtotime($start);
                    $end = $start + 86400;

                    $params[0] .= " and created>=" . $start . " and created <=" . $end;
                } else {
                    $start = strtotime(date('Y-m-d', strtotime('-' . ($day + 1) . ' days')));
                    $params[0] .= " and created>=" . $start . " and created<=" . strtotime(date('Ymd'));
                }

            }
            $list = SiteRewardLog::findList($params);
            $count = SiteRewardLog::dataCount($params[0]);
            if ($list) {
                $uids = array_unique(array_column($list, 'user_id'));
                $users = Users::getByColumnKeyList(['id in (' . implode(',', $uids) . ')', 'columns' => 'avatar,username,id'], 'id');
                foreach ($list as &$item) {
                    $item['username'] = $users[$item['user_id']]['username'];
                    $item['avatar'] = $users[$item['user_id']]['avatar'];
                }

            }
        } //按平台统计
        else if ($type == 'platform') {
            $platform = $this->request->get("platform", 'string', '');

            $params[0] = 'reward_type=' . ($reward_type == 'cash' ? 1 : 2);
            $params['columns'] = 'platform,type,money,created,user_id,type,reward_type';
            $params['offset'] = (($page - 1) * $limit);
            $params['limit'] = $limit;
            if ($platform) {
                $params[0] .= " and platform=" . (SocialManager::$_share_platform[$platform]);
            }
            //具体到某一天
            if ($time) {
                if ($time == '昨天') {
                    $start = date('Ymd', strtotime("-1 day"));
                } else if ($time == '前天') {
                    $start = date('Ymd', strtotime("-2 day"));
                } else {
                    $start = str_replace('-', '', $time);
                }
                $params[0] .= " and ymd=" . $start;
            } else {
                //时间段
                if ($day == 'custom') {
                    $start = str_replace('-', '', $start_day);
                    $end = str_replace('-', '', $end_day);
                    $params[0] .= "and ymd>=" . $start . " and ymd<=" . $end;
                } //固定多少天
                else {
                    $start = date('Ymd', strtotime('-' . ($day + 1) . ' days'));
                    $params[0] .= " and ymd>=" . $start . " and ymd<" . date('Ymd');
                }
            }
            $list = SiteRewardLog::findList($params);
            $count = SiteRewardLog::dataCount($params[0]);
            $_share_platform = array_flip(SocialManager::$_share_platform);
            if ($list) {
                $uids = array_unique(array_column($list, 'user_id'));
                $users = Users::getByColumnKeyList(['id in (' . implode(',', $uids) . ')', 'columns' => 'avatar,username,id'], 'id');
                foreach ($list as &$item) {
                    $item['username'] = $users[$item['user_id']]['username'];
                    $item['avatar'] = $users[$item['user_id']]['avatar'];
                    $item['platform'] = isset($_share_platform[$item['platform']]) ? $_share_platform[$item['platform']] : '其他';
                }

            }
        }
        $bar = Pagination::getAjaxPageBar($count, $page, $limit);
        $data = $this->getFromOB('stat/partial/index', array('list' => $list, 'bar' => $bar, 'type' => $type, 'component' => 'reward'));
        $this->ajax->outRight($data);
    }

    //领取用户统计
    public function pickUserAction()
    {
        $type = $this->request->get("type", 'string', 'pick_count');
        $day = $this->request->get("day", 'string', 'today');
        $start_day = $this->request->get("start", 'string', '');
        $end_day = $this->request->get("end", 'string', '');

        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 10);
        $order = $this->request->get("order", 'string', 'pick_count');
        $sort = $this->request->get("sort", 'string', 'desc');

        //  PackageDayStat::findList()

        $list = [];
        $count = 0;
        $day_count = 1;

        $params['columns'] = 'user_id as uid,created';
        $params['limit'] = $limit;
        $params['offset'] = ($page - 1) * $limit . "," . $limit;

        //今天
        if ($day == 'today') {
            $start = strtotime(date('Ymd'));
            $end = $start + 86400;;
            $params[0] = " created>=" . $start . " and created<=" . $end;
        } //昨天
        else if ($day == 'yesterday') {
            $start = strtotime(date('Ymd', strtotime('-1 day')));
            $end = $start + 86400;
            $params[0] = " created>=" . $start . " and created<=" . $end;
        } //前天
        else if ($day == 'before_yesterday') {
            $start = strtotime(date('Ymd', strtotime('-2 day')));
            $end = $start + 86400;
            $params[0] = " created>=" . $start . " and created<=" . $end;
        } //时间段
        elseif ($day == 'custom') {
            $start = strtotime($start_day);
            $end = strtotime($end_day) + 86400;
            $params[0] = " created>=" . $start . " and created <=" . $end;
            $day_count = StatManager::getDayCount($start_day, $end_day);
        } //固定多少天
        else {
            $start = strtotime(date('Ymd', strtotime('-' . ($day - 1) . ' days')));
            $params[0] = " created>=" . $start;
            $day_count = $day;
        }

        //
        $params['columns'] .= ',count(1) as count,sum(money) as money';
        $params['group'] = 'user_id';

        //领取次数
        if ($order == 'pick_count') {
            $params['order'] = " count " . " $sort,money desc ";
        } //领取金额
        else if ($order == 'pick_money') {
            $params['group'] = 'user_id';
            $params['order'] = " money " . " $sort";
        } else {
            $params['order'] = " count " . " $sort ,money desc ";
        }
        $list = RedPackagePickLog::findList($params);
        $count = $this->db->query("select count(1) as count2 from (select count(1) as count,user_id from red_package_pick_log where $params[0]  group by user_id) as tt")->fetch(\PDO::FETCH_ASSOC);
        $count = $count['count2'];
        //  $count = PackageDayStat::dataCount($params[0]);
        if ($list) {
            $uids = array_column($list, 'uid');
            $users = Users::getByColumnKeyList(['id in (' . implode(',', $uids) . ')', 'columns' => 'avatar,username,id'], 'id');
            foreach ($list as &$item) {
                $item['username'] = $users[$item['uid']]['username'];
                $item['avatar'] = $users[$item['uid']]['avatar'];
            }

        }
//        } //领红包金额
//        else if ($type == 'pick_money') {
//            $params['columns'] = 'pick_top,ymd';
//            //昨天
//            if ($day == 'yesterday') {
//                $params[0] = " ymd=" . date('Ymd', strtotime('-1 day'));
//            } //前天
//            else if ($day == 'before_yesterday') {
//                $params[0] = " ymd=" . date('Ymd', strtotime('-2 day'));
//            } //时间段
//            elseif ($day == 'custom') {
//                $start = str_replace("-", '', $start_day);
//                $end = str_replace("-", '', $end_day);
//                $params[0] = " ymd>=" . $start . " and ymd <=" . $end;
//                $day_count = $end - $start + 1;
//            } //固定多少天
//            else {
//                $start = date('Ymd', strtotime('-' . ($day - 1) . ' days'));
//                $params[0] = " ymd>=" . $start;
//                $day_count = $day;
//            }
//            $list = PackageDayStat::findList($params);
//            //  $count = PackageDayStat::dataCount($params[0]);
//            if ($list) {
//                $res = [];
//                foreach ($list as $item) {
//                    $uids = json_decode($item['pick_top'], true);
//                    foreach ($uids as $k => $u) {
//                        if (key_exists($u['uid'], $res)) {
//                            $res[$u['uid']]['count'] += $u['total'];
//                        } else {
//                            $res[$u['uid']] = ['count' => $u['total']];
//                        }
//                        $res[$u['uid']]['uid'] = $u['uid'];
//                    }
//                }
//                $uid_list = array_keys($res);
//                $count = count($uid_list);
//                $order_columns = array_column($res, 'count');
//                array_multisort($order_columns, SORT_DESC, $res);
//                $list = array_slice($res, ($page - 1) * $limit, $limit);
//                $uids = array_column($list, 'uid');
//                $users = Users::getByColumnKeyList(['id in (' . implode(',', $uids) . ')', 'columns' => 'avatar,username,id'], 'id');
//                foreach ($list as &$item) {
//                    $item['username'] = $users[$item['uid']]['username'];
//                    $item['avatar'] = $users[$item['uid']]['avatar'];
//                }
//
//            }
//        }
        $bar = Pagination::getAjaxPageBar($count, $page, $limit);
        $data = $this->getFromOB('stat/squarePackage/pick_user', array('list' => $list, 'bar' => $bar, 'day' => $day_count));
        $this->ajax->outRight($data);
    }

}