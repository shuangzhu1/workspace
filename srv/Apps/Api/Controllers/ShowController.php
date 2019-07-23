<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/12
 * Time: 10:51
 */

namespace Multiple\Api\Controllers;


use Models\Statistics\StatisticsShowTotal;
use Models\Statistics\StatisticsShowUser;
use Models\User\UserInfo;
use Models\User\UserShow;
use Services\User\Show\ShowManager;
use Util\Ajax;

class ShowController extends ControllerBase
{
    //上传秀场信息
    public function uploadAction()
    {
        $uid = $this->uid;
        $video = $this->request->get("video", 'string', '');
        $images = $this->request->get("images", 'string', '');


        if (!$uid || !$images) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'birthday,phone']);
        if (!$this->request->get("is_r", 'int', 0)) {
            if ($user_info['phone'] == '') {
                $this->ajax->outError(Ajax::ERROR_EMPTY_BIND_PHONE);
            }
            if ($user_info['birthday'] == '') {
                $this->ajax->outError(Ajax::ERROR_EMPTY_BIRTHDAY);
            }
        }
        if (ShowManager::init()->save($uid, $video, $images)) {
            $this->ajax->outRight("提交成功", Ajax::SUCCESS_SUBMIT);
        }
        $this->ajax->outError(Ajax::FAIL_SUBMIT);
    }

    //点赞
    public function likeAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!UserShow::exist('user_id=' . $to_uid . ' and enable=1')) {
            $this->ajax->outError(Ajax::ERROR_SHOW_DISABLE);
        }
        $res = ShowManager::init()->like($uid, $to_uid);
        if ($res === true) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        } else if ($res === 1) {
            $this->ajax->outError(Ajax::ERROR_SHOW_HAS_DISLIKE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    //踩
    public function dislikeAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!UserShow::exist('user_id=' . $to_uid . ' and enable=1')) {
            $this->ajax->outError(Ajax::ERROR_SHOW_DISABLE);
        }
        $res = ShowManager::init()->dislike($uid, $to_uid);
        if ($res === true) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        } else if ($res === 1) {
            $this->ajax->outError(Ajax::ERROR_SHOW_HAS_LIKE);
        }

        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    //开启秀场
    public function openAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (ShowManager::init()->open($uid)) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    //关闭秀场
    public function closeAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (ShowManager::init()->close($uid)) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    //秀场列表
    public function listAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 1);//当前第几页
        $limit = $this->request->get("limit", 'int', 20);//每页显示的数量
        $sex = $this->request->get("sex", 'int', 0);//性别
        $distance = $this->request->get("distance", 'int', 5);//距离 单位千米
        $age_start = $this->request->get("age_start", 'int', 0);//年龄起始
        $age_end = $this->request->get("age_end", 'int', 0);//年龄结束

        $c = $this->request->get("c", 'int', 0);//星座
        $lng = $this->request->get('lng', 'string', '');//精度
        $lat = $this->request->get('lat', 'string', '');//纬度
        $filter = [];
        if (!$uid || !$lng || !$lat) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($sex) {
            $filter['sex'] = $sex;
        }
        if ($age_start) {
            $filter['age_start'] = $age_start;
        }
        if($age_end){
            $filter['age_end'] = $age_end;
        }
        if ($distance) {
            $filter['distance'] = $distance;
        }
        if ($c) {
            $filter['c'] = $c;
        }
        $res = ShowManager::init()->list($uid, $lng, $lat, $filter, $page, $limit);

        $this->ajax->outRight($res);
    }

    //秀场排行榜  --前十名
    public function rankAction()
    {
        $uid = $this->uid;
        $issue = $this->request->get("issue", 'int', 0);//第几期
        if (!$uid || !$issue) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $show_user = StatisticsShowUser::findOne(['user_id=' . $uid . ' and issue=' . $issue, 'columns' => 'rank,score']);
        $res = ['my_rank' => (object)[], 'data_list' => []];
        if ($show_user) {
            $res['my_rank'] = $show_user;
        }
        $res['data_list'] = ShowManager::init()->top(1, 10, $uid);
        $this->ajax->outRight($res);
    }

    //获取TA、我的秀场信息
    public function detailAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0);
        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(ShowManager::init()->detail($uid, $to_uid));
    }
}