<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/3
 * Time: 17:04
 */

namespace Multiple\Panel\Plugins;

use Components\Rules\Point\PointRule;
use Components\Time;
use Models\Group\Group;
use Models\Social\SocialComment;
use Models\Social\SocialCommentReply;
use Models\Social\SocialDiscuss;
use Models\Social\SocialLike;
use Models\Social\SocialNews;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Services\Discuss\DiscussManager;
use Services\Im\ImManager;
use Services\User\GroupManager;
use Util\Ajax;
use Util\FilterUtil;

class SocialManager extends Plugin
{
    private static $instance = null;

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**获取评论列表
     * @param $uid
     * @param $type
     * @param $item_id
     * @param int $limit
     * @param int $last_id --上拉加载
     * @param int $first_id --下拉刷新
     * @return array
     */
    public function commentList($uid, $type, $item_id, $limit = 20, $first_id = 0, $last_id = 0)
    {
        $params[] = 'status=' . \Services\Social\SocialManager::COMMENT_STATUS_NORMAL . ' and type="' . $type . '" and item_id=' . $item_id;
        $params['columns'] = 'id as comment_id,user_id as uid,content,images,comment_cnt as reply_cnt,like_cnt,created,parent_id';
        $params['order'] = 'created desc';
        $params['limit'] = $limit;
        if ($last_id) {
            $params[0] .= " and id <" . $last_id;
        } else {
            $params[0] .= " and id >" . $first_id;
        }
        $list = SocialComment::findList($params);
        $res['data_count'] = SocialComment::dataCount($params[0]);

        if ($list) {
            $comment_ids = implode(',', array_column($list, 'comment_id'));
            $uids = implode(',', array_unique(array_column($list, 'uid')));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade'], 'uid');

            if ($uid) {
                $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
                $likes = SocialLike::getByColumnKeyList(['type="' . \Services\Social\SocialManager::TYPE_COMMENT . '" and user_id=' . $uid . ' and item_id in (' . $comment_ids . ')' . ' and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合

            } else {
                $user_contact = [];
                $likes = [];
            }
            $parent_ids = [];//父级评论id

            foreach ($list as &$item) {
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : $users[$item['uid']]['username'];
                $item['sex'] = $users[$item['uid']]['sex'];
                $item['avatar'] = $users[$item['uid']]['avatar'];
                $item['grade'] = $users[$item['uid']]['grade'];
                $item['parent'] =[];
                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['is_like'] = isset($likes[$item['comment_id']]) ? 1 : 0;
                $item['content'] = FilterUtil::unPackageContentTag($item['content'], $uid, '/panel/users/detail/?user_id=');
                if ($item['parent_id'] && !in_array($item['parent_id'], $parent_ids)) {
                    $parent_ids[] = $item['parent_id'];
                }
            }
            if ($parent_ids) {
                $parent_comments = $this->getParentComment($uid, $parent_ids);
                foreach ($list as &$it) {
                    $it['parent_id'] && $it['parent'] = $parent_comments[$it['parent_id']];
                }
            }
//            $sql = "SELECT comment_id,id,user_id as uid,to_user_id as to_uid,content,images,created
//                    FROM social_comment_reply a
//                    WHERE 2 > (
//                    SELECT COUNT( 1 )
//                    FROM social_comment_reply
//                    WHERE comment_id = a.comment_id
//                    AND id > a.id ) and comment_id in(" . $comment_ids . ")
//                    ORDER BY a.created DESC ";
//            $reply = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
//            if ($reply) {
//                // $reply = array_combine(array_column($reply, 'comment_id'), $reply);
//                $reply_data = [];
//                $uids = implode(',', array_unique(array_merge(array_column($reply, 'uid'), array_column($reply, 'to_uid'))));
//                $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade'], 'uid');
//                if ($uid) {
//                    $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
//                } else {
//                    $user_contact = [];
//                }
//                foreach ($reply as &$r) {
//                    $r['reply_id'] = $r['id'];
//                    $comment_id = $r['comment_id'];
//                    unset($r['id']);
//                    unset($r['parent_id']);
//                    unset($r['comment_id']);
//                    $r['content'] = FilterUtil::unPackageContentTag($r['content'], $uid, '/panel/users/detail/?user_id=');
//                    $r['username'] = (isset($user_contact[$r['uid']]) && $user_contact[$r['uid']]['mark']) ? $user_contact[$r['uid']]['mark'] : $users[$r['uid']]['username'];
//                    $r['to_username'] = (isset($user_contact[$r['to_uid']]) && $user_contact[$r['to_uid']]['mark']) ? $user_contact[$r['to_uid']]['mark'] : $users[$r['to_uid']]['username'];
//                    /*              $r['sex'] = $users[$r['user_id']]['sex'];
//                                  $r['avatar'] = $users[$r['user_id']]['avatar'];
//                                  $r['grade'] = $users[$r['user_id']]['grade'];*/
//                    if (isset($reply_data[$comment_id])) {
//                        $reply_data[$comment_id][] = $r;
//                    } else {
//                        $reply_data[$comment_id] = [$r];
//                    }
//                }
//                foreach ($list as &$item) {
//                    $item['reply_list'] = isset($reply_data[$item['comment_id']]) ? $reply_data[$item['comment_id']] : [];
//                }
//            }
        }
        return $list;

    }
    /**获取父级评论信息
     * @param $uid
     * @param $parent_ids
     * @return array|\Phalcon\Mvc\ResultsetInterface
     */
    public function getParentComment($uid, $parent_ids)
    {
        $res = [];
        if ($parent_ids) {
            $res = SocialComment::getByColumnKeyList(['id in (' . implode(',', $parent_ids) . ')', 'columns' => 'id,user_id as uid,content,status,images'], 'id');
            if ($res) {
                $parent_uids = array_unique(array_column($res, 'uid'));
                $parent_uids = implode(',', $parent_uids);
                $parent_users = UserInfo::getByColumnKeyList(['user_id in (' . $parent_uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade'], 'uid');

                foreach ($res as &$p) {
                    $p['username'] = $parent_users[$p['uid']]['username'] ;
                    $p['content'] = FilterUtil::unPackageContentTagApp($p['content'], $uid);
                }
            }
        }
        return $res;
    }
    /**获取回复列表
     * @param $uid
     * @param $comment_id
     * @param int $limit
     * @param int $first_id
     * @param int $last_id
     * @return array
     */
    public function replyList($uid, $comment_id, $limit = 20, $first_id = 0, $last_id = 0)
    {
        $res = ['data_count' => 0, 'data_list' => [], 'like_users' => []];
        $params[] = 'status=' . \Services\Social\SocialManager::COMMENT_STATUS_NORMAL . ' and comment_id=' . $comment_id;
        $params['columns'] = 'id as reply_id,comment_id,images,user_id as uid,to_user_id ,content,like_cnt,created,if(parent_id>0,0,1) as is_first_level';
        $params['order'] = 'created desc';
        $params['limit'] = $limit;
        if ($last_id > 0) {
            $params[0] .= " and id<" . $last_id;
        } else {
            $params[0] .= " and id>" . $first_id;
        }
        $comment = SocialComment::findOne(['id=' . $comment_id, 'columns' => 'like_cnt']);
        if (!$comment) {
            return $res;
        }
        if ($comment['like_cnt'] > 0) {
            $like_users = SocialLike::getByColumnKeyList(['type="' . \Services\Social\SocialManager::TYPE_COMMENT . '" and item_id=' . $comment_id . ' and enable=1', 'columns' => 'user_id as uid,created', 'order' => 'created', 'limit' => 5], 'uid');
            $user_infos = Users::findList(['id in (' . implode(',', array_column($like_users, 'uid')) . ')', 'columns' => 'id as uid,avatar']);
            $order_data = [];//排序

            foreach ($user_infos as $u) {
                $order_data[] = $like_users[$u['uid']]['created'];
            }
            array_multisort($order_data, SORT_DESC, $user_infos);
            $res['like_users'] = $user_infos;
        }
        $res['data_count'] = SocialCommentReply::dataCount('status=' . \Services\Social\SocialManager::COMMENT_STATUS_NORMAL . ' and comment_id=' . $comment_id);
        $list = [];
        if ($res['data_count'] > 0) {
            $list = SocialCommentReply::findList($params);
        }

        if ($list) {
            $reply_ids = implode(',', array_column($list, 'reply_id'));
            $uids = implode(',', array_unique(array_merge(array_column($list, 'uid'), array_column($list, 'to_user_id'))));

            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade'], 'uid');
            if ($uid) {
                $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
                $likes = SocialLike::getByColumnKeyList(['type="' . \Services\Social\SocialManager::TYPE_REPLY . '" and user_id=' . $uid . ' and item_id in (' . $reply_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            } else {
                $user_contact = [];
                $likes = [];
            }


            foreach ($list as &$item) {
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : $users[$item['uid']]['username'];
                $item['sex'] = $users[$item['uid']]['sex'];
                $item['avatar'] = $users[$item['uid']]['avatar'];
                $item['grade'] = $users[$item['uid']]['grade'];
                $item['content'] = FilterUtil::unPackageContentTag($item['content'], $uid, '/panel/users/detail/?user_id=');

                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['is_like'] = isset($likes[$item['reply_id']]) ? 1 : 0;

                $item['to_username'] = (isset($user_contact[$item['to_user_id']]) && $user_contact[$item['to_user_id']]['mark']) ? $user_contact[$item['to_user_id']]['mark'] : $users[$item['to_user_id']]['username'];
            }
        }
        $res['data_list'] = $list;
        return $res;
    }


    /**点赞
     * @param $uid --用户id
     * @param $item_id --动态/资讯等id
     * @param $type -点赞类型
     * @return bool
     */
    public function like($uid, $item_id, $type)
    {
        if (!$data = $this->dataExist($item_id, $type, 'user_id')) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //点过赞了 返回true
        if ($like = SocialLike::findOne('type="' . $type . '" and item_id=' . $item_id . ' and user_id=' . $uid)) {
            if ($like['enable'] != 1) {
                SocialLike::updateOne(['enable' => 1, 'modify' => time()], ['id' => $like['id']]);
                $this->changeCnt($type, $item_id, 'like_cnt', true); //更新点赞数
            }
            return true;
        } else {
            $like = new SocialLike();
            if ($like->insertOne(['type' => $type, 'item_id' => $item_id, 'user_id' => $uid, 'created' => time()])) {
                //自己赞自己不发消息
                if ($data['to_user_id'] != $uid) {
                    $data = ['user_id' => $uid, 'to_user_id' => $data['user_id']];
                    //评论
                    if ($type == \Services\Social\SocialManager::TYPE_COMMENT) {
                        $data['type_name'] = '评论';
                        $item_info = self::getCommentItem($item_id);
                        if ($item_info) {
                            $data['item_id'] = $item_info['item_id'];
                            $data['type'] = $item_info['type'];
                            if (!$data = $this->dataExist($data['item_id'], $data['type'], '1')) {
                                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
                            }
                        }
                    } //回复
                    else if ($type == \Services\Social\SocialManager::TYPE_REPLY) {
                        $data['type_name'] = '回复';
                        $item_info = self::getCommentReplyItem($item_id);
                        if ($item_info) {
                            $data['item_id'] = $item_info['item_id'];
                            $data['type'] = $item_info['type'];
                            if (!$data = $this->dataExist($data['item_id'], $data['type'], '1')) {
                                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
                            }
                        }
                    } //动态
                    else if ($type == \Services\Social\SocialManager::TYPE_DISCUSS) {
                        $data['type_name'] = '动态';
                        $data['item_id'] = $item_id;
                        $data['type'] = $type;
                    } else if ($type == \Services\Social\SocialManager::TYPE_NEWS) {
                    }
                    //自己赞自己不发消息
                    if ($data['item_id'] && $data['user_id'] != $data['to_user_id']) {
                        ImManager::init()->initMsg(ImManager::TYPE_LIKE, $data);
                        //发送im消息
                    }
                }
                $this->changeCnt($type, $item_id, 'like_cnt', true); //更新点赞数

                //送经验值
                PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_ADD_LIKE);
                return true;
            }
        }

        return false;
    }

    /**取消赞
     * @param $uid --用户id
     * @param $item_id --动态/资讯等id
     * @param $type -点赞类型
     * @return bool
     */
    public function dislike($uid, $item_id, $type)
    {
        //没有赞过 返回true
        if (!$like = SocialLike::findOne('type="' . $type . '" and item_id=' . $item_id . ' and user_id=' . $uid)) {
            return true;
        }
        if ($like['enable'] == 0) {
            return true;
        }

        if (SocialLike::updateOne(['enable' => 0, 'modify' => time()], ['id' => $like['id']])) {
            $this->changeCnt($type, $item_id, 'like_cnt', false); //更新点赞数
            return true;
        }
        return false;
    }

    /**更新字段数值
     * @param $type
     * @param $item_id
     * @param $column -字段名
     * @param $is_add
     */
    public function changeCnt($type, $item_id, $column, $is_add = true)
    {
        $table_name = $this->getTableName($type);
        if ($is_add) {
            $this->db->execute("update " . $table_name . " set " . $column . "=" . $column . "+1 where id=" . $item_id);
        } else {
            $this->db->execute("update " . $table_name . " set " . $column . "=" . $column . "-1 where id=" . $item_id . " and " . $column . ">0");
        }
    }

    /**获取表名
     * @param $type
     * @return string
     */
    public function getTableName($type)
    {
        $table_name = '';
        //评论
        if ($type == \Services\Social\SocialManager::TYPE_COMMENT) {
            $table_name = 'social_comment';
        } //回复
        else if ($type == \Services\Social\SocialManager::TYPE_REPLY) {
            $table_name = 'social_comment_reply';
        } //动态
        else if ($type == \Services\Social\SocialManager::TYPE_DISCUSS) {
            $table_name = 'social_discuss';
        } //资讯
        else if ($type == \Services\Social\SocialManager::TYPE_NEWS) {
            $table_name = 'social_news';
        }
        return $table_name;
    }
    /*发送im消息的简短信息*/
    /**
     * @param $type
     * @param $item_id
     * @return bool
     */
    public function getImShortDate($type, $item_id)
    {
        $data = [];
        switch ($type) {
            case \Services\Social\SocialManager::TYPE_DISCUSS: //动态
                $data = SocialDiscuss::findOne(["id=" . $item_id, 'columns' => 'media,media_type,content,share_original_type,share_original_item_id']);
                break;
            case \Services\Social\SocialManager::TYPE_NEWS: //资讯
                $data = SocialNews::findOne(["id=" . $item_id]);
                break;
            default:

        }
        return $data;
    }

    /**获取评论的主体 如动态 资讯等
     * @param $comment_id
     * @return array
     */
    public function getCommentItem($comment_id)
    {
        $data = SocialComment::findOne('id=' . $comment_id);
        return $data;
    }

    /**获取回复的主体 如动态 资讯等
     * @param $reply_id
     * @return array
     */
    public function getCommentReplyItem($reply_id)
    {
        $data = SocialCommentReply::findOne('id=' . $reply_id);
        return $data;
    }

    /**判断数据是否存在
     * @param $item_id
     * @param $type
     * @param $columns
     * @return bool
     */
    public function dataExist($item_id, $type, $columns = '1')
    {
        $data = '';
        switch ($type) {
            case \Services\Social\SocialManager::TYPE_DISCUSS: //动态
                $data = SocialDiscuss::findOne(["id=" . $item_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'columns' => $columns]);
                break;
            case \Services\Social\SocialManager::TYPE_NEWS:
                //新闻资讯
                $data = SocialNews::findOne(["id=" . $item_id . ' and status=' . \Services\Social\SocialManager::NEWS_STATUS_NORMAL, 'columns' => $columns]);
                break;
            case \Services\Social\SocialManager::TYPE_GROUP:
                //公开群
                $data = Group::findOne(["id=" . $item_id . ' and status=' . GroupManager::GROUP_STATUS_NORMAL, 'columns' => $columns]);
                break;
            case \Services\Social\SocialManager::TYPE_USER:
                //用户
                $data = Users::findOne(["id=" . $item_id . ' and status=1', 'columns' => $columns]);
                break;
            case \Services\Social\SocialManager::TYPE_COMMENT:
                //评论
                $data = SocialComment::findOne(["id=" . $item_id . ' and status=' . \Services\Social\SocialManager::COMMENT_STATUS_NORMAL, 'columns' => $columns]);

                break;
            case \Services\Social\SocialManager::TYPE_REPLY:
                //回复
                $data = SocialCommentReply::findOne(["id=" . $item_id . ' and status=' . \Services\Social\SocialManager::COMMENT_STATUS_NORMAL, 'columns' => $columns]);
                break;
            default:
        }
        if ($columns == '1') {
            return $data ? true : false;
        } else {
            return $data;
        }

    }

}