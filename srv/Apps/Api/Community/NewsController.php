<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/10
 * Time: 9:07
 */

namespace Community;


use Multiple\Api\Controllers\ControllerBase;
use Services\Community\CommunityNewsManager;
use Services\User\Behavior\Behavior;
use Util\Ajax;

class NewsController extends ControllerBase
{
    //发布新闻
    public function publishAction()
    {
        $uid = $this->uid;
        $media_type = $this->request->get('media_type', 'int', 1);//1-纯文本 2-视频 3-图片
        $media = $this->request->get('media', 'string', '');//类型为 2/3时必填 图片多张时以，分割
        $content = $this->request->get('content');//文字内容
        $title = $this->request->get("title", 'string', '');//新闻标题
        $comm_id = $this->request->get("comm_id", 'int', 0);//社区id
        $push = $this->request->get("push", 'int', 0);//推送给所有社群
        !$content && $content = '';
        if (!$uid || !key_exists($media_type, CommunityNewsManager::$media_type) || !$media && !$content || !$comm_id || !$title) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        //检测频繁度
        Behavior::init(Behavior::TYPE_COMMUNITY_NEWS, $uid)->checkBehavior();
        // Debug::log("data:" . var_export($_REQUEST, true), 'debug');
        $res = CommunityNewsManager::getInstance()->publish($comm_id, $uid, $media_type, $content, $media, $title, $push);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_PUBLISH);
        }
        $this->ajax->outRight($res);
    }

    //新闻列表
    public function listAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        $page = $this->request->get("page", 'int', 1);//第几页
        $limit = $this->request->get("limit", 'int', 20);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityNewsManager::getInstance()->list($comm_id, $uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    //删除社区新闻
    public function deleteAction()
    {
        $news_id = $this->request->get("news_id", 'int', 0);
        $uid = $this->uid;
        if (!$news_id || !$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (CommunityNewsManager::getInstance()->delete($uid, $news_id)) {
            $this->ajax->outRight("删除成功", Ajax::SUCCESS_DELETE);
        }
        $this->ajax->outError(Ajax::FAIL_DELETE);

    }
}