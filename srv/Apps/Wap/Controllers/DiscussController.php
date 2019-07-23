<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/3
 * Time: 13:34
 */

namespace Multiple\Wap\Controllers;


use Models\Social\SocialComment;
use Models\Social\SocialLike;
use Models\User\UserInfo;
use Models\User\Users;
use Multiple\Wap\Helper\SocialManager;
use Multiple\Wap\Helper\UserStatus;
use Multiple\Wap\Helper\DiscussManager;
use Phalcon\Tag;
use Phalcon\Mvc\View;

class DiscussController extends ControllerBase
{
    /**动态详情**/
    public function detailAction()
    {
        $this->view->disableLevel([
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true
        ]);

        $item_id = $this->request->get('item_id', 'int', 0);
        if (!$item_id || $item_id < 0) {
            die("该贴已经不存在了");
        }
        $uid = UserStatus::getUid();
        $discuss = DiscussManager::detail($uid > 0 ? $uid : 0, $item_id);
        $this->view->setVar('item', $discuss);
        $this->view->setVar('header', '恐龙谷动态');
        $this->view->title = '恐龙谷动态';
        $this->view->description = '恐龙谷动态' . $discuss['content'] ? '-' . $discuss['content'] : '';

    }

    public function replyAction()
    {
        $item_id = $this->request->get('item_id', 'int', 0);
        $uid = UserStatus::getUid();
        if (!$item_id) {
            die("该评论不存在了");
        }
        $comment = SocialComment::findOne(['id=' . $item_id, 'columns' => 'id as comment_id,like_cnt,user_id,created,content']);
        if (!$comment) {
            die("该评论不存在了");
        }
        $comment['is_like'] = 0;
        if ($comment['like_cnt'] > 0) {
            $like_users = SocialLike::getByColumnKeyList(['type="' . \Services\Social\SocialManager::TYPE_COMMENT . '" and item_id=' . $item_id . ' and enable=1', 'columns' => 'user_id as uid,created', 'order' => 'created', 'limit' => 5], 'uid');
            $user_infos = Users::findList(['id in (' . implode(',', array_column($like_users, 'uid')) . ')', 'columns' => 'id as uid,avatar']);
            $order_data = [];//排序
            foreach ($user_infos as $u) {
                $order_data[] = $like_users[$u['uid']]['created'];
            }
            array_multisort($order_data, SORT_DESC, $user_infos);
            $comment['like_users'] = $user_infos;
            if ($uid && isset($like_users[$uid])) {
                $comment['is_like'] = 1;
            }
        }
        $user_info = UserInfo::findOne(['user_id=' . $comment['user_id'], 'columns' => 'avatar,username,user_id as uid,sex,grade']);
        $this->view->setVar('item', $comment);
        $this->view->setVar('user_info', $user_info);
        $this->view->title = '回复列表';
        $this->view->description = '回复列表' . $comment['content'] ? '-' . $comment['content'] : '';

        //$this->view->setVar('header', '回复列表');
    }
}