<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/20
 * Time: 17:22
 */

namespace Multiple\Api\Controllers;


use Models\User\UserProfile;
use Models\User\Users;
use Services\Discuss\DiscussManager;
use Services\User\ContactManager;
use Services\User\UserStatus;
use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserInfo;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;

class RecommendController extends ControllerBase
{
    /*推荐用户-可能认识的人*/
    public function userAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $attention_uids = UserAttention::getColumn(["owner_id=" . $uid, 'columns' => 'user_id as uids', 'limit' => 1000], 'uids');
        // $uids = $this->db->query("select owner_id,group_concat(user_id) as uids from user_attention where owner_id=" . $uid . ' group by owner_id limit 1000')->fetch(\PDO::FETCH_ASSOC);


        $blacklist = UserBlacklist::getColumn(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uids'], 'uids');

        //已经关注过的用户不出现
        if ($attention_uids) {
            $uids = implode(',', $attention_uids) . ',' . $uid;
        } else {
            $uids = $uid;
        }
        //拉对方黑 或者被对方拉黑的用户都不应该出现
        if ($blacklist) {
            $uids .= ',' . implode(',', $blacklist);
        }
        $users = UserInfo::findList(['user_id not in (' . $uids . ') and status=' . UserStatus::STATUS_NORMAL . ' and user_type = ' . UserStatus::USER_TYPE_NORMAL, 'columns' => 'user_id as uid,username,true_name,avatar,sex,is_auth,auth_type,job,industry,company,grade,signature,"" as newest_dynamic,birthday,charm,constellation', 'limit' => 10, 'order' => 'rand()']);
        if ($users) {
            foreach ($users as $item) {
                //星座
                if ($item['constellation']) {
                    $item['constellation'] = UserStatus::$constellation[$item['constellation']];
                } else {
                    $item['constellation'] = '';
                }
            }
            //最新动态
            $discuss = ContactManager::init()->getNewestDynamic($uid, array_column($users, 'uid'));
            if ($discuss) {
                foreach ($users as &$item) {
                    if (isset($discuss[$item['uid']])) {
                        if ($discuss[$item['uid']]['content'] == '') {
                            $item['newest_dynamic'] = ($discuss[$item['share']]['share_original_item_id'] > 0 ? "转发" : '') . DiscussManager::$media_type[$discuss[$item['share']]['media_type']];
                        } else {
                            $item['newest_dynamic'] = FilterUtil::unPackageContentTag($discuss[$item['uid']]['content'], $uid);
                        }
                    }
                }

            }
        }

        $this->ajax->outRight($users);
    }

    /*注册-可能感兴趣的人*/
    public function interestUserAction()
    {
        $user_count = 9;//最多显示的人
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $profile = UserProfile::findOne(['user_id=' . $uid, 'columns' => 'sex']);
        //----异性,非机器人 新用户优先推荐
        $where = 'user_id <> ' . $uid . ' and status=' . UserStatus::STATUS_NORMAL . ' and user_type=' . UserStatus::USER_TYPE_NORMAL . ' and sex=' . ($profile['sex'] == 1 ? 2 : 1);
        $users = UserInfo::findList([$where, 'columns' => 'user_id as uid,username,true_name,avatar,sex,is_auth,auth_type,job,industry,company,grade,signature', 'limit' => $user_count, 'order' => 'created desc']);

        $count = count($users);
        //----少于9个 同性补齐
        if ($count < $user_count) {
            $users2 = UserInfo::findList(['user_id <> ' . $uid . ' and status=' . UserStatus::STATUS_NORMAL . ' and user_type=' . UserStatus::USER_TYPE_NORMAL . ' and sex=' . ($profile['sex'] == 1 ? 1 : 2), 'columns' => 'user_id as uid,username,true_name,avatar,sex,is_auth,auth_type,job,industry,company,grade,signature', 'limit' => $user_count - $count, 'order' => 'created desc']);
            if ($users2) {
                $users = array_merge($users, $users2);
                $count += count($users2);
            }
        }
        //----少于9个 异性机器人补齐
        if ($count < $user_count) {
            $users3 = UserInfo::findList(['user_id <> ' . $uid . ' and status=' . UserStatus::STATUS_NORMAL . ' and user_type=' . UserStatus::USER_TYPE_ROBOT . ' and sex=' . ($profile['sex'] == 1 ? 2 : 1), 'columns' => 'user_id as uid,username,true_name,avatar,sex,is_auth,auth_type,job,industry,company,grade,signature', 'limit' => $user_count - $count, 'order' => 'created desc']);
            if ($users3) {
                $users = array_merge($users, $users3);
                $count += count($users3);
            }
        }
        //----少于9个 同性机器人补齐
        if ($count < $user_count) {
            $users4 = UserInfo::findList(['user_id <> ' . $uid . ' and status=' . UserStatus::STATUS_NORMAL . ' and user_type=' . UserStatus::USER_TYPE_ROBOT . ' and sex=' . ($profile->sex == 1 ? 1 : 2), 'columns' => 'user_id as uid,username,true_name,avatar,sex,is_auth,auth_type,job,industry,company,grade,signature', 'limit' => $user_count - $count, 'order' => 'created desc']);
            if ($users4) {
                $users = array_merge($users, $users4);
            }
        }
        $this->ajax->outRight($users);
    }

    /*匹配 和附近的异性打招呼*/
    public function matchAction()
    {
        $uid = $this->uid;
        $lng = $this->request->get("lng", 'string', '');//经度
        $lat = $this->request->get("lat", 'string', '');//纬度
        $page = $this->request->get('page', 'int', 1);//第几页
        $limit = $this->request->get('limit', 'int', 20);//每页数量
        if (!$uid || !$lng || !$lat) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        Ajax::outRight(UserStatus::getInstance()->matchUser($uid, $lng, $lat, $page, $limit));
    }
}