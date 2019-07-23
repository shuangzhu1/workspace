<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/9
 * Time: 10:59
 */

namespace Services\User\Behavior;


use Phalcon\Mvc\User\Plugin;

class BehaviorDefine extends Plugin
{
    const TYPE_DISCUSS_PUBLISH = 1;// 'publish_discuss';//发布动态
    const TYPE_DISCUSS_LIKE = 2;// 'like';//点赞
    const TYPE_DISCUSS_COLLECT = 3;// 'collect';//收藏
    const TYPE_INVITE_UPLOAD_PHOTOS = 4;// 'invite_upload_photos';//邀请上传照片墙
    // const TYPE_VIDEO_SCAN = 5;//'video_scan';//播放视频
    const TYPE_COMMENT = 6;// 'comment';//评论/回复
    const TYPE_COMMUNITY_NEWS = 7;// 'comment';//发布新闻


    //第一版此次操作时间间隔限制
    public static $expired = [
        self::TYPE_DISCUSS_PUBLISH => '5',//5秒钟内只能发一条
        self::TYPE_DISCUSS_LIKE => '1',//2秒钟内只能操作一次
        self::TYPE_DISCUSS_COLLECT => '1',//1秒钟内只能操作一次
        self::TYPE_INVITE_UPLOAD_PHOTOS => '2',//2秒钟内只能操作一次
        self::TYPE_COMMUNITY_NEWS => '5',//5秒钟内只能操作一次

        // self::TYPE_VIDEO_SCAN => '1',//1秒钟内只能操作一次
    ];
    //第二版 一分钟请求次数限制
    public static $api_count = [
        self::TYPE_DISCUSS_PUBLISH => '6',//一分钟内最多只能发6条动态
        self::TYPE_DISCUSS_LIKE => '120',//一分钟可以操作120次
        self::TYPE_DISCUSS_COLLECT => '60',
        self::TYPE_INVITE_UPLOAD_PHOTOS => '30',//
        self::TYPE_COMMUNITY_NEWS => '6',//

        // self::TYPE_VIDEO_SCAN => '60',//60
    ];
    public static $name = [
        self::TYPE_DISCUSS_PUBLISH => '发布动态',
        self::TYPE_DISCUSS_LIKE => '点赞',
        self::TYPE_DISCUSS_COLLECT => '收藏',
        self::TYPE_INVITE_UPLOAD_PHOTOS => '邀请好友上传照片',
        self::TYPE_COMMENT => '评论/回复',
        self::TYPE_COMMUNITY_NEWS => '社区新闻',//


        // self::TYPE_VIDEO_SCAN => '更新播放量',
    ];

    protected static $blacklist_ip_count = 20;//接口20次超频 加入ip黑名单
}
