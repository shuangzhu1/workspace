<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/10
 * Time: 9:48
 */

namespace Services\Community;


use Components\Kafka\Producer;
use Components\Time;
use Models\Community\CommunityAttention;
use Models\Community\CommunityNews;
use Models\Community\CommunityProfile;
use Models\Social\SocialLike;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Phalcon\Mvc\User\Plugin;
use Services\Kafka\TopicDefine;
use Services\Site\SensitiveManager;
use Services\Social\SocialManager;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;

class CommunityNewsManager extends Plugin
{
    private static $instance = null;
    private static $ajax = null;

    const status_normal = 1;//正常
    const status_deleted = 2;//被删除
    const status_shield = 0;//被屏蔽

    const TYPE_TEXT = 1; //纯文本
    const TYPE_VIDEO = 2; //小视频
    const TYPE_PICTURE = 3; //图片

    public static $media_type = [
        self::TYPE_TEXT => "文字",
        self::TYPE_VIDEO => "小视频",
        self::TYPE_PICTURE => "图片",
    ];

    /**
     * @param bool $is_cli
     * @return  CommunityNewsManager
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
     * @param $title -新闻标题
     * @param $push -是否推送到所有社群
     * @return bool
     */
    public function publish($comm_id, $uid, $media_type, $content, $media, $title, $push)
    {
        $attention = CommunityAttention::findOne(['comm_id=' . $comm_id . " and user_id=" . $uid, 'columns' => 'role']);
        if (!$attention) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_NOT_MEMBER);
        }
        $community_profile = CommunityProfile::findOne(['comm_id=' . $comm_id, 'columns' => 'news_level,push_group']);
        if (!$community_profile) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //仅允许管理员及区主发布
        if ($community_profile['discuss_level'] == 1) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_DISCUSS_NEED_ADMIN);
        }  //仅区主发布
        if ($community_profile['discuss_level'] == 2) {
            self::$ajax->outError(Ajax::ERROR_COMMUNITY_DISCUSS_NEED_OWNER);
        }

        $news_data = [
            'user_id' => $uid,
            'media_type' => $media_type,
            'content' => $content,
            'media' => $media,
            'title' => $title,
            'created' => time(),
            'push' => $push,
            'comm_id' => $comm_id
        ];


        $news_data['content'] = SensitiveManager::filterContent($news_data['content']);
        $news = new CommunityNews();
        if (!$news_id = $news->insertOne($news_data)) {
            Debug::log('publish discuss:' . json_encode($news->getMessages(), true), 'error');
            return false;
        }
        if ($push) {
            Producer::getInstance($this->di->get("config")->kafka->host)->setTopic(TopicDefine::TOPIC_COMMUNITY_GROUP_PUSH)->produce(["comm_id" => $comm_id, 'type' => 'comm_news', 'item_id' => $news_id]);
        }

        return $news_id;
    }

    /**删除新闻
     * @param $uid
     * @param $news_id
     * @return bool
     */
    public function delete($uid, $news_id)
    {
        $news = CommunityNews::findOne(['id=' . $news_id . ' and status=' . self::status_normal, 'columns' => 'comm_id']);
        if (!$news) {
            self::$ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //删除动态的人不是动态发布人
        if ($news['user_id'] != $uid) {
            $community_attention = CommunityAttention::findOne(['comm_id=' . $news['comm_id'] . " and user_id=" . $uid, 'columns' => 'role']);
            if (!$community_attention || ($community_attention['role'] != CommunityManager::role_owner)) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "权限不足");
            }
        }
        if (!CommunityNews::updateOne(['status' => self::status_deleted, 'modify' => time()], 'id=' . $news_id)) {
            return false;
        }
    }

    //社区新闻列表
    public function list($comm_id, $uid, $page = 1, $limit = 20)
    {
        $res = ['data_list' => []];
        $where = 'status=' . self::status_normal . " and comm_id=" . $comm_id;

        $list = CommunityNews::findList([$where, 'order' => 'created desc', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'columns' => 'id as news_id,title,user_id as uid,created,content,media_type,media']);
        if ($list) {
            $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
            $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合
            $comm_attention = CommunityAttention::getColumn(['comm_id=' . $comm_id . " and user_id in (" . $user_ids . ")", 'columns' => 'role,user_id'], 'role', 'user_id');
            foreach ($list as &$item) {
                $item['user_info'] = $user_info[$item['uid']];
                $item['user_info']['contact_mark'] = '';
                if (isset($comm_attention[$item['uid']])) {
                    $item['user_info']['role'] = intval($comm_attention[$item['uid']]);
                } else {
                    $item['user_info']['role'] = -1;
                }
                if (isset($user_personal_setting[$item['uid']]) && $user_personal_setting[$item['uid']]['mark'] != '') {
                    $item['contact_mark'] = $user_personal_setting[$item['uid']]['mark'];
                }
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
            }
            // $list = $this->format($uid, $list);
            $res['data_list'] = $list;
        }
        return $res;
    }
}