<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/2
 * Time: 17:36
 */

namespace Community;


use Multiple\Api\Controllers\ControllerBase;
use Services\Community\CommunityManager;
use Util\Ajax;

class MainController extends ControllerBase
{
    //检测社区名是否可用
    public function checkNameAction()
    {
        $uid = $this->uid;
        $name = $this->request->get("name", 'string', '');
        if (!$uid || !$name) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityManager::getInstance()->checkName($uid, $name);
        if ($res) {
            $this->ajax->outRight(1);
        }
        $this->ajax->outRight(0);
    }

    //申请创建社区
    public function applyAction()
    {
        $uid = $this->uid;
        $name = $this->request->get("name", 'string', '');
        $brief = $this->request->get("brief", 'string', '');
        $cover = $this->request->get("cover", 'string', '');
        $extra_desc = $this->request->get("extra_desc", 'string', '');
        $extra_img = $this->request->get("extra_img", 'string', '');
        $type = $this->request->get("type", 'int', 1); //1-个人 2-企业

        if (!$uid || !$name || !$brief || !$cover || !$extra_img || !$extra_desc || !in_array($type, [1, 2])) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        CommunityManager::getInstance()->apply($uid, $name, $brief, $cover, $extra_desc, $extra_img, $type);
    }

    //关注社区
    public function attentionAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        CommunityManager::getInstance()->attention($uid, $comm_id);
    }

    //社区取消关注
    public function unAttentionAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        CommunityManager::getInstance()->unAttention($uid, $comm_id);
    }

    //我的社区及我加入的社区
    public function myCommunityAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $res = CommunityManager::getInstance()->myCommunity($uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    //我的社区申请
    public function myApplyAction()
    {
        $uid = $this->uid;
        $res = CommunityManager::getInstance()->myApply($uid);
        $this->ajax->outRight($res);
    }

    //推荐的社区
    public function recommendCommunityAction()
    {
        $uid = $this->uid;
        $limit = $this->request->get("limit", 'int', 4);
        $res = CommunityManager::getInstance()->recommendCommunity($uid, $limit);
        $this->ajax->outRight($res);
    }

    //社区搜索
    public function searchAction()
    {
        $uid = $this->uid;
        $key = $this->request->get("key", "string", '');
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $res = CommunityManager::getInstance()->search($uid, $key, $page, $limit);
        $this->ajax->outRight($res);
    }

    //社区详情
    public function detailAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityManager::getInstance()->detail($uid, $comm_id);
        $this->ajax->outRight($res);
    }

    //设置管理员
    public function setManagerAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0);
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$to_uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        CommunityManager::getInstance()->setManager($uid, $to_uid, $comm_id);
    }

    //取消管理员
    public function removeManagerAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0);
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$to_uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        CommunityManager::getInstance()->removeManager($uid, $to_uid, $comm_id);
    }

    //管理员列表
    public function managerListAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityManager::getInstance()->managerList($uid, $comm_id);
        $this->ajax->outRight($res);
    }

    //设置权限
    public function settingAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        $discuss_level = $this->request->get("discuss_level", 'int', -1);//动态发布权限【0-全部社区成员 1-管理员】
        $news_level = $this->request->get("news_level", 'int', -1);//新闻发布权限【1-管理员及区主 2-仅区主】
        $push_group = $this->request->get("push_group", 'int', -1);//管理员新闻发布推送到所有社群【0-不允许 1-允许】

        $data = [];
        if (in_array($discuss_level, [0, 1])) {
            $data['discuss_level'] = $discuss_level;
        }
        if (in_array($news_level, [1, 2])) {
            $data['news_level'] = $news_level;
        }
        if (in_array($push_group, [0, 1])) {
            $data['news_level'] = $news_level;
        }
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        CommunityManager::getInstance()->setting($uid, $comm_id, $data);
    }

    //关注者列表
    public function followersAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        $key = $this->request->get("key", 'string', '');//搜索关键字
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityManager::getInstance()->followersAction($uid, $comm_id, $key, $page, $limit);
        $this->ajax->outRight($res);
    }

    //社区好友
    public function friendsAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        $key = $this->request->get("key", 'string', '');//搜索关键字
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityManager::getInstance()->friends($uid, $comm_id, $key, $page, $limit);
        $this->ajax->outRight($res);
    }

    //推荐新闻
    public function recommendNewsAction()
    {
        $uid = $this->uid;
        $limit = $this->request->get("limit", 'int', 4);
        $res = CommunityManager::getInstance()->recommendNews($uid, $limit);
        $this->ajax->outRight($res);
    }

}