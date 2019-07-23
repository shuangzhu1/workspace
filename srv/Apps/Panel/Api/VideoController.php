<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/3
 * Time: 8:55
 */

namespace Multiple\Panel\Api;


use Models\Admin\Admins;
use Models\User\UserInfo;
use Models\User\Users;
use Models\User\UserVideo;
use Models\Virtual\VirtualVideo;
use Services\Admin\AdminLog;
use Services\User\UserStatus;
use Services\User\VideoManager;
use Util\Ajax;
use Util\Pagination;

class VideoController extends ApiBase
{
    public function addAction()
    {
        $url = $this->request->get("url");
        $is_publish = $this->request->get("is_publish", 'int', 1);
        $title = $this->request->get("title", 'string', '');
        $app_uid = $this->request->get("app_uid", 'int', 0);

        if (!$url || empty($url['video']) || empty($url['thumb'])) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }

        if (!$app_uid) {
            $app_uid = Users::findOne(['user_type=' . UserStatus::USER_TYPE_ROBOT, 'columns' => 'id,rand() as rand', 'order' => 'rand desc']);// $this->request->get('app_uid', 'int', 0);//app_uid
            $app_uid = $app_uid['id'];
        }

        $url = $url['thumb'] . "?" . $url['video'];
        $time = time();
        if ($is_publish) {
            $res = VideoManager::getInstance()->publish($app_uid, $url, '');
            if ($res) {
                $item = VirtualVideo::insertOne(['user_id' => $app_uid, 'admin_id' => $this->admin['id'], 'status' => 1, 'created' => $time, 'publish_time' => $time, 'video_id' => $res, 'url' => $url, 'title' => $title]);
                AdminLog::init()->add('发布虚拟视频', AdminLog::TYPE_VIRTUAL_VIDEO, $item, array('type' => "update", 'id' => $res));
                Ajax::outRight("发布成功");
            }
        } else {
            $item = VirtualVideo::insertOne(['user_id' => $app_uid, 'admin_id' => $this->admin['id'], 'status' => 0, 'created' => $time, 'video_id' => 0, 'url' => $url, 'title' => $title]);
            AdminLog::init()->add('添加虚拟视频到发布队列', AdminLog::TYPE_VIRTUAL_VIDEO, $item, array('type' => "update", 'id' => $item));

            Ajax::outRight("视频已添加至未发布队列");
        }
        Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "操作失败");
    }

    //获取虚拟视频 记录
    public function virtualListAction()
    {
        $status = $this->request->get('status', 'string', 0);
        $start = $this->request->get('start', 'int');
        $end = $this->request->get('end', 'int');
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $order = $this->request->getPost('order', 'string', '');//order
        $sort = $this->request->getPost('sort', 'string', '');//sort
        $admin_id = $this->request->get('admin_id', 'int', 0);//管理员id
        $where = [];
        $order_column = 'created desc';//排序字段
        if ($start) {
            $where[] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $where[] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($status != '-1') {
            $where[] = ' status = ' . $status;
        }
        if ($admin_id) {
            $where[] = ' admin_id  =' . $admin_id;
        }
        if ($order && $sort) {
            $order_column = $order . " " . $sort;
        }
        $where = $where ? implode(' and ', $where) : '';
        $list = VirtualVideo::findList([$where, 'order' => $order_column, 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
        $count = VirtualVideo::dataCount($where);
        $data = [];
        if ($list) {
            $admin = Admins::getByColumnKeyList(['', 'columns' => 'id,name'], 'id');
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            foreach ($list as $i) {
                $i['admin_name'] = $admin[$i['admin_id']]['name'];
                $i['username'] = $users[$i['user_id']]['username'];
                $data[] = [$this->getFromOB('virtual/video/item', array('item' => $i))];
            }
        } else {
            $data[] .= "<tr><td colspan='12'>暂无数据</td></tr>";
        }
        $bar = Pagination::getAjaxListPageBar($count, $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $count, 'bar' => $bar]);
    }

    //队列内发布视频
    public function publishAction()
    {
        $id = $this->request->get("id", 'int', 0);
        if (!$id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $video = VirtualVideo::findOne(['id=' . $id . " and status=0"]);
        if (!$video) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $res = VideoManager::getInstance()->publish($video['user_id'], $video['url'], '');
        if ($res) {
            VirtualVideo::updateOne(['status' => 1, 'publish_time' => time(), 'video_id' => $res], 'id=' . $id);
            AdminLog::init()->add('发布虚拟视频', AdminLog::TYPE_VIRTUAL_VIDEO, $res, array('type' => "update", 'id' => $id));

            Ajax::outRight("发布成功");
        }
        Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "发布失败");
    }

    //队列内发布视频
    public function publishBatchAction()
    {
        $id = $this->request->get("id");
        if (!$id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }

        $video = VirtualVideo::findList(['id in (' . implode(',', $id) . ')' . " and status=0"]);
        if (!$video) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        foreach ($video as $item) {
            $res = VideoManager::getInstance()->publish($item['user_id'], $item['url'], '');
            if ($res) {
                VirtualVideo::updateOne(['status' => 1, 'publish_time' => time(), 'video_id' => $res], 'id=' . $item['id']);
                AdminLog::init()->add('发布虚拟视频', AdminLog::TYPE_VIRTUAL_VIDEO, $res, array('type' => "update", 'id' => $item['id']));
            }
        }
        Ajax::outRight("发布成功");
    }

    public function setTitleAction()
    {
        $id = $this->request->get("id");
        $title = $this->request->get("title");
        if (!$id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $item = VirtualVideo::updateOne(['title' => $title], 'id=' . $id);
        AdminLog::init()->add('虚拟视频编辑主题', AdminLog::TYPE_VIRTUAL_VIDEO, $item, array('type' => "update", 'id' => $item));

        Ajax::outRight("编辑成功");
    }
}