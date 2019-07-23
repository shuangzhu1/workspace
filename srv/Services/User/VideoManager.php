<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/6
 * Time: 10:22
 */

namespace Services\User;


use Models\Music\Music;
use Models\Social\SocialLike;
use Models\User\UserAttention;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserVideo;
use Models\User\UserVideoQuestion;
use Phalcon\Mvc\User\Plugin;
use Services\Im\ImManager;
use Services\Site\SensitiveManager;
use Services\Social\SocialManager;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;

class VideoManager extends Plugin
{
    const STATUS_NORMAL = 1; //正常
    const STATUS_SHIELD = 0;//被系统屏蔽
    const STATUS_REMOVE = 2;//用户已删除

    private static $instance = null;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //附近的视频  首页顶部
    public function nearTop($uid, $lng, $lat, $limit = 20)
    {
        //$sex = UserProfile::findOne(['id=' . $uid, 'columns' => 'sex']);
        $res = ['data_list' => []];
        ///  $query = "select v.id as vid,v.user_id as uid,GetDistances(l.lat,l.lng,$lng,$lat) as distance,v.created,v.url from (select user_id,url,max(created) as created,id from user_video as v where status=1 and user_id<>$uid GROUP BY user_id) as v  left join  user_location as l on v.user_id=l.user_id  order by distance asc,created desc  limit $limit";
        $query = "select v.id as vid,v.user_id as uid,GetDistances(l.lat,l.lng,$lng,$lat) as distance from (select user_id,url,max(id) as id from user_video as v where status=1 GROUP BY user_id) as v  left join  user_location as l on v.user_id=l.user_id  order by v.id desc  limit $limit";

        $list = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        //var_dump($list);exit;
        if ($list) {
            $vids = array_column($list, 'vid');
            $list = UserVideo::findList(['id in (' . implode(',', $vids) . ')', 'columns' => 'id as vid,url,user_id as uid', 'order' => 'created desc']);
            $user_info = Users::getByColumnKeyList(['id in (' . implode(',', array_unique(array_column($list, 'uid'))) . ')', 'columns' => 'username,id,avatar'], 'id');
            foreach ($list as $item) {
                $tmp = $item;
                $tmp['username'] = $user_info[$item['uid']]['username'];
                $tmp['avatar'] = $user_info[$item['uid']]['avatar'];
                // unset($tmp['created']);
                //  unset($tmp['distance']);
                $res['data_list'][] = $tmp;
            }
        }
        return $res;
    }

    //精选视频  首页顶部
    public function recommendTop($uid, $lng, $lat, $limit = 20)
    {
        //$sex = UserProfile::findOne(['id=' . $uid, 'columns' => 'sex']);
        $res = ['data_list' => []];
        ///  $query = "select v.id as vid,v.user_id as uid,GetDistances(l.lat,l.lng,$lng,$lat) as distance,v.created,v.url from (select user_id,url,max(created) as created,id from user_video as v where status=1 and user_id<>$uid GROUP BY user_id) as v  left join  user_location as l on v.user_id=l.user_id  order by distance asc,created desc  limit $limit";
        // $query = "select v.id as vid,v.user_id as uid,GetDistances(l.lat,l.lng,$lng,$lat) as distance from (select user_id,url,max(id) as id from user_video as v where status=1 and is_recommend = 1 GROUP BY user_id) as v  left join  user_location as l on v.user_id=l.user_id  order by v.id desc  limit $limit";
        // $query = "select v.id as vid,v.user_id as uid,GetDistances(l.lat,l.lng,$lng,$lat) as distance from (select user_id,url,max(id) as id from user_video as v where status=1 and is_recommend = 1 GROUP BY user_id) as v  left join  user_location as l on v.user_id=l.user_id  order by rand() limit $limit";
        //  $list = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        //var_dump($list);exit;
        // if ($list) {
        //  $vids = array_column($list, 'vid');
        //  $list = UserVideo::findList(['id in (' . implode(',', $vids) . ') and is_recommend=1', 'columns' => 'id as vid,url,user_id as uid', 'order' => 'created desc']);
        $list = UserVideo::findList(['is_recommend=1', 'columns' => 'id as vid,url,user_id as uid, rand() as rand', 'order' => 'rand desc', 'limit' => $limit]);

        $user_info = Users::getByColumnKeyList(['id in (' . implode(',', array_unique(array_column($list, 'uid'))) . ')', 'columns' => 'username,id,avatar'], 'id');
        foreach ($list as $item) {
            $tmp = $item;
            unset($tmp['rand']);
            $tmp['username'] = $user_info[$item['uid']]['username'];
            $tmp['avatar'] = $user_info[$item['uid']]['avatar'];
            // unset($tmp['created']);
            //  unset($tmp['distance']);
            $res['data_list'][] = $tmp;
        }
        //}
        //  var_dump($res);exit;
        return $res;
    }

    //附近的视频列表
    public function nearList($uid, $lng, $lat, $page = 1, $limit = 20)
    {
        //$sex = UserProfile::findOne(['id=' . $uid, 'columns' => 'sex']);
        $res = ['data_list' => []];
        $query = " select  v.id as vid, v.user_id as uid,GetDistances(l.lat,l.lng,$lat,$lng) as distance,v.created,v.url,v.like_cnt,v.title from user_video as v left join user_location as l on v.user_id=l.user_id  where v.status=1 and v.is_recommend=1 order by created desc,distance asc  limit " . ($page - 1) * $limit . ',' . $limit;
        $list = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        if ($list) {
            $vids = array_column($list, 'vid');
            $uids = array_unique(array_column($list, 'uid'));
            $user_info = Users::getByColumnKeyList(['id in (' . implode(',', $uids) . ')', 'columns' => 'username,avatar,id'], 'id');
            $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_VIDEO . '" and user_id=' . $uid . ' and item_id in (' . implode(',', $vids) . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . implode(',', $uids) . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合

            foreach ($list as $item) {
                $tmp = $item;
                $tmp['username'] = $user_info[$item['uid']]['username'];
                $tmp['avatar'] = $user_info[$item['uid']]['avatar'];
                //是否关注
                if (isset($user_attention[$item['uid']])) {
                    $tmp['is_attention'] = 1;
                } else {
                    $tmp['is_attention'] = 0;
                }
                //是否点赞
                if (isset($likes[$item['vid']])) {
                    $tmp['is_like'] = 1;
                } else {
                    $tmp['is_like'] = 0;
                }
                $tmp['distance'] = $tmp['distance'] ? $tmp['distance'] : rand(100, 10000);
                unset($tmp['created']);
                $res['data_list'][] = $tmp;
            }
        }
        return $res;
    }

    //精选视频列表
    public function recommendList($uid, $lng, $lat, $page = 1, $limit = 20)
    {
        //$sex = UserProfile::findOne(['id=' . $uid, 'columns' => 'sex']);
        $res = ['data_list' => []];
        //第一页随机
        if ($page == 1) {
            $order = ' rand() desc';
        } else {
            $order = ' created desc';
            $page = $page - 1;
        }

        $query = " select  v.id as vid, v.user_id as uid,GetDistances(l.lat,l.lng,$lat,$lng) as distance,v.created,v.url,v.like_cnt,v.title,v.song_id,v.like_cnt,v.comment_cnt,v.reward_cnt,v.qid from user_video as v left join user_location as l on v.user_id=l.user_id  where v.status=1 and v.is_recommend =1 order by $order,distance asc  limit " . ($page - 1) * $limit . ',' . $limit;
        $list = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        if ($list) {
            $vids = array_column($list, 'vid');
            $uids = array_unique(array_column($list, 'uid'));
            $user_info = Users::getByColumnKeyList(['id in (' . implode(',', $uids) . ')', 'columns' => 'username,avatar,id'], 'id');
            $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_VIDEO . '" and user_id=' . $uid . ' and item_id in (' . implode(',', $vids) . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id as uid'], 'uid');//关注人集合

            $song_ids = array_unique(array_column($list, 'song_id'), SORT_NUMERIC);
            $song_ids = array_merge($song_ids, [0]);//去除song_id = 0;
            $song_ids = implode(',', $song_ids);
            $song_info = Music::findList(['id in (' . $song_ids . ')', 'columns' => 'id,name,singer,thumb']);
            $keys = array_column($song_info, 'id');
            $song_info = array_combine($keys, $song_info);


            foreach ($list as $item) {
                $song_id = $item['song_id'];
                $tmp = $item;
                $tmp['username'] = $user_info[$item['uid']]['username'];
                $tmp['avatar'] = $user_info[$item['uid']]['avatar'];
                //是否关注
                if (isset($user_attention[$item['uid']])) {
                    $tmp['is_attention'] = 1;
                } else {
                    $tmp['is_attention'] = 0;
                }
                //是否点赞
                if (isset($likes[$item['vid']])) {
                    $tmp['is_like'] = 1;
                } else {
                    $tmp['is_like'] = 0;
                }
                //点赞数量
                $tmp['like_cnt'] = (int)$item['like_cnt'];
                //评论数量
                $tmp['comment_cnt'] = (int)$item['comment_cnt'];
                //转发数量
                $tmp['reward_cnt'] = (int)$item['reward_cnt'];
                //视频title
                if (!empty($item['title'])) {

                    $tmp['title'] = $item['title'];
                } elseif (empty($item['title']) && $item['qid'] != 0)//视频问答问题
                {
                    $question = UserVideoQuestion::findOne(['id = ' . $item['qid'], 'columns' => 'question'])['question'];
                    $tmp['title'] = $question;
                } else
                    $tmp['title'] = '';
                //音乐详情
                //$song_id = $video['song_id'];
                $tmp['title'] = FilterUtil::unPackageContentTagApp($tmp['title'], $uid);

                if ($song_id != 0) {
                    $tmp['music_info']['name'] = $song_info[$item['song_id']]['name'] ?: '';
                    $tmp['music_info']['singer'] = $song_info[$item['song_id']]['singer'] ?: '';
                    $tmp['music_info']['thumb'] = $song_info[$item['song_id']]['thumb'] ?: '';
                } else {
                    $tmp['music_info'] = (object)[];
                }
                $tmp['distance'] = $tmp['distance'] ? $tmp['distance'] : rand(100, 10000);
                unset($tmp['created']);
                $res['data_list'][] = $tmp;
            }
        }
        return $res;
    }

    //视频详情
    public function detail($vid, $uid)
    {
        $video = UserVideo::findOne(['id=' . $vid, 'columns' => 'id as vid, user_id as uid,url,like_cnt,qid,title,song_id,comment_cnt,reward_cnt']);
        $song_id = $video['song_id'];
        unset($video['song_id']);
        if ($video) {
            $user_info = Users::findOne(['id=' . $video['uid'], 'columns' => 'username,avatar']);

            $video['username'] = $user_info['username'];
            $video['avatar'] = $user_info['avatar'];
            //是否已赞
            if (SocialLike::exist('type="' . SocialManager::TYPE_VIDEO . '" and user_id=' . $uid . ' and item_id=' . $vid . ' and enable=1')) {
                $video['is_like'] = 1;
            } else {
                $video['is_like'] = 0;
            }
            //是否关注
            if ($attention = UserAttention::exist('owner_id=' . $uid . ' and user_id=' . $video['uid'])) {
                $video['is_attention'] = 1;
            } else {
                $video['is_attention'] = 0;
            }
            //是否问答视频
            if (($qid = $video['qid']) != 0) {
                $tmp = UserVideoQuestion::findOne(['id = ' . $qid, 'columns' => 'id,question,video_info']);
                $video['question']['content'] = $tmp['question'];
                $video['question']['qid'] = $tmp['id'];
                $video['question']['answered_time'] = json_decode($tmp['video_info'], true)['created'];
            } else {
                $video['question'] = (object)[];
            }
            //点赞数量
            $video['like_cnt'] = (int)$video['like_cnt'];
            //评论数量
            $video['comment_cnt'] = (int)$video['comment_cnt'];
            //转发数量
            $video['reward_cnt'] = (int)$video['reward_cnt'];
            //音乐详情
            if ($song_id !== 0) {
                $song_info = Music::findOne(['id = ' . $song_id, 'columns' => 'name,singer,thumb']);
                if ($song_id) {
                    $video['music_info']['name'] = $song_info['name'];
                    $video['music_info']['singer'] = $song_info['singer'];
                    $video['music_info']['thumb'] = $song_info['thumb'];
                } else {
                    $video['music_info'] = (object)[];
                }

            }
            $video['title'] = FilterUtil::unPackageContentTagApp($video['title'], $uid);

        } else {
            $video = (object)[];
        }
        return $video;
    }

    //我的/他的视频
    public function myVideo($uid, $to_uid, $page = 1, $limit = 20)
    {
        $res = ['data_list' => []];
        $list = UserVideo::findList(['user_id=' . $to_uid . ' and status=1', 'columns' => 'user_id as uid,id as vid,url,like_cnt,comment_cnt,view_cnt,qid,title,created', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc']);
        if ($list) {
            $vids = array_column($list, 'vid');
            $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_VIDEO . '" and user_id=' . $uid . ' and item_id in (' . implode(',', $vids) . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            foreach ($list as &$item) {
                $item['title'] = FilterUtil::unPackageContentTagApp($item['title'], $uid);
                //是否点赞
                if (isset($likes[$item['vid']])) {
                    $item['is_like'] = 1;
                } else {
                    $item['is_like'] = 0;
                }
                if (($qid = $item['qid']) !== 0)//视频问答
                {
                    $ques = UserVideoQuestion::findOne(['id = ' . $qid, 'columns' => 'question']);
                    if ($ques)
                        $item['question']['content'] = $ques['question'];
                    else
                        $item['question'] = (object)[];
                } else {
                    $item['question'] = (object)[];

                }
            }

        } else {
            $list = [];
        }
        $res['data_list'] = $list;
        return $res;
    }
    //
    /**发布视频
     * @param $uid
     * @param $url
     * @param $title
     * @param $song_id
     * @param int $is_forward 是否转发到动态
     * @return bool
     */
    public function publish($uid, $url, $title, $song_id = 0, $is_forward = 0)
    {
        $data = ['user_id' => $uid, 'url' => $url, 'created' => time(), 'title' => $title, 'song_id' => $song_id];

        //敏感词过滤
        $data['title'] = SensitiveManager::filterContent($data['title']);
        //@的用户
        $at_uid = FilterUtil::packageContentTagApp($data['title'], $uid);
        //更新歌曲使用数量
        Music::updateOne(['use_count ' => 'use_count + 1'], ['id ' => $song_id]);
        $id = UserVideo::insertOne($data);
        if (!$id) return false;
        if ($is_forward) {
            SocialManager::init()->forward($uid, SocialManager::TYPE_VIDEO, $id, "转发视频");
        }
        //发at消息
        if ($at_uid) {
            foreach ($at_uid as $item) {
                ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $id, 'type' => SocialManager::TYPE_VIDEO, 'content' => $data['title'], 'user_id' => $uid, 'to_user_id' => $item]);
            }
        }
        return true;
    }

    //删除视频
    public function delete($uid, $vid)
    {
        if (UserVideo::exist(['id=' . $vid . ' and user_id=' . $uid])) {
            //视频问答视频延时删除 14*86400
            $video = UserVideo::findOne(['id=' . $vid . ' and user_id=' . $uid, 'columns' => 'qid']);
            if ($video) {
                $video_info = UserVideoQuestion::findOne(['id = ' . $video['qid'], 'columns' => 'video_info']);
                $video_last_uptime = isset(json_decode($video_info['video_info'], true)['update']) ? json_decode($video_info['video_info'], true)['update'] : json_decode($video_info['video_info'], true)['created'];
                if ((time() - $video_last_uptime) < 14 * 86400)//首次上传视频后14内不能删除视频
                {
                    Ajax::init()->outError(Ajax::ERROR_DELETED_NOT_EXPIRE);
                }
                UserVideoQuestion::updateOne(['status' => QuestionManager::STATUS_DELETED], 'id = ' . $video['qid']);
            }
            return UserVideo::updateOne(['status' => VideoManager::STATUS_REMOVE], 'id=' . $vid);
        }
        return true;
    }

    //更新播放量
    public function updateVideoScan($video, $uid)
    {
        UserVideo::updateOne('view_cnt=view_cnt+1', 'id=' . $video);
    }

}
