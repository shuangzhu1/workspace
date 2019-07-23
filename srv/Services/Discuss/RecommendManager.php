<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/25
 * Time: 16:46
 */

namespace Services\Discuss;


use Components\Time;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussRecommend;
use Models\Social\SocialFav;
use Models\Social\SocialLike;
use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Phalcon\Mvc\User\Plugin;
use Services\Social\SocialManager;
use Services\User\UserStatus;
use Util\FilterUtil;

class RecommendManager extends DiscussBase
{
    private static $instance = null;

    private static $chunk = 500;//50条数据为一块

    /**
     * @return  RecommendManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //获取随机推荐列表
    public function list($uid, $page = 1, $limit = 20, $last_id = 0, $merge = false)
    {
        $res = ["data_list" => [], 'last_id' => 0, 'data_count' => 0];
        if ($page == 1) {
            $top = SocialDiscuss::findOne(["status=" . DiscussManager::STATUS_NORMAL . " and is_recommend=1", 'columns' => "id", 'order' => 'id desc']);
            if ($top) {
                $last_id = $top['id'];
            }
        }
        if ($last_id) {
            $range_start = $last_id;
            if ($last_id >= self::$chunk) {
                $res['last_id'] = $last_id - self::$chunk;
                $range_end = $last_id - self::$chunk;
            } else {
                $range_end = 0;
            }

            $range = $this->randNumber($range_end, $range_start, $limit);
            $where = "id in(" . implode(',', $range) . ") and scan_type=" . DiscussManager::SCAN_TYPE_ALL . " and status=" . DiscussManager::STATUS_NORMAL . " and user_id<>" . $uid . " and is_recommend=1";

            //过滤转发的
            $where .= " and share_original_item_id=0 and media_type=" . DiscussManager::TYPE_VIDEO;

            $black_list = UserBlacklist::findList(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as user_id']);
            if ($black_list) {
                $where .= " and user_id not in (" . implode(',', array_column($black_list, 'user_id')) . ') ';
            }
            $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,is_top,created,address,lng,lat,scan_type,allow_download,package_id']);

            //没有找到数据 并且数据没有遍历完
            if (!$list && $range_end > 0) {
                return $this->list($uid, $page + 1, $limit, $res['last_id']);
            }

            $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
            $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合

            $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
            $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合
            //是否点过赞 收藏过
            $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
            $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            $collects = SocialFav::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //收藏集合


            $order_column = [];//排序


            foreach ($list as &$item) {
                $key = array_search($item['discuss_id'], $range);
                $order_column[] = $key;

                $item['user_info'] = $user_info[$item['uid']];
                $item['user_info']['is_contact'] = 0;
                $item['user_info']['contact_mark'] = ($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark'])) ? $user_personal_setting[$item['uid']]['mark'] : '';
                $item['user_info']['is_attention'] = 0;
                //联系人
                if (isset($user_contact[$item['uid']])) {
                    $item['user_info']['is_contact'] = 1;
                    $item['user_info']['contact_mark'] = $user_contact[$item['uid']]['mark'];
                    $item['user_info']['is_attention'] = 1;
                } //已关注
                elseif (isset($user_attention[$item['uid']])) {
                    $item['user_info']['is_attention'] = 1;
                } else {
                }
                $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
                $item['is_collection'] = isset($collects[$item['discuss_id']]) ? 1 : 0;
                //转发的原始内容
                $item['original_info'] = (object)[];
                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item = array_merge($item, $this->getOriginalInfo($uid, $item));

                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);

            }
            $res['data_list'] = $list;
            $res['data_list'] && array_multisort($order_column, SORT_ASC, $res['data_list']);


            //动态一次补齐
            if (!$merge && count($res['data_list']) < 5 && $res['last_id'] > 0) {
                $merge_list = $this->list($uid, $page + 1, 10, $res['last_id'], true);
                $res['data_list'] = array_merge($res['data_list'], $merge_list['data_list']);
                $res['last_id'] = $merge_list['last_id'];
            }
        }
        return $res;
    }

    //固定推荐列表
    public function staticList($uid, $page = 1, $limit = 20, $last_id = 0)
    {
        $res = ["data_list" => [], 'last_id' => 0, 'data_count' => 0];
        //  $where = "scan_type=" . DiscussManager::SCAN_TYPE_ALL . " and status=" . DiscussManager::STATUS_NORMAL . " and user_id<>" . $uid . " and is_recommend=1";
        $where = " user_id<>" . $uid;


        //过滤转发的
        // $where .= " and share_original_item_id=0 ";
        //排序
        $order = "created desc";

        //版本切换
        if ((version_compare(app_version, '1.0.5', '>') && client_type == 'ios') || (version_compare(app_version, '1.0.49', '>') && client_type == 'android')) {
            //  $order = "recommend_time desc,created desc";
            if ($last_id) {
                //$where .= " and recommend_time<" . $last_id;
                $where .= " and created<" . $last_id;
            }
        } else {
            if ($last_id) {
                //$where .= " and id<" . $last_id;
                $where .= " and discuss_id<" . $last_id;
            }
        }


        //去掉黑名单的用户
        $black_list = UserBlacklist::findList(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as user_id']);
        if ($black_list) {
            $where .= " and user_id not in (" . implode(',', array_column($black_list, 'user_id')) . ') ';
        }
        $person_setting = UserPersonalSetting::getColumn(['(owner_id=' . $uid . ' and scan_his_discuss=0) or (user_id=' . $uid . ' and scan_my_discuss=0)', 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as uid'], 'uid');
        if ($person_setting) {
            $where .= " and (user_id not in (" . implode(',', $person_setting) . '))';
        }
        $list = SocialDiscussRecommend::findList([$where, 'offset' => ($page - 1) * $limit,
            'order' => $order, 'limit' => $limit, 'columns' => 'user_id as uid,discuss_id']);

        /*  $list = SocialDiscuss::findList([$where, 'offset' => ($page - 1) * $limit,
              'order' => $order,
              "limit" => $limit, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,recommend_time,reward_cnt']);*/

        if ($list) {
            $discuss_ids = array_column($list, 'discuss_id');
            $list = SocialDiscuss::findList(['id in (' . implode(',', $discuss_ids) . ')',
                'order' => "recommend_time desc,created desc",
                'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,recommend_time,reward_cnt']);

            $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
            $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合

            $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
            $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合
            //是否点过赞 收藏过
            $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
            $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            $collects = SocialFav::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //收藏集合


            foreach ($list as &$item) {
                $item['user_info'] = $user_info[$item['uid']];
                $item['user_info']['is_contact'] = 0;
                $item['user_info']['contact_mark'] = ($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark'])) ? $user_personal_setting[$item['uid']]['mark'] : '';
                $item['user_info']['is_attention'] = 0;
                //联系人
                if (isset($user_contact[$item['uid']])) {
                    $item['user_info']['is_contact'] = 1;
                    /* if (!$item['user_info']['contact_mark']) {
                         $item['user_info']['contact_mark'] =$user_contact[$item['uid']]['mark'];
                     }*/
                    $item['user_info']['is_attention'] = 1;
                } //已关注
                elseif (isset($user_attention[$item['uid']])) {
                    $item['user_info']['is_attention'] = 1;
                } else {
                }
                $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
                $item['is_collection'] = isset($collects[$item['discuss_id']]) ? 1 : 0;
                //转发的原始内容
                $item['original_info'] = (object)[];


                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
                $item = array_merge($item, $this->getOriginalInfo($uid, $item));


            }
            //版本切换
            if ((version_compare(app_version, '1.0.5', '>') && client_type == 'ios') || (version_compare(app_version, '1.0.49', '>') && client_type == 'android')) {
                $res['last_id'] = $list[count($list) - 1]['recommend_time'];
            } else {
                $res['last_id'] = $list[count($list) - 1]['discuss_id'];
            }
        } else {
            $res['last_id'] = $last_id;
        }
        $res['data_list'] = $list;

        return $res;
    }


    //生成一个打乱顺序的数组
    public function randNumber($start, $end, $limit)
    {
        $res = [];
        if ($end) {
            for ($i = $end; $i > $start; $i--) {
                $res[] = $i;
            }
            shuffle($res);
            $res = array_slice($res, 0, $limit);
        }
        return $res;
    }

}