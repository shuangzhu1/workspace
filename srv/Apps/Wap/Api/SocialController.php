<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/6
 * Time: 13:41
 */

namespace Multiple\Wap\Api;


use Multiple\Wap\Helper\UserStatus;
use Services\Social\SocialManager;
use Services\User\Behavior\Behavior;
use Util\Ajax;

class SocialController extends ControllerBase
{
    public function initialize()
    {
        $this->checkLogin = true;
        $this->user_id = UserStatus::init()->getUid();
    }

    /*点赞*/
    public function likeAction()
    {


        $type = $this->request->get('type', 'string', '');
        $item_id = $this->request->get('item_id', 'int', 0);
        if (!$type || !$item_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_LIKE, $this->user_id)->checkBehavior();
        if (!$res = SocialManager::init()->like($this->user_id, $item_id, $type)) {
            Ajax::outError(Ajax::FAIL_HANDLE);
        }
        Ajax::outRight($res);
    }

    /*取消赞*/
    public function dislikeAction()
    {


        $type = $this->request->get('type', 'string', '');//
        $item_id = $this->request->get('item_id', 'int', 0);
        if (!$this->user_id || !$type || !$item_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_LIKE, $this->user_id)->checkBehavior();
        if (!$res = SocialManager::init()->dislike($this->user_id, $item_id, $type)) {
            Ajax::outError(Ajax::FAIL_HANDLE);
        }
        Ajax::outRight($res);
    }

    /*--收藏--*/
    public function collectAction()
    {
        $uid = $this->user_id;
        $type = $this->request->get('type', 'string', '');//
        $item_id = $this->request->get('item_id', 'int', 0);
        if (!$uid || !$type || !$item_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_COLLECT, $this->user_id)->checkBehavior();

        if (SocialManager::init()->collect($uid, $item_id, $type)) {
            Ajax::outRight("收藏成功", Ajax::SUCCESS_HANDLE);
        }
        Ajax::outError(Ajax::FAIL_HANDLE);
    }

    /*--取消收藏--*/
    public function unCollectAction()
    {
        $uid = $this->user_id;
        $type = $this->request->get('type', 'string', '');//
        $item_id = $this->request->get('item_id', 'string', ''); //多个以，分割
        if (!$uid || !$type || !$item_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_COLLECT, $this->user_id)->checkBehavior();

        if (SocialManager::init()->unCollect($uid, $item_id, $type)) {
            Ajax::outRight("取消成功", Ajax::SUCCESS_HANDLE);
        }
        Ajax::outError(Ajax::FAIL_HANDLE);
    }
}