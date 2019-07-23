<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/27
 * Time: 15:07
 */
namespace Services\MiddleWare\Sl\Behavior;

use Components\Kafka\Producer;
use Phalcon\Mvc\User\Plugin;
use Services\MiddleWare\Sl\Base;

class BehaviorManager extends Plugin
{
    private static $instance = null;
    protected $uid = 0;
    protected $behavior = 0;


    //动态
    const behavior_publish_text_discuss = '300000000';#发布纯文本动态3000-0-00-00
    const behavior_publish_video_discuss = '300000100';#发布视频动态3000-0-01-00
    const behavior_publish_img_discuss = '300000200';#发布图文动态3000-0-02-00
    const behavior_publish_audio_discuss = '300000300';#发布音频动态3000-0-03-00
    const behavior_publish_package_discuss = '300000400';#发布红包动态3000-0-04-00
    const behavior_publish_good_discuss = '300000500';#发布商品动态3000-0-05-00

    const behavior_read_discuss = '300010000';#动态阅读3000-1-00-00
    const behavior_like_discuss = '300010001';#动态点赞3000-1-00-01
    const behavior_comment_discuss = '300010002';#动态评论3000-1-00-02
    const behavior_forward_discuss = '300010003';#动态转发3000-1-00-03


    //短视频
    const behavior_publish_video = '4000000';#发布短视频4000-0-00
    const behavior_read_video = '4000100';#视频阅读4000-1-00
    const behavior_like_video = '4000101';#视频点赞4000-1-01
    const behavior_comment_video = '4000102';#视频评论4000-1-02
    const behavior_forward_video = '4000103';#视频转发4000-1-03


    public static function instance($uid, $behavior)
    {
        if (!self::$instance) {
            self::$instance = new self($uid, $behavior);
        }
        return self::$instance;
    }

    public function __construct($uid, $behavior)
    {
        $this->uid = $uid;
        $this->behavior = $behavior;
    }

    //抄送
    public function send()
    {
        if ($this->uid && $this->behavior) {
            Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_behavior_statis)
                ->produce(['behavior' => (string)($this->behavior), 'uid' => intval($this->uid)]);
        }
    }

}