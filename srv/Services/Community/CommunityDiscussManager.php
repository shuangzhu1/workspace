<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/3
 * Time: 14:34
 */

namespace Services\Community;


use Models\Community\Community;
use Models\Community\CommunityAttention;
use Models\Community\CommunityDiscuss;
use Models\Community\CommunityProfile;
use Models\Social\SocialLike;
use Models\User\UserAttention;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Phalcon\Mvc\User\Plugin;
use Services\Discuss\DiscussManager;
use Services\Im\ImManager;
use Services\Site\SensitiveManager;
use Services\Social\SocialManager;
use Services\User\UserStatus;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;
use Util\Time;

class CommunityDiscussManager extends Plugin
{
    private static $instance = null;
    private static $ajax = null;

    /**
     * @param bool $is_cli
     * @return  CommunityDiscussManager
     */
    public static function getInstance($is_cli = false)
    {
        if (!self::$instance) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    private function __construct($is_cli)
    {
        self::$ajax = new Ajax();
    }

    /** 发动态
     * @param $uid
     * @param $comm_id --社区id
     * @param $media_type --类型 1-纯文字 2-视频 3-图片【可以包含文字】
     * @param $content -文字内容
     * @param $media -图片/视频地址
     * @param bool $open_location -是否公开位置
     * @param string $address -公开地址
     * @param string $lng -经度
     * @param string $lat -纬度
     * @param int $allow_download -是否允许下载
     * @param int $package_id -红包id
     * @param string $package_info -红包信息
     * @param string $area_code -地区码
     * @param int $is_top -是否置顶
     * @return bool
     */
    public function publish($comm_id, $uid, $media_type, $content, $media, $open_location = false, $address = '', $lng = '', $lat = '', $allow_download = 1, $package_id = 0, $package_info = '', $area_code = '', $is_top = 0)
    {
        $attention = CommunityAttention::findOne(['comm_id=' . $comm_id . " and user_id=" . $uid, 'columns' => 'role']);
        if (!$attention) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_NOT_MEMBER);
        }
        $community_profile = CommunityProfile::findOne(['comm_id=' . $comm_id, 'columns' => 'discuss_level']);
        if (!$community_profile) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //仅允许管理员发布
        if ($community_profile['discuss_level'] == 1) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_DISCUSS_NEED_ADMIN);
        }
        $discuss_data = [
            'user_id' => $uid,
            'media_type' => $media_type,
            'content' => $content,
            'media' => $media,
            'allow_download' => $allow_download == 0 ? 0 : 1,
            'scan_type' => DiscussManager::SCAN_TYPE_ALL,
            'created' => time(),
            'area_code' => $area_code,
            'comm_id' => $comm_id
        ];
        //公开位置
        if ($open_location) {
            $discuss_data['address'] = $address;
            $discuss_data['lng'] = $lng;
            $discuss_data['lat'] = $lat;
        }
        if ($is_top) {
            $discuss_data['is_top'] = 1;
            $discuss_data['top_time'] = $discuss_data['created'];
        }
        if ($package_id) {
            $discuss_data['package_id'] = $package_id;
        }
        if ($package_info) {
            $discuss_data['package_info'] = htmlspecialchars_decode($package_info);
        }

        $at_uid = FilterUtil::packageContentTagApp($discuss_data['content'], $uid);

        $discuss_data['content'] = SensitiveManager::filterContent($discuss_data['content']);
        $discuss = new CommunityDiscuss();
        if (!$discuss_id = $discuss->insertOne($discuss_data)) {
            Debug::log('publish discuss:' . json_encode($discuss->getMessages(), true), 'error');
            return false;
        }

        //发at消息
        if ($at_uid) {
            foreach ($at_uid as $item) {
                ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $discuss_id, 'comm_id' => $comm_id, 'type' => SocialManager::TYPE_COMMUNITY_DISCUSS, 'content' => $content, 'user_id' => $uid, 'to_user_id' => $item]);
            }
        }

        return $discuss_id;
    }

    /**社区动态列表
     * @param $comm_id
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function list($comm_id, $uid, $page = 1, $limit = 20)
    {
        $res = ['data_list' => []];
        $where = 'status=' . DiscussManager::STATUS_NORMAL . " and comm_id=" . $comm_id;

        $list = CommunityDiscuss::findList([$where, 'order' => 'top_time desc,created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'columns' => 'id as discuss_id,user_id as uid,created,is_top,view_cnt,content,media_type,media,address,lng,lat,comment_cnt,like_cnt']);
        if ($list) {
            $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
            $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合

            // if ($type) {
            $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
            $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合

            $comm_attention = CommunityAttention::getColumn(['comm_id=' . $comm_id . " and user_id in (" . $user_ids . ")", 'columns' => 'role,user_id'], 'role', 'user_id');
            foreach ($list as &$item) {
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
                if (isset($comm_attention[$item['uid']])) {
                    $item['user_info']['role'] = intval($comm_attention[$item['uid']]);
                } else {
                    $item['user_info']['role'] = -1;
                }
            }
            $list = $this->format($uid, $list);
            $res['data_list'] = $list;
        }
        return $res;
    }

    //列表数据格式化
    /**
     * @param $uid
     * @param $list
     * @return array
     */
    public function format($uid, $list)
    {
        if ($list) {
            $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
            $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_COMMUNITY_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            foreach ($list as &$item) {
                $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
                //转发的原始内容
                $item['original_info'] = (object)[];

                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
                // $item = array_merge($item, $this->getOriginalInfo($uid, $item));
            }

        }
        if ($list) {
            $list = array_values($list);
        }
        return $list;
    }

    /**删除社区动态
     * @param $uid
     * @param $discuss_id
     * @return bool
     */
    public function deleteDiscuss($uid, $discuss_id)
    {
        $discuss = CommunityDiscuss::findOne(['id=' . $discuss_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'columns' => 'id,user_id,comm_id']);
        if (!$discuss) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //删除动态的人不是动态发布人
        if ($discuss['user_id'] != $uid) {
            $community_attention = CommunityAttention::findOne(['comm_id=' . $discuss['comm_id'] . " and user_id=" . $uid, 'columns' => 'role']);
            if (!$community_attention || ($community_attention['role'] != CommunityManager::role_owner && $community_attention['role'] != CommunityManager::role_admin)) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "权限不足");
            }
        }
        if (!CommunityDiscuss::updateOne(['status' => DiscussManager::STATUS_DELETED, 'modify' => time()], 'id=' . $discuss_id)) {
            return false;
        }
        return true;
    }

    /**动态置顶
     * @param $uid
     * @param $discuss_id
     * @return bool
     */
    public function topDiscuss($uid, $discuss_id)
    {
        try {
            $discuss = CommunityDiscuss::findOne(['id=' . $discuss_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'columns' => 'is_top,comm_id']);
            if (!$discuss) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            //已经置过顶了
            if ($discuss['is_top'] == 1) {
                return true;
            }
            //删除动态的人不是动态发布人
            if ($discuss['user_id'] != $uid) {
                $community_attention = CommunityAttention::findOne(['comm_id=' . $discuss['comm_id'] . " and user_id=" . $uid, 'columns' => 'role']);
                if (!$community_attention || ($community_attention['role'] != CommunityManager::role_owner && $community_attention['role'] != CommunityManager::role_admin)) {
                    Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "权限不足");
                }
            }
            $data['is_top'] = 1;
            $data['modify'] = time();
            $data['top_time'] = $data['modify'];
            if (!CommunityDiscuss::updateOne($data, 'id=' . $discuss_id)) {
                throw new \Exception("更新动态失败");
            }
            return true;
        } catch (\Exception $e) {
            Debug::log($e->getMessage(), 'error');
            return false;
        }
    }

    /**动态取消置顶
     * @param $uid
     * @param $discuss_id
     * @return bool
     */
    public function unTopDiscuss($uid, $discuss_id)
    {
        $discuss = CommunityDiscuss::findOne(['id=' . $discuss_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'columns' => 'is_top,comm_id']);
        if (!$discuss) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //已经置过顶了
        if ($discuss['is_top'] == 0) {
            return true;
        }
        //删除动态的人不是动态发布人
        if ($discuss['user_id'] != $uid) {
            $community_attention = CommunityAttention::findOne(['comm_id=' . $discuss['comm_id'] . " and user_id=" . $uid, 'columns' => 'role']);
            if (!$community_attention || ($community_attention['role'] != CommunityManager::role_owner && $community_attention['role'] != CommunityManager::role_admin)) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "权限不足");
            }
        }
        $data['is_top'] = 0;
        $data['modify'] = time();
        $data['top_time'] = 0;
        if (!CommunityDiscuss::updateOne($data, 'id=' . $discuss_id)) {
            return false;
        }
        return true;
    }

}