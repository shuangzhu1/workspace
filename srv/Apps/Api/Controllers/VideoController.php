<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/6
 * Time: 9:16
 */

namespace Multiple\Api\Controllers;


use Models\Music\Music;
use Models\Social\SocialLike;
use Models\User\UserAttention;
use Models\User\UserInfo;
use Models\User\UserVideo;
use Models\User\UserVideoQuestion;
use Services\MiddleWare\Sl\Behavior\BehaviorManager;
use Services\Social\SocialManager;
use Services\User\Behavior\Behavior;
use Services\User\Behavior\BehaviorDefine;
use Util\Ajax;
use Services\User\VideoManager;
use Util\Debug;

class VideoController extends ControllerBase
{
    //附近视频  首页选集
    public function nearTopAction()
    {
        $uid = $this->uid;
        $limit = $this->request->get("limit", 'int', 10);
        $lng = $this->request->get('lng', 'string', '');//精度
        $lat = $this->request->get('lat', 'string', '');//纬度

        if (!$uid || !$lng || !$lat) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //  echo time();exit;
        //$res = VideoManager::getInstance()->nearTop($uid, $lng, $lat, $limit);
        $res = VideoManager::getInstance()->recommendTop($uid, $lng, $lat, $limit);
        $this->ajax->outRight($res);
    }

    //附近视频列表
    public function nearListAction()
    {
        $uid = $this->uid;
        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("page", 'int', 1);
        $lng = $this->request->get('lng', 'string', '');//经度
        $lat = $this->request->get('lat', 'string', '');//纬度

        if (!$uid || !$lng || !$lat) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //  echo time();exit;
        //$res = VideoManager::getInstance()->nearList($uid, $lng, $lat, $page, $limit);
        $res = VideoManager::getInstance()->recommendList($uid, $lng, $lat, $page, $limit);
        $this->ajax->outRight($res);
    }

    //视频详情
    public function detailAction()
    {
        $uid = $this->uid;
        $vid = $this->request->get("vid", 'int', 0);
        if (!$uid || !$vid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = VideoManager::getInstance()->detail($vid, $uid);
        $this->ajax->outRight($res);
    }

    //发布视频
    public function publishAction()
    {
        $uid = $this->uid;
        $url = $this->request->get("url", 'string', '');
        $title = $this->request->get("title");
        $song_id = $this->request->get("song_id", 'int', 0);
        $is_forward = $this->request->get("is_forward", 'int', 0);//是否转发

        if (!$uid || !$url) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = VideoManager::getInstance()->publish($uid, $url, $title, $song_id, $is_forward);
        if ($res) {
            BehaviorManager::instance($uid, BehaviorManager::behavior_publish_video)->send();
            $this->ajax->outRight(Ajax::SUCCESS_PUBLISH);
        } else {
            $this->ajax->outError(Ajax::FAIL_PUBLISH);
        }
    }

    //删除视频
    public function deleteAction()
    {
        $uid = $this->uid;
        $vid = $this->request->get("vid", 'int', 0);
        if (!$uid || !$vid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = VideoManager::getInstance()->delete($uid, $vid);
        if ($res) {
            $this->ajax->outRight(Ajax::SUCCESS_DELETE);
        } else {
            $this->ajax->outError(Ajax::FAIL_DELETE);
        }
    }

    //我的视频/他的视频
    public function myVideoAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        $page = $this->request->get('page', 'int', 1); //第几页

        if (!$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(VideoManager::getInstance()->myVideo($uid, $to_uid, $page, $limit));
    }

    //播放视频
    public function scanAction()
    {
        $vid = $this->request->get("vid", 'int', 0);
        $uid = $this->uid;
        //频率检测
        // $res = Behavior::init(BehaviorDefine::TYPE_VIDEO_SCAN, $uid)->checkBehavior(true);
        //  if ($res) {
        VideoManager::getInstance()->updateVideoScan($vid, $uid);
        BehaviorManager::instance($uid, BehaviorManager::behavior_read_video)->send();
        //   }
        $this->ajax->outRight('');
    }

    //视频--关注列表
    public function myAttentionAction()
    {
        $p = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $uid = $this->request->get('uid', 'int', '');
        if (!$uid)
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        $myAttention = UserAttention::findList(['owner_id = ' . $uid]);
        $myAttentionUids = implode(',', array_column($myAttention, 'user_id'));
        if (!empty($myAttentionUids))
            $video = UserVideo::findList(['status = 1 and user_id in (' . $myAttentionUids . ')', 'limit' => $limit, 'offset' => ($p - 1) * $limit, 'order' => 'created desc']);
        else
            $video = [];
        $data = [];
        $data['data_list'] = [];
        if (!empty($video)) {

            $user_ids = implode(',', array_unique(array_column($video, 'user_id'), SORT_NUMERIC));
            $user_info = UserInfo::findList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id,username,avatar']);
            $keys = array_column($user_info, 'user_id');
            $user_info = array_combine($keys, $user_info);//用户信息
            $qids = array_unique(array_column($video, 'qid'), SORT_NUMERIC);
            $qids = array_merge(array_diff($qids, [0]));//去除qid=0
            $qids = implode(',', $qids);
            $question_info = UserVideoQuestion::findList(['id in (' . $qids . ')', 'columns' => 'id,question']);
            $keys = array_column($question_info, 'id');
            $question_info = array_combine($keys, $question_info);//问题详情
            $vids = array_column($video, 'id');
            $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_VIDEO . '" and user_id=' . $uid . ' and item_id in (' . implode(',', $vids) . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合

            $song_ids = array_unique(array_column($video, 'song_id'), SORT_NUMERIC);
            $song_ids = array_merge(array_diff($song_ids, [0]));//去除song_id = 0;
            $song_ids = implode(',', $song_ids);
            $song_info = Music::findList(['id in (' . $song_ids . ')', 'columns' => 'id,name,singer,thumb']);
            $keys = array_column($song_info, 'id');
            $song_info = array_combine($keys, $song_info);

            $i = 0;
            foreach ($video as $k => $v) {
                $data['data_list'][$i]['is_attention'] = 1;
                $data['data_list'][$i]['distance'] = 0;
                $data['data_list'][$i]['vid'] = (int)$v['id'];
                $data['data_list'][$i]['uid'] = (int)$v['user_id'];
                $data['data_list'][$i]['url'] = $v['url'];
                $data['data_list'][$i]['username'] = $user_info[$v['user_id']]['username'];
                $data['data_list'][$i]['avatar'] = $user_info[$v['user_id']]['avatar'];
                $data['data_list'][$i]['is_like'] = isset($likes[$v['id']]) ? 1 : 0;
                $data['data_list'][$i]['like_cnt'] = (int)$v['like_cnt'];
                $data['data_list'][$i]['comment_cnt'] = (int)$v['comment_cnt'];
                $data['data_list'][$i]['reward_cnt'] = (int)$v['reward_cnt'];
                $data['data_list'][$i]['title'] = !empty($v['title']) ? $v['title'] : (isset($question_info[$v['qid']]['question']) ? $question_info[$v['qid']]['question'] : '');
                $data['data_list'][$i]['song_id'] = $v['song_id'];
                if ($v['song_id'] != 0) {
                    $data['data_list'][$i]['music_info']['name'] = $song_info[$v['song_id']]['name'] ? : '';
                    $data['data_list'][$i]['music_info']['singer'] = $song_info[$v['song_id']]['singer'] ? : '';
                    $data['data_list'][$i]['music_info']['thumb'] = $song_info[$v['song_id']]['thumb'] ? : '';
                } else {
                    $data['data_list'][$i]['music_info'] = (object)[];
                }
                $i++;
            }

        } else
            $data['data_list'] = [];

        Ajax::init()->outRight($data);

    }

}