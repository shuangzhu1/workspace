<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/12
 * Time: 14:52
 */

namespace Services\User\Show;


use Models\Statistics\StatisticsShowTotal;
use Models\Statistics\StatisticsShowUser;
use Models\User\UserAttention;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\UserProfile;
use Models\User\Users;
use Models\User\UserShow;
use Models\User\UserShowLike;
use Phalcon\Mvc\User\Plugin;
use Services\Im\ImManager;
use Services\Im\SysMessage;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\User\UserStatus;
use Util\Debug;

class ShowManager extends Plugin
{
    private static $instance = null;

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** 保存/编辑秀场
     * @param $uid
     * @param string $video
     * @param string $images
     * @return bool
     */
    public function save($uid, $video = '', $images = '')
    {
        //之前有上传过  属于编辑
        if (UserShow::exist('user_id=' . $uid)) {
            $data = ['modify' => time()];
            //清空
            if ($video == 'empty') {
                $data['video'] = '';
            } else if ($video) {
                $data['video'] = $video;
            }

            if ($images) {
                $data['images'] = $images;
            }
            if (!UserShow::updateOne($data, 'user_id=' . $uid)) {
                Debug::log("秀场编辑失败", 'error');
                return false;
            }
        } else {
            //app不可信任判断
            if ($video == 'empty') {
                $video = '';
            }
            $user_profile = UserProfile::findOne(['user_id=' . $uid, 'columns' => 'sex,charm']);
            $data = ['created' => time(), 'user_id' => $uid, 'video' => $video, 'charm' => $user_profile['charm'], 'images' => $images, 'sex' => $user_profile['sex']];
            //是否机器人
            if ($this->request->get("is_r", 'int', 0)) {
                $data['user_type'] = 2;
            }
            if (!UserShow::insertOne($data)) {
                Debug::log("秀场保存失败", 'error');
                return false;
            }
        }
        return true;
    }

    /** 点赞
     * @param $uid
     * @param $to_uid
     * @return bool
     */
    public function like($uid, $to_uid)
    {
        try {
            $this->db->begin();
            //之前赞过或者踩过
            if ($showLike = UserShowLike::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id,is_like'])) {
                //之前赞过
                if ($showLike['is_like'] == 1) {
                    $this->db->commit();
                    return true;
                } //之前踩过
                else {
                    if (!UserShow::updateOne("like_cnt=like_cnt+1,dislike_cnt=dislike_cnt-1,current_month_like=current_month_like+1,current_month_dislike=current_month_dislike-1", 'user_id=' . $to_uid)) {
                        throw new \Exception("更新点赞数踩数失败");
                    }
                    $this->db->commit();
                    return 1;
                    /* if (!UserShowLike::updateOne(['is_like' => 1], 'id=' . $showLike['id'])) {
                         throw new \Exception("之前踩过更新为点赞失败");
                     }
                   */
                }
            } //之前没有赞过也没有踩过
            else {
                $userShowLike = ["owner_id" => $uid, "user_id" => $to_uid, 'created' => time(), 'is_like' => 1];
                if (!UserShowLike::insertOne($userShowLike)) {
                    throw new \Exception("点赞-》插入数据失败");
                }
                if (!UserShow::updateOne("like_cnt=like_cnt+1,current_month_like=current_month_like+1,current_month_charm=current_month_charm+1,charm=charm+1", 'user_id=' . $to_uid)) {
                    throw new \Exception("更新点赞数失败");
                }
                if (!UserProfile::updateOne("charm=charm+1", 'user_id=' . $to_uid)) {
                    throw new \Exception("更新用户魅力值失败");
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log("秀场点赞失败:" . $e->getMessage(), 'error');
            return false;
        }
    }

    /**踩
     * @param $uid
     * @param $to_uid
     * @return bool
     */
    public function dislike($uid, $to_uid)
    {
        try {
            $this->db->begin();
            //之前赞过或者踩过
            if ($showLike = UserShowLike::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id,is_like'])) {
                //之前踩过
                if ($showLike['is_like'] == 0) {
                    $this->db->commit();
                    return true;
                } //之前赞过
                else {
                    if (!UserShow::updateOne("like_cnt=like_cnt-1,dislike_cnt=dislike_cnt+1,current_month_like=current_month_like-1,current_month_dislike=current_month_dislike+1", 'user_id=' . $to_uid)) {
                        throw new \Exception("更新踩数点赞数失败");
                    }
                    $this->db->commit();
                    return 1;
                    /* if (!UserShowLike::updateOne(['is_like' => 0], 'id=' . $showLike['id'])) {
                         throw new \Exception("之前赞过更新为踩失败");
                     }
                  */
                }
            } //之前没有赞过也没有踩过
            else {
                $userShowLike = ["owner_id" => $uid, "user_id" => $to_uid, 'created' => time(), 'is_like' => 0];
                if (!UserShowLike::insertOne($userShowLike)) {
                    throw new \Exception("踩-》插入数据失败");
                }
                if (!UserShow::updateOne("dislike_cnt=dislike_cnt+1,current_month_dislike=current_month_dislike+1", 'user_id=' . $to_uid)) {
                    throw new \Exception("更新踩数失败");
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log("秀场踩失败:" . $e->getMessage());
            return false;
        }
    }

    /**关闭秀场
     * @param $uid
     * @return bool
     */
    public function close($uid)
    {
        if (UserShow::updateOne('enable=0', 'user_id=' . $uid)) {
            return true;
        }
        return false;
    }

    /**开启秀场
     * @param $uid
     * @return bool
     */
    public function open($uid)
    {
        if (UserShow::updateOne('enable=1', 'user_id=' . $uid)) {
            return true;
        }
        return false;
    }

    /**获取 秀场列表
     * @param $uid -用户id
     * @param $lng -经度
     * @param $lat -纬度
     * @param $filter -刷选条件
     * @param int $page -第几页
     * @param int $limit -每页显示的数量
     * @return array
     */
    public function list($uid, $lng, $lat, $filter, $page = 1, $limit = 20)
    {
        $where = "s.enable=1 ";
        if (!empty($filter['sex'])) {
            $where .= " and p.sex=" . $filter['sex'];
        }
        if(!empty($filter['age_start']) ){
            $birthday_start = strtotime((date('Y') - $filter['age_start']) . '-' . date('m') . '-' . date('d'));
            $where .= " and UNIX_TIMESTAMP(p.birthday)<=" . $birthday_start;
        }
        if(!empty($filter['age_end'])){
            $birthday_end = strtotime((date('Y') - $filter['age_end'] + 1) . '-' . date('m') . '-' . date('d'));
            $where .= " and UNIX_TIMESTAMP(p.birthday)>=" . $birthday_end;
        }
        if (!empty($filter['distance'])) {
            $where .= " and if(s.user_type=1,GetDistances(l.lat,l.lng,$lat,$lng)<=" . ($filter['distance'] * 1000) . ",FLOOR(7 + (RAND() * 6))=7)";
        }
        if (!empty($filter['c'])) {
            $where .= " and p.constellation=" . $filter['c'];
        }
        //  $order = " order by GetDistances(l.lat,l.lng,$lat,$lng) asc";
        $order = " order by score desc";

        //确定排序
        //  $order_user_id = $this->db->query("select s.user_id from user_show as s left join users  u on s.user_id=u.id left join user_profile as p on s.user_id=p.user_id LEFT join user_location as l  on s.user_id=l.user_id where $where order by (s.charm-s.dislike_cnt) desc")->fetchAll(\PDO::FETCH_ASSOC);
        $query = "select s.user_id as uid,s.user_type,video,(s.charm-s.dislike_cnt+us.fans_cnt) as score,images,u.username,u.avatar,p.sex,u.grade,p.is_auth,p.birthday,p.constellation,l.lng,l.lat, if(s.user_type=1,GetDistances(l.lat,l.lng,$lat,$lng),0) as distance,p.charm from user_show as s left join users  u on s.user_id=u.id left join user_profile as p on s.user_id=p.user_id left join user_count_stat as us on s.user_id=us.user_id  LEFT join user_location as l  on s.user_id=l.user_id where ($where) $order ";
        /*        $total_count = $res = $this->db->query("select count(1) as count from user_show as s left join users  u on s.user_id=u.id left join user_profile as p on s.user_id=p.user_id left join user_count_stat as us on s.user_id=us.user_id  LEFT join user_location as l  on s.user_id=l.user_id where $where")->fetch(\PDO::FETCH_ASSOC);*/
        if ($page >= 1 && $limit) {
            $query .= " limit " . ($page - 1) * $limit . ',' . $limit;
        }
        //var_dump($query);exit;
        $res = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        if ($res) {
            //$order_user_id = array_column($order_user_id, 'user_id');
            //   $uids = implode(',', $order_user_id);
            $uids = implode(',', array_column($res, 'uid'));
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人设置列表
            $fans = UserAttention::getColumn(['user_id in (' . $uids . ')', 'columns' => 'count(1) as count,user_id', 'group' => 'user_id'], 'count', 'user_id');

            foreach ($res as $j => &$item1) {
                $item1['rank'] = $j + 1; //array_search($item['uid'], $order_user_id) + 1;
                $item1['constellation'] = $item1['constellation'] ? UserStatus::$constellation[$item1['constellation']] : '';
                $item1['fans_count'] = isset($fans[$item1['uid']]) ? intval($fans[$item1['uid']]) : 0;
                //机器人
                if ($item1['user_type'] == UserStatus::USER_TYPE_ROBOT) {
                    $item1['distance'] = rand(1, (!empty($filter['distance']) ? $filter['distance'] : 5) * 1000);
                }
                $user_personal_setting && isset($user_personal_setting[$item1['user_id']]) && $user_personal_setting[$item1['user_id']]['mark'] && $item['username'] = $user_personal_setting[$item1['user_id']]['mark'];
                unset($item1['lng']);
                unset($item1['lat']);
                unset($item1['user_type']);
            }
        }
        /*    //机器人补齐
            if (!$res || count($res) < $limit) {
                $last_info = $this->db->query("select (s.charm-s.dislike_cnt+us.fans_cnt) as score from user_show as s left join users  u on s.user_id=u.id left join user_profile as p on s.user_id=p.user_id left join user_count_stat as us on s.user_id=us.user_id  LEFT join user_location as l  on s.user_id=l.user_id where $where order by (s.charm-s.dislike_cnt+us.fans_cnt) asc")->fetch(\PDO::FETCH_ASSOC);
                $res = array_merge($res, $this->makeUp($uid, $filter, ($page - 1) * $limit + count($res), $limit, $page, $total_count, $last_info['score']));
            }*/
        return $res;
    }

    //机器人数据补齐
    public function makeUp($uid, $filter, $current_rank = 0, $limit = 20, $page, $total, $last_score)
    {
        $where = "s.enable=1 and s.user_type=" . UserStatus::USER_TYPE_ROBOT;
        if (!empty($filter['sex'])) {
            $where .= " and p.sex=" . $filter['sex'];
        }

        if (!empty($filter['age_start']) && !empty($filter['age_end'])) {
            $birthday_start = (date('Y') - $filter['age_start']) . '-' . date('m') . '-' . date('d');
            $birthday_end = (date('Y') - $filter['age_end'] + 1) . '-' . date('m') . '-' . date('d');
            $where .= " and p.birthday<=" . $birthday_end . " and p.birthday>=" . $birthday_start;
        }
        if (!empty($filter['c'])) {
            $where .= " and p.constellation=" . $filter['c'];
        }
        $order = " order by rand() desc";
        $query = "select s.user_id as uid,video,images,u.username,u.avatar,p.sex,u.grade,p.is_auth,p.birthday,p.constellation,l.lng,l.lat,p.charm from user_show as s left join users  u on s.user_id=u.id left join user_profile as p on s.user_id=p.user_id LEFT join user_location as l  on s.user_id=l.user_id where $where $order ";
        $query .= " limit " . $limit;
        $res = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        //-------随机人气值-------------
        $rand_score_start = 0;
        $rand_score_end = 0;

        if ($last_score > 0) {
            if ($last_score - $page * $limit >= 0) {
                $rand_score_start = $last_score - $page * $limit + 100;
                $rand_score_end = $last_score - ($page - 1) * $limit + 100;
            } else {
                $rand_score_end = ($last_score / ($limit + 100));
                $rand_score_end = $rand_score_end < 100 ? 1000 : $rand_score_end;
                $rand_score_start = 1;
            }
        } else {
            $rand_score_start = rand(1, 1000);
        }
        $rand_arr = $this->randScore($rand_score_start, $rand_score_end, $limit);


        if ($res) {
            //$order_user_id = array_column($order_user_id, 'user_id');
            //   $uids = implode(',', $order_user_id);
            $uids = implode(',', array_column($res, 'uid'));
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人设置列表
            $fans = UserAttention::getColumn(['user_id in (' . $uids . ')', 'columns' => 'count(1) as count,user_id', 'group' => 'user_id'], 'count', 'user_id');

            foreach ($res as $i => $item) {
                $res[$i]['score'] = $rand_arr[$i];
                //   $item['charm'] = rand(10, 1000);
                $res[$i]['distance'] = !empty($filter['distance']) ? $filter['distance'] * 1000 - rand(0, 1000) : rand(0, 10000);
                $res[$i]['rank'] = $current_rank + $i + 1; //array_search($item['uid'], $order_user_id) + 1;
                $res[$i]['constellation'] = $item['constellation'] ? UserStatus::$constellation[$item['constellation']] : '';
                $res[$i]['fans_count'] = isset($fans[$item['uid']]) ? intval($fans[$item['uid']]) : 0;
                $user_personal_setting && isset($user_personal_setting[$item['user_id']]) && $user_personal_setting[$item['user_id']]['mark'] && $item['username'] = $user_personal_setting[$item['user_id']]['mark'];
                unset($res[$i]['lng']);
                unset($res[$i]['lat']);
            }
        }
        return $res;
    }

    //统计
    public function statistics($issue = '')
    {
        set_time_limit(0);
        $limit = 1000;
        $p = 1;
        $res = ''; //用户列表 字符串
        $user_count = 0; //参与的用户数

        try {
            $this->db->begin();
            $this->original_mysql->begin();
            if ($issue) {
                StatisticsShowUser::remove("issue=" . $issue);
                StatisticsShowTotal::remove('issue=' . $issue);
                $key = $issue;
            } else {
                $key = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SEQUENCE, 'show'); //当前第几期
            }
            //todo  数据重复的情况暂未考虑
            while ($list = UserShow::findList(['enable=1', 'order' => 'score desc ', 'offset' => ($p - 1) * $limit, 'limit' => $limit, 'columns' => '(charm-dislike_cnt) as score,user_id'])) {
                foreach ($list as $k => $item) {
                    $res .= "|" . $item['user_id'] . "," . $item['score'];
                    $user_count += 1;
                    $data = ["user_id" => $item['user_id'], 'rank' => $k + 1, 'issue' => $key, 'score' => $item['score']];
                    if (!($p > 1 && strpos($res, '|' . $item['user_id']) >= 0)) {
                        if (!StatisticsShowUser::insertOne($data)) {
                            throw new \Exception("插入StatisticsShowUser数据失败:" . var_export($data, true));
                        }
                    }
                }
                if ($res) {
                    $res = substr($res, 1);
                    $data = ['rank' => $res, 'issue' => $key, 'user_count' => $user_count, 'created' => time()];
                    if (!StatisticsShowTotal::insertOne($data)) {
                        throw new \Exception("插入StatisticsShowTotal数据失败:" . var_export($data, true));
                    }
                }
                $p++;
            }

            //清空当前数据
            if (!UserShow::updateOne(['current_month_like' => 0, 'current_month_dislike' => 0, 'current_month_charm' => 0], '1=1')) {
                throw new \Exception("UserShow数据更新失败:");
            }

            SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_SEQUENCE, 'show', ['val' => ($key + 1)]); //增加期数
            $this->db->commit();
            $this->original_mysql->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->original_mysql->rollback();
            Debug::log("秀场统计失败:" . $e->getMessage(), "error");
            return false;
        }
    }

    /** 获取前几位的排名
     * @param $issue
     * @param $count
     * @param $uid
     * @return array
     */
    public function top($issue, $count, $uid)
    {
        $list = [];
        $total = StatisticsShowTotal::findOne(['issue=' . $issue, 'columns' => 'rank']);
        if (!$total) {
            return $list;
        }
        $current_count = 0;
        $rank = $total['rank'];
        while ($current_count < $count && $rank) {
            //不是最后一个
            if (($split_pos = strpos($rank, '|')) !== false) {
                $str = substr($rank, 0, $split_pos); //取出当前串第一个 排名数据
                $pos = strpos($str, ',');
                $list[] = ['uid' => substr($str, 0, $pos), 'score' => substr($str, $pos + 1)];
                $rank = substr($rank, $split_pos + 1);
                $current_count += 1;
            } //最后一个
            else {
                $pos = strpos($rank, ',');
                $list[] = ['uid' => substr($rank, 0, $pos), 'score' => substr($rank, $pos + 1)];
                $rank = '';
                $current_count += 1;
            }
        }
        if ($list) {
            $uids = implode(',', array_column($list, 'uid'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth'], 'uid');
            $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
            foreach ($list as &$item) {
                $item['sex'] = $users[$item['uid']]['sex'];
                $item['avatar'] = $users[$item['uid']]['avatar'];
                $item['is_auth'] = $users[$item['uid']]['is_auth'];
                $item['grade'] = $users[$item['uid']]['grade'];
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($item['true_name'] ? $item['true_name'] : $users[$item['uid']]['username']);
            }

        }
        return $list;
    }

    /**获取个人秀场
     * @param $uid
     * @param $to_uid
     * @return mixed|object
     */
    public function detail($uid, $to_uid)
    {
        $res = (object)[];
        //查看自己的
        if ($uid == $to_uid) {
            $show = UserShow::findOne(['user_id=' . $to_uid, 'columns' => 'video,images,like_cnt,dislike_cnt,enable']);
        } else {
            $show = UserShow::findOne(['user_id=' . $to_uid . ' and enable=1', 'columns' => 'video,images,like_cnt,dislike_cnt,enable']);
        }
        if ($show) {
            $show['is_like'] = 0;
            $show['is_dislike'] = 0;
            $res = $show;
            $like = UserShowLike::findOne(["owner_id=" . $uid . ' and user_id=' . $to_uid, 'columns' => 'is_like']);
            if ($like) {
                if ($like['is_like'] == 1) {
                    $res['is_like'] = 1;
                } else {
                    $res['is_dislike'] = 1;
                }
            }
        }
        return $res;
    }

    //
    /**发送消息
     * @param int $issue
     * @return bool
     */
    public function sendMessage($issue = 0)
    {
        if ($issue) {
            $show = StatisticsShowTotal::findOne(['issue=' . $issue, 'columns' => 'rank,issue']);
        } else {
            $show = StatisticsShowTotal::findOne(['issue=' . $issue, 'order' => 'issue desc', 'columns' => 'rank,issue']);
        }
        if ($show && $show['rank']) {
            //获得第一名信息
            if (($pos = strpos($show['rank'], '|')) !== false) {
                $first_body = substr($show['rank'], 0, $pos);
            } else {
                $first_body = $show['rank'];
            }
            $first_body = explode(',', $first_body)[0];
            if ($first_body) {
                $first_body_info = UserInfo::findOne(['user_id=' . $first_body, 'columns' => 'username,sex,avatar']);
                $first_body_show = UserShow::findOne(['user_id=' . $first_body, 'columns' => 'images']);
                $first_body_show['images'] = substr($first_body_show['images'], 0, strpos($first_body_show['images'], ','));

                //把消息加入队列
                $redis = $this->di->get("message_queue");
                $i = 1;
                $uids = [];//参与了选秀的选手
                while ($list = StatisticsShowUser::findList(['issue=' . $show['issue'], 'columns' => 'user_id,rank', 'offset' => ($i - 1) * 1000, 'limit' => 1000])) {
                    foreach ($list as $item) {
                        $data = [
                            'extend_type' => ImManager::TYPE_SHOW_CHAMPION,
                            'to_user_id' => $item['user_id'],
                            'user_id' => $first_body,
                            'username' => $first_body_info['username'],
                            'avatar' => $first_body_info['avatar'],
                            'sex' => $first_body_info['sex'],
                            'rank' => $item['rank'],
                            'issue' => $show['issue'],
                            'images' => $first_body_show['images']
                        ];
                        $redis->rPush(CacheSetting::KEY_SYSTEM_MESSAGE_PUSH_LIST . ":" . 'show', json_encode($data, JSON_UNESCAPED_UNICODE));
                        $uids[] = $item['user_id'];
                    }
                    $i++;
                };
                $i = 1;
                while ($list = Users::getColumn(['status=1 and user_type=' . UserStatus::USER_TYPE_NORMAL, 'columns' => 'id', 'offset' => ($i - 1) * 500, 'limit' => 500], 'id')) {
                    $uids = array_diff($list, $uids);
                    if ($uids) {
                        $data = [
                            'extend_type' => ImManager::TYPE_SHOW_CHAMPION,
                            'to_user_id' => implode(',', $uids),
                            'user_id' => $first_body,
                            'username' => $first_body_info['username'],
                            'avatar' => $first_body_info['avatar'],
                            'sex' => $first_body_info['sex'],
                            'rank' => '',
                            'issue' => $show['issue'],
                            'images' => $first_body_show['images']
                        ];
                        $redis->rPush(CacheSetting::KEY_SYSTEM_MESSAGE_PUSH_LIST . ":" . 'show', json_encode($data, JSON_UNESCAPED_UNICODE));
                    }
                    $i++;
                };
                //发消息
                //todo 发送失败的情况
                while ($data = $redis->lPop(CacheSetting::KEY_SYSTEM_MESSAGE_PUSH_LIST . ":" . 'show')) {
                    var_dump(json_decode($data, true));
                    ImManager::init()->initMsg(ImManager::TYPE_SHOW_CHAMPION, json_decode($data, true));
                }
            }
            //
        }
        return true;
    }

    /**生成一随机分数组
     * @param $start
     * @param $end
     * @param $count
     * @return array
     */
    public function randScore($start, $end, $count)
    {
        $res = [];
        for ($i = 0; $i < $count; $i++) {
            $res[] = rand($start, $end);
        }
        rsort($res);
        return $res;
    }

}