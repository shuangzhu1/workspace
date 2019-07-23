<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/3
 * Time: 16:40
 */

namespace Multiple\Wap\Api;


use Multiple\Wap\Helper\SocialManager;
use Multiple\Wap\Helper\UserStatus;
use Util\Ajax;

class CommentController extends ControllerBase
{
    public function listAction()
    {
        $item_id = $this->request->get('item_id', 'int', 0);
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        if (!$item_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $uid = UserStatus::getUid();
        $data = [];
        $res = SocialManager::init()->commentList($uid ? $uid : 0, \Services\Social\SocialManager::TYPE_DISCUSS, $item_id, $page, $limit);
        if ($page == 1) {
            if ($res['hot_list']) {
                foreach ($res['hot_list'] as $item) {
                    $data[] = [$this->getFromOB('discuss/partial/comment', array('comment' => $item, 'is_hot' => 1))];
                }
            }
            if ($res['data_list']) {
                foreach ($res['data_list'] as $item) {
                    $data[] = [$this->getFromOB('discuss/partial/comment', array('comment' => $item, 'is_hot' => 0))];
                }

            }
        } else {
            if ($res['data_list']) {
                foreach ($res['data_list'] as $item) {
                    $data[] = [$this->getFromOB('discuss/partial/comment', array('comment' => $item, 'is_hot' => 0))];
                }
            }
        }

        $data = array('count' => $res['data_count'], "limit" => $limit, 'data_list' => $data);
        Ajax::outRight($data);
    }

    public function replyListAction()
    {
        $uid = $this->user_id;
        $comment_id = $this->request->get('item_id', 'int', 0);
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$comment_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $res = SocialManager::init()->replyList($uid, $comment_id, $page, $limit);
        $data = [];
        if ($res) {
            foreach ($res['data_list'] as $item) {
                $data[] = [$this->getFromOB('discuss/partial/reply', array('reply' => $item))];
            }
        }
        $data = array('count' => $res['data_count'], "limit" => $limit, 'data_list' => $data);

        Ajax::outRight($data);
    }
}