<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/3
 * Time: 9:45
 */

namespace Multiple\Api\Controllers;


use Services\User\ChatManager;
use Util\Ajax;

class ChatController extends ControllerBase
{
    /*设置/取消置顶*/
    public function setTopAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'string', 1); //类型 1-个人 2-群
        $to = $this->request->get('to', 'string', '');// 会话id --置顶的个人/群ID
        $is_top = $this->request->get('is_top', 'int', 1);//是否置顶 1-置顶 0-取消置顶
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //**置顶
        if ($is_top) {
            if (!in_array($type, ChatManager::$top_type) || !$to) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            if (ChatManager::init()->setTop($uid, $type, $to)) {
                $this->ajax->outRight("置顶成功", Ajax::SUCCESS_TOP);
            } else {
                $this->ajax->outError(Ajax::FAIL_TOP);
            }
        } //取消置顶
        else {
            if (ChatManager::init()->unSetTop($uid, $type, $to)) {
                $this->ajax->outRight("取消成功", Ajax::SUCCESS_CANCEL);
            } else {
                $this->ajax->outError(Ajax::FAIL_CANCEL);
            }
        }
    }

    /*列表置顶*/
    public function topListAction()
    {
        $uid = $this->uid;
        $this->ajax->outRight(ChatManager::init()->topList($uid));
    }
}