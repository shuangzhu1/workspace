<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/22
 * Time: 17:21
 */

namespace Multiple\Wap\Controllers;


use Models\Community\CommunityAttention;
use Models\Community\CommunityNews;
use Models\User\UserInfo;
use Models\User\Users;
use Services\Community\CommunityManager;
use Services\Community\CommunityNewsManager;

class CommunityController extends ControllerBase
{
    //新闻详情
    public function newsDetailAction()
    {
        $news_id = str_replace('.html', '', $this->dispatcher->getParam(0));
        if (!$news_id) {
            die("新闻不存在");
        }
        $news = CommunityNews::findOne(['id=' . $news_id . " and status=" . CommunityNewsManager::status_normal, 'columns' => 'comm_id,title,content,media,created,media_type,user_id,view_cnt']);
        if (!$news) {
            die("新闻不存在");
        }
        $news['role'] = '';
        $attention = CommunityAttention::findOne(['user_id=' . $news['user_id'] . " and comm_id=" . $news['comm_id'], 'columns' => 'role']);
        if ($attention) {
            if ($attention['role'] == CommunityManager::role_normal) {
                $news['role'] = '';
            } else if ($attention['role'] == CommunityManager::role_admin) {
                $news['role'] = '管理员';
            } else if ($attention['role'] == CommunityManager::role_owner) {
                $news['role'] = '区主';
            }
        }
        $user_info = UserInfo::findOne(["user_id=" . $news['user_id'], 'columns' => 'username,avatar']);
        $news['user_info'] = $user_info;
        $this->view->setVar('item', $news);
    }
}