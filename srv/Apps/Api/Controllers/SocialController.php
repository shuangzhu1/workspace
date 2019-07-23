<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/17
 * Time: 9:23
 */

namespace Multiple\Api\Controllers;


use Components\Kafka\Producer;
use Models\Social\SocialDiscussBillboard;
use Models\Social\SocialDiscussViewLog;
use Services\Discuss\DiscussManager;
use Services\Discuss\TagManager;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Behavior\BehaviorManager;
use Services\Site\CacheSetting;
use Services\Site\CashRewardManager;
use Services\Social\SocialManager;
use Services\User\Behavior\Behavior;
use Util\Ajax;
use Util\Debug;

class SocialController extends ControllerBase
{
    /*--点赞--*/
    public function likeAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');//
        $item_id = $this->request->get('item_id', 'int', 0);
        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_LIKE, $uid)->checkBehavior();

        /*   if (!SocialManager::init()->like($uid, $item_id, $type)) {
               $this->ajax->outError(Ajax::FAIL_HANDLE);
           }*/
        $redis = $this->di->get("publish_queue");
        $redis->publish(CacheSetting::KEY_LIKE, json_encode(['uid' => $uid, 'type' => $type, 'item_id' => $item_id, 'is_add' => true]));
        //抄送
        if ($type == SocialManager::TYPE_DISCUSS && !$this->is_r) {
            Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_discuss_weight_change)
                ->produce(['itemid' => intval($item_id), 'value' => 2, 'created' => time()]);
            if ($type == SocialManager::TYPE_DISCUSS) {
                BehaviorManager::instance($uid, BehaviorManager::behavior_like_discuss)->send();
            } else if ($type == SocialManager::TYPE_VIDEO) {
                BehaviorManager::instance($uid, BehaviorManager::behavior_like_video)->send();
            }

            //更新 排序数据
            SocialDiscussBillboard::updateOne(['order_num' => 'order_num+2'], 'discuss_id=' . $item_id . " and ymd=" . date('Ymd'));
        }

        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
    }

    /*--取消赞--*/
    public function dislikeAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');//
        $item_id = $this->request->get('item_id', 'int', 0);
        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_LIKE, $uid)->checkBehavior();

        /*  if (!SocialManager::init()->dislike($uid, $item_id, $type)) {
              $this->ajax->outError(Ajax::FAIL_HANDLE);
          }*/
        $redis = $this->di->get("publish_queue");
        $redis->publish(CacheSetting::KEY_LIKE, json_encode(['uid' => $uid, 'type' => $type, 'item_id' => $item_id, 'is_add' => false]));
        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
    }

    /*--收藏--*/
    public function collectAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');//
        $item_id = $this->request->get('item_id', 'int', 0);
        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_COLLECT, $uid)->checkBehavior();

        if (SocialManager::init()->collect($uid, $item_id, $type)) {
            $this->ajax->outRight("收藏成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    /*--取消收藏--*/
    public function unCollectAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');//
        $item_id = $this->request->get('item_id', 'string', ''); //多个以，分割
        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_COLLECT, $uid)->checkBehavior();

        if (SocialManager::init()->unCollect($uid, $item_id, $type)) {
            $this->ajax->outRight("取消成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    /*--点赞列表--*/
    public function likeListAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');
        $item_id = $this->request->get('item_id', 'int', 0);
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 100);
        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SocialManager::init()->likeList($uid, $type, $item_id, $page, $limit);
        $this->ajax->outRight($res);
    }

    /*--收藏列表--*/
    public function collectListAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');
        $item_id = $this->request->get('item_id', 'int', 0);
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 100);
        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SocialManager::init()->collectList($uid, $type, $item_id, $page, $limit);
        $this->ajax->outRight($res);

    }

    /*--转发列表--*/
    public function forwardListAction()
    {
        $uid = $this->uid;
        $item_id = $this->request->get('discuss_id', 'int', 0);
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 100);
        if (!$uid || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SocialManager::init()->forwardList($uid, $item_id, $page, $limit);
        $this->ajax->outRight($res);

    }

    /*--转发--*/
    public function forwardAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');
        $item_id = $this->request->get('item_id', 'int', 0);
        $content = $this->request->get('content', 'green');//转发的内容

        $open_location = $this->request->get('open_location', 'int', 0);//是否公开位置 0-不公开 1-公开
        $address = $this->request->get('address', 'string', '');//具体地址
        $lng = $this->request->get('lng', 'string', '');//精度 公开位置才要传
        $lat = $this->request->get('lat', 'string', '');//纬度 公开位置才有传
        $tags = $this->request->get('tags', 'string', '');//标签 多个标签以，分割
        $is_top = $this->request->get('is_top', 'int', 0);//是否置顶

        $scan_type = $this->request->get('scan_type', 'int', 1);//查看类型 1-
        $scan_user = trim($this->request->get('scan_user', 'string', ''));//允许查看的/禁止查看的人

        $package_id = $this->request->get('package_id', 'string', '');//红包id
        $package_info = $this->request->get('package_info', 'string', '');//红包信息

        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!in_array($scan_type, DiscussManager::$scan_type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //抄送
        if ($type == SocialManager::TYPE_DISCUSS && !$this->is_r) {
            Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_discuss_weight_change)
                ->produce(['itemid' => intval($item_id), 'value' => 4, 'created' => time()]);
            if ($type == SocialManager::TYPE_DISCUSS) {
                BehaviorManager::instance($uid, BehaviorManager::behavior_forward_discuss)->send();
            } else if ($type == SocialManager::TYPE_VIDEO) {
                BehaviorManager::instance($uid, BehaviorManager::behavior_forward_video)->send();
            }

            //更新 排序数据
            SocialDiscussBillboard::updateOne(['order_num' => 'order_num+4'], 'discuss_id=' . $item_id . " and ymd=" . date('Ymd'));
        }

        if ($discuss_id = SocialManager::init()->forward($uid, $type, $item_id, $content, $tags, $is_top, $open_location, $address, $lng, $lat, $scan_type, $scan_user, $package_id, $package_info)) {
            $this->ajax->outRight($discuss_id);
        }
        $this->ajax->outError(Ajax::FAIL_FORWARD);
    }

    /*--分享--*/
    public function shareAction()
    {
        $site = $this->request->get('site', 'string', ''); //分享平台(QQ,微博,朋友圈,QQ空间,微信好友)
        $title = $this->request->get('title', 'string', ''); //分享的标题
        $url = $this->request->get('url', 'string', ''); //分享的链接
        $type = $this->request->get('type', 'string', ''); //分享的类型('discuss'-动态,'news'-资讯,'activity'-活动,'user'-名片,'group'-群,'video'-视频,'package'-广场红包 'promote'-分享恐龙谷)
        $uid = $this->uid; //分享的用户
        $item_id = $this->request->get('item_id', 'int', 0); //分享的数据id
        $spm = $this->request->get('spm', 'string', ''); //分享的spm

        if (!$uid || !$type || !$spm) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!SocialManager::init()->share($uid, $type, $item_id, 'app', $site, $title, $url, $spm)) {
            $this->ajax->outError(Ajax::FAIL_SHARE);
        }
        $reward = new  CashRewardManager();
        $reward = $reward->drawCheck($uid, CashRewardManager::TYPE_SHARE);
        $this->ajax->outRight(["is_reward" => $reward ? 1 : 0]);
    }

    /*--发表评论--*/
    public function commentAction()
    {
        $uid = $this->uid;
        $item_id = $this->request->get('item_id', 'int', "");
        $type = $this->request->get('type', 'string', ''); //类型
        $content = $this->request->get('content', 'green'); //评论内容
        $images = $this->request->get('images', 'string', ''); //评论图片
        !$content && $content = '';

        if (!$uid || !$item_id || !$type || (!$content && !$images)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_COMMENT, $uid)->checkBehavior();

        //抄送
        if ($type == SocialManager::TYPE_DISCUSS && !$this->is_r) {
            Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_discuss_weight_change)
                ->produce(['itemid' => intval($item_id), 'value' => 3, 'created' => time()]);

            if ($type == SocialManager::TYPE_DISCUSS) {
                BehaviorManager::instance($uid, BehaviorManager::behavior_comment_discuss)->send();
            } else if ($type == SocialManager::TYPE_VIDEO) {
                BehaviorManager::instance($uid, BehaviorManager::behavior_comment_video)->send();
            }

            //更新 排序数据
            SocialDiscussBillboard::updateOne(['order_num' => 'order_num+3'], 'discuss_id=' . $item_id . " and ymd=" . date('Ymd'));
        }

        if (SocialManager::init()->comment($uid, $type, $item_id, $content, $images)) {
            $this->ajax->outRight('发布成功', Ajax::SUCCESS_PUBLISH);
        }
        $this->ajax->outError(Ajax::FAIL_PUBLISH);
    }

    /*--回复--*/
    public function replyAction()
    {
        $uid = $this->uid;
        $comment_id = $this->request->get('comment_id', 'int', 0);//评论id 一级回复用到
        $reply_id = $this->request->get('reply_id', 'int', 0);//二级回复必须要传

        $content = $this->request->get('content', 'green'); //回复的内容
        $images = $this->request->get('images', 'string', ''); //评论图片

        !$content && $content = '';
        if (!$uid || !$comment_id || (!$content && !$images)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_COMMENT, $uid)->checkBehavior();

        if (SocialManager::init()->reply($uid, $comment_id, $reply_id, $content, $images)) {
            $this->ajax->outRight('发布成功', Ajax::SUCCESS_PUBLISH);
        }
        $this->ajax->outError(Ajax::FAIL_PUBLISH);
    }

    /*--删除评论/回复--*/
    public function removeCommentAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');
        $item_id = $this->request->get('item_id', 'int', '');
        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!SocialManager::init()->removeComment($uid, $type, $item_id)) {
            $this->ajax->outError(Ajax::FAIL_DELETE);
        }
        $this->ajax->outRight("删除成功", Ajax::SUCCESS_DELETE);
    }

    /*--举报--*/
    public function reportAction()
    {
        $uid = $this->uid;
        $reason = $this->request->get('reason_id', 'int', '');
        $type = $this->request->get('type', 'string', '');
        $item_id = $this->request->get('item_id', 'int', '');
        $imgs = $this->request->get('imgs', 'string', '');//举报原因

        if (!$uid || !$reason) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (SocialManager::init()->report($uid, $reason, $type, $item_id, $imgs)) {
            $this->ajax->outRight("举报成功", Ajax::SUCCESS_REPORT);
        }
        $this->ajax->outError(Ajax::FAIL_SEND);
    }

    /*--评论列表--*/
    public function commentListAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');
        $item_id = $this->request->get('item_id', 'int', '');
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid || !$item_id || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(SocialManager::init()->commentList($uid, $type, $item_id, $page, $limit));
    }

    /*--回复列表--*/
    public function replyListAction()
    {
        $uid = $this->uid;
        $comment_id = $this->request->get('comment_id', 'int', 0);
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid || !$comment_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(SocialManager::init()->replyList($uid, $comment_id, $page, $limit));
    }

    /*--获取举报理由列表--*/
    public function getReportReasonAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');
        if (!$uid || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(SocialManager::init()->reportReasonList($type));
    }

    /*--我的收藏列表--*/
    public function myCollectAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');//收藏类型
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(SocialManager::init()->myCollect($uid, $type, $page, $limit));
    }

    /*--更新阅读数--*/
    public function updateReadCntAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', '');
        $item_id = $this->request->get('item_id', 'int', '');

        if (!$uid || !$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //抄送
        if ($type == SocialManager::TYPE_DISCUSS && !$this->is_r) {
            Producer::getInstance($this->di->getShared("config")->kafka->host)->setTopic(Base::topic_discuss_weight_change)
                ->produce(['itemid' => intval($item_id), 'value' => 1, 'created' => time()]);
            if ($type == SocialManager::TYPE_DISCUSS) {
                BehaviorManager::instance($uid, BehaviorManager::behavior_read_discuss)->send();
            } else if ($type == SocialManager::TYPE_VIDEO) {
                BehaviorManager::instance($uid, BehaviorManager::behavior_read_video)->send();
            }
            //更新 排序数据
            SocialDiscussBillboard::updateOne(['order_num' => 'order_num+1'], 'discuss_id=' . $item_id . " and ymd=" . date('Ymd'));
        }


        if (SocialManager::init()->updateReadCnt($uid, $type, $item_id)) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    /*查看动态访问记录*/
    public function readListAction()
    {
        $discuss_id = $this->request->get('discuss_id', 'int', 0);
        $type = $this->request->get("type", 'string', 'discuss');//discuss-动态 comm_discuss-社区动态
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$discuss_id || !$this->uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(SocialManager::init()->readList($this->uid, $type, $discuss_id, $page, $limit));
    }

    /*
     * 访客记录
     * */
    public function viewLogAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("type", 'string', '');
        $item_id = $this->request->get("item_id", 'int', 0);
        if (!$uid || !$type || !$item_id || !in_array($type, [SocialManager::TYPE_SHOP, SocialManager::TYPE_GOOD])) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SocialManager::init()->recordViewer($uid, $type, $item_id);
        $this->ajax->outRight("");

    }

    //设置标签
    public function setTagsAction()
    {
        $uid = $this->uid;
        $tags = $this->request->get("tags", 'green', '');
        if (TagManager::getInstance()->setTags($uid, $tags)) {
            $this->ajax->outRight("设置成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);

    }

    //获取标签
    public function getTagsAction()
    {
        $uid = $this->uid;
        $this->ajax->outRight(TagManager::getInstance()->getTags($uid));
    }
}