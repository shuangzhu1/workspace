<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/20
 * Time: 17:48
 */

namespace Multiple\Api\Controllers;


use Models\Social\SocialDiscuss;
use Models\Social\SocialFav;
use Models\Social\SocialLike;
use Models\User\UserAttention;
use Models\User\UserContactMember;
use Models\User\UserPersonalSetting;
use Services\Discuss\DiscussManager;
use Services\Site\SearchManager;
use Services\Social\SocialManager;
use Services\User\UserStatus;
use Models\User\UserBlacklist;
use Models\User\UserInfo;
use Util\Ajax;
use Util\FilterUtil;
use Util\Time;

class SearchController extends ControllerBase
{
    /*--名人推荐--*/
    public function userRecommendAction()
    {
        $uid = $this->uid;
        //$limit = $this->request->get('limit', 'int', 20);
        //  $page = $this->request->get('page', 'int', 1);
        $uids = $this->db->query("select group_concat(user_id) as uids,owner_id from user_attention where owner_id=" . $uid . ' group by owner_id')->fetch(\PDO::FETCH_ASSOC);
        $blacklist = UserBlacklist::getColumn(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uids'], 'uids');

        //已经关注过的用户不出现
        if ($uids && $uids['uids']) {
            $uids = $uids['uids'] . ',' . $uid;
        } else {
            $uids = $uid;
        }
        //拉对方黑 或者被对方拉黑的用户都不应该出现
        if ($blacklist) {
            $uids .= ',' . implode(',', $blacklist);
        }

        $users = UserInfo::findList(['user_id not in (' . $uids . ') and is_auth=1 and status=' . UserStatus::STATUS_NORMAL . ' and user_type=' . UserStatus::USER_TYPE_NORMAL, 'columns' => 'user_id as uid,username,true_name,avatar,sex,is_auth,auth_type,job,industry,company,grade', 'limit' => 8, 'order' => 'rand()']);
        if ($users) {
            $fans_list = UserAttention::getColumn(['user_id in (' . implode(',', array_column($users, 'uid')) . ')', 'group' => 'uid', 'columns' => 'count(1) as count,user_id as uid'], 'count', 'uid');
            foreach ($users as &$item) {
                $item['fans_count'] = isset($fans_list[$item['uid']]) ? intval($fans_list[$item['uid']]) : 0;
            }
        }
        $this->ajax->outRight($users);
    }

    //认证用户
    public function authUserAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $type = $this->request->get("type", 'int', 0);

        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $uids = $this->db->query("select group_concat(user_id) as uids,owner_id from user_attention where owner_id=" . $uid . ' group by owner_id')->fetch(\PDO::FETCH_ASSOC);
        $blacklist = UserBlacklist::getColumn(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uids'], 'uids');

        //已经关注过的用户不出现
        if ($uids && $uids['uids']) {
            $uids = $uids['uids'] . ',' . $uid;
        } else {
            $uids = $uid;
        }
        //拉对方黑 或者被对方拉黑的用户都不应该出现
        if ($blacklist) {
            $uids .= ',' . implode(',', $blacklist);
        }
        $where = 'user_id not in (' . $uids . ') and is_auth=1 and status=' . UserStatus::STATUS_NORMAL . ' and user_type=' . UserStatus::USER_TYPE_NORMAL;
        if ($type) {
            $where .= " and auth_type=" . $type;
        }
        $users = UserInfo::findList([$where, 'columns' => 'user_id as uid,username,true_name,avatar,sex,auth_desc,grade,created', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc']);
        $this->ajax->outRight($users);
    }

    /*--动态推荐--*/
    public function discussRecommendAction()
    {
        $uid = $this->uid;
        $limit = $this->request->get('limit', 'int', 3);

        //状态为正常,不是转发的,公开发布,图片/视频,1个月内
        $where = " status=1 and share_original_item_id=0 and scan_type=" . DiscussManager::SCAN_TYPE_ALL . ' and media_type in (' . DiscussManager::TYPE_VIDEO . ',' . DiscussManager::TYPE_PICTURE . ')' . " and created>=" . strtotime('-1 month');
        $not_user = [$uid];

        $black_list = UserBlacklist::getColumn(["owner_id=" . $uid . " or user_id=" . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uid'], 'uid');
        //去除黑名单用户
        if ($black_list) {
            $not_user = $black_list;
        }
        //去除设置为不看其动态/不允许我看起动态
        $look_discuss = UserPersonalSetting::getColumn(["(scan_his_discuss=0 and owner_id=" . $uid . ') or (scan_my_discuss=0 and user_id=' . $uid . ')', 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uid'], 'uid');
        $look_discuss && $not_user = array_merge($not_user, $look_discuss);

        //去除好友的及关注的
        $attention = UserAttention::getColumn(["owner_id=" . $uid, 'columns' => 'user_id as uid'], 'uid');
        $attention && $not_user = array_merge($not_user, $attention);

        if ($not_user) {
            $not_user = array_unique($not_user);
            $where .= " and user_id not in (" . implode(',', $not_user) . ') ';
        }

        $list = SocialDiscuss::findList([$where, "order" => 'rand_column', 'limit' => $limit, "columns" => "id as discuss_id,rand() as rand_column,user_id as uid,content,media,media_type,like_cnt,comment_cnt,created"]);
        if ($list) {
            $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
            $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,username,avatar'], 'uid');//用户信息集合
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合
            foreach ($list as &$item) {
                $item['user_info'] = $user_info[$item['uid']];
                if (($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark']))) {
                    $item['user_info']['username'] = $user_personal_setting[$item['uid']]['mark'];
                }
            }
            foreach ($list as &$item) {
                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
            }
        }
        $this->ajax->outRight(['data_list' => $list]);
    }

    //综合搜索
    public function complexAction()
    {
        $s = $this->request->get("s", 'string', '');//关键字
        $lng = $this->request->get("lng", 'string', '');
        $lat = $this->request->get("lat", 'string', '');
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $type = $this->request->get("type", 'int', 1); //0-全部 1-用户 20-店铺
        if (!$lat || !$lng || !$s) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        $search = new SearchManager($this->uid, $type, $s, $lng, $lat, $page, $limit);
        $res = $search->complex();
        $this->ajax->outRight($res);
    }
}