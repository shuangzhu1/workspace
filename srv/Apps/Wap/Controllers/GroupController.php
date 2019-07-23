<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/15
 * Time: 9:08
 */

namespace Multiple\Wap\Controllers;

use Models\Group\Group;
use Models\Group\GroupMember;
use Models\Statistics\StatisticsGroupWeek;
use Models\User\Users;
use Services\Stat\StatManager;
use Services\User\GroupManager;

class GroupController extends ControllerBase
{
    public function statAction()
    {
        $gid = $this->request->get("gid", 'int', 0);
        $uid = $this->request->get("uid", 'int', 0);
        if (!$gid || !$uid) {
            $this->error404();
            return;
        }
        //  GroupManager::init()->groupInfo()
        $group_info = Group::findOne(['id=' . $gid . " and status=" . GroupManager::GROUP_STATUS_NORMAL, 'columns' => 'user_id,created,if(name<>"",name,default_name) as name,user_id,if(avatar<>"",avatar,default_avatar) as avatar']);
        if (!$group_info) {
            $this->error404();
            return;
        }
        if (!GroupMember::exist(['gid=' . $gid . " and user_id=" . $uid . " and (member_type=" . GroupManager::GROUP_MEMBER_ADMIN . " or member_type=" . GroupManager::GROUP_MEMBER_ADMIN . ")"])) {
            $this->error404();
            return;
        }
        $avatar = Users::findOne(['id = ' . $group_info['user_id'], 'columns' => 'avatar,username']);
        $group_info['admin_avatar'] = $avatar['avatar'];
        $group_info['admin_name'] = $avatar['username'];

        $stat_data = StatisticsGroupWeek::findOne(['gid=' . $gid]);
        $days = StatManager::getDays(8);
        array_pop($days);
        $res = ['member_cnt' => [], 'speakers' => [], 'message_cnt' => [], 'show_no_speaker_label' => 1, 'member_m_top' => []];
        $labels = [];
        if ($stat_data) {
            $speakers = json_decode($stat_data['speakers'], true);
            $member_cnt = json_decode($stat_data['member_cnt'], true);
            $message_cnt = json_decode($stat_data['message_cnt'], true);
            $member_m_top = json_decode($stat_data['member_m_top'], true);

            foreach ($days as $item) {
                $key = explode('-', $item);
                $key = $key[1] . '.' . intval($key[2]);
//                $labels[] = $key;
                $labels[] = "'" . $key . "'";
                $res['member_cnt'][$key] = isset($member_cnt[$item]) ? $member_cnt[$item] : 0;
                $res['message_cnt'][$key] = isset($message_cnt[$item]) ? $message_cnt[$item] : 0;
                if (isset($speakers[$item])) {
                    $res['speakers'][$key] = $speakers[$item];
                    if ($speakers[$item] != 0) {
                        $res['show_no_speaker_label'] = 0;
                    }
                } else {
                    $res['speakers'][$key] = 0;
                }
            }
            $uids = implode(',', array_keys($member_m_top));
            $members = GroupMember::getByColumnKeyList(['gid=' . $gid . " and user_id in(" . $uids . ')', 'columns' => 'member_type,nick,user_id'], 'user_id');
            $user_info = Users::getByColumnKeyList(['id in(' . $uids . ')', 'columns' => 'id,username,avatar'], 'id');
            $i = 0;
            foreach ($member_m_top as $k => $m) {
                if ($i >= 20) {
                    break;
                }
                $tmp = [
                    'uid' => $k,
                    'count' => $m,
                    'member_type' => !empty($members[$k]['member_type']) ? $members[$k]['member_type'] : 1,
                    'username' => $user_info[$k]['username'],
                    'avatar' => $user_info[$k]['avatar']];
                $res['member_m_top'][] = $tmp;
                $i++;
            }

        } else {
            foreach ($days as $item) {
                $key = explode('-', $item);
                $key = $key[1] . '.' . intval($key[2]);
                $labels[] = "'" . $key . "'";
                $res['member_cnt'][$key] = 0;
                $res['speakers'][$key] = 0;
                $res['message_cnt'][$key] = 0;
                $res['member_m_top'] = [];

            }
        }
        $res['speakers'] = implode(",", $res['speakers']);
        $res['member_cnt'] = implode(",", $res['member_cnt']);
        $res['message_cnt'] = implode(",", $res['message_cnt']);

        $this->view->setVar('data', $res);
        $this->view->setVar('days', implode(',', $labels));
        $this->view->setVar('group', $group_info);
        $this->view->title = "群成员数据";
    }
}