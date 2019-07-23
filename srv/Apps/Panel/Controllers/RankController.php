<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/11
 * Time: 15:47
 */

namespace Multiple\Panel\Controllers;


use Models\Group\Group;
use Models\Social\SocialDiscuss;
use Models\Social\SocialLike;
use Models\User\UserCountStat;
use Models\User\UserGift;
use Models\User\UserGiftLog;
use Models\User\UserInfo;
use Models\User\UserLoginLog;

class RankController extends ControllerBase
{
    //榜单
    public function topAction()#榜单top#
    {
        $type = $this->request->get("type", 'int', 1); //1-粉丝 2-动态 3-被点赞
        $res = [];
        //粉丝
        if ($type == 1) {
            $res = UserCountStat::findList(['', 'columns' => 'user_id,fans_cnt', 'order' => 'fans_cnt desc', 'limit' => 100]);
            if ($res) {
                $user_id = array_column($res, 'user_id');
                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_id) . ")", 'columns' => 'user_id,username,avatar,sex,grade'], 'user_id');
                foreach ($res as &$item) {
                    $item = array_merge($item, $user_info[$item['user_id']]);
                }
            }
        }
        //动态
        if ($type == 2) {
            $res = UserCountStat::findList(['', 'columns' => 'user_id,discuss_cnt', 'order' => 'discuss_cnt desc', 'limit' => 100]);
            if ($res) {
                $user_id = array_column($res, 'user_id');
                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_id) . ")", 'columns' => 'user_id,username,avatar,sex,grade'], 'user_id');
                foreach ($res as &$item) {
                    $item = array_merge($item, $user_info[$item['user_id']]);
                }
            }
        }
        //获得点赞
        if ($type == 3) {
            $res = SocialDiscuss::findList(['', 'group' => 'user_id', 'columns' => 'sum(like_cnt) as like_cnt,user_id', 'order' => 'like_cnt desc', 'limit' => 100]);
            if ($res) {
                $user_id = array_column($res, 'user_id');
                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_id) . ")", 'columns' => 'user_id,username,avatar,sex,grade'], 'user_id');
                foreach ($res as &$item) {
                    $item = array_merge($item, $user_info[$item['user_id']]);
                }
            }
        }
        //魅力
        if ($type == 4) {
            $res = UserInfo::findList(['', 'columns' => 'charm,username,avatar,user_id,sex,grade', 'order' => 'charm desc', 'limit' => 100]);
        }
        //登录天数
        if ($type == 5) {
            $res = $this->db->query("select user_id,count(*) as login_time from (select user_id,ymd from user_login_log GROUP BY user_id,ymd) as g GROUP BY user_id ORDER BY login_time desc limit 100")->fetchAll(\PDO::FETCH_ASSOC);
            if ($res) {
                $user_id = array_column($res, 'user_id');
                $user_info = $this->db->query("select u.id as user_id,u.username,u.grade,p.sex,u.avatar,u.last_login_time from users as u left join user_profile as p on u.id=p.user_id where u.id in (" . implode(',', $user_id) . ")")->fetchAll(\PDO::FETCH_ASSOC);
                $user_info = array_combine(array_column($user_info, 'user_id'), $user_info);
                // $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_id) . ")", 'columns' => 'user_id,username,avatar,sex,grade'], 'user_id');
                foreach ($res as &$item) {
                    $item = array_merge($item, $user_info[$item['user_id']]);
                }
            }
        }
        //收到礼物
        if ($type == 6) {
            $res = UserGift::findList(['', 'columns' => 'user_id,sum(own_count) as gift_count', 'group' => 'user_id', 'order' => 'gift_count desc', 'limit' => 100]);
            if ($res) {
                $user_id = array_column($res, 'user_id');
                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_id) . ")", 'columns' => 'user_id,username,avatar,sex,grade'], 'user_id');
                foreach ($res as &$item) {
                    $item = array_merge($item, $user_info[$item['user_id']]);
                }
            }
        }
        //发出的礼物
        if ($type == 7) {
            $res = UserGiftLog::findList(['', 'columns' => 'owner_id,count(*) as gift_count', 'group' => 'owner_id', 'order' => 'gift_count desc', 'limit' => 100]);
            if ($res) {
                $user_id = array_column($res, 'owner_id');
                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_id) . ")", 'columns' => 'user_id,username,avatar,sex,grade'], 'user_id');
                foreach ($res as &$item) {
                    $item = array_merge($item, $user_info[$item['owner_id']]);
                }
            }
        }
        //创建群聊
        if ($type == 8) {
            $res = Group::findList(['', 'columns' => 'user_id,count(*) as group_count', 'group' => 'user_id', 'order' => 'group_count desc', 'limit' => 100]);
            if ($res) {
                $user_id = array_column($res, 'user_id');
                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_id) . ")", 'columns' => 'user_id,username,avatar,sex,grade'], 'user_id');
                foreach ($res as &$item) {
                    $item = array_merge($item, $user_info[$item['user_id']]);
                }
            }
        }
        $this->view->setVar('list', $res);
        $this->view->setVar('type', $type);
    }
}