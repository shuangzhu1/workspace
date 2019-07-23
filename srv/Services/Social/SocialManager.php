<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/9
 * Time: 14:31
 */

namespace Services\Social;


use Components\Rules\Point\PointRule;
use Models\Community\CommunityDiscuss;
use Models\Customer\CustomerGame;
use Models\Shop\Shop;
use Models\Shop\ShopGoods;
use Models\Social\SocialShareBackLog;
use Models\Square\RedPackage;
use Models\User\UserPersonalSetting;
use Models\User\UserVideo;
use Services\Shop\GoodManager;
use Services\Shop\ShopManager;
use Services\Site\CashRewardManager;
use Services\Site\SiteKeyValManager;
use Services\User\ContactManager;
use Services\User\Square\SquareTask;
use Services\User\UserStatus;
use Models\Group\Group;
use Models\Site\SiteReportReason;
use Models\Social\SocialComment;
use Models\Social\SocialCommentReply;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussTopLog;
use Models\Social\SocialDiscussViewLog;
use Models\Social\SocialFav;
use Models\Social\SocialLike;
use Models\Social\SocialNews;
use Models\Social\SocialReport;
use Models\Social\SocialShare;
use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPointGrade;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Services\Discuss\DiscussManager;
use Services\Discuss\TagManager;
use Services\Im\ImManager;
use Services\Im\SysMessage;
use Services\Site\CacheSetting;
use Services\User\GroupManager;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;
use Util\Time;

class SocialManager extends Plugin
{
    const TYPE_GROUP = 'group';//群
    const TYPE_DISCUSS = 'discuss';//动态
    const TYPE_NEWS = 'news';//新闻
    const TYPE_SHARE = 'share';//分享
    const TYPE_ACTIVITY = 'activity';//活动
    const TYPE_USER = 'user';//用户
    const TYPE_COMMENT = 'comment';//评论
    const TYPE_REPLY = 'reply';//回复
    const TYPE_INVITE = 'invite';//邀请
    const TYPE_VIDEO = 'video';//视频
    const TYPE_SHOP = 'shop';//店铺
    const TYPE_GOOD = 'good';//商品
    const TYPE_PACKAGE = 'package';//红包
    const TYPE_PROMOTE = 'promote';//推广恐龙谷
    const TYPE_COMMUNITY_DISCUSS = 'comm_discuss';//社区动态
    const TYPE_COMMUNITY_NEWS = 'comm_news';//社区新闻


    const COMMENT_STATUS_SHIELD = 0;//被系统删除
    const COMMENT_STATUS_NORMAL = 1;//正常
    const COMMENT_STATUS_DELETED = 2;//被用户删除

    const NEWS_STATUS_SHIELD = 0;//被屏蔽
    const NEWS_STATUS_NORMAL = 1;//正常

    const VIDEO_STATUS_SHIELD = 0;//被屏蔽
    const VIDEO_STATUS_NORMAL = 1;//正常
    const VIDEO_STATUS_DELETED = 2;//被用户删除

    const REPORT_STATUS_SENDING = 0;//刚提交
    const REPORT_STATUS_SUCCESS = 1;//审核通过
    const REPORT_STATUS_FAILED = 2;//审核未通过

    const REPORT_REASON_TYPE_DISCUSS = 1;//动态
    const REPORT_REASON_TYPE_GROUP = 2;//群


    public static $_comment_type = [
        self::TYPE_DISCUSS => '动态',
        self::TYPE_VIDEO => '视频',
        self::TYPE_PACKAGE => '红包',
    ];
    public static $_forward_type = [
        self::TYPE_DISCUSS => '动态',
        self::TYPE_NEWS => '新闻资讯',
        self::TYPE_USER => '名片',
        self::TYPE_GROUP => '群名片',
        self::TYPE_SHOP => '店铺',
        self::TYPE_GOOD => '商品',
        self::TYPE_SHARE => '第三方App分享',
        self::TYPE_VIDEO => '视频',

    ];
    public static $_share_type = [
        self::TYPE_DISCUSS => '动态',
        self::TYPE_USER => '名片',
        self::TYPE_GROUP => '群名片',
        self::TYPE_INVITE => '邀请',
        self::TYPE_PROMOTE => '推广恐龙谷',
    ];
    //分享平台
    public static $_share_platform = [
        "其他" => 0,
        "QQ" => 1,
        "QQ空间" => 2,
        "朋友圈" => 3,
        "微信好友" => 4,
        "微博" => 5,
        "手机短信" => 6
    ];
    private static $instance = null;

    public $ajax = null;

    /** $is_cli
     * @param bool $is_cli php cli模式
     * @return null|SocialManager
     */
    public static function init($is_cli = false)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($is_cli);
        }
        return self::$instance;
    }

    public function __construct($is_cli)
    {
        if (!$is_cli) {
            $this->ajax = new Ajax();
        }
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
            //Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            return false;

        }
        try {
            $res = true;
            $this->db->begin();
            // $redis = $this->di->get("publish_queue");
            //点过赞了 返回true

            if ($like = SocialLike::findOne(['type="' . $type . '" and item_id=' . $item_id . ' and user_id=' . $uid])) {
                if ($like['enable'] != 1) {
                    if (SocialLike::updateOne(["enable" => 1, 'modify' => time()], ['id' => $like['id'], 'enable' => 0])) {
                        $this->changeCnt($type, $item_id, 'like_cnt', true); //更新点赞数
                        // $redis->publish(CacheSetting::KEY_LIKE, json_encode(['uid' => $uid, 'type' => $type, 'item_id' => $item_id, 'is_add' => true]));
                        $this->db->commit();
                        return true;
                    }
                    $res = 1;
                } else {
                    $res = 2;
                }
            } else {

                $like = new SocialLike();

                if ($like->insertOne(['type' => $type, 'item_id' => $item_id, 'user_id' => $uid, 'created' => time()])) {
                    //自己赞自己不发消息
                    if ($data['user_id'] != $uid) {
                        $data = ['user_id' => $uid, 'to_user_id' => $data['user_id']];
                        //评论
                        if ($type == self::TYPE_COMMENT) {
                            $data['type_name'] = '评论';
                            $item_info = self::getCommentItem($item_id);
                            if ($item_info) {
                                $data['item_id'] = $item_info['item_id'];
                                $data['type'] = $item_info['type'];
                                if (!$this->dataExist($data['item_id'], $data['type'], '1')) {
                                    //Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
                                    $this->db->rollback();
                                    return false;
                                }
                            }
                        } //回复
                        else if ($type == self::TYPE_REPLY) {
                            $data['type_name'] = '回复';
                            $item_info = self::getCommentReplyItem($item_id);
                            if ($item_info) {
                                $data['item_id'] = $item_info['item_id'];
                                $data['type'] = $item_info['type'];
                                if (!$info = $this->dataExist($data['item_id'], $data['type'], '1')) {
                                    // Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
                                    $this->db->rollback();
                                    return false;
                                }
                            }
                        } //动态
                        else if ($type == self::TYPE_DISCUSS) {
                            $data['type_name'] = '动态';
                            $data['item_id'] = $item_id;
                            $data['type'] = $type;
                        } else if ($type == self::TYPE_VIDEO) {
                            $data['type_name'] = '视频';
                            $data['item_id'] = $item_id;
                            $data['type'] = $type;
                        }
                        //自己赞自己不发消息
                        if ($data['item_id'] && $data['user_id'] != $data['to_user_id']/* && $type != self::TYPE_VIDEO*/) {
                            ImManager::init()->initMsg(ImManager::TYPE_LIKE, $data);
                            //发送im消息
                        }
                    }
                    $this->changeCnt($type, $item_id, 'like_cnt', true); //更新点赞数
                    //  $redis->publish(CacheSetting::KEY_LIKE, json_encode(['uid' => $uid, 'type' => $type, 'item_id' => $item_id, 'is_add' => true]));

                    //送经验值
                    PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_ADD_LIKE);

                    $res = 1;
                }
            }
            $this->db->commit();
            return $res;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log("点赞失败:" . $e->getMessage(), 'error');
            return false;
        }

    }

    /**取消赞
     * @param $uid --用户id
     * @param $item_id --动态/资讯等id
     * @param $type -点赞类型
     * @return bool
     */
    public function dislike($uid, $item_id, $type)
    {
        try {
            $res = true;
            $this->db->begin();
            //$redis = $this->di->get("publish_queue");
            //没有赞过 返回true
            if (!$like = SocialLike::findOne(['type="' . $type . '" and item_id=' . $item_id . ' and user_id=' . $uid])) {
                $res = 2;
            } else {
                if ($like['enable'] == 0) {
                    $res = 2;
                } else {
                    if (SocialLike::updateOne(["enable" => 0, "modify" => time()], ["id" => $like['id'], 'enable' => 1])) {
                        $this->changeCnt($type, $item_id, 'like_cnt', false); //更新点赞数
                        // $redis->publish(CacheSetting::KEY_LIKE, json_encode(['uid' => $uid, 'type' => $type, 'item_id' => $item_id, 'is_add' => false]));

                        $res = 1;
                    }
                }
            }
            $this->db->commit();
            return $res;
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log("取消赞失败:" . $e->getMessage(), 'error');
            return false;
        }
    }

    /**更新字段数值
     * @param $type
     * @param $item_id
     * @param $column -字段名
     * @param $is_add
     * @return boolean
     */
    public function changeCnt($type, $item_id, $column, $is_add = true)
    {
        $table_name = $this->getTableName($type);

        if ($table_name == 'red_package') {
            $where = 'package_id="' . $item_id . '"';
        } else {
            $where = 'id="' . $item_id . '"';
        }

        if ($is_add) {
            $sql = "update " . $table_name . " set " . $column . "=" . $column . '+1 where ' . $where;
        } else {
            $sql = "update " . $table_name . " set " . $column . "=" . $column . "-1 where " . $where . " and " . $column . ">0";
        }
        if (in_array($table_name, ['shop', 'shop_goods', 'red_package', 'user_video', 'community_discuss', 'community_news'])) {
            $res = $this->original_mysql->execute($sql);
        } else {
            $res = $this->db->execute($sql);
        }
        return $res;
    }

    /**获取表名
     * @param $type
     * @return string
     */
    public function getTableName($type)
    {
        $table_name = '';
        //评论
        if ($type == self::TYPE_COMMENT) {
            $table_name = 'social_comment';
        } //回复
        else if ($type == self::TYPE_REPLY) {
            $table_name = 'social_comment_reply';
        } //动态
        else if ($type == self::TYPE_DISCUSS) {
            $table_name = 'social_discuss';
        } //社区动态
        else if ($type == self::TYPE_COMMUNITY_DISCUSS) {
            $table_name = 'community_discuss';
        } //社区新闻
        else if ($type == self::TYPE_COMMUNITY_NEWS) {
            $table_name = 'community_news';
        } //视频
        else if ($type == self::TYPE_VIDEO) {
            $table_name = 'user_video';
        }//商品
        else if ($type == self::TYPE_GOOD) {
            $table_name = 'shop_goods';
        }//商铺
        else if ($type == self::TYPE_SHOP) {
            $table_name = 'shop';
        } //红包
        else if ($type == self::TYPE_PACKAGE) {
            $table_name = 'red_package';
        }
        return $table_name;
    }

    /**收藏
     * @param $uid --用户id
     * @param $item_id --动态/资讯等id
     * @param $type -点赞类型
     * @return bool
     */
    public function collect($uid, $item_id, $type)
    {
        $data = '';
        switch ($type) {
            case self::TYPE_DISCUSS:
                $data = SocialDiscuss::findOne(["id=" . $item_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'columns' => 'id']);
                break;
            case self::TYPE_SHOP:
                $data = Shop::findOne(["id=" . $item_id . ' and status=' . ShopManager::status_normal, 'columns' => 'id']);
                break;
            case self::TYPE_GOOD:
                $data = ShopGoods::findOne(["id=" . $item_id . ' and status=' . GoodManager::status_normal, 'columns' => 'id']);
                break;
            default :
                break;
        }
        if (!$data/* && $type == self::TYPE_DISCUSS*/) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $fav = SocialFav::findOne('user_id=' . $uid . ' and type="' . $type . '" and item_id=' . $item_id);
        if ($fav) {
            if ($fav['enable'] != 1) {
                if (SocialFav::updateOne(['enable' => 1, 'modify' => time()], ['id' => $fav['id']])) {
                    //送经验值
                    PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_COLLECT);
                    $this->changeCnt($type, $item_id, 'fav_cnt', true); //更新收藏数
                }
            }
            return true;
        } else {
            $fav = new SocialFav();
            if ($fav->insertOne(['type' => $type, 'user_id' => $uid, 'item_id' => $item_id, 'created' => time()])) {
                //SocialDiscuss::updateOne(['fav_cnt' => $data['fav_cnt']+1], ['id' => $item_id]);//更新收藏数
                //送经验值
                PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_COLLECT);
                $this->changeCnt($type, $item_id, 'fav_cnt', true);
                return true;
            }
        }
        return false;

    }

    /**取消收藏
     * @param $uid --用户id
     * @param $item_id --动态/资讯等id
     * @param $type -点赞类型
     * @return bool
     */
    public function unCollect($uid, $item_id, $type)
    {
        $item_id_arr = array_filter(explode(',', $item_id));
        //没有收藏过 返回true
        if (count($item_id_arr) == 1) {
            if (!$fav = SocialFav::findOne('type="' . $type . '" and item_id=' . $item_id_arr[0] . ' and user_id=' . $uid)) {
                return true;
            }
            if ($fav['enable'] == 0) {
                return true;
            }
            if (SocialFav::updateOne(['enable' => 0, 'modify' => time()], ['id' => $fav['id']])) {
                $this->changeCnt($type, $item_id_arr[0], 'fav_cnt', false);//更新收藏数
                return true;
            }
        } else {
            $list = SocialFav::findList(['user_id=' . $uid . ' and type="' . $type . '" and item_id in (' . implode(',', $item_id_arr) . ') and enable=1']);
            foreach ($list as $item) {
                if (SocialFav::updateOne(['enable' => 0, 'modify' => time()], ['id' => $item['id']])) {
                    $this->changeCnt($type, $item->item_id, 'fav_cnt', false);//更新收藏数
                }
            }
            return true;
        }


        return false;

    }

    /**获取点赞列表
     * @param $uid
     * @param $type
     * @param $item_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function likeList($uid, $type, $item_id, $page = 0, $limit = 100)
    {
        $res = [/*'data_count' => 0, */
            'data_list' => []];
        /*   $res['data_count'] = SocialLike::count('type="' . $type . '" and item_id=' . $item_id . ' and enable=1');*/
        $params[] = 'type="' . $type . '" and item_id=' . $item_id . ' and enable=1';
        $params['order'] = 'created desc';
        $params['columns'] = 'user_id as uid,created';

        if ($page > 0) {
            $params['offset'] = ($page - 1) * $limit;
            $params['limit'] = $limit;
        }
        $list = SocialLike::findList($params);
        if ($list) {
            $uids = implode(',', array_column($list, 'uid'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth,signature,company,job,industry,charm,birthday,constellation'], 'uid');
            $user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');

            foreach ($list as &$item) {
                $item = $users[$item['uid']];
                //星座
                if ($item['constellation']) {
                    $item['constellation'] = UserStatus::$constellation[$item['constellation']];
                } else {
                    $item['constellation'] = '';
                }
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($users[$item['uid']]['username']);
                $item['newest_dynamic'] = '';
            }

        }
        $res['data_list'] = $list;
        //最新动态
        if ($res['data_list']) {
            $discuss = ContactManager::init()->getNewestDynamic($uid, array_column($res['data_list'], 'uid'));
            if ($discuss) {
                foreach ($res['data_list'] as &$item) {
                    if (isset($discuss[$item['uid']])) {
                        if ($discuss[$item['uid']]['content'] == '') {
                            $item['newest_dynamic'] = ($discuss[$item['share']]['share_original_item_id'] > 0 ? "转发" : '') . DiscussManager::$media_type[$discuss[$item['share']]['media_type']];
                        } else {
                            $item['newest_dynamic'] = FilterUtil::unPackageContentTag($discuss[$item['uid']]['content'], $uid);
                        }
                    }
                }
            }
        }
        return $res;
    }

    /**获取动态访问列表
     * @param $uid
     * @param $type
     * @param $discuss_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function readList($uid, $type, $discuss_id, $page = 0, $limit = 20)
    {
        $res = ['data_list' => []];
        $where = 'discuss_id="' . $discuss_id . '"';

        if ($type == self::TYPE_DISCUSS) {
            $where .= " and type=1";
        } else if ($type == self::TYPE_COMMUNITY_DISCUSS) {
            $where .= " and type=2";
        } else if ($type == self::TYPE_NEWS) {
            $where .= " and type=3";
        }
        $detail = SocialDiscussViewLog::findOne([$where, 'columns' => 'detail']);
        if ($detail) {
            $detail = json_decode($detail['detail'], true);
            arsort($detail);
            $list = array_slice($detail, ($page - 1) * $limit, $limit, true);
            if ($list) {
                $order_date = [];//排序字段
                $uids = implode(',', array_keys($list));
                $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth,signature,company,job,industry'], 'uid');
                $user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
                foreach ($list as $k => $i) {
                    $item = $users[$k];
                    $item['created'] = (string)($i);
                    $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($users[$item['uid']]['username']);
                    $res['data_list'][] = $item;
                    $order_date[] = $i;
                }
                array_multisort($order_date, SORT_DESC, $res['data_list']);
            }
        }
        return $res;
    }

    /**获取收藏列表
     * @param $uid
     * @param $type
     * @param $item_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function collectList($uid, $type, $item_id, $page = 0, $limit = 100)
    {
        $res = [/*'data_count' => 0, */
            'data_list' => []];
        /*  $res['data_count'] = SocialFav::count('type="' . $type . '" and item_id=' . $item_id . ' and enable=1');*/
        $params[] = 'type="' . $type . '" and item_id=' . $item_id . ' and enable=1';
        $params['order'] = 'created desc';
        $params['columns'] = 'user_id as uid';

        if ($page > 0) {
            $params['offset'] = ($page - 1) * $limit;
            $params['limit'] = $limit;
        }
        $list = SocialFav::findList($params);
        if ($list) {
            $uids = implode(',', array_column($list, 'uid'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth,signature,company,job,industry,true_name'], 'uid');
            $user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');

            foreach ($list as &$item) {
                $item = $users[$item['uid']];
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($users[$item['uid']]['username']);
                unset($item['true_name']);
            }
        }
        $res['data_list'] = $list;
        return $res;
    }

    /**获取转发列表
     * @param $uid
     * @param $item_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function forwardList($uid, $item_id, $page = 0, $limit = 100)
    {
        $res = ['data_list' => []];
        // $res['data_count'] = SocialFav::count('type="' . $type . '" and item_id=' . $item_id . ' and enable=1');
        $params[] = '((share_original_type="discuss" and share_original_item_id=' . $item_id . ") or parent_item_id=" . $item_id . ') and status=' . DiscussManager::STATUS_NORMAL;
        $params['order'] = 'created desc';
        $params['columns'] = 'user_id as uid,created,content,id as discuss_id';

        if ($page > 0) {
            $params['offset'] = ($page - 1) * $limit;
            $params['limit'] = $limit;
        }
        $list = SocialDiscuss::findList($params);
        if ($list) {
            $uids = implode(',', array_column($list, 'uid'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade,is_auth,signature,true_name'], 'uid');
            $user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');

            foreach ($list as &$item) {
                $item = array_merge($item, $users[$item['uid']]);
                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : ($users[$item['uid']]['username']);
                unset($item['true_name']);
            }
        }
        $res['data_list'] = $list;
        return $res;
    }
    //转发
    /**转发
     * @param $uid
     * @param $type
     * @param $item_id
     * @param $content -文本内容
     * @param $tags -标签
     * @param $is_top -是否置顶
     * @param bool $open_location -是否公开位置
     * @param string $address -公开地址
     * @param string $lng -经度
     * @param string $lat -纬度
     * @param int $scan_type -查看类型
     * @param string $scan_user -允许查看/不给谁看的用户id
     * @return bool
     */
    public function forward($uid, $type, $item_id, $content, $tags, $is_top, $open_location = false, $address = '', $lng = '', $lat = '', $scan_type = 1, $scan_user = '', $package_id = '', $package_info = '')
    {
        if (!array_key_exists($type, self::$_forward_type)) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }

        if (client_type == 'ios' && version_compare(app_version, '1.0.0', '<=')) {
            if ($type == self::TYPE_NEWS) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "暂不支持转发资讯");
            }
        }
        $content = $content ? $content : "转发" . SocialManager::$_forward_type[$type];
        $data = $this->dataExist($item_id, $type);
        if (!$data) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }

        $discuss = new  SocialDiscuss();
        $data = ['content' => $content, 'user_id' => $uid, 'media_type' => DiscussManager::TYPE_TEXT, 'created' => time(), 'tags' => $tags];
        if ($package_id) {
            $data['package_id'] = $package_id;
        }
        if ($package_info) {
            $data['package_info'] = htmlspecialchars_decode($package_info);
        }
        //商品
        if ($type == self::TYPE_GOOD) {
            $data['media_type'] = DiscussManager::TYPE_GOODS;
        }
        //动态置顶
        if ($is_top) {
            $user = Users::findOne(['id=' . $uid, 'columns' => 'grade,coins']);
            $top_discuss = UserPointGrade::getColumn(['grade=' . $user['grade'], 'columns' => 'top_discuss'], 'top_discuss');
            $grade_top_count = SocialDiscussTopLog::dataCount("user_id=" . $uid . ' and type=' . DiscussManager::TOP_TYPE_GRADE);
            //等级特权置顶还没用完
            if ($top_discuss[0] > $grade_top_count) {
                $data['is_top'] = 1;
                $data['top_type'] = DiscussManager::TOP_TYPE_GRADE;
            } //判断龙豆是否够
            else {
                $coins = PointRule::init()->getRulePoints(\Components\Rules\Coin\PointRule::BEHAVIOR_TOP_DISCUSS); //置顶所需要的龙豆数
                //龙豆足够
                if ($user['coins'] >= $coins) {
                    $data['is_top'] = 1;
                    $data['top_type'] = DiscussManager::TOP_TYPE_COIN;
                } else {
                    Ajax::init()->outError(Ajax::ERROR_COIN_NOT_ENOUGH);
                }
            }
        }
        //公开位置
        if ($open_location) {
            $data['address'] = $address;
            $data['lng'] = $lng;
            $data['lat'] = $lat;
        }
        if ($tags) {
            $data['tags_name'] = TagManager::getInstance()->getTagNames($tags);
        }
        //查看类型
        if ($scan_type == DiscussManager::SCAN_TYPE_PRIVATE) {
            $data['scan_type'] = $scan_type;
        } elseif (($scan_type == DiscussManager::SCAN_TYPE_PART_FRIEND || $scan_type == DiscussManager::SCAN_TYPE_FORBIDDEN) && $scan_user != '') {
            $data['scan_type'] = $scan_type;
            $data['scan_user'] = $scan_user;
        } else {
            $data['scan_type'] = DiscussManager::SCAN_TYPE_ALL;
        }
        $im_data = [];//im消息内容

        //动态
        if ($type == self::TYPE_DISCUSS) {
            $parent = SocialDiscuss::findOne(['id=' . $item_id, 'columns' => 'share_original_type,media_type,share_original_item_id,parent_item_id,user_id,parent_item_id_str']);
            //转发的动态不是原始转发内容
            if ($parent['share_original_item_id'] > 0) {
                $data['share_original_type'] = $parent['share_original_type'];
                $data['share_original_item_id'] = $parent['share_original_item_id'];
                $data['parent_item_id'] = $item_id;
                $data['parent_item_id_str'] = $parent['parent_item_id_str'] ? $parent['parent_item_id_str'] . ',' . $item_id : $item_id;
                //原始转发的是动态
                if ($parent['share_original_type'] == self::TYPE_DISCUSS) {
                    $original_discuss = SocialDiscuss::findOne(['id=' . $parent['share_original_item_id'], 'columns' => 'user_id,status,media_type']);
                    $data['media_type'] = $original_discuss['media_type'];
                    if ($original_discuss['status'] == DiscussManager::STATUS_NORMAL) {
                        //发消息给原贴用户
                        $im_data[] = ['item_id' => $parent['share_original_item_id'], 'type' => self::TYPE_DISCUSS, 'type_name' => '动态', 'user_id' => $uid, 'to_user_id' => $original_discuss['user_id']];
                    }
                } else if ($parent['share_original_type'] == self::TYPE_GOOD) {
                    $data['media_type'] = DiscussManager::TYPE_GOODS;
                }
            } else {
                $data['share_original_type'] = $type;
                $data['share_original_item_id'] = $item_id;
                $data['parent_item_id'] = $item_id;
                $data['parent_item_id_str'] = $item_id;
                $data['media_type'] = $parent['media_type'];
            }
            //发消息给上级发帖用户
            $im_data[] = ['item_id' => $item_id, 'type' => self::TYPE_DISCUSS, 'type_name' => '动态', 'user_id' => $uid, 'to_user_id' => $parent['user_id']];
            //@的用户
            $at_uid = FilterUtil::packageContentTagApp($data['content'], $uid);
        } //转发资讯
        else if ($type == self::TYPE_NEWS) {
            $data['share_original_type'] = $type;
            $data['share_original_item_id'] = $item_id;
            $news_content = json_decode($data['content'], true);
            //@的用户
            $at_uid = FilterUtil::packageContentTagApp($news_content['content'], $uid);
        } //转发商品
        else if ($type == self::TYPE_GOOD) {
            $data['share_original_type'] = $type;
            $data['share_original_item_id'] = $item_id;
            $good = ShopGoods::findOne(['id=' . $item_id, 'columns' => 'id as good_id,name,brief,images as media,3 as media_type,price,unit,user_id as uid']);
            $good['content'] = $content;
            $news_content = $good;
            $content = json_encode($good, JSON_UNESCAPED_UNICODE);
            //  $news_content = json_decode($data['content'], true);
            //@的用户
            $at_uid = FilterUtil::packageContentTagApp($content, $uid);
            $data['content'] = $content;
        } //转发店铺
        else if ($type == self::TYPE_SHOP) {
            $data['share_original_type'] = $type;
            $data['share_original_item_id'] = $item_id;
            $shop = Shop::findOne(['id=' . $item_id, 'columns' => 'id as shop_id,name,brief,images as media,3 as media_type,user_id as uid']);
            $shop['content'] = $content;
            $news_content = $shop;
            $content = json_encode($shop, JSON_UNESCAPED_UNICODE);

            //  $news_content = json_decode($data['content'], true);
            //@的用户
            $at_uid = FilterUtil::packageContentTagApp($content, $uid);
            $data['content'] = $content;
        } //转发视频
        else if ($type == self::TYPE_VIDEO) {
            $data['share_original_type'] = $type;
            $data['share_original_item_id'] = $item_id;
            // $video = UserVideo::findOne(['id=' . $item_id, 'columns' => 'id as video_id,title,brief,url as media,2 as media_type,user_id as uid']);
            // $video['content'] = $content;
            //$news_content = $video;
            $at_uid = FilterUtil::packageContentTagApp($content, $uid);

            //$content = json_encode($video, JSON_UNESCAPED_UNICODE);
            //  $news_content = json_decode($data['content'], true);
            //@的用户

            $data['content'] = $content;
        }

        $data['created'] = time();
        if ($discuss_id = $discuss->insertOne($data)) {
            //送经验值
            PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_FORWARD);

            if (isset($data['top_type'])) {
                //龙豆置顶 扣除龙豆值
                if ($data['top_type'] == DiscussManager::TOP_TYPE_COIN) {
                    PointRule::init()->executeRule($uid, \Components\Rules\Coin\PointRule::BEHAVIOR_TOP_DISCUSS);
                }
                //记录置顶记录
                $log = new SocialDiscussTopLog();
                if (!$log->insertOne(['user_id' => $uid, 'discuss_id' => $discuss_id, 'type' => $data['top_type'], 'created' => $data['created']])) ;
                {
                    Debug::log(json_encode($log->getMessages(), JSON_UNESCAPED_UNICODE), 'error');
                }
            }
            //送经验值
            PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_FORWARD);
            //给好友及粉丝发新动态通知
            $contacts = UserContactMember::getColumn(['user_id=' . $uid, 'columns' => 'owner_id'], 'owner_id');

            if ($contacts) {
                SysMessage::init()->initMsg(SysMessage::TYPE_NEW_DISCUSS, ['to_user_id' => $contacts]);
            }
            //更新转发数
            if ($type == self::TYPE_DISCUSS) {
                //更新第一级转发数
                if ($data['share_original_type'] == self::TYPE_DISCUSS || $data['share_original_type'] == self::TYPE_NEWS) {
                    $this->db->begin();
                    $this->changeCnt($data['share_original_type'], $data['share_original_item_id'], 'forward_cnt');
                    $this->db->commit();
                }
                //更新当前要转发的动态转发数
                if (isset($data['parent_item_id']) && $data['share_original_item_id'] != $data['parent_item_id'] && substr_count($data['parent_item_id_str'], ',') > 0) {
                    $this->db->begin();
                    $this->changeCnt(self::TYPE_DISCUSS, $data['parent_item_id'], 'forward_cnt');
                    $this->db->commit();
                }

            } elseif ($type == self::TYPE_NEWS) {
                //  $this->changeCnt(self::TYPE_NEWS, $item_id, 'forward_cnt');

            } elseif ($type == self::TYPE_GOOD) {
                $this->changeCnt(self::TYPE_GOOD, $item_id, 'forward_cnt');

            } elseif ($type == self::TYPE_SHOP) {
                $this->changeCnt(self::TYPE_SHOP, $item_id, 'forward_cnt');
            } elseif ($type == self::TYPE_VIDEO) {
                $this->changeCnt(self::TYPE_VIDEO, $item_id, 'forward_cnt');
            }
            //发送动态通知消息
            if ($im_data) {
                foreach ($im_data as $im) {
                    if ($im['user_id'] != $im['to_user_id']) {
                        ImManager::init()->initMsg(ImManager::TYPE_FORWARD, $im);
                    }
                }
            }
            //发at消息
            if ($at_uid) {
                foreach ($at_uid as $item) {
                    ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $discuss_id, 'type' => SocialManager::TYPE_DISCUSS, 'content' => isset($news_content) ? $news_content['content'] : $content, 'user_id' => $uid, 'to_user_id' => $item]);
                }
            }


            return $discuss_id;
        }
        return false;
    }

    /**分享
     * @param $uid
     * @param $type
     * @param $item_id
     * @param $plate
     * @param $site
     * @param $title
     * @param $url
     * @param $spm
     * @return bool
     */
    public function share($uid, $type, $item_id, $plate, $site, $title, $url, $spm)
    {
        //邀请
        if (!in_array($type, [self::TYPE_INVITE, self::TYPE_PROMOTE])) {
            $data = $this->dataExist($item_id, $type);
            if (!$data) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
        }
        $data = array(
            'plate' => $plate,
            'site' => $site,
            'title' => $title,
            'url' => $url,
            'type' => $type,
            'user_id' => $uid,
            'item_id' => $item_id,
            'created' => time(),
            'ymd' => date('Ymd'),
            'spm' => $spm
        );
        if (isset(self::$_share_platform[$site])) {
            $data['platform'] = self::$_share_platform[$site];
        }
        $share = new SocialShare();
        if ($share_id = $share->insertOne($data)) {
            $log = SocialShareBackLog::findList("spm='" . $spm . "' and share_id=0");
            if ($log) {
                $count = 0;
                foreach ($log as $l) {
                    SocialShareBackLog::updateOne(['share_id' => $share_id], ['id' => $l['id']]);
                    $count += 1;
                }
                $count > 0 && SocialShare::updateOne(['back_count' => intval($count)], ['id' => $share_id]);
            }
            //分享的是用户 相当于邀请好友 送龙豆
            /* if ($type == self::TYPE_USER) {
                 \Components\Rules\Coin\PointRule::init()->executeRule($uid, \Components\Rules\Coin\PointRule::BEHAVIOR_INVITE);
             }*/
            //送经验值
            PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_ADD_SHARE);

            //推广恐龙谷
            if ($type == self::TYPE_PROMOTE) {
                SquareTask::init()->executeRule($uid, device_id, SquareTask::TASK_PROMOTE_DOWNLOAD);
            }

            /*  if ($type == self::TYPE_DISCUSS) {
                  //赠送系统奖励
                  $CashRewardManager = new CashRewardManager();
                  $res = $CashRewardManager->reward($uid, CashRewardManager::TYPE_SHARE, $item_id);

              }*/

            return true;
        }
        return false;

    }

    /**
     */
    /**分享 回链
     * @param $uid
     * @param $type
     * @param $item_id
     * @param $plate
     * @param $site
     * @param $title
     * @param $url
     * @param $spm
     * @return bool
     */
    public function shareBack($uid, $type, $item_id, $plate, $site, $title, $url, $spm)
    {
        //邀请
        if ($type != 'invite') {
            $data = $this->dataExist($item_id, $type);
            if (!$data) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
        }

        $data = array(
            'plate' => $plate,
            'site' => $site,
            'title' => $title,
            'url' => $url,
            'type' => $type,
            'user_id' => $uid,
            'item_id' => $item_id,
            'created' => time(),
            'spm' => $spm
        );
        $share = new SocialShare();
        if ($share->insertOne($data)) {
            //分享的是用户 相当于邀请好友 送龙豆
            /* if ($type == self::TYPE_USER) {
                 \Components\Rules\Coin\PointRule::init()->executeRule($uid, \Components\Rules\Coin\PointRule::BEHAVIOR_INVITE);
             }*/
            return true;
        }
        return false;

    }

    /**发表评论
     * @param $uid
     * @param $type
     * @param $item_id
     * @param $content
     * @param $images
     * @return bool
     */
    public function comment($uid, $type, $item_id, $content, $images)
    {
        $data = $this->dataExist($item_id, $type, 'user_id');
        if (!$data) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }

        $count = SocialComment::dataCount('user_id=' . $uid . ' and created>=' . strtotime('-1 hour') . ' and type="' . $type . '" and item_id=' . $item_id);
        //一个小时内 同一数据评论了10次 绝对是攻击
        if ($count >= 10) {
            Ajax::outError(Ajax::ERROR_REQUEST_FREQUENCY);
        }
        $comment = new SocialComment();
        if ($comment_id = $comment->insertOne(['user_id' => $uid, 'type' => $type, 'item_id' => $item_id, 'content' => $content, 'created' => time(), 'images' => $images])) {
            //发送im消息
            //自己给自己评论不发消息
            if ($uid != $data['user_id']) {
                ImManager::init()->initMsg(ImManager::TYPE_COMMENT, ['comment_content' => $content, 'item_id' => $item_id, 'type' => $type, 'user_id' => $uid, 'to_user_id' => $data['user_id'], 'type_name' => self::$_comment_type[$type]]);
            }
            //@的用户
            $at_uid = FilterUtil::packageContentTagApp($content, $data['user_id']);
            //发at消息
            if ($at_uid) {
                foreach ($at_uid as $item) {
                    ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $item_id, 'type' => SocialManager::TYPE_DISCUSS, 'content' => $content, 'user_id' => $uid, 'to_user_id' => $item]);
                }
            }
            $this->changeCnt($type, $item_id, 'comment_cnt');//更新评论数
            //送经验值
            PointRule::init()->executeRule($uid, PointRule::BEHAVIOR_ADD_COMMENT);

            //鉴黄入队
            if ($images && !strpos($images, "gif/")) {
                $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "img_check");
                $setting = json_decode($setting, true);
                if ($setting && $setting['enable'] == 1 && $setting['enable_discuss'] == 1) {
                    $redis = $this->di->get("redis_queue");
                    $media = array_filter(explode(',', $images));
                    foreach ($media as $k => $item) {
                        $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_COMMENT_LIST, $comment_id . "|" . ($k) . "|" . $item);
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**发表回复
     * @param $uid
     * @param $comment_id -评论id
     * @param $reply_id -回复id
     * @param $content
     * @param $images
     * @return bool
     */
    public function reply($uid, $comment_id, $reply_id, $content, $images)
    {
        //二级及以上回复
        if ($reply_id) {
            $reply_data = $this->dataExist($reply_id, self::TYPE_REPLY, 'user_id,type,item_id,parent_id');
            if (!$reply_data) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
        }
        $comment_data = $this->dataExist($comment_id, self::TYPE_COMMENT, 'user_id,type,item_id');
        if (!$comment_data) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //帖子已经被删除了
        if (!$discuss = $this->dataExist($comment_data['item_id'], $comment_data['type'], 'user_id')) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $count = SocialCommentReply::dataCount('user_id=' . $uid . ' and created>=' . strtotime('-1 hour') . ' and type="' . $comment_data['type'] . '" and item_id=' . $comment_data['item_id'] . ' and parent_id=' . $reply_id);
        //一个小时内 同一数据回复了10次 绝对是攻击
        if ($count >= 10) {
            Ajax::outError(Ajax::ERROR_REQUEST_FREQUENCY);
        }
        $to_user_id = isset($reply_data) ? $reply_data['user_id'] : $comment_data['user_id'];

        if ($reply_id) {
            $comment = new SocialCommentReply();
            if ($reply_id = $comment->insertOne(['user_id' => $uid, /*'parent' => $parent_id, */
                'to_user_id' => $to_user_id, 'type' => $comment_data['type'], 'item_id' => $comment_data['item_id'], 'comment_id' => $comment_id, 'content' => $content, 'parent_id' => $reply_id, 'created' => time(), 'images' => $images])
            ) {
                //发送im消息
                $data = ['reply_content' => $content, 'comment_id' => $comment_id, 'item_id' => $comment_data['item_id'], 'type' => $comment_data['type'], 'user_id' => $uid, 'to_user_id' => $to_user_id, 'type_name' => self::$_comment_type[$comment_data['type']]];
                //自己给自己回复不发消息
                if ($data['user_id'] != $data['to_user_id']) {
                    ImManager::init()->initMsg(ImManager::TYPE_REPLY, $data);
                }
                $this->changeCnt(self::TYPE_COMMENT, $comment_id, 'comment_cnt');//更新评论数
                //如果回复的是动态,更新评论数
                if ($comment_data['type'] == self::TYPE_DISCUSS) {
                    $this->changeCnt(self::TYPE_DISCUSS, $comment_data['item_id'], 'comment_cnt');//更新评论数
                }
                //@的用户
                $at_uid = FilterUtil::packageContentTagApp($content, $discuss['user_id']);
                //发at消息
                if ($at_uid) {
                    foreach ($at_uid as $item) {
                        ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $comment_id, 'type' => SocialManager::TYPE_COMMENT, 'content' => $content, 'user_id' => $uid, 'to_user_id' => $item]);
                    }
                }
                //鉴黄入队
                if ($images && !strpos($images, "gif/")) {
                    $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "img_check");
                    $setting = json_decode($setting, true);
                    if ($setting && $setting['enable'] == 1 && $setting['enable_discuss'] == 1) {
                        $redis = $this->di->get("redis_queue");
                        $media = array_filter(explode(',', $images));
                        foreach ($media as $k => $item) {
                            $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_REPLY_LIST, $reply_id . "|" . ($k) . "|" . $item);
                        }
                    }
                }
                return true;
            }
        } else {
            $comment = new SocialComment();
            if ($reply_id = $comment->insertOne(['user_id' => $uid,
                'type' => $comment_data['type'], 'item_id' => $comment_data['item_id'], 'parent_id' => $comment_id, 'content' => $content, 'created' => time(), 'images' => $images])
            ) {
                //发送im消息
                $data = ['reply_content' => $content, 'comment_id' => $comment_id, 'item_id' => $comment_data['item_id'], 'type' => $comment_data['type'], 'user_id' => $uid, 'to_user_id' => $to_user_id, 'type_name' => self::$_comment_type[$comment_data['type']]];
                //自己给自己回复不发消息
                if ($data['user_id'] != $data['to_user_id']) {
                    ImManager::init()->initMsg(ImManager::TYPE_REPLY, $data);
                }
                $this->changeCnt(self::TYPE_COMMENT, $comment_id, 'comment_cnt');//更新评论数
                //如果回复的是动态,更新评论数
                if ($comment_data['type'] == self::TYPE_DISCUSS) {
                    $this->changeCnt(self::TYPE_DISCUSS, $comment_data['item_id'], 'comment_cnt');//更新评论数
                }
                //@的用户
                $at_uid = FilterUtil::packageContentTagApp($content, $discuss['user_id']);
                //发at消息
                if ($at_uid) {
                    foreach ($at_uid as $item) {
                        ImManager::init()->initMsg(ImManager::TYPE_MENTION, ['item_id' => $comment_id, 'type' => SocialManager::TYPE_COMMENT, 'content' => $content, 'user_id' => $uid, 'to_user_id' => $item]);
                    }
                }
                //鉴黄入队
                if ($images && !strpos($images, "gif/")) {
                    $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "img_check");
                    $setting = json_decode($setting, true);
                    if ($setting && $setting['enable'] == 1 && $setting['enable_discuss'] == 1) {
                        $redis = $this->di->get("redis_queue");
                        $media = array_filter(explode(',', $images));
                        foreach ($media as $k => $item) {
                            $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_COMMENT_LIST, $reply_id . "|" . ($k) . "|" . $item);
                        }
                    }
                }
                return true;
            }
        }

        return false;
    }

    /**获取评论列表
     * @param $uid
     * @param $type
     * @param $item_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function commentList($uid, $type, $item_id, $page = 0, $limit = 20)
    {
        $res = ['data_list' => [], 'hot_list' => [], 'data_count' => 0];
        $params[] = 'status=' . self::COMMENT_STATUS_NORMAL . ' and type="' . $type . '" and item_id=' . $item_id;
        $params['columns'] = 'id as comment_id,user_id as uid,content,comment_cnt as reply_cnt,like_cnt,created,images,(comment_cnt+like_cnt) as order_column,parent_id';
        $params['order'] = 'created desc';
        if ($page > 0) {
            $params['limit'] = $limit;
            $params['offset'] = ($page - 1) * $limit;
        }

        //第一页前3条为热门评论
        if ($page <= 1) {
            $res['hot_list'] = [];
            /*  if (SocialComment::dataCount($params[0]) > 3) {
                  //热门评论
                  $res['hot_list'] = SocialComment::findList([$params[0] . ' and (comment_cnt>0 or like_cnt>0)', 'columns' => $params['columns'], 'order' => 'order_column desc,created desc', 'limit' => 3]);
              }*/
            /*   if ($hot_comment) {
                   $params[0] .= ' and id not in(' . implode(',', array_column($hot_comment, 'comment_id')) . ')';

               } else {
                   return $res;
               }*/
        } else {
            //第一页之后就不需要返回热门评论了
            /* $hot_comment = SocialComment::getColumn([$params[0], 'columns' => 'id', 'order' => 'comment_cnt desc,created desc', 'limit' => 3], 'id');
             if ($hot_comment) {
                 $params[0] .= ' and id not in(' . implode(',', $hot_comment) . ')';
             } else {
                 return $res;
             }*/
        }
        //热门评论数等于3
        /*  if (count($res['hot_list']) == 3) {
              $res['data_count'] = SocialComment::count($params[0]);
              //数据条数大于0才去查询
              if ($res['data_count'] > 0) {
                  $list = SocialComment::find($params);
                  $list = $list->toArray();
              }
          }*/

        //第一页  前三条为热门评论
        /*  if ($page <= 1) {
              $list = array_merge($hot_comment, $list);
          }*/
        $list = SocialComment::findList($params);
//        if ($res['hot_list']) {
//            $comment_ids = implode(',', array_column($res['hot_list'], 'comment_id'));
//
//            $uids = implode(',', array_unique(array_column($res['hot_list'], 'uid')));
//            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade'], 'uid');
//
//            if ($uid) {
//                $user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
//            } else {
//                $user_contact = [];
//            }
//            $likes = SocialLike::getByColumnKeyList(['type="' . self::TYPE_COMMENT . '" and user_id=' . $uid . ' and item_id in (' . $comment_ids . ')' . ' and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
//
//            $parent_ids = [];//父级评论id
//            foreach ($res['hot_list'] as &$item) {
//                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : $users[$item['uid']]['username'];
//                $item['sex'] = $users[$item['uid']]['sex'];
//                $item['avatar'] = $users[$item['uid']]['avatar'];
//                $item['grade'] = $users[$item['uid']]['grade'];
//                $item['parent'] = (object)[];
//                //显示时间
//                $item['show_time'] = Time::formatHumaneTime($item['created']);
//                $item['is_like'] = isset($likes[$item['comment_id']]) ? 1 : 0;
//                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
//                if ($item['parent_id'] && !in_array($item['parent_id'], $parent_ids)) {
//                    $parent_ids[] = $item['parent_id'];
//                }
//                unset($item['order_column']);
//            }
//            if ($parent_ids) {
//                $parent_comments = $this->getParentComment($uid, $parent_ids);
//                foreach ($res['hot_list'] as &$it) {
//                    $it['parent_id'] && $it['parent'] = $parent_comments[$it['parent_id']];
//                }
//            }
////            $sql = "SELECT comment_id,id,user_id as uid,to_user_id as to_uid,content,images,created
////                    FROM social_comment_reply a
////                    WHERE 3 > (
////                    SELECT COUNT( 1 )
////                    FROM social_comment_reply
////                    WHERE comment_id = a.comment_id
////                    AND id > a.id ) and status=1 and comment_id in(" . $comment_ids . ")
////                    ORDER BY a.created DESC ";
////            $reply = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
////            if ($reply) {
////                // $reply = array_combine(array_column($reply, 'comment_id'), $reply);
////                $reply_data = [];
////                $uids = implode(',', array_unique(array_merge(array_column($reply, 'uid'), array_column($reply, 'to_uid'))));
////                $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade'], 'uid');
////                if ($uid) {
////                    $user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
////                } else {
////                    $user_contact = [];
////                }
////                foreach ($reply as &$r) {
////                    $r['reply_id'] = $r['id'];
////                    $comment_id = $r['comment_id'];
////                    $r['content'] = FilterUtil::unPackageContentTagApp($r['content'], $uid);
////                    unset($r['id']);
////                    unset($r['parent_id']);
////                    unset($r['comment_id']);
////                    $r['username'] = (isset($user_contact[$r['uid']]) && $user_contact[$r['uid']]['mark']) ? $user_contact[$r['uid']]['mark'] : $users[$r['uid']]['username'];
////                    $r['to_username'] = (isset($user_contact[$r['to_uid']]) && $user_contact[$r['to_uid']]['mark']) ? $user_contact[$r['to_uid']]['mark'] : $users[$r['to_uid']]['username'];
////                    /*              $r['sex'] = $users[$r['user_id']]['sex'];
////                                  $r['avatar'] = $users[$r['user_id']]['avatar'];
////                                  $r['grade'] = $users[$r['user_id']]['grade'];*/
////                    if (isset($reply_data[$comment_id])) {
////                        $reply_data[$comment_id][] = $r;
////                    } else {
////                        $reply_data[$comment_id] = [$r];
////                    }
////                }
////
////                foreach ($res['hot_list'] as &$item) {
////                    $item['reply_list'] = isset($reply_data[$item['comment_id']]) ? $reply_data[$item['comment_id']] : [];
////                }
////            }
//        }
        if ($list) {
            $comment_ids = implode(',', array_column($list, 'comment_id'));
            $uids = implode(',', array_unique(array_column($list, 'uid')));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade'], 'uid');
            $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
            $likes = SocialLike::getByColumnKeyList(['type="' . self::TYPE_COMMENT . '" and user_id=' . $uid . ' and item_id in (' . $comment_ids . ')' . ' and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            $parent_ids = [];//父级评论id
            foreach ($list as &$item) {
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : $users[$item['uid']]['username'];
                $item['sex'] = $users[$item['uid']]['sex'];
                $item['avatar'] = $users[$item['uid']]['avatar'];
                $item['grade'] = $users[$item['uid']]['grade'];
                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
                $item['parent'] = (object)[];
                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['is_like'] = isset($likes[$item['comment_id']]) ? 1 : 0;
                if ($item['parent_id'] && !in_array($item['parent_id'], $parent_ids)) {
                    $parent_ids[] = $item['parent_id'];
                }
                unset($item['order_column']);
            }
            if ($parent_ids) {
                $parent_comments = $this->getParentComment($uid, $parent_ids);
                foreach ($list as &$i) {
                    $i['parent_id'] && $i['parent'] = $parent_comments[$i['parent_id']];
                }
            }
//            $sql = "SELECT comment_id,id,user_id as uid,to_user_id as to_uid,content,images,created
//                    FROM social_comment_reply a
//                    WHERE 3 > (
//                    SELECT COUNT( 1 )
//                    FROM social_comment_reply
//                    WHERE comment_id = a.comment_id
//                    AND id > a.id ) and status=1  and comment_id in(" . $comment_ids . ")
//                    ORDER BY a.created DESC ";
//            $reply = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
//            if ($reply) {
//                // $reply = array_combine(array_column($reply, 'comment_id'), $reply);
//                $reply_data = [];
//                $uids = implode(',', array_unique(array_merge(array_column($reply, 'uid'), array_column($reply, 'to_uid'))));
//                $users = UserInfo::getByColumnKeyList(['user_id in (' . $uids . ')', 'columns' => 'avatar,username,sex,user_id as uid,grade'], 'uid');
//                $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
//                foreach ($reply as &$r) {
//                    $r['content'] = FilterUtil::unPackageContentTagApp($r['content'], $uid);
//                    $r['reply_id'] = $r['id'];
//                    $comment_id = $r['comment_id'];
//                    unset($r['id']);
//                    unset($r['parent_id']);
//                    unset($r['comment_id']);
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
//
//
//                foreach ($list as &$item) {
//                    $item['reply_list'] = isset($reply_data[$item['comment_id']]) ? $reply_data[$item['comment_id']] : [];
//                }
//            }
        }
        $res['data_list'] = $list;
        $res['data_count'] = SocialComment::dataCount($params[0]);
        return $res;

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
                if ($uid) {
                    $parent_user_contact = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $parent_uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');
                } else {
                    $parent_user_contact = [];
                }
                foreach ($res as &$p) {
                    $p['username'] = (isset($parent_user_contact[$p['uid']]) && $parent_user_contact[$p['uid']]['mark']) ? $parent_user_contact[$p['uid']]['mark'] : $parent_users[$p['uid']]['username'];
                    $p['content'] = FilterUtil::unPackageContentTagApp($p['content'], $uid);
                }
            }
        }
        return $res;
    }

    /**获取回复列表
     * @param $uid
     * @param $comment_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function replyList($uid, $comment_id, $page = 0, $limit = 20)
    {
        $res = ['data_count' => 0, 'data_list' => [], 'like_users' => []];
        $params[] = 'status=' . self::COMMENT_STATUS_NORMAL . ' and comment_id=' . $comment_id;
        $params['columns'] = 'id as reply_id,comment_id,user_id as uid,to_user_id ,content,like_cnt,created,if(parent_id>0,0,1) as is_first_level,images';
        $params['order'] = 'created desc';
        if ($page > 0) {
            $params['limit'] = $limit;
            $params['offset'] = ($page - 1) * $limit;
        }
        $comment = SocialComment::findOne(['id=' . $comment_id, 'columns' => 'like_cnt']);
        if (!$comment) {
            return $res;
        }
        if ($comment['like_cnt'] > 0) {
            $like_users = SocialLike::getByColumnKeyList(['type="' . self::TYPE_COMMENT . '" and item_id=' . $comment_id . ' and enable=1', 'columns' => 'user_id as uid,created', 'order' => 'created', 'limit' => 5], 'uid');
            $user_infos = Users::findList(['id in (' . implode(',', array_column($like_users, 'uid')) . ')', 'columns' => 'id as uid,avatar']);
            $order_data = [];//排序

            foreach ($user_infos as $u) {
                $order_data[] = $like_users[$u['uid']]['created'];
            }
            array_multisort($order_data, SORT_DESC, $user_infos);
            $res['like_users'] = $user_infos;
        }
        $res['data_count'] = SocialCommentReply::dataCount('status=' . self::COMMENT_STATUS_NORMAL . ' and comment_id=' . $comment_id);
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
                $likes = SocialLike::getByColumnKeyList(['type="' . self::TYPE_REPLY . '" and user_id=' . $uid . ' and item_id in (' . $reply_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            } else {
                $user_contact = [];
                $likes = [];
            }
            foreach ($list as &$item) {
                $item['username'] = (isset($user_contact[$item['uid']]) && $user_contact[$item['uid']]['mark']) ? $user_contact[$item['uid']]['mark'] : $users[$item['uid']]['username'];
                $item['sex'] = $users[$item['uid']]['sex'];
                $item['avatar'] = $users[$item['uid']]['avatar'];
                $item['grade'] = $users[$item['uid']]['grade'];
                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);

                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['is_like'] = isset($likes[$item['reply_id']]) ? 1 : 0;
                $item['to_user_username'] = (isset($user_contact[$item['to_user_id']]) && $user_contact[$item['to_user_id']]['mark']) ? $user_contact[$item['to_user_id']]['mark'] : $users[$item['to_user_id']]['username'];
            }
        }
        $res['data_list'] = $list;
        return $res;
    }

    /**获取举报原因列表
     * @param $type
     * @return array
     */

    public function reportReasonList($type)
    {
        $type = $type == self::TYPE_GROUP ? 2 : 1;
        return array_values(self::getReportReason($type));
    }

    /**举报
     * @param $uid
     * @param $reason_id
     * @param $type
     * @param $item_id
     * @param $images //证据截图
     * @return bool
     */
    public function report($uid, $reason_id, $type, $item_id, $images)
    {
        $column = $type == 'user' ? 'id' : 'user_id';
        $data = $this->dataExist($item_id, $type, $column);
        if (!$data) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }

        //已经提交过了,且正在审核中
        if (SocialReport::exist('type="' . $type . '" and item_id=' . $item_id . ' and reporter=' . $uid . ' and status=' . self::REPORT_STATUS_SENDING)) {
            Ajax::outError(Ajax::ERROR_REPORT_HAS_SENT);
        }
        $reason = $this->getReportReason($type == 'user' ? self::REPORT_REASON_TYPE_GROUP : self::REPORT_REASON_TYPE_DISCUSS, $reason_id);
        if (!$reason) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "原因不存在");
        }
        $report = new SocialReport();
        $report_data = [
            "user_id" => $data[$column],
            "reporter" => $uid,
            "type" => $type,
            "item_id" => $item_id,
            "created" => time(),
            "reason_id" => $reason ? $reason['id'] : 0,
            "reason_content" => $reason ? $reason['content'] : "",
            "images" => $images
        ];
        if ($report->insertOne($report_data)) {
            if ($type != self::TYPE_USER) {
                $this->changeCnt($type, $item_id, 'report_cnt');
            }
            return true;
        }
        return false;

    }

    /**获取举报理由
     * @param int $type 1-动态 2-群
     * @param null $reason_id
     * @param bool $refresh
     * @return array
     */
    public function getReportReason($type = 1, $reason_id = null, $refresh = true)
    {
        $cacheSetting = new CacheSetting();
        $data = $cacheSetting->get(CacheSetting::PREFIX_REPORT_REASON, $type); /*缓存数据读取*/
        if (!$data || $refresh) {
            $reason = SiteReportReason::getByColumnKeyList(['type=' . $type . " and enable=1", 'order' => 'sort asc', 'columns' => 'id,content'], 'id');
            $cacheSetting->set(CacheSetting::PREFIX_REPORT_REASON, $type, $reason);
        }
        if ($reason_id) {
            return isset($data[$reason_id]) ? $data[$reason_id] : [];
        } else {
            return $data;
        }
    }


    /**删除评论/回复
     * @param $uid
     * @param $type
     * @param $item_id
     * @return bool
     */

    public function removeComment($uid, $type, $item_id)
    {
        if ($type != self::TYPE_COMMENT && $type != self::TYPE_REPLY) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        //评论
        if ($type == self::TYPE_COMMENT) {
            $data = SocialComment::findOne(['id=' . $item_id . ' and status=' . self::COMMENT_STATUS_NORMAL, 'columns' => 'id,type,item_id,user_id']);
            if (!$data) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            } else {
                //红包评论 发红包人可以删除评论
                if ($data['user_id'] != $uid && $data['item_id'] == self::TYPE_PACKAGE) {
                    $red_package = RedPackage::findOne(['package_id="' . $data['item_id'] . '"', 'columns' => 'user_id']);
                    if ($red_package['user_id'] != $data['uid']) {
                        Ajax::outError(Ajax::ERROR_DATA_HAS_EXISTS, "没有权限");
                    }
                }
            }
            $res = SocialComment::updateOne(['status' => self::COMMENT_STATUS_DELETED], ['id' => $data['id']]);
        } //回复
        else {
            $data = SocialCommentReply::findOne(['id=' . $item_id . ' and status=' . self::COMMENT_STATUS_NORMAL, 'columns' => 'user_id,id,type,item_id']);
            if (!$data) {
                Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
            } else {
                //红包评论 发红包人可以删除评论
                if ($data['user_id'] != $uid && $data['item_id'] == self::TYPE_PACKAGE) {
                    $red_package = RedPackage::findOne(['package_id="' . $data['item_id'] . '"', 'columns' => 'user_id']);
                    if ($red_package['user_id'] != $data['uid']) {
                        Ajax::outError(Ajax::ERROR_DATA_HAS_EXISTS);
                    }
                }
            }
            $res = SocialCommentReply::updateOne(['status' => self::COMMENT_STATUS_DELETED], ['id' => $data['id']]);
        }
        if ($res) {
            //更新评论数
            if ($type == self::TYPE_COMMENT) {
                $this->changeCnt($data['type'], $data['item_id'], 'comment_cnt', false);
                //更新评动态论数
                if ($data['comment_cnt'] > 0 && $data['type'] == self::TYPE_DISCUSS) {
                    //   $this->db->execute("update social_discuss set comment_cnt= comment_cnt-" . $data['comment_cnt'] . " where id=" . $data['item_id'] . " and comment_cnt>0");
                    $this->db->execute("update social_discuss set comment_cnt= comment_cnt-1  where id=" . $data['item_id'] . " and comment_cnt>0");
                }
            } else {
                $this->changeCnt(self::TYPE_COMMENT, $data['comment_id'], 'comment_cnt', false);
                //如果回复的是动态,更新评论数
                if ($data['type'] == self::TYPE_DISCUSS) {
                    $this->changeCnt(self::TYPE_DISCUSS, $data['item_id'], 'comment_cnt', false);//更新评论数
                }
            }
            return true;
        }
        return false;
    }

    /**我的收藏列表
     * @param $uid
     * @param $type
     * @param $page
     * @param $limit
     * @return array
     */

    public function myCollect($uid, $type, $page, $limit)
    {
        //没传类型-获取收藏数量
        if (!$type) {
            $res = [['type' => self::TYPE_DISCUSS, 'data_count' => "0"], ['type' => self::TYPE_NEWS, 'data_count' => "0"], ['type' => self::TYPE_SHOP, 'data_count' => "0"]];
            $data = $this->db->query("select type,count(1) as count from social_fav where " . 'user_id=' . $uid . ' and enable=1' . " group by type")->fetchAll(\PDO::FETCH_ASSOC);

            $data = array_column($data, 'count', 'type');
            //$data = SocialFav::getByColumnKeyList(['user_id=' . $uid . ' and enable=1', 'columns' => 'type,count(1) as count', 'group' => 'type'], 'type');
            if ($data) {
                isset($data[self::TYPE_DISCUSS]) && $res[0]['data_count'] = $data[self::TYPE_DISCUSS];
                isset($data[self::TYPE_NEWS]) && $res[1]['data_count'] = $data[self::TYPE_NEWS];
                isset($data[self::TYPE_SHOP]) && $res[2]['data_count'] = $data[self::TYPE_SHOP];
            }
            return $res;
        } //获取收藏列表
        else {
            $res = ['data_count' => 0, 'data_list' => []];
            $params[] = 'user_id=' . $uid . ' and type="' . $type . '" and enable=1';
            $params['columns'] = 'item_id,user_id as uid,created';
            $params['order'] = 'created desc';
            if ($page > 0) {
                $params['limit'] = $limit;
                $params['offset'] = ($page - 1) * $limit;
            }
            $res['data_count'] = SocialFav::dataCount('user_id=' . $uid . ' and type="' . $type . '" and enable=1');
            $list = SocialFav::findList($params);
            if ($list) {
                $item_ids = implode(',', array_unique(array_column($list, 'item_id')));//动态、资讯id集合
                if ($type == self::TYPE_DISCUSS) {
                    $data = SocialDiscuss::getByColumnKeyList(['id in (' . $item_ids . ')', 'columns' => 'id as discuss_id,status,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,package_info,is_recommend,reward_cnt'], 'discuss_id');
                    $likes = SocialLike::getByColumnKeyList(['type="' . self::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $item_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合

                    foreach ($data as &$item) {
                        $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
                        $item['is_collection'] = 1;
                        //转发的原始内容
                        $item['original_info'] = (object)[];

                        //新闻资讯
                        if ($item['share_original_type'] == self::TYPE_NEWS) {
                            $content = json_decode($item['content'], true);

                            $item['content'] = $content['content'];
                            $item['original_info'] = [
                                'title' => isset($content['title']) ? $content['title'] : '',
                                'news_id' => isset($content['news_id']) ? $content['news_id'] : 0,
                                'media' => isset($content['media']) ? $content['media'] : '',
                                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                                'logo' => isset($content['logo']) ? $content['logo'] : '',
                                'author' => isset($content['author']) ? $content['author'] : '',
                            ];
                        } //第三方分享
                        else if ($item['share_original_type'] == self::TYPE_SHARE) {

                            /*   $content = json_decode($item['content'], true);

                               $item['content'] = $content['content'];*/
                            if ($item['parent_item_id_str']) {
                                $top_discuss_id = explode(',', $item['parent_item_id_str'])[0];
                                $content = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content']);
                                $content = json_decode($content['content'], true);
                            } else {
                                $content = json_decode($item['content'], true);
                            }
                            $item['content'] = $content['content'];
                            $item['original_info'] = [
                                'content' => isset($content['title']) ? $content['title'] : '',
                                'link' => isset($content['link']) ? $content['link'] : '',
                                'title' => isset($content['title']) ? $content['title'] : '',
                                'media' => isset($content['media']) ? $content['media'] : '',
                                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                                'from' => isset($content['from']) ? $content['from'] : '',
                            ];
                        } //商铺
                        else if ($item['share_original_type'] == SocialManager::TYPE_SHOP) {

                            /*   $content = json_decode($item['content'], true);

                               $item['content'] = $content['content'];*/
                            if ($item['parent_item_id_str']) {
                                $top_discuss_id = explode(',', $item['parent_item_id_str'])[0];
                                $content = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content']);
                                $content = json_decode($content['content'], true);
                            } else {
                                $content = json_decode($item['content'], true);
                                $item['content'] = $content['content'];
                            }
                            // $item['content'] = $content['content'];
                            $item['original_info'] = [
                                'shop_id' => isset($content['shop_id']) ? $content['shop_id'] : 0,
                                'media' => isset($content['media']) ? $content['media'] : '',
                                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                                'name' => isset($content['name']) ? $content['name'] : '',
                                'brief' => isset($content['brief']) ? $content['brief'] : '',
                                'uid' => $content['uid'],
                                'username' => UserStatus::getUserName($uid, $content['uid'])

                            ];
                        } //商品
                        else if ($item['share_original_type'] == SocialManager::TYPE_GOOD) {
                            /*   $content = json_decode($item['content'], true);

                               $item['content'] = $content['content'];*/
                            if ($item['parent_item_id_str']) {
                                $top_discuss_id = explode(',', $item['parent_item_id_str'])[0];
                                $contents = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content,package_id,package_info']);
                                $content = json_decode($contents['content'], true);
                                $item['package_id'] = $contents['package_id'];
                                $item['package_info'] = $contents['package_info'];
                            } else {
                                $content = json_decode($item['content'], true);
                                $item['content'] = $content['content'];
                            }
                            //  $item['content'] = $content['content'];
                            $item['original_info'] = [
                                'good_id' => isset($content['good_id']) ? $content['good_id'] : 0,
                                'media' => isset($content['media']) ? $content['media'] : '',
                                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                                'name' => isset($content['name']) ? $content['name'] : '',
                                'brief' => isset($content['brief']) ? $content['brief'] : '',
                                'price' => isset($content['price']) ? $content['price'] : '0',
                                'unit' => isset($content['unit']) ? $content['unit'] : '件',
                                'uid' => $content['uid'],
                                'username' => UserStatus::getUserName($uid, $content['uid'])
                            ];
                        } //附近视频
                        else if ($item['share_original_type'] == SocialManager::TYPE_VIDEO) {
                            $original_info = SocialManager::init()->getShortDate($item['share_original_type'], $item['share_original_item_id'], $uid);
                            if ($original_info) {
                                $item['original_info'] = $original_info;
                            }
                        } else {
                            if ($item['share_original_item_id']) {
                                $original_info = SocialManager::init()->getShortDate($item['share_original_type'], $item['share_original_item_id'], $uid);
                                if ($original_info) {
                                    $item['original_info'] = $original_info;
                                }
                            }
                        }
                        //显示时间
                        $item['show_time'] = Time::formatHumaneTime($item['created']);
                    }
                    $user_ids = implode(',', array_unique(array_column($data, 'uid'))); //用户集合
                    $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,username,sex,avatar,grade'], 'uid');//用户信息集合
                    $user_blacklist = UserBlacklist::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id'], 'user_id');//黑名单集合
                    $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
                    $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id'], 'user_id');//关注人集合
                    foreach ($list as &$item) {
                        $item = array_merge($item, $data[$item['item_id']]);
                        unset($item['item_id']);
                        // unset($item['uid']);
                        $item['user_info'] = $user_info[$item['uid']];
                        $item['user_info']['is_contact'] = 0;
                        $item['user_info']['contact_mark'] = '';
                        $item['user_info']['is_attention'] = 0;
                        $item['user_info']['is_blacklist'] = 0;
                        //黑名单列表
                        if (isset($user_blacklist[$item['uid']])) {
                            $item['user_info']['is_blacklist'] = 1;
                        } //联系人
                        else if (isset($user_contact[$item['uid']])) {
                            $item['user_info']['is_contact'] = 1;
                            $item['user_info']['contact_mark'] = $user_contact[$item['uid']]['mark'];
                            $item['user_info']['is_attention'] = 1;
                        } //已关注
                        elseif (isset($user_attention[$item['uid']])) {
                            $item['user_info']['is_attention'] = 1;
                        } else {
                        }
                        $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
                    }

                } elseif ($type == self::TYPE_SHOP) {
                    foreach ($list as $item)//获取收藏店铺信息
                    {
                        $tmp[] = Shop::findOne(['id = ' . $item['item_id'], 'columns' => "id,user_id as uid,name,brief,images,address"]);
                    }
                    $shop_ids = array_column($tmp, 'id');
                    if (count($shop_ids) > 0) {
                        $goods_num = $this->original_mysql->query("select shop_id,count(1) as count from  shop_goods where shop_id in(" . implode(',', $shop_ids) . ") and status = 1 group by shop_id")->fetchAll(\PDO::FETCH_ASSOC);
                        foreach ($goods_num as $v) {
                            $nums[$v['shop_id']] = $v;
                        }
                        foreach ($tmp as $kk => $vv) {
                            $tmp[$kk]['goods_num'] = $nums[$vv['id']]['count'];
                        }

                    }
                    $list = $tmp;
                } else {

                }
            }
            $res['data_list'] = $list;
            return $res;
        }
    }

    /**更新阅读数
     * @param $uid
     * @param $type
     * @param $item_id
     * @return bool
     */

    public function updateReadCnt($uid, $type, $item_id)
    {
        if (!$this->dataExist($item_id, $type)) {
            Ajax::init()->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //阅读数 缓存下来
        $this->redis->hIncrBy(CacheSetting::$setting[CacheSetting::PREFIX_READ_COUNT]['prefix'], $type . '#' . $item_id, 1);

        //  $redis = new CacheSetting();
        // $redis->incr(CacheSetting::PREFIX_READ_COUNT, $type . "_" . $item_id);
        //  $this->changeCnt($type, $item_id, 'view_cnt');
        $val = $this->redis->hGet(CacheSetting::$setting[CacheSetting::PREFIX_READ_LIST]['prefix'], $type . '#' . $item_id);
        if ($val) {
            $val = json_decode($val, true);
            $val[$uid] = time();
        } else {
            $val = [];
            $val[$uid] = time();
        }
        $this->redis->hSet(CacheSetting::$setting[CacheSetting::PREFIX_READ_LIST]['prefix'], $type . '#' . $item_id, json_encode($val));

        //记录日志
        /* if (self::TYPE_DISCUSS == $type) {
             $log = SocialDiscussViewLog::findOne(['discuss_id=' . $item_id, 'columns' => 'id,detail']);
             if (!$log) {
                 $data = [$uid => time()];
                 $log = new SocialDiscussViewLog();
                 $log_data = ['discuss_id' => $item_id, 'detail' => json_encode($data, JSON_UNESCAPED_UNICODE)];
                 $log->insertOne($log_data);
             } else {
                 $detail = json_decode($log['detail'], true);
                 $detail[$uid] = time();
                 $detail = json_encode($detail, JSON_UNESCAPED_UNICODE);
                 SocialDiscussViewLog::updateOne(['detail' => $detail], ['id' => $log['id']]);
             }
         }*/
        return true;
    }

    /*获取简短数据(图片,标题,简介)*/
    /**
     * @param $type -类型
     * @param $item_id
     * @param $uid
     * @return bool
     */
    public function getShortDate($type, $item_id, $uid = 0)
    {
        $data = [];
        switch ($type) {
            case self::TYPE_DISCUSS: //动态
                $data = SocialDiscuss::findOne(["id=" . $item_id, 'columns' => 'status,id as discuss_id,user_id as uid,media,media_type,content,package_id']);
                $users = UserStatus::getInstance()->getCacheUserInfo($data['uid']);
                // $data->content = FilterUtil::parseContentUrl($data->content);
                $data['content'] = FilterUtil::unPackageContentTagApp($data['content'], $uid);
                $data['username'] = $users['username'];
                $data['avatar'] = $users['avatar'];
                break;
            case self::TYPE_GROUP:
                //公开群
                $data = Group::findOne(["id=" . $item_id, 'columns' => 'status,id as gid,if(avatar<>"",avatar,defaultavatar) as avatar,if(name<>"",name,defaultname) as name']);
                break;
            case self::TYPE_USER:
                //用户
                $data = Users::findOne(["id=" . $item_id, 'columns' => 'status,avatar,username,id as uid']);
                break;
            case self::TYPE_COMMENT:
                //评论
                $data = SocialComment::findOne(["id=" . $item_id, 'columns' => 'user_id as uid,id as comment_id']);
                break;
            case self::TYPE_REPLY:
                //回复
                $data = SocialCommentReply::findOne(["id=" . $item_id, 'columns' => 'user_id as uid,comment_id,to_user_id,id as reply_id']);
                break;
            case self::TYPE_VIDEO:
                //附近视频
                $data = UserVideo::findOne(["id=" . $item_id, 'columns' => 'status,title as content,user_id as uid,url as media,2 as media_type,id as video_id']);
                $users = UserStatus::getInstance()->getCacheUserInfo($data['uid']);
                // $data->content = FilterUtil::parseContentUrl($data->content);
                $data['content'] = FilterUtil::unPackageContentTagApp($data['content'], $uid);
                $data['username'] = $users['username'];
                $data['avatar'] = $users['avatar'];
                break;
            default:

        }
        return $data;
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
            case self::TYPE_DISCUSS: //动态
                $data = SocialDiscuss::findOne(["id=" . $item_id, 'columns' => 'media,media_type,content,share_original_type,share_original_item_id']);
                break;
            case self::TYPE_VIDEO: //视频
                $data = UserVideo::findOne(["id=" . $item_id, 'columns' => 'url']);
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
            case self::TYPE_DISCUSS: //动态
                $data = SocialDiscuss::findOne(["id=" . $item_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'columns' => $columns], false);
                break;
            case self::TYPE_COMMUNITY_DISCUSS: //社区动态
                $data = CommunityDiscuss::findOne(["id=" . $item_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'columns' => $columns], false);
                break;
            case self::TYPE_GROUP:
                //公开群
                $data = Group::findOne(["id=" . $item_id . ' and status=' . GroupManager::GROUP_STATUS_NORMAL, 'columns' => $columns]);
                break;
            case self::TYPE_USER:
                //用户
                $data = Users::findOne(["id=" . $item_id . ' and status=' . UserStatus::STATUS_NORMAL, 'columns' => $columns]);
                break;
            case self::TYPE_COMMENT:
                //评论
                $data = SocialComment::findOne(["id=" . $item_id . ' and status=' . self::COMMENT_STATUS_NORMAL, 'columns' => $columns], false);
                break;
            case self::TYPE_REPLY:
                //回复
                $data = SocialCommentReply::findOne(["id=" . $item_id . ' and status=' . self::COMMENT_STATUS_NORMAL, 'columns' => $columns], false);
                break;
            case self::TYPE_NEWS:
                return true;
            case self::TYPE_VIDEO:
                //视频
                $data = UserVideo::findOne(["id=" . $item_id . ' and status=' . self::VIDEO_STATUS_NORMAL, 'columns' => $columns], false);
                break;
            case self::TYPE_SHOP:
                //店铺
                $data = Shop::findOne(["id=" . $item_id . ' and status=' . ShopManager::status_normal, 'columns' => $columns], false);
                break;
            case self::TYPE_GOOD:
                //商品
                $data = ShopGoods::findOne(["id=" . $item_id . ' and status=' . GoodManager::status_normal, 'columns' => $columns], false);
                break;
            case self::TYPE_PACKAGE:
                //红包
                $data = RedPackage::findOne(["package_id='" . $item_id . "'", 'columns' => $columns], false);
                break;
            case self::TYPE_ACTIVITY:
                //活动
                return true;
                break;
            default:
        }
        if ($columns == '1') {
            return $data ? true : false;
        } else {
            return $data;
        }
    }

    //记录查看历史
    public function recordViewer($uid, $type, $item_id)
    {
        //店铺
        if ($type == self::TYPE_SHOP) {
            if (!Shop::exist("id=" . $item_id)) {
                return false;
            }
        } //商品
        elseif ($type == self::TYPE_GOOD) {
            if (!ShopGoods::exist("id=" . $item_id)) {
                return false;
            }
        }
        $redis = $this->di->get("redis_queue");
        $table_name = CacheSetting::KEY_VIEWER . $type . "_" . date('Ymd');
        //今天还没有创建这张表
        if ($redis->hLen($table_name) == 0) {
            $redis->hSet($table_name, $item_id, json_encode([$uid => [time()]]));
            $redis->expire($table_name, 86400 * 2);//2天过期
        } else {
            $value = $redis->hGet($table_name, $item_id);
            //还没有记录
            if (!$value) {
                $redis->hSet($table_name, $item_id, json_encode([$uid => [time()]]));
            } else {
                $value = json_decode($value, true);
                //今天之前有过记录
                if (key_exists($uid, $value)) {
                    //上次请求在2分钟内 过滤
                    if (time() - current($value[$uid]) <= 120) {
                        return false;
                    }
                    array_unshift($value[$uid], time());
                } else {
                    $value[$uid] = [time()];
                }
            }
        }
        return true;
    }
}
