<?php
/**
 * Created by PhpStorm.
 * User: luguiwu
 * Date: 15-8-5
 * Time: 下午2:06
 */

namespace Multiple\Panel\Controllers;


use Models\Group\Group;
use Models\Social\SocialDiscuss;
use Models\Social\SocialShare;
use Models\Social\SocialShareBackLog;
use Models\Statistics\PackageDayStat;
use Models\Statistics\UserOnlineDay;
use Models\Statistics\VipDayStat;
use Models\User\UserInfo;
use Models\User\UserLoginLog;
use Models\User\UserOnline;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserThirdParty;
use Models\Vip\VipOrder;
use Services\Discuss\DiscussManager;
use Services\Site\CacheSetting;
use Services\Social\ShareManager;
use Services\Social\SocialManager;
use Services\Stat\StatManager;
use Services\User\GroupManager;
use Services\User\UserStatus;
use Util\Pagination;

class StatController extends ControllerBase
{
    public function indexAction()
    {

    }

    /**
     * 推广统计
     */
    public function promoAction()#推广统计#
    {

    }

    //**用户统计
    public function userAction()#用户统计#
    {
        $today_ymd = date('Ymd');
        $yesterday_ymd = date('Ymd', strtotime("-1 day"));
        $user_data = Users::findList(["FROM_UNIXTIME(created,'%Y%m%d')=" . $yesterday_ymd . " or FROM_UNIXTIME(created,'%Y%m%d')=" . $today_ymd, "columns" => 'id,FROM_UNIXTIME(created,"%Y%m%d") as ymd']);
        $data = ['today' => ['total_count' => 0, 'normal_count' => 0, 'qq_count' => 0, 'weixin_count' => 0],
            'yesterday' => ['total_count' => 0, 'normal_count' => 0, 'qq_count' => 0, 'weixin_count' => 0],
            'expect' => ['total_count' => 0, 'normal_count' => 0, 'qq_count' => 0, 'weixin_count' => 0]
        ];

        if ($user_data) {
            $third_user = UserThirdParty::getColumn(['user_id in (' . implode(',', array_column($user_data, 'id')) . ')', 'columns' => 'user_id,type'], 'type', 'user_id');
            foreach ($user_data as $item) {
                if ($item['ymd'] != $today_ymd) {
                    $key = 'yesterday';
                } else {
                    $key = 'today';
                }
                $data[$key]['total_count'] += 1;

                if ($third_user && isset($third_user[$item['id']])) {
                    //文本
                    if ($third_user[$item['id']] == UserStatus::LOGIN_QQ) {
                        $data[$key]['qq_count'] += 1;
                    } //图片
                    else if ($third_user[$item['id']] == UserStatus::LOGIN_WEICHAT) {
                        $data[$key]['weixin_count'] += 1;
                    }
                } else {
                    $data[$key]['normal_count'] += 1;
                }
            }
            foreach ($data['today'] as $key => $i) {
                if ($i == 0) {
                    $data['expect'][$key] = 0;
                } else {
                    $data['expect'][$key] = intval(round($i / intval(date('H')), 2) * 24);
                }
            }

        }
        $this->view->setVar('data', $data);
        ///echo strtotime('2016-9-11');exit;

    }

    //**用户登录统计
    public function userLoginAction()#登录活跃度#
    {
        ///echo strtotime('2016-9-11');exit;
        $start = strtotime(date('Y-m-d', strtotime('-30 days')));
        $start_month = strtotime(date('Y-m-d', strtotime('-1 year')));

        /**/
        $users = $this->db->query("select date ,count(*) as count from (select  user_id,FROM_UNIXTIME(login_time,'%Y-%m-%d') as date from user_login_log where login_time>= " . $start . " GROUP BY user_id,date) as log GROUP BY log.date")->fetchAll(\PDO::FETCH_ASSOC);
        $users_years = $this->db->query("select date ,count(*) as count from (select  user_id,FROM_UNIXTIME(login_time,'%Y-%m') as date from user_login_log where login_time>= " . $start_month . "   GROUP BY user_id,date) as log GROUP BY log.date")->fetchAll(\PDO::FETCH_ASSOC);

        $users && $users = array_combine(array_column($users, 'date'), $users);
        $users_years && $users_years = array_combine(array_column($users_years, 'date'), $users_years);

        $week_date = [
            'keys' => StatManager::getDays(7),
            'values' => '',
            'labels' => '',
        ];
        $month_date = [
            'keys' => StatManager::getDays(30),
            'values' => '',
            'labels' => '',
        ];
        $year_date = [
            'keys' => StatManager::getMonths(12),
            'values' => '',
            'labels' => '',
        ];
        if ($users) {
            $i = $j = 0;
            foreach ($week_date['keys'] as &$item) {
                $week_date['labels'] .= ",'" . ($i == 4 ? '前天' : ($i == 5 ? "昨天" : ($i == 6 ? "今天" : $item))) . "'";
                if (isset($users[$item])) {
                    $week_date['values'][$i] = $users[$item]['count'];
                } else {
                    $week_date['values'][$i] = 0;
                }
                $i++;
            }
            foreach ($month_date['keys'] as &$item) {
                $month_date['labels'] .= ",'" . ($j == 27 ? '前天' : ($j == 28 ? "昨天" : ($j == 29 ? "今天" : $item))) . "'";
                if (isset($users[$item])) {
                    $month_date['values'][$j] = $users[$item]['count'];
                } else {
                    $month_date['values'][$j] = 0;
                }
                $j++;
            }
            $week_date['labels'] = mb_substr($week_date['labels'], 1);
            $month_date['labels'] = mb_substr($month_date['labels'], 1);

        } else {
            $i = $j = 0;
            foreach ($week_date['keys'] as &$item) {
                $week_date['labels'] .= ",'" . ($i == 4 ? '前天' : ($i == 5 ? "昨天" : ($i == 6 ? "今天" : $item))) . "'";
                $week_date['values'][$i] = 0;
                $i++;
            }
            foreach ($month_date['keys'] as &$item) {
                $month_date['labels'] .= ",'" . ($j == 4 ? '前天' : ($j == 5 ? "昨天" : ($j == 6 ? "今天" : $item))) . "'";
                $month_date['values'][$j] = 0;
                $j++;
            }
            $week_date['labels'] = mb_substr($week_date['labels'], 1);
            $month_date['labels'] = mb_substr($month_date['labels'], 1);
        }
        if ($users_years) {
            $i = 0;
            foreach ($year_date['keys'] as &$item) {
                $year_date['labels'] .= ",'" . $item . "'";
                if (isset($users_years[$item])) {
                    $year_date['values'][$i] = $users_years[$item]['count'];
                } else {
                    $year_date['values'][$i] = 0;
                }
                $i++;
            }
            $year_date['labels'] = mb_substr($year_date['labels'], 1);
        } else {
            $i = 0;
            foreach ($year_date['keys'] as &$item) {
                $year_date['labels'] .= ",'" . $item . "'";
                $year_date['values'][$i] = 0;
                $i++;
            }
            $year_date['labels'] = mb_substr($year_date['labels'], 1);
        }
        //echo json_encode($year_date['values'], JSON_UNESCAPED_UNICODE);exit;
        $this->view->setVar('week_date', $week_date);
        $this->view->setVar('month_date', $month_date);
        $this->view->setVar('year_date', $year_date);
    }

    /***在线统计****/
    public function userOnlineAction()#在线统计#
    {
        $type = $this->request->get('type', 'int', 0);
        if (!$type) {
            $data = [];
            $json_data = [];
            $redis = $this->di->get("redis");
            $count = 1;// $redis->hGet(CacheSetting::KEY_USER_ONLINE, CacheSetting::KEY_USER_ONLINE_COUNT);
            $user_list = [];
            $list = $redis->hGetAll(CacheSetting::KEY_USER_ONLINE_LIST);
            if ($count > 0) {
                if ($list) {
                    $uid = [];
                    $order_list_temp = [];
                    $order_list = [];

                    foreach ($list as $item) {
                        $item = json_decode($item, true);
                        if($item['province']){
                            if (isset($data[$item['province']])) {
                                $json_data[$item['province']]['value'] += 1;
                                $data[$item['province']]['count'] += 1;
                                $data[$item['province']]['user'][] = $item['uid'];
                            } else {
                                $json_data[$item['province']]['value'] = 1;
                                $json_data[$item['province']]['name'] = $item['province'];
                                $data[$item['province']] = ['count' => 1, 'user' => [$item['uid']]];
                            }
                            $uid[] = $item['uid'];
                            $order_list_temp[$item['uid']] = $item['time'];
                        }

                    }
                    $user_list = Users::findList(["id in (" . implode(',', $uid) . ")", 'columns' => 'id as user_id,username,avatar,last_phone_model']);
                    foreach ($user_list as $u) {
                        $order_list[] = $order_list_temp[$u['user_id']];
                    }
                    array_multisort($order_list, SORT_DESC, $user_list);
                }
                $json_data = json_encode(array_values($json_data), JSON_UNESCAPED_UNICODE);
            }
            $this->view->setVar('json_data', $json_data ? $json_data : json_encode([['name' => '广东', 'value' => 0]], JSON_UNESCAPED_UNICODE));
            $this->view->setVar('user_count', $count);
            $this->view->setVar('user_list', $user_list);
            $this->view->setVar('online_list', $list);

        } //历史
        else if ($type == 1) {
            //取最近一周的数据
            $week_date = [
                'keys' => StatManager::getDays(8),
                'values' => '',
                'labels' => '',
            ];
            unset($week_date['keys'][7]);
            $user_online = UserOnlineDay::findList('ymd>=' . str_replace('-', '', $week_date['keys'][0]) . " and ymd<=" . str_replace('-', '', $week_date['keys'][6]));;
            //七天数据
            $days = StatManager::getDays(8);

            unset($days[7]);
            $data = [];
            foreach ($days as $d) {
                for ($i = 0; $i <= 23; $i++) {
                    $data[$d][$i]['user'] = [];
                    $data[$d][$i]['count'] = 0;
                }
            }
            if ($user_online) {
                foreach ($user_online as $item) {
                    $detail = json_decode($item['detail'], true);
                    $day = substr($item['ymd'], 0, 4) . '-' . substr($item['ymd'], 4, 2) . '-' . substr($item['ymd'], 6);
                    foreach ($detail as $i => $d) {
                        $data[$day][$i]['count'] = $detail[$i];
                    }
                }
            }
            $i = 0;
            foreach ($week_date['keys'] as &$item) {
                $week_date['labels'] .= ",'" . $item . "'";
                $week_date['values'] = $data;
                $i++;
            }
            $week_date['labels'] = mb_substr($week_date['labels'], 1);
            $this->view->setVar('week_date', $week_date);
            //    UserOnline::find("")
        }
        $this->view->setVar('type', $type);

    }

    //分享统计
    public function shareAction()#分享统计#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 15);
        $key = $this->request->get('key', 'string', '');//关键字
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $type = $this->request->get('type', 'string', '0');//类型
        $platform = $this->request->get('platform', 'string', '');//平台
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username="' . $key . '" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($type) {
            $params[0][] = ' type ="' . $type . '"';
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($platform) {
            $params[0][] = ' site ="' . $platform . '"';
        }
        //排序
        if ($sort) {
            if ($sort == 'back_count') {
                $params['order'] = "back_count $sort_order,created desc";
            }
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';

        $count = SocialShare::dataCount($params[0]);
        $list = SocialShare::findList($params);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('type', $type);
        $this->view->setVar('list', $list);
        $this->view->setVar('platform', $platform);
        $this->view->setVar('key', $key);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    public function shareChartAction()#分享图表统计#
    {
        $today_ymd = date('Ymd');
        $yesterday_ymd = date('Ymd', strtotime("-1 day"));
        $share_data = SocialShare::findList(["ymd=" . $yesterday_ymd . " or ymd=" . $today_ymd, "columns" => 'user_id,type,ymd']);
        $data = ['today' => ['share_user_count' => 0, 'share_user' => [], 'total_count' => 0, 'discuss_count' => 0, 'invite_count' => 0, 'user_count' => 0, 'back_count' => 0],
            'yesterday' => ['share_user_count' => 0, 'share_user' => [], 'total_count' => 0, 'discuss_count' => 0, 'invite_count' => 0, 'user_count' => 0, 'back_count' => 0],
            'expect' => ['share_user_count' => 0, 'total_count' => 0, 'discuss_count' => 0, 'invite_count' => 0, 'user_count' => 0, 'back_count' => 0]
        ];
        if ($share_data) {
            foreach ($share_data as $item) {
                if ($item['ymd'] != $today_ymd) {
                    $key = 'yesterday';
                } else {
                    $key = 'today';
                }
                if (!in_array($item['user_id'], $data[$key]['share_user'])) {
                    $data[$key]['share_user'][] = $item['user_id'];
                    $data[$key]['share_user_count'] += 1;
                }
                //邀请
                if ($item['type'] == SocialManager::TYPE_INVITE) {
                    $data[$key]['invite_count'] += 1;
                } //动态
                else if ($item['type'] == SocialManager::TYPE_DISCUSS) {
                    $data[$key]['discuss_count'] += 1;
                } //名片
                else if ($item['type'] == SocialManager::TYPE_USER) {
                    $data[$key]['user_count'] += 1;
                }

                $data[$key]['back_count'] += $item['back_count'];
                $data[$key]['total_count'] += 1;
            }
            foreach ($data['today'] as $key => $i) {
                if ($key == 'share_user') {
                    continue;
                }
                if ($i == 0) {
                    $data['expect'][$key] = 0;
                } else {
                    $data['expect'][$key] = intval(round($i / intval(date('H')), 2) * 24);
                }
            }

        }
        $this->view->setVar('data', $data);

    }


    public function discussAction()#动态统计#
    {

        $today_ymd = date('Ymd');
        $yesterday_ymd = date('Ymd', strtotime("-1 day"));
        $discuss_data = SocialDiscuss::findList(["FROM_UNIXTIME(created,'%Y%m%d')=" . $yesterday_ymd . " or FROM_UNIXTIME(created,'%Y%m%d')=" . $today_ymd, "columns" => 'user_id,media_type,FROM_UNIXTIME(created,"%Y%m%d") as ymd']);
        $data = ['today' => ['share_user_count' => 0, 'share_user' => [], 'total_count' => 0, 'text_count' => 0, 'image_count' => 0, 'video_count' => 0, 'audio_count' => 0, 'package_count' => 0],
            'yesterday' => ['share_user_count' => 0, 'share_user' => [], 'total_count' => 0, 'text_count' => 0, 'image_count' => 0, 'video_count' => 0, 'audio_count' => 0, 'package_count' => 0],
            'expect' => ['share_user_count' => 0, 'total_count' => 0, 'text_count' => 0, 'image_count' => 0, 'video_count' => 0, 'audio_count' => 0, 'package_count' => 0]
        ];

        if ($discuss_data) {
            foreach ($discuss_data as $item) {
                if ($item['ymd'] != $today_ymd) {
                    $key = 'yesterday';
                } else {
                    $key = 'today';
                }
                if (!in_array($item['user_id'], $data[$key]['share_user'])) {
                    $data[$key]['share_user'][] = $item['user_id'];
                    $data[$key]['share_user_count'] += 1;
                }
                //文本
                if ($item['media_type'] == DiscussManager::TYPE_TEXT) {
                    $data[$key]['text_count'] += 1;
                } //图片
                else if ($item['media_type'] == DiscussManager::TYPE_PICTURE) {
                    $data[$key]['image_count'] += 1;
                } //视频
                else if ($item['media_type'] == DiscussManager::TYPE_VIDEO) {
                    $data[$key]['video_count'] += 1;
                } //音频
                else if ($item['media_type'] == DiscussManager::TYPE_AUDIO) {
                    $data[$key]['audio_count'] += 1;
                } //红包
                else if ($item['media_type'] == DiscussManager::TYPE_RED_PACKET) {
                    $data[$key]['package_count'] += 1;
                }
                $data[$key]['total_count'] += 1;
            }
            foreach ($data['today'] as $key => $i) {
                if ($key == 'share_user') {
                    continue;
                }
                if ($i == 0) {
                    $data['expect'][$key] = 0;
                } else {
                    $data['expect'][$key] = intval(round($i / intval(date('H')), 2) * 24);
                }
            }

        }
        $this->view->setVar('data', $data);

    }

    public function discussDetailAction()#动态详细详情#
    {
        $type = $this->request->get("type", 'int', '0');
        $this->view->setVar('type', $type);
    }

    public function groupAction()#群聊统计#
    {
        $today_ymd = date('Ymd');
        $yesterday_ymd = date('Ymd', strtotime("-1 day"));
        $group_data = Group::findList(["FROM_UNIXTIME(created,'%Y%m%d')=" . $yesterday_ymd . " or FROM_UNIXTIME(created,'%Y%m%d')=" . $today_ymd, "columns" => 'user_id,FROM_UNIXTIME(created,"%Y%m%d") as ymd,status']);
        $data = ['today' => ['share_user_count' => 0, 'share_user' => [], 'total_count' => 0, 'valid_count' => 0],
            'yesterday' => ['share_user_count' => 0, 'share_user' => [], 'total_count' => 0, 'valid_count' => 0],
            'expect' => ['share_user_count' => 0, 'total_count' => 0, 'valid_count' => 0]
        ];
        if ($group_data) {
            foreach ($group_data as $item) {
                if ($item['ymd'] != $today_ymd) {
                    $key = 'yesterday';
                } else {
                    $key = 'today';
                }
                if ($item['status'] == GroupManager::GROUP_STATUS_NORMAL) {
                    $data[$key]['valid_count'] += 1;
                }
                if (!in_array($item['user_id'], $data[$key]['share_user'])) {
                    $data[$key]['share_user'][] = $item['user_id'];
                    $data[$key]['share_user_count'] += 1;
                }
                $data[$key]['total_count'] += 1;
            }
            foreach ($data['today'] as $key => $i) {
                if ($key == 'share_user') {
                    continue;
                }
                if ($i == 0) {
                    $data['expect'][$key] = 0;
                } else {
                    $data['expect'][$key] = intval(round($i / intval(date('H')), 2) * 24);
                }
            }
        }
        $this->view->setVar('data', $data);
    }

    public function groupDetailAction()#群聊详细统计#
    {

    }

    //系统奖励
    public function rewardAction()#奖励统计#
    {

    }

    public function packageAction()#动态红包#
    {
    }

    public function squareAction()#广场红包#
    {

    }

    public function squarePackageAction()#广场红包统计#
    {
        $today_ymd = date('Ymd', strtotime("-1 day"));
        $yesterday_ymd = date('Ymd', strtotime("-2 day"));
        $stat = PackageDayStat::getByColumnKeyList(["ymd=" . $yesterday_ymd . " or ymd=" . $today_ymd, "columns" => 'ymd,package'], 'ymd');
        $data = [
            'today' => [],
            'yesterday' => [],
        ];
        if ($stat) {
            foreach ($stat as $item) {
                if ($item['ymd'] != $today_ymd) {
                    $key = 'yesterday';
                } else {
                    $key = 'today';
                }
                $data[$key] = json_decode($item['package'], true);
            }
        }
        $this->view->setVar('data', $data);
    }

    public function loginCountAction()#每天登录人数#
    {
        //$this->view->pick('users/loginCount');
    }

    public function retainUserAction()#留存用户#
    {

    }

    public function virtualCoinAction()#虚拟币统计#
    {


    }

    public function vipAction()#vip统计#
    {

    }

    public function appVersionAction()#用户App版本#
    {

    }
}

