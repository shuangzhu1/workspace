<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/18
 * Time: 19:22
 */

namespace Multiple\Wap\Controllers;


use Models\User\UserInfo;
use Models\User\Users;
use Models\User\UserVideo;
use Phalcon\Mvc\View;
use Services\User\VideoManager;

class VideoController extends ControllerBase
{
    //视频详情
    public function detailAction()
    {
        $this->view->disableLevel([
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true
        ]);

        $item_id = $this->request->get("item_id", 'int', 0);
        if (!$item_id) {
            $this->error404();
            return;
        }
        $video = UserVideo::findOne(['id=' . $item_id . ' and status=' . VideoManager::STATUS_NORMAL]);
        if (!$video) {
            $this->error404();
            return;
        }
        $users = UserInfo::findOne(['user_id=' . $video['user_id'], 'columns' => 'username,avatar,sex,user_id']);

        $more = UserVideo::findList(['id <>' . $item_id . " and status=" . VideoManager::STATUS_NORMAL . " and is_recommend=1", 'limit' => 12, 'order' => 'rand', 'columns' => 'rand() as rand,url,id']);

        $this->view->setVar('video', $video);
        $this->view->setVar('user', $users);
        $this->view->setVar('more', $more);
    }

}