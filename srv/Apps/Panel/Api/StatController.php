<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/31
 * Time: 19:02
 */

namespace Multiple\Panel\Api;


use Models\Group\Group;
use Models\Site\SiteCashReward;
use Models\Social\SocialDiscuss;
use Models\Social\SocialShare;
use Models\Statistics\PackageDayStat;
use Models\Statistics\SiteCashRewardTotal;
use Models\Statistics\SiteReward;
use Models\Statistics\StatisticsGroup;
use Models\Statistics\UserRegister;
use Models\Statistics\VipDayStat;
use Models\User\UserLoginLog;
use Models\User\UserProfile;
use Models\User\Users;
use Models\Vip\VipOrder;
use Services\Discuss\DiscussManager;
use Services\Discuss\TagManager;
use Services\Site\CacheSetting;
use Services\Site\CashRewardManager;
use Services\Stat\StatManager;
use Services\Stat\UserManager;
use Util\Ajax;
use Util\Pagination;

class StatController extends ApiBase
{
    public function shareAction()
    {
        $start     = strtotime(date('Y-m-d', strtotime('-7 days')));
        $type      = $this->request->get("type", 'string', 'all');
        $week_date = [
            'keys'   => StatManager::getDays(7),
            'values' => [[0, 0, 0, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0], [0, 0, 0, 0, 0, 0, 0]],
            'labels' => [],
        ];
        $key_count = count($week_date['keys']);
        foreach ($week_date['keys'] as $k => &$item) {
            $week_date['labels'][] = ($key_count - $k == 3 ? '前天' : ($key_count - $k == 2 ? "昨天" : ($key_count - $k == 1 ? "今天" : $item)));
        }

        //  $week_date['labels'] = mb_substr($week_date['labels'], 1);

        $where = "created>=" . $start;
        if ($type != 'all') {
            $where .= " and type='" . $type . "'";
        }
        $share_data = SocialShare::findList([$where, "group" => "ymd,site", "columns" => 'ymd,count(1) as count,site,FROM_UNIXTIME(created,"%Y-%m-%d") as date']);
        if ($share_data) {
            foreach ($share_data as $item2) {
                $key = array_search($item2['date'], $week_date['keys']);
                switch ($item2['site']) {
                    case "QQ":
                        $week_date['values'][1][$key] += $item2['count'];
                        break;
                    case "微博":
                        $week_date['values'][2][$key] += $item2['count'];
                        break;
                    case "朋友圈":
                        $week_date['values'][3][$key] += $item2['count'];
                        break;
                    case "QQ空间":
                        $week_date['values'][4][$key] += $item2['count'];
                        break;
                    case "微信好友":
                        $week_date['values'][5][$key] += $item2['count'];
                        break;
                }
                $week_date['values'][0][$key] += $item2['count'];
            }
        }
        $week_date['values'] = json_encode($week_date['values'], JSON_UNESCAPED_UNICODE);
        $week_date['labels'] = json_encode($week_date['labels'], JSON_UNESCAPED_UNICODE);

        $this->ajax->outRight($week_date);
    }

    public function discussAction()
    {
        $day       = $this->request->get("day", 'string', 'today');
        $start_day = $this->request->get("start", 'string', '');
        $end_day   = $this->request->get("end", 'string', '');
        $type      = $this->request->get("type", 'string', 'all');

        //今天
        if ($day == 'today') {
            $start      = strtotime(date('Ymd'));
            $chart_date = [
                'keys'   => StatManager::getHour(),
                'values' => [],
                'labels' => [],
                'count'  => 0
            ];

            for ($i = 0; $i < count($chart_date['keys']); $i++) {
                $chart_date['values'][] = 0;
            }
            $j = 0;
            foreach ($chart_date['keys'] as $item) {
                $chart_date['labels'][] = $item . ":00";
            }
            $where = "created>=" . $start;
            if ($type != 'all') {
                if ($type == DiscussManager::TYPE_RED_PACKET) {
                    $where .= " and package_id>0";
                } else {
                    $where .= " and media_type='" . $type . "'";
                }
            }
            $share_data = SocialDiscuss::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
            if ($share_data) {
                foreach ($chart_date['keys'] as &$item) {
                    if (isset($share_data[$item])) {
                        $chart_date['values'][$j] = intval($share_data[$item]['count']);
                        $chart_date['count']      += $share_data[$item]['count'];
                    } else {
                        $chart_date['values'][$j] = 0;
                    }
                    $j++;
                }
            }
            $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } //昨天
        else if ($day == 'yesterday') {
            $start = strtotime(date('Ymd', strtotime("-1 day")));
            $end   = $start + 86400;

            $chart_date = [
                'keys'   => StatManager::getHour(),
                'values' => [],
                'labels' => [],
                'count'  => 0
            ];

            for ($i = 0; $i < count($chart_date['keys']); $i++) {
                $chart_date['values'][] = 0;
            }
            $j = 0;
            foreach ($chart_date['keys'] as $item) {
                $chart_date['labels'][] = $item . ":00";
            }
            $where = "created>=" . $start . " and created<=" . $end;
            if ($type != 'all') {
                if ($type)
                    if ($type == DiscussManager::TYPE_RED_PACKET) {
                        $where .= " and package_id>0";
                    } else {
                        $where .= " and media_type='" . $type . "'";
                    }
            }
            $share_data = SocialDiscuss::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
            if ($share_data) {
                foreach ($chart_date['keys'] as &$item) {
                    if (isset($share_data[$item])) {
                        $chart_date['values'][$j] = intval($share_data[$item]['count']);
                        $chart_date['count']      += $share_data[$item]['count'];
                    } else {
                        $chart_date['values'][$j] = 0;
                    }
                    $j++;
                }
            }
            $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } //时间段
        elseif ($day == '') {
            $start = strtotime($start_day);
            $end   = strtotime($end_day) + 86400;

            $chart_date = [
                'keys'   => StatManager::getLimitDay($start_day, $end_day),
                'values' => [],
                'labels' => [],
                'count'  => 0
            ];

            for ($i = 0; $i < count($chart_date['keys']); $i++) {
                $chart_date['values'][] = 0;
            }
            $j = 0;
            foreach ($chart_date['keys'] as $item) {
                $chart_date['labels'][] = $item;
            }
            $where = "created>=" . $start . " and created<=" . $end;
            if ($type != 'all') {
                if ($type == DiscussManager::TYPE_RED_PACKET) {
                    $where .= " and package_id>0";
                } else {
                    $where .= " and media_type='" . $type . "'";
                }
            }
            $share_data = SocialDiscuss::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
            if ($share_data) {
                foreach ($chart_date['keys'] as &$item) {
                    if (isset($share_data[$item])) {
                        $chart_date['values'][$j] = intval($share_data[$item]['count']);
                        $chart_date['count']      += $share_data[$item]['count'];
                    } else {
                        $chart_date['values'][$j] = 0;
                    }
                    $j++;
                }
            }
            $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } else {
            $start      = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
            $chart_date = [
                'keys'   => StatManager::getDays($day),
                'values' => [],
                'labels' => [],
                'count'  => 0
            ];

            for ($i = 0; $i < $day; $i++) {
                $chart_date['values'][] = 0;
            }
            $key_count = count($chart_date['keys']);

            foreach ($chart_date['keys'] as $k => &$item) {
                $chart_date['labels'][] = ($key_count - $k == 3 ? '前天' : ($key_count - $k == 2 ? "昨天" : ($key_count - $k == 1 ? "今天" : $item)));
            }
            $where = "created>=" . $start;
            if ($type != 'all') {
                if ($type == DiscussManager::TYPE_RED_PACKET) {
                    $where .= " and package_id>0";
                } else {
                    $where .= " and media_type='" . $type . "'";
                }
            }
            $share_data = SocialDiscuss::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
            if ($share_data) {
                $j = 0;
                foreach ($chart_date['keys'] as &$item) {
                    if (isset($share_data[$item])) {
                        $chart_date['values'][$j] = intval($share_data[$item]['count']);
                        $chart_date['count']      += $share_data[$item]['count'];
                    } else {
                        $chart_date['values'][$j] = 0;
                    }
                    $j++;
                }
            }
            $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        }
        $this->ajax->outRight($chart_date);
    }

    public function discussComplexAction()
    {
        $chart_type = $this->request->get("chart_type", 'string', 'line'); //line-折线 circle-扇形
        $day        = $this->request->get("day", 'string', 'today');
        $start_day  = $this->request->get("start", 'string', '');
        $end_day    = $this->request->get("end", 'string', '');
        $type       = $this->request->get("type", 'string', 'all');

        $chart_date = [
            'keys'   => [],
            'values' => [],
            'labels' => [],
            'count'  => 0
        ];
        //折线 --
        if ($chart_type == 'line') {
            //今天
            if ($day == 'today') {
                $start              = strtotime(date('Ymd'));
                $chart_date['keys'] = StatManager::getHour();

                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item . ":00";
                }
                $where = "created>=" . $start;
                if ($type != 'all') {
                    if ($type == DiscussManager::TYPE_RED_PACKET) {
                        $where .= " and package_id>0";
                    } else {
                        $where .= " and media_type='" . $type . "'";
                    }
                }
                $share_data = SocialDiscuss::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } //昨天
            else if ($day == 'yesterday') {
                $start              = strtotime(date('Ymd', strtotime("-1 day")));
                $end                = $start + 86400;
                $chart_date['keys'] = StatManager::getHour();


                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item . ":00";
                }
                $where = "created>=" . $start . " and created<=" . $end;
                if ($type != 'all') {
                    if ($type == DiscussManager::TYPE_RED_PACKET) {
                        $where .= " and package_id>0";
                    } else {
                        $where .= " and media_type='" . $type . "'";
                    }
                }
                $share_data = SocialDiscuss::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];

                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } //时间段
            elseif ($day == 'custom') {
                $start              = strtotime($start_day);
                $end                = strtotime($end_day) + 86400;
                $chart_date['keys'] = StatManager::getLimitDay($start_day, $end_day);

                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item;
                }
                $where = "created>=" . $start . " and created<=" . $end;
                if ($type != 'all') {
                    if ($type == DiscussManager::TYPE_RED_PACKET) {
                        $where .= " and package_id>0";
                    } else {
                        $where .= " and media_type='" . $type . "'";
                    }
                }
                $share_data = SocialDiscuss::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } else {
                $start              = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
                $chart_date['keys'] = StatManager::getDays($day);

                for ($i = 0; $i < $day; $i++) {
                    $chart_date['values'][] = 0;
                }
                $key_count = count($chart_date['keys']);
                foreach ($chart_date['keys'] as $k => &$item) {
                    $chart_date['labels'][] = ($key_count - $k == 3 ? '前天' : ($key_count - $k == 2 ? "昨天" : ($key_count - $k == 1 ? "今天" : $item)));
                }
                $where = "created>=" . $start;
                if ($type != 'all') {
                    $where .= " and media_type='" . $type . "'";
                }
                $share_data = SocialDiscuss::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
                if ($share_data) {
                    $j = 0;
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            }
        } //扇形 按标签来统计
        else if ($chart_type == 'circle') {
            $tags       = TagManager::getInstance()->list();
            $chart_date = [
                'keys'   => array_column($tags, 'name', 'id'),
                'values' => [],
                'labels' => "",
                'total'  => 0
            ];
            foreach ($chart_date['keys'] as $key => $item) {
                $chart_date['values'][$key] = ['name' => $item, 'value' => 0];
                $chart_date['labels'][]     = $item;
            }
            //今天
            if ($day == 'today') {
                $start = strtotime(date('Ymd'));
                $where = "created>=" . $start;
            } //昨天
            else if ($day == 'yesterday') {
                $start = strtotime(date('Ymd', strtotime("-1 day")));
                $end   = $start + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } //时间段
            elseif ($day == 'custom') {
                $start = strtotime($start_day);
                $end   = strtotime($end_day) + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } else {
                $start = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
                $where = "created>=" . $start;
            }
            if ($type != 'all') {
                if ($type == DiscussManager::TYPE_RED_PACKET) {
                    $where .= " and package_id>0";
                } else {
                    $where .= " and media_type='" . $type . "'";
                }
            }
            $list = SocialDiscuss::getByColumnKeyList([$where, "group" => "tags", "columns" => 'count(1) as count,tags'], "tags");
            if ($list) {
                foreach ($list as $k => $item) {
                    $tag_ids = explode(',', $k);
                    foreach ($tag_ids as $i) {
                        if (isset($chart_date['values'][$i])) {
                            $chart_date['values'][$i]['value'] += $item['count'];
                        }
                    }
                    $chart_date['total'] += $item['count'];
                }
            }
            $chart_date['keys']   = json_encode(array_values($chart_date['keys']), JSON_UNESCAPED_UNICODE);
            $chart_date['values'] = json_encode(array_values($chart_date['values']), JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } //类型统计
        else if ($chart_type == 'type_circle') {
            $media_type = DiscussManager::$media_type;
            $chart_date = [
                'keys'   => $media_type,
                'values' => [],
                'labels' => "",
                'total'  => 0
            ];
            foreach ($chart_date['keys'] as $key => $item) {
                $chart_date['values'][$key] = ['name' => $item, 'value' => 0];
                $chart_date['labels'][]     = $item;
            }
            //今天
            if ($day == 'today') {
                $start = strtotime(date('Ymd'));
                $where = "created>=" . $start;
            } //昨天
            else if ($day == 'yesterday') {
                $start = strtotime(date('Ymd', strtotime("-1 day")));
                $end   = $start + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } //时间段
            elseif ($day == 'custom') {
                $start = strtotime($start_day);
                $end   = strtotime($end_day) + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } else {
                $start = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
                $where = "created>=" . $start;
            }
            $list = SocialDiscuss::getByColumnKeyList([$where, "group" => "media_type", "columns" => 'count(1) as count,media_type'], "media_type");
            if ($list) {
                foreach ($list as $k => $item) {
                    $chart_date['values'][$k]['value'] += $item['count'];
                    $chart_date['total']               += $item['count'];
                }
            }
            $chart_date['keys']   = json_encode(array_values($chart_date['keys']), JSON_UNESCAPED_UNICODE);
            $chart_date['values'] = json_encode(array_values($chart_date['values']), JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } //总统计
        else if ($chart_type == 'sum') {
            $chart_date = [
                'user_count'     => 0,
                'total_count'    => 0,
                'original_count' => 0,
                'forward_count'  => 0,
                'valid_count'    => 0,
                'deleted_count'  => 0,
                'labels'         => ['参与用户数', '总动态数', '原创动态数', '非原创动态数', '有效动态数', '已删动态数']
            ];

            //今天
            if ($day == 'today') {
                $start = strtotime(date('Ymd'));
                $where = "created>=" . $start;
            } //昨天
            else if ($day == 'yesterday') {
                $start = strtotime(date('Ymd', strtotime("-1 day")));
                $end   = $start + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } //时间段
            elseif ($day == 'custom') {
                $start = strtotime($start_day);
                $end   = strtotime($end_day) + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } else {
                $start = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
                $where = "created>=" . $start;
            }
            if ($type != 'all') {
                if ($type == DiscussManager::TYPE_RED_PACKET) {
                    $where .= " and package_id>0";
                } else {
                    $where .= " and media_type='" . $type . "'";
                }
            }
            $list1                     = SocialDiscuss::getColumn([$where, "group" => "user_id", "columns" => 'count(1) as count,user_id'], 'count');
            $chart_date['user_count']  = count($list1);
            $chart_date['total_count'] = SocialDiscuss::dataCount($where);
            $list2                     = SocialDiscuss::getByColumnKeyList([$where, "group" => "col", "columns" => 'count(1) as count,if(share_original_item_id>0,1,0) as col'], 'col');
            if (isset($list2[0])) {
                $chart_date['original_count'] = $list2[0]['count'];
            }
            if (isset($list2[1])) {
                $chart_date['forward_count'] = $list2[1]['count'];
            }

            $list3 = SocialDiscuss::getByColumnKeyList([$where, "group" => "col", "columns" => 'count(1) as count,if(status=1,1,0) as col'], 'col');
            if (isset($list3[1])) {
                $chart_date['valid_count'] = $list3[1]['count'];
            }
            if (isset($list3[0])) {
                $chart_date['deleted_count'] = $list3[0]['count'];
            }
        }
        $this->ajax->outRight($chart_date);
    }

    //用户统计
    public function userComplexAction()
    {
        $day        = $this->request->get("day", 'string', 'today');
        $start_day  = $this->request->get("start", 'string', '');
        $end_day    = $this->request->get("end", 'string', '');
        $type       = $this->request->get("type", 'string', 'total');
        $chart_date = [
            'keys'   => [],
            'values' => [],
            'labels' => [],
            'count'  => 0
        ];
        //折线 -- 总统计
        if ($type == 'total') {
            //今天
            if ($day == 'today') {
                $start              = strtotime(date('Ymd'));
                $chart_date['keys'] = StatManager::getHour();

                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item . ":00";
                }
                $where = "created>=" . $start . " and user_type=1";

                $share_data = Users::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } //昨天
            else if ($day == 'yesterday') {
                $start              = strtotime(date('Ymd', strtotime("-1 day")));
                $end                = $start + 86400;
                $chart_date['keys'] = StatManager::getHour();

                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item . ":00";
                }
                $where = "created>=" . $start . " and created<=" . $end . " and user_type=1";

                $share_data = USers::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } //前天
            else if ($day == 'before_yesterday') {
                $start              = strtotime(date('Ymd', strtotime("-2 day")));
                $end                = $start + 86400;
                $chart_date['keys'] = StatManager::getHour();

                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item . ":00";
                }
                $where = "created>=" . $start . " and created<=" . $end . " and user_type=1";

                $share_data = USers::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } //时间段
            elseif ($day == 'custom') {
                $start              = strtotime($start_day);
                $end                = strtotime($end_day) + 86400;
                $chart_date['keys'] = StatManager::getLimitDay($start_day, $end_day);


                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item;
                }
                $where = "created>=" . $start . " and created<=" . $end . " and user_type=1";

                $share_data = Users::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } else {
                $start              = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
                $chart_date['keys'] = StatManager::getDays($day);

                for ($i = 0; $i < $day; $i++) {
                    $chart_date['values'][] = 0;
                }
                $key_count = count($chart_date['keys']);
                foreach ($chart_date['keys'] as $k => &$item) {
                    $chart_date['labels'][] = ($key_count - $k == 3 ? '前天' : ($key_count - $k == 2 ? "昨天" : ($key_count - $k == 1 ? "今天" : $item)));
                }
                $where = "created>=" . $start . " and user_type=1";

                $share_data = USers::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
                if ($share_data) {
                    $j = 0;
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            }
        } //按设备统计  -饼状图
        else if ($type == 'device') {
            $chart_date = [
                'keys'   => ['ios', 'android', '其他'],
                'values' => [["name" => 'ios', 'value' => 0], ["name" => 'android', 'value' => 0], ["name" => 'h5', 'value' => 0], ["name" => '其他', 'value' => 0]],
                'labels' => ["ios", "android", "h5", "其他"],
                'total'  => 0
            ];
            //今天
            if ($day == 'today') {
                $start = strtotime(date('Ymd'));
                $where = "created>=" . $start;
            } //昨天
            else if ($day == 'yesterday') {
                $start = strtotime(date('Ymd', strtotime("-1 day")));
                $end   = $start + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } //前天
            else if ($day == 'before_yesterday') {
                $start = strtotime(date('Ymd', strtotime("-2 day")));
                $end   = $start + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } //全部
            else if ($day == 'all') {
                $where = "1";
            } //时间段
            elseif ($day == 'custom') {
                $start = strtotime($start_day);
                $end   = strtotime($end_day) + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } else {
                $start = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
                $where = "created>=" . $start;
            }
            $list = $this->db->query("select count(1) as count,platform from user_profile as up left join users as u on up.user_id=u.id where $where group by up.platform")->fetchAll();
            /* $list = UserProfile::getByColumnKeyList([$where, "group" => "platform", "columns" => 'count(1) as count,platform'], "platform");*/
            if ($list) {
                foreach ($list as $item) {
                    $k = $item['platform'];
                    if (strtolower($k) == 'ios') {
                        $chart_date['values'][0]['value'] += $item['count'];
                    } else if (strtolower($k) == 'android') {
                        $chart_date['values'][1]['value'] += $item['count'];
                    } else if (strtolower($k) == 'wap') {
                        $chart_date['values'][2]['value'] += $item['count'];
                    } else {
                        $chart_date['values'][3]['value'] += $item['count'];
                    }
                    $chart_date['total'] += $item['count'];
                }
            }
            $chart_date['keys']   = json_encode(array_values($chart_date['keys']), JSON_UNESCAPED_UNICODE);
            $chart_date['values'] = json_encode(array_values($chart_date['values']), JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } //按方式统计 -饼状图
        else if ($type == 'type') {
            $chart_date = [
                'keys'   => ['手机', 'QQ', '微信', "其他"],
                'values' => [["name" => "手机", "value" => 0], ["name" => "QQ", "value" => 0], ["name" => "微信", "value" => 0], ["name" => "其他", 'value' => 0]],
                'labels' => ["手机", "QQ", "微信", "其他"],
                'total'  => 0
            ];
            //今天
            if ($day == 'today') {
                $start = strtotime(date('Ymd'));
                $where = "created>=" . $start;
            } //昨天
            else if ($day == 'yesterday') {
                $start = strtotime(date('Ymd', strtotime("-1 day")));
                $end   = $start + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } //昨天
            else if ($day == 'before_yesterday') {
                $start = strtotime(date('Ymd', strtotime("-2 day")));
                $end   = $start + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } //全部
            else if ($day == 'all') {
                $where = "1";
            } //时间段
            elseif ($day == 'custom') {
                $start = strtotime($start_day);
                $end   = strtotime($end_day) + 86400;
                $where = "created>=" . $start . " and created<=" . $end;
            } else {
                $start = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
                $where = "created>=" . $start;
            }

            $list = $this->db->query("select count(1) as count,register_type from user_profile as up left join users as u on up.user_id=u.id where $where group by up.register_type")->fetchAll();

            if ($list) {
                foreach ($list as $item) {
                    $k = $item['register_type'];
                    if ($k == 'phone') {
                        $chart_date['values'][0]['value'] += $item['count'];
                    } else if (strtolower($k) == 'qq') {
                        $chart_date['values'][1]['value'] += $item['count'];
                    } else if (strtolower($k) == 'weixin') {
                        $chart_date['values'][2]['value'] += $item['count'];
                    } else {
                        $chart_date['values'][3]['value'] += $item['count'];
                    }
                    $chart_date['total'] += $item['count'];
                }
            }
            $chart_date['keys']   = json_encode(array_values($chart_date['keys']), JSON_UNESCAPED_UNICODE);
            $chart_date['values'] = json_encode(array_values($chart_date['values']), JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } //按地区统计 地图模式
        else if ($type == 'area') {
            $chart_date = ['values' => [], 'total' => 0];
            if ($day == 'yesterday') {
                $where = 'ymd=' . date('Ymd', strtotime('-1 days'));
            } else if ($day == 'before_yesterday') {
                $where = 'ymd=' . date('Ymd', strtotime('-2 days'));
            } //全部
            else if ($day == 'all') {
                $where = '';

            } //时间段
            elseif ($day == 'custom') {
                $start = str_replace('-', '', $start_day);
                $end   = str_replace('-', '', $end_day);
                $where = 'ymd>=' . $start . " and ymd<=" . $end;
            } else {
                $start = (date('Ymd', strtotime('-' . $day . ' days')));
                $where = 'ymd>=' . $start;
            }
            $list = UserRegister::findList([$where]);
            if ($list) {
                foreach ($list as $item) {
                    $data = json_decode($item['detail'], true);
                    if ($data['total'] > 0) {
                        $chart_date['total'] += $data['total'];
                        foreach ($data['province'] as $k => $p) {
                            if (isset($chart_date['values'][UserManager::$province[$k]])) {
                                $chart_date['values'][UserManager::$province[$k]]['value'] += $p;
                            } else {
                                $chart_date['values'][UserManager::$province[$k]]['value'] = $p;
                                $chart_date['values'][UserManager::$province[$k]]['name']  = UserManager::$province[$k];
                            }
                        }
                    }
                }
            }
            $chart_date['values'] = json_encode(array_values($chart_date['values']), JSON_UNESCAPED_UNICODE);
        }
        $this->ajax->outRight($chart_date);
    }

    public function groupAction()
    {
        $day       = $this->request->get("day", 'string', 'today');
        $start_day = $this->request->get("start", 'string', '');
        $end_day   = $this->request->get("end", 'string', '');

        //今天
        if ($day == 'today') {
            $start      = strtotime(date('Ymd'));
            $chart_date = [
                'keys'   => StatManager::getHour(),
                'values' => [],
                'labels' => [],
                'count'  => 0,
            ];

            for ($i = 0; $i < count($chart_date['keys']); $i++) {
                $chart_date['values'][] = 0;
            }
            $j = 0;
            foreach ($chart_date['keys'] as $item) {
                $chart_date['labels'][] = $item . ":00";
            }
            $where = "created>=" . $start;

            $share_data = Group::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
            if ($share_data) {
                foreach ($chart_date['keys'] as &$item) {
                    if (isset($share_data[$item])) {
                        $chart_date['values'][$j] = intval($share_data[$item]['count']);
                        $chart_date['count']      += $share_data[$item]['count'];
                    } else {
                        $chart_date['values'][$j] = 0;
                    }
                    $j++;
                }
            }
            $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } //昨天
        else if ($day == 'yesterday') {
            $start = strtotime(date('Ymd', strtotime("-1 day")));
            $end   = $start + 86400;

            $chart_date = [
                'keys'   => StatManager::getHour(),
                'values' => [],
                'labels' => [],
                'count'  => 0,
            ];

            for ($i = 0; $i < count($chart_date['keys']); $i++) {
                $chart_date['values'][] = 0;
            }
            $j = 0;
            foreach ($chart_date['keys'] as $item) {
                $chart_date['labels'][] = $item . ":00";
            }
            $where = "created>=" . $start . " and created<=" . $end;

            $share_data = Group::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
            if ($share_data) {
                foreach ($chart_date['keys'] as &$item) {
                    if (isset($share_data[$item])) {
                        $chart_date['values'][$j] = intval($share_data[$item]['count']);
                        $chart_date['count']      += $share_data[$item]['count'];
                    } else {
                        $chart_date['values'][$j] = 0;
                    }
                    $j++;
                }
            }
            $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } //时间段
        elseif ($day == '') {
            $start = strtotime($start_day);
            $end   = strtotime($end_day) + 86400;

            $chart_date = [
                'keys'   => StatManager::getLimitDay($start_day, $end_day),
                'values' => [],
                'labels' => [],
                'count'  => 0,
            ];

            for ($i = 0; $i < count($chart_date['keys']); $i++) {
                $chart_date['values'][] = 0;
            }
            $j = 0;
            foreach ($chart_date['keys'] as $item) {
                $chart_date['labels'][] = $item;
            }
            $where = "created>=" . $start . " and created<=" . $end;

            $share_data = Group::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
            if ($share_data) {
                foreach ($chart_date['keys'] as &$item) {
                    if (isset($share_data[$item])) {
                        $chart_date['values'][$j] = intval($share_data[$item]['count']);
                        $chart_date['count']      += $share_data[$item]['count'];
                    } else {
                        $chart_date['values'][$j] = 0;
                    }
                    $j++;
                }
            }
            $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        } else {
            $start      = strtotime(date('Y-m-d', strtotime('-' . $day . ' days')));
            $chart_date = [
                'keys'   => StatManager::getDays($day),
                'values' => [],
                'labels' => [],
                'count'  => 0,
            ];

            for ($i = 0; $i < $day; $i++) {
                $chart_date['values'][] = 0;
            }
            $key_count = count($chart_date['keys']);
            foreach ($chart_date['keys'] as $k => &$item) {
                $chart_date['labels'][] = ($key_count - $k == 3 ? '前天' : ($key_count - $k == 2 ? "昨天" : ($key_count - $k == 1 ? "今天" : $item)));
            }
            $where = "created>=" . $start;

            $share_data = Group::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
            if ($share_data) {
                $j = 0;
                foreach ($chart_date['keys'] as &$item) {
                    if (isset($share_data[$item])) {
                        $chart_date['values'][$j] = intval($share_data[$item]['count']);
                        $chart_date['count']      += $share_data[$item]['count'];
                    } else {
                        $chart_date['values'][$j] = 0;
                    }
                    $j++;
                }
            }
            $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
            $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
        }
        $this->ajax->outRight($chart_date);
    }

    //群复杂统计
    public function groupComplexAction()
    {
        $day       = $this->request->get("day", 'string', 'today');
        $start_day = $this->request->get("start", 'string', '');
        $end_day   = $this->request->get("end", 'string', '');
        $type      = $this->request->get("type", 'string', 'total');

        $chart_date = [
            'keys'   => [],
            'values' => [],
            'labels' => [],
            'count'  => 0
        ];
        //折线 -- 总群聊
        if ($type == 'total') {
            //今天
            if ($day == 'today') {
                $start              = strtotime(date('Ymd'));
                $chart_date['keys'] = StatManager::getHour();

                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item . ":00";
                }
                $where = "created>=" . $start;

                $share_data = Group::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } //昨天
            else if ($day == 'yesterday') {
                $start              = strtotime(date('Ymd', strtotime("-1 day")));
                $end                = $start + 86400;
                $chart_date['keys'] = StatManager::getHour();

                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item . ":00";
                }
                $where = "created>=" . $start . " and created<=" . $end;

                $share_data = Group::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%H") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } //时间段
            elseif ($day == 'custom') {
                $start              = strtotime($start_day);
                $end                = strtotime($end_day) + 86400;
                $chart_date['keys'] = StatManager::getLimitDay($start_day, $end_day);


                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item;
                }
                $where = "created>=" . $start . " and created<=" . $end;

                $share_data = Group::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
                if ($share_data) {
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } else {
                $start              = strtotime(date('Y-m-d', strtotime('-' . ($day - 1) . ' days')));
                $chart_date['keys'] = StatManager::getDays($day);

                for ($i = 0; $i < $day; $i++) {
                    $chart_date['values'][] = 0;
                }
                $key_count = count($chart_date['keys']);
                foreach ($chart_date['keys'] as $k => &$item) {
                    $chart_date['labels'][] = ($key_count - $k == 3 ? '前天' : ($key_count - $k == 2 ? "昨天" : ($key_count - $k == 1 ? "今天" : $item)));
                }
                $where = "created>=" . $start;

                $share_data = Group::getByColumnKeyList([$where, "group" => "date", "columns" => 'count(1) as count,FROM_UNIXTIME(created,"%Y-%m-%d") as date'], "date");
                if ($share_data) {
                    $j = 0;
                    foreach ($chart_date['keys'] as &$item) {
                        if (isset($share_data[$item])) {
                            $chart_date['values'][$j] = intval($share_data[$item]['count']);
                            $chart_date['count']      += $share_data[$item]['count'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            }
        } //活跃群聊
        else if ($type == 'active_group') {
            //时间段
            if ($day == 'custom') {
                $start              = str_replace('-', '', $start_day);
                $end                = str_replace('-', '', $end_day);
                $chart_date['keys'] = StatManager::getLimitDay($start_day, $end_day);

                for ($i = 0; $i < count($chart_date['keys']); $i++) {
                    $chart_date['values'][] = 0;
                }
                $j = 0;
                foreach ($chart_date['keys'] as $item) {
                    $chart_date['labels'][] = $item;
                }
                $where = "ymd>=" . $start . " and ymd<=" . $end;

                $share_data = StatisticsGroup::getByColumnKeyList([$where, "columns" => 'active_count as count,ymd,active_gids'], "ymd");
                if ($share_data) {
                    $gids = '';
                    foreach ($chart_date['keys'] as &$item) {
                        $temp = str_replace('-', '', $item);
                        if (isset($share_data[$temp])) {
                            $chart_date['values'][$j] = intval($share_data[$temp]['count']);
                            // $chart_date['count'] += $share_data[$temp]['count'];
                            $gids .= ',' . $share_data[$temp]['active_gids'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                    $chart_date['count'] = count(array_unique(array_filter(explode(',', $gids))));
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            } else {
                $start              = date('Ymd', strtotime('-' . ($day - 1) . ' days'));
                $chart_date['keys'] = StatManager::getDays($day);

                for ($i = 0; $i < $day; $i++) {
                    $chart_date['values'][] = 0;
                }
                $j         = 0;
                $key_count = count($chart_date['keys']);
                foreach ($chart_date['keys'] as $k => &$item) {
                    $chart_date['labels'][] = ($key_count - $k == 3 ? '前天' : ($key_count - $k == 2 ? "昨天" : ($key_count - $k == 1 ? "今天" : $item)));
                }
                $where = "ymd>=" . $start;

                $share_data = StatisticsGroup::getByColumnKeyList([$where, "columns" => 'active_count  as count,ymd,active_gids'], "ymd");

                if ($share_data) {
                    $gids = '';
                    foreach ($chart_date['keys'] as &$item) {
                        $temp = str_replace('-', '', $item);
                        if (isset($share_data[$temp])) {
                            $chart_date['values'][$j] = intval($share_data[$temp]['count']);
                            // $chart_date['count'] += $share_data[$temp]['count'];
                            $gids .= ',' . $share_data[$temp]['active_gids'];
                        } else {
                            $chart_date['values'][$j] = 0;
                        }
                        $j++;
                    }
                    $chart_date['count'] = count(array_unique(array_filter(explode(',', $gids))));
                }
                $chart_date['values'] = json_encode($chart_date['values'], JSON_UNESCAPED_UNICODE);
                $chart_date['labels'] = json_encode($chart_date['labels'], JSON_UNESCAPED_UNICODE);
            }
        }


        $this->ajax->outRight($chart_date);
    }

    /**
     * 推广统计
     */
    public function promoAction()
    {
        $arr = $this->postApi('forms/range', ['type' => 'promote']);
        foreach ($arr as $key => $item) {
            $tmp[$key]['year']  = substr($item, 0, 4);
            $tmp[$key]['month'] = substr($item, 4, 2);
            $tmp[$key]['day']   = substr($item, 6, 2);
            $range[$key]        = $tmp[$key]['year'] . '/' . $tmp[$key]['month'] . '/' . $tmp[$key]['day'];
        }
        //获取统计区间
        $begin           = $this->request->get('start', 'int', $arr['begin']);
        $end             = $this->request->get('end', 'int', $arr['end']);
        $keys            = [
            'new_user'      => '新注册用户',
            'activate_user' => '激活用户',
            'total_cost'    => '总花费',
            'level1_cost'   => '一级花费',
            'level2_cost'   => '二级花费',
            'level3_cost'   => '三级花费'
        ];
        $data            = [];
        $aa              = $this->request->get('type');
        $chart['labels'] = array_values($keys);
        $res             = $this->forms->query("select * from promote_form where created >= $begin and created <=$end")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($keys as $v) {
            $chart['values'][] = [
                'name' => $v,
                'type' => 'line',
                'data' => []
            ];
        };
        foreach ($res as $k => $v) {
            $form = json_decode($v['data'], true)['form'];
            /*foreach (array_keys($keys) as  $kk => $vv)
            {
                //金额除100
                if( in_array($kk,[2,3,4,5]) )
                {
                    $data[$kk][] = $form[$vv] / 100;
                }else
                {
                    $data[$kk][] = $form[$vv];
                }

            }*/
            unset($res[$k]['data']);
            $res[$k]['form'] = $form;
        }
        foreach ($res as $item) {
            $chart['days'][] = substr($item['created'], 0, 4) . '/' . substr($item['created'], 4, 2) . '/' . substr($item['created'], 6, 2);
            foreach (array_keys($keys) as $k => $v) {

                if (in_array($k, [2, 3, 4, 5]))
                    $chart['values'][$k]['data'][] = $item['form'][$v] / 100;
                else
                    $chart['values'][$k]['data'][] = $item['form'][$v];

            }
        }
        $chart['range'] = $range;
        //汇总数据
        $summary = $this->postApi('forms/detail', ['type' => 'promote', 'begin' => $begin, 'end' => $end]);
        /*foreach( $summary['form'] as $k => $v)
        {
            $summary['form'][$k] = $v;
            unset($summary['form'][$k]);
        }*/
        $chart['summary'] = $summary;
        Ajax::init()->outRight($chart);
    }

    //系统奖励
    public function rewardAction()
    {
        $reward_type = $this->request->get("reward_type", 'string', 'cash');
        $day         = $this->request->get("day", 'string', 'today');
        $start_day   = $this->request->get("start", 'string', '');
        $end_day     = $this->request->get("end", 'string', '');
        $type        = $this->request->get("type", 'string', 'total');

        //日趋势图
        if ($type == 'total') {
            $chart_data = [
                'keys'   => [],
                'values' => [],
                'labels' => [],
                'count'  => 0
            ];
            //时间段
            if ($day == 'custom') {
                $start              = str_replace('-', '', $start_day);
                $end                = str_replace('-', '', $end_day);
                $chart_data['keys'] = StatManager::getLimitDay($start_day, $end_day);

                for ($i = 0; $i < count($chart_data['keys']); $i++) {
                    $chart_data['values'][] = 0;
                }
                foreach ($chart_data['keys'] as $item) {
                    $chart_data['labels'][] = $item;
                }
                $where = "ymd>=" . $start . " and ymd<=" . $end;
            } //具体多少天内
            else {
                $start = date('Y-m-d', strtotime('-' . ($day + 1) . ' days'));
                $days  = StatManager::getDays($day + 1);
                unset($days[count($days) - 1]);
                $chart_data['keys'] = $days;
                $where              = "ymd>=" . str_replace('-', '', $start);
                $key_count          = count($chart_data['keys']);
                foreach ($chart_data['keys'] as $k => &$item) {
                    $chart_data['labels'][] = ($key_count - $k == 2 ? '前天' : ($key_count - $k == 1 ? "昨天" : $item));
                }
            }
            $list = SiteReward::getByColumnKeyList([$where], 'ymd');
            if ($list) {
                $j = 0;
                if ($list) {
                    foreach ($chart_data['keys'] as &$item) {
                        $temp = str_replace('-', '', $item);
                        if (isset($list[$temp])) {
                            $detail                   = json_decode($list[$temp]['detail'], true);
                            $money                    = $reward_type == 'cash' ? intval($detail['type'][$reward_type]['total']) / 100 : $detail['type'][$reward_type]['total'];
                            $chart_data['values'][$j] = $money;
                            $chart_data['count']      += $money;
                        } else {
                            $chart_data['values'][$j] = 0;
                        }
                        $j++;
                    }
                }
            }
            $chart_data['values'] = json_encode($chart_data['values'], JSON_UNESCAPED_UNICODE);
            $chart_data['labels'] = json_encode($chart_data['labels'], JSON_UNESCAPED_UNICODE);


        }//平台趋势图
        else {
            $chart_data = [
                'keys'   => [],
                'values' => [],
                'labels' => [],
                'count'  => 0,
            ];
            //时间段
            if ($day == 'custom') {
                $start              = str_replace('-', '', $start_day);
                $end                = str_replace('-', '', $end_day);
                $chart_data['keys'] = StatManager::getLimitDay($start_day, $end_day);

                /*   for ($i = 0; $i < count($chart_data['keys']); $i++) {
                       $chart_data['values'][] = 0;
                   }*/
                foreach ($chart_data['keys'] as $item) {
                    $chart_data['labels'][] = $item;
                }
                $where = "ymd>=" . $start . " and ymd<=" . $end;
            } //具体多少天内
            else {
                $start = date('Y-m-d', strtotime('-' . ($day + 1) . ' days'));
                $days  = StatManager::getDays($day + 1);
                unset($days[count($days) - 1]);
                $chart_data['keys'] = $days;
                $where              = "ymd>=" . str_replace('-', '', $start);
                $key_count          = count($chart_data['keys']);
                foreach ($chart_data['keys'] as $k => &$item) {
                    $chart_data['labels'][] = ($key_count - $k == 2 ? '前天' : ($key_count - $k == 1 ? "昨天" : $item));
                }
            }
            $list = SiteReward::getByColumnKeyList([$where], 'ymd');
            if ($list) {
                $j = 0;
                if ($list) {
                    foreach ($chart_data['keys'] as $item) {
                        $temp = str_replace('-', '', $item);
                        if (isset($list[$temp])) {
                            $detail = json_decode($list[$temp]['detail'], true);
                            foreach ($detail['platform'] as $k => $d) {
                                if ($k == 0) {
                                    continue;
                                }
                                $money = $reward_type == 'cash' ? intval($d[$reward_type]) / 100 : intval($d[$reward_type]);

                                $chart_data['values'][$k][$j] += $money;
                                $chart_data['count']          += $money;
                                //  var_dump($chart_data);
                            }
                        } else {
                            for ($i = 1; $i <= 5; $i++) {
                                $chart_data['values'][$i][$j] = 0;
                            }
                        }
                        $j++;
                    }
                }
            }
            /*    var_dump($list);
                var_dump($chart_data);
                exit;*/
            $chart_data['values'] = json_encode(array_values($chart_data['values']), JSON_UNESCAPED_UNICODE);
            $chart_data['labels'] = json_encode($chart_data['labels'], JSON_UNESCAPED_UNICODE);
        }
        $this->ajax->outRight($chart_data);
    }

    //系统红包
    public function packageAction()
    {
        $day          = $this->request->get("day", 'string', 'today');
        $start_day    = $this->request->get("start", 'string', '');
        $end_day      = $this->request->get("end", 'string', '');
        $package_type = $this->request->get("package_type", 'int', CashRewardManager::TYPE_ROBOT_DISCUSS_PACKAGE);

        //日趋势图
        $chart_data = [
            'keys'   => [],
            'values' => [],
            'labels' => [],
            'count'  => 0
        ];
        $where      = "type=" . $package_type;
        //时间段
        if ($day == 'custom') {
            $start              = str_replace('-', '', $start_day);
            $end                = str_replace('-', '', $end_day);
            $chart_data['keys'] = StatManager::getLimitDay($start_day, $end_day);

            for ($i = 0; $i < count($chart_data['keys']); $i++) {
                $chart_data['values'][] = 0;
            }
            foreach ($chart_data['keys'] as $item) {
                $chart_data['labels'][] = $item;
            }
            $where .= " and ymd>=" . $start . " and ymd<=" . $end;
        } //具体多少天内
        else {
            $start = date('Y-m-d', strtotime('-' . ($day + 1) . ' days'));
            $days  = StatManager::getDays($day + 1);
            unset($days[count($days) - 1]);
            $chart_data['keys'] = $days;
            $where              .= " and ymd>=" . str_replace('-', '', $start);
            $key_count          = count($chart_data['keys']);
            foreach ($chart_data['keys'] as $k => &$item) {
                $chart_data['labels'][] = ($key_count - $k == 2 ? '前天' : ($key_count - $k == 1 ? "昨天" : $item));
            }
        }
        $list = SiteCashRewardTotal::getByColumnKeyList([$where], 'ymd');
        if ($list) {
            $j = 0;
            if ($list) {
                foreach ($chart_data['keys'] as &$item) {
                    $temp = str_replace('-', '', $item);
                    if (isset($list[$temp])) {
                        $chart_data['values'][$j] = $list[$temp]['money'] / 100;
                        $chart_data['count']      += $list[$temp]['money'] / 100;
                    } else {
                        $chart_data['values'][$j] = 0;
                    }
                    $j++;
                }
            }
        }
        $chart_data['values'] = json_encode($chart_data['values'], JSON_UNESCAPED_UNICODE);
        $chart_data['labels'] = json_encode($chart_data['labels'], JSON_UNESCAPED_UNICODE);

        $this->ajax->outRight($chart_data);
    }

    //广场红包
    public function squarePackageAction()
    {
        $day        = $this->request->get("day", 'string', 'today');
        $start_day  = $this->request->get("start", 'string', '');
        $end_day    = $this->request->get("end", 'string', '');
        $type       = $this->request->get("type", 'string', 'user');
        $chart_data = [
            'keys'   => '',
            'values' => [],
            'labels' => [],
            'total'  => []
        ];
        //时间段
        if ($day == 'custom') {
            $start              = str_replace('-', '', $start_day);
            $end                = str_replace('-', '', $end_day);
            $chart_data['keys'] = StatManager::getLimitDay($start_day, $end_day);

            foreach ($chart_data['keys'] as $i) {
                $chart_data['labels'][] = $i;
            }
            $where = "  ymd>=" . $start . " and ymd<=" . $end;
        } //具体多少天内
        else {
            $start = date('Y-m-d', strtotime('-' . ($day + 1) . ' days'));
            $days  = StatManager::getDays($day + 1);
            unset($days[count($days) - 1]);
            $chart_data['keys'] = $days;
            $where              = "  ymd>=" . str_replace('-', '', $start);
            //  $key_count = count($chart_data['keys']);
            foreach ($chart_data['keys'] as $i) {
                $chart_data['labels'][] = $i;// ($key_count - $k == 2 ? '前天' : ($key_count - $k == 1 ? "昨天" : $item));
            }
        }

        $data = PackageDayStat::getByColumnKeyList([$where, "columns" => 'ymd,package,send_uids,pick_uids'], 'ymd');
        //  $key_count = count($week_date['keys']);
        //用户统计
        if ($type == 'user') {
            $send_uids  = '';
            $pick_uids  = '';
            $robot_uids = $this->di->get('redis')->originalGet(CacheSetting::KEY_ROBOT_UIDS);

            foreach ($chart_data['keys'] as $item) {
                $k = str_replace('-', '', $item);
                if (isset($data[$k])) {
                    $package                   = json_decode($data[$k]['package'], true);
                    $chart_data['values'][0][] = $package['send_person_count'];
                    $chart_data['values'][1][] = $package['pick_person_count'];
                    $chart_data['values'][2][] = $package['send_person_robot_count'];
                    $chart_data['values'][3][] = $package['send_person_real_count'];
//                    $chart_data['values'][4][] = $package['pick_person_robot_count'];
                    $chart_data['values'][4][] = $package['pick_person_real_count'];
                    $send_uids                 .= "," . $data[$k]['send_uids'];
                    $pick_uids                 .= "," . $data[$k]['pick_uids'];
                } else {
                    $chart_data['values'][0][] = 0;
                    $chart_data['values'][1][] = 0;
                    $chart_data['values'][2][] = 0;
                    $chart_data['values'][3][] = 0;
//                    $chart_data['values'][4][] = 0;
                    $chart_data['values'][4][] = 0;
                }
            }
            $send_uids           = count(array_unique(array_filter(explode(',', substr($send_uids, 1)))));
            $pick_uids           = count(array_diff(array_unique(array_filter(explode(',', substr($pick_uids, 1)))), explode(',', $robot_uids)));
            $chart_data['total'] = ["send" => $send_uids, 'pick' => $pick_uids];

        } //金额统计
        else if ($type == 'money') {
            foreach ($chart_data['keys'] as $item) {
                $key = str_replace('-', '', $item);
                if (isset($data[$key])) {
                    $package                   = json_decode($data[$key]['package'], true);
                    $chart_data['values'][0][] = sprintf('%.2f', $package['send_total_money'] / 100);
                    $chart_data['values'][1][] = sprintf('%.2f', $package['pick_total_money'] / 100);
                    $chart_data['values'][2][] = sprintf('%.2f', $package['send_robot_user_money'] / 100);
                    $chart_data['values'][3][] = sprintf('%.2f', $package['send_real_user_money'] / 100);
//                    $chart_data['values'][4][] = sprintf('%.2f',$package['pick_robot_user_money']/100);
                    $chart_data['values'][4][] = sprintf('%.2f', $package['pick_real_user_money'] / 100);
                } else {
                    $chart_data['values'][0][] = 0;
                    $chart_data['values'][1][] = 0;
                    $chart_data['values'][2][] = 0;
                    $chart_data['values'][3][] = 0;
//                    $chart_data['values'][4][] = 0;
                    $chart_data['values'][4][] = 0;
                }
            }
        } //红包个数统计
        else if ($type == 'package_count') {
            foreach ($chart_data['keys'] as $item) {
                $key = str_replace('-', '', $item);
                if (isset($data[$key])) {
                    $package                   = json_decode($data[$key]['package'], true);
                    $chart_data['values'][0][] = $package['send_package_total_count'];
                    $chart_data['values'][1][] = $package['pick_total_count'];
                } else {
                    $chart_data['values'][0][] = 0;
                    $chart_data['values'][1][] = 0;
                }
                if (isset($data[$key])) {
                    $package                   = json_decode($data[$key]['package'], true);
                    $chart_data['values'][0][] = $package['send_package_total_count'];
                    $chart_data['values'][1][] = $package['pick_total_count'];
                    $chart_data['values'][2][] = $package['send_robot_user_count'];
                    $chart_data['values'][3][] = $package['send_real_user_count'];
//                    $chart_data['values'][4][] = $package['pick_robot_user_count'];
                    $chart_data['values'][4][] = $package['pick_real_user_count'];

                } else {
                    $chart_data['values'][0][] = 0;
                    $chart_data['values'][1][] = 0;
                    $chart_data['values'][2][] = 0;
                    $chart_data['values'][3][] = 0;
                    $chart_data['values'][4][] = 0;
//                    $chart_data['values'][5][] = 0;
                }
            }
        }

        foreach ($chart_data['values'] as $k => $value) {
            foreach ($value as $v) {
                isset($chart_data['total'][$k]) ? $chart_data['total'][$k] += $v : $chart_data['total'][$k] = $v;
            }
        }
        $chart_data['values'] = json_encode($chart_data['values'], JSON_UNESCAPED_UNICODE);
        $chart_data['labels'] = json_encode($chart_data['labels'], JSON_UNESCAPED_UNICODE);
        $chart_data['day']    = count($chart_data['keys']);
        $this->ajax->outRight($chart_data);
    }

    //每天登录人数
    public function loginCountAction()
    {
        $end       = $this->request->get('end', 'string', date('Ymd', strtotime('-1 day')));
        $start     = $this->request->get('start', 'string', $end);
        $data_days = $this->db_statistics->query("select * from user_login_day where ymd >= $start and ymd <= $end")->fetchAll(\PDO::FETCH_ASSOC);
        $res       = [
            'users_per_province'  => [],
            'proportion_of_users' => [
                'new' => 0,
                'old' => 0
            ]
        ];
        //合并多天数据
        if ($data_days) {
            foreach ($data_days as $data_day) {
                $data = json_decode($data_day['detail'], true);//每天统计数据

                $res['users_per_province']         = array_merge_recursive($res['users_per_province'], $data['users_per_province']);
                $res['proportion_of_users']['new'] += $data['proportion_of_users']['new'];
                $res['proportion_of_users']['old'] += $data['proportion_of_users']['old'];
            }
            foreach ($res['users_per_province'] as $k => $v) {
                if (is_array($v))
                    $res['users_per_province'][$k] = array_sum($v);
            }

        }
        $res['max_users_count'] = max($res['users_per_province']);//每个省份最大值
        $res['total_users']     = array_sum($res['users_per_province']);//总登录用户数
        $res['period']['start'] = date('Y/m/d', strtotime($start));
        $res['period']['end']   = date('Y/m/d', strtotime($end));
        Ajax::init()->outRight($res);
    }

    public function retainUserAction()
    {
        $start = $this->request->get("start");//起始时间
        $end   = $this->request->get("end");//结束时间
        $page  = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 100);
        $type  = $this->request->get("type", 'string', 'day');//day-日 week-周 month-月
        $res   = ['label' => ['', '', '', '', '', '', '', '', ''], 'data' => []];
        if ($type == 'day') {
            $days         = StatManager::getLimitDay($start, $end);
            $res['label'] = ['1天后', '2天后', '3天后', '4天后', '5天后', '6天后', '7天后', '14天后', '30天后'];
            $d            = [1, 2, 3, 4, 5, 6, 7, 14, 30];
            if ($days) {
                $limit_day = date('Ymd', strtotime('-1 days'));
                foreach ($days as $item) {
                    $day               = $item;
                    $time              = strtotime($item);
                    $tag               = true;
                    $uids              = Users::getColumn(['created>=' . $time . " and created<=" . ($time + 86400) . " and user_type=1", 'columns' => 'id'], 'id');
                    $res['data'][$day] = ['count' => count($uids)];
                    foreach ($d as $k => $i) {
                        $current_day = date('Ymd', $time + (86400 * $i));
                        if ($tag) {
                            if ($current_day <= $limit_day) {
                                $count = $this->db->query("select count(1) as count from (select user_id,count(1) as count from user_login_log where user_id in (" . implode(',', $uids) . ") and ymd=" . $current_day . ' group by user_id) as g')->fetch(\PDO::FETCH_ASSOC);
                                $count = $count['count'];
                                isset($res['data'][$day]['list']) ? $res['data'][$day]['list'][] = $count : $res['data'][$day]['list'] = [$count];
                            } else {
                                $tag = false;
                                isset($res['data'][$day]['list']) ? $res['data'][$day]['list'][] = -1 : $res['data'][$day]['list'] = [-1];
                            }
                        } else {
                            isset($res['data'][$day]['list']) ? $res['data'][$day]['list'][] = -1 : $res['data'][$day]['list'] = [-1];

                        }
                    }
                }
            }
            $data = [];
            if ($page == 1) {
                $data[] = $this->getFromOB('stat/userRetain/label', array('label' => $res['label']));
            }
            foreach ($res['data'] as $day => $item) {
                $data[] = $this->getFromOB('stat/userRetain/item', array('item' => $item, 'day' => $day));
            }
            $bar = Pagination::getAjaxListPageBar(count($days), $page, $limit);
            $this->ajax->outRight(['list' => $data, 'count' => count($days), 'bar' => $bar]);
        }
    }

    /**
     * 虚拟币统计
     */
    public function virtualCoinAction()
    {
        $type         = (int) $this->request->get('type', 'int', 0);//0:龙钻统计 1：收益统计
        $default_date = date('Ymd', time() - 86400);
        $begin        = $this->request->get('begin', 'string', $default_date);
        $end          = $this->request->get('end', 'string', $default_date);
        $where        = '';
        if (!empty($begin))
            $where[] = "created >= " . $begin;
        else
            $begin = 20180103;
        if (!empty($end))
            $where[] = "created <= " . $end;
        else
            $end = $default_date;
        if (!empty($where))
            $where = 'where ' . implode(' and ', $where);
        $res   = $this->postApi('forms/detail', ['type' => 'vtc', 'begin' => $begin, 'end' => $end]);
        $total = $res['form']['virtualcoins'][$type];
        $list  = $this->forms->query("select * from vtc_form " . $where)->fetchAll(\PDO::FETCH_ASSOC);
        $days  = [];//趋势图时间
        $data  = [];
        if ($list) {
            switch ($type) {
                case 0://龙钻
                    foreach ($list as $item) {
                        $data['days'][]                              = date('Y/m/d', strtotime($item['created']));
                        $record                                      = json_decode($item['data'], 'true')['form']['virtualcoins'];
                        $days[]                                      = date('Y/m/d', strtotime($item['created']));
                        $record                                      = $record[0];
                        $data['income']['alipay']['items'][]         = $record['income']['alipay'] ?: 0;
                        $data['income']['cash']['items'][]           = $record['income']['cash'] ?: 0;
                        $data['income']['ios']['items'][]            = $record['income']['ios'] ?: 0;
                        $data['income']['wechat']['items'][]         = $record['income']['wechat'] ?: 0;
                        $data['income']['public_account']['items'][] = $record['income']['public_account'] ?: 0;
                        $data['income']['system_reward']['items'][]  = $record['income']['system_reward'] ?: 0;
                        $data['income']['items'][]                   = $record['income']['total'] ?: 0;
                        $data['defray']['items'][]                   = $record['defray']['total'] ?: 0;
                    }
                    $data['income']['alipay']['total']         = array_sum($data['income']['alipay']['items']);
                    $data['income']['cash']['total']           = array_sum($data['income']['cash']['items']);
                    $data['income']['ios']['total']            = array_sum($data['income']['ios']['items']);
                    $data['income']['wechat']['total']         = array_sum($data['income']['wechat']['items']);
                    $data['income']['public_account']['total'] = array_sum($data['income']['public_account']['items']);
                    $data['income']['system_reward']['total']  = array_sum($data['income']['system_reward']['items']);

                    break;
                case 1://收益
                    foreach ($list as $item) {
                        $data['days'][]                             = date('Y/m/d', strtotime($item['created']));
                        $record                                     = json_decode($item['data'], 'true')['form']['virtualcoins'];
                        $days[]                                     = date('Y/m/d', strtotime($item['created']));
                        $record                                     = $record[1];
                        $data['income']['activity']['items'][]      = ($record['income']['activity'] ?: 0) / 100;
                        $data['income']['square_redbag']['items'][] = ($record['income']['square_redbag'] ?: 0) / 100;
                        $data['income']['items'][]                  = ($record['income']['total'] ?: 0) / 100;
                        $data['defray']['items'][]                  = ($record['defray']['total'] ?: 0) / 100;
                    }
                    $data['income']['activity']['total']      = array_sum($data['income']['activity']['items']) / 100;
                    $data['income']['square_redbag']['total'] = array_sum($data['income']['square_redbag']['items']) / 100;
                    break;
            }

        }
        $data['range']['begin']  = date('Y/m/d', strtotime($begin));
        $data['range']['end']    = date('Y/m/d', strtotime($end));
        $data['total']['income'] = ($type === 0) ? $total['income']['total'] : $total['income']['total'] / 100;
        $data['total']['defray'] = ($type === 0) ? $total['defray']['total'] : $total['defray']['total'] / 100;
        Ajax::outRight($data);

    }

    /**
     * vip统计
     */
    public function vipAction()
    {
        $start = $this->request->get('start','string',date('Ymd',strtotime('-8 day')));
        $end = $this->request->get('end','string',date('Ymd',strtotime('-1 day')));

        // start -> end 时间内用户行为数据
        $list = VipDayStat::findList(['ymd >= ' . $start . ' and ' . 'ymd <= ' . $end]);
        $arr_purchase_rate = [];
        $arr_click_rate = [];
        if( $list )
        {
            foreach( $list as $item )
            {
                //初始化变量
                $purchase_rate = 0;
                $click_rate = 0;
                //进入vip购买界面用户
                $opear1 = json_decode($item['opera1'],true);
                if( count($opear1) > 0 )
                {
                    $user_not_vip = UserProfile::findList(['is_vip = 0 and user_id in(' . implode(',',array_keys($opear1)) . ')','columns' => 'user_id']);
                    if( count($user_not_vip) > 0 )
                    {
                        //计算付费率
                        $purchase_rate =  sprintf('%.2f',(($item['opera3_cnt'] / count($user_not_vip)) * 100 ));
                        //计算点击转化率
                        $uid_not_vip = array_column($user_not_vip,'user_id');
                        $tmp = array_filter($opear1,function($k) use($uid_not_vip) {
                            return in_array($k,$uid_not_vip) ? true : false;
                        },ARRAY_FILTER_USE_KEY);//进入vip页面的用户中非vip用户

                        $click_rate = sprintf('%.2f',(($item['opera3_cnt'] / array_sum($tmp)) * 100 ));//非vip用户点击总次数
                    }
                }

                $arr_purchase_rate[$item['ymd']] =  $purchase_rate;
                $arr_click_rate[$item['ymd']] =  $click_rate;

            }

        }
        //当前筛选时间内vip订单数据
        $vipOrder = VipOrder::findList(['created >= ' . strtotime($start) . ' and created <= ' . strtotime($end),'columns'=>'created,month,FROM_UNIXTIME(created,"%Y%m%d") as ymd']);
        //购买时长分布--每天
        $data_purchase_length_day = array_fill_keys(array_column($vipOrder,'ymd'),[ 1 => 0, 3 => 0 , 6 => 0]);
        foreach($vipOrder as $item)
        {
            $data_purchase_length_day[$item['ymd']][$item['month']] += 1;
        }
        //购买时长分布--汇总
        $data_purchase_length_sum = array_count_values(array_column($vipOrder,'month'));
        $data_purchase_length_sum[1] = $data_purchase_length_sum[1] ?: 0;
        $data_purchase_length_sum[3] = $data_purchase_length_sum[3] ?: 0;
        $data_purchase_length_sum[6] = $data_purchase_length_sum[6] ?: 0;
        $res['purchase']['line']['data'] = array_values($arr_purchase_rate);
        $res['purchase']['line']['day'] = array_keys($arr_purchase_rate);
        $res['purchase']['line']['avg'] = 0;//todo
        $res['click']['line']['data'] = array_values($arr_click_rate);
        $res['click']['line']['day'] = array_keys($arr_click_rate);
        $res['click']['line']['avg'] = 0;//todo
        $res['vip_len']['pie']['sum'] = $data_purchase_length_sum;
        $res['vip_len']['line']['data'] = [array_column($data_purchase_length_day,'1'),array_column($data_purchase_length_day,'3'),array_column($data_purchase_length_day,'6')];
        $res['vip_len']['line']['day'] = array_keys($data_purchase_length_day);

        $res['start'] = date('Y/m/d',strtotime($start));
        $res['end'] = date('Y/m/d',strtotime($end));
        $this->ajax->outRight($res);


    }

    public function appVersionAction()
    {
        $start = $this->request->get('start','string',date('Ymd',strtotime('-2 day')));
        $end = $this->request->get('end','string',date('Ymd',time()));

        $ios = UserLoginLog::findList(["id in(select max(id) from user_login_log group by user_id) and  ymd >= ". $start . ' and ymd <= ' . $end . ' and client_type = "ios"','columns' => 'app_version']);
        $android = UserLoginLog::findList(["id in(select max(id) from user_login_log group by user_id) and  ymd >= ". $start . ' and ymd <= ' . $end . ' and client_type = "android"','columns' => 'app_version']);
        $version_ios = array_count_values(array_column($ios,'app_version'));
        $versions_android = array_count_values(array_column($android,'app_version'));
        $res['ios']['legend'] = array_keys($version_ios);
        $res['ios']['data'] = $version_ios;
        $res['android']['legend'] = array_keys($versions_android);
        $res['android']['data'] = $versions_android;

        sort($res['ios']['legend']);
        sort($res['android']['legend']);

        $res['start'] = date('Y/m/d',strtotime($start));
        $res['end'] = date('Y/m/d',strtotime($end));
        $this->ajax->outRight($res);
    }

}