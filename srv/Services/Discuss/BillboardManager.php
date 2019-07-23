<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/13
 * Time: 17:51
 */

namespace Services\Discuss;


use Components\Time;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussBillboard;
use Models\Social\SocialDiscussBillboardDetail;
use Models\Social\SocialDiscussHot;
use Models\Social\SocialDiscussHotWeek;
use Models\Social\SocialFav;
use Models\Social\SocialLike;
use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Services\Social\SocialManager;
use Util\FilterUtil;

class BillboardManager extends DiscussBase
{
    private static $instance = null;

    /**
     * @return  BillboardManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //获取日榜单
    /**
     * @param $uid
     * @param int $last_id
     * @param int $limit
     * @param int $v_id
     *
     * @return array
     */
    public function getDayBillboard($uid, $last_id = 0, $limit = 20, $v_id = 0)
    {
        $res = ['v_id' => (string)$v_id, 'last_id' => $last_id, 'data_list' => []];
//        $ymd = SocialDiscussBillboard::findOne(['order' => 'ymd desc', 'columns' => 'ymd']);
//        $ymd = $ymd ? $ymd['ymd'] : 0;
//
//        $where = 'ymd=' . $ymd;
//        if ($last_id) {
//            $where .= ' and id<' . $last_id;
//        }
//        $board = SocialDiscussBillboard::getByColumnKeyList([$where, 'order' => 'id desc', 'columns' => 'id,discuss_id,order_num', 'limit' => $limit], 'discuss_id');
//        if ($board) {
//            $ids = array_column($board, 'discuss_id');
//            $last_id = end($board)['id'];
//            $where = 'status=' . DiscussManager::STATUS_NORMAL . " and share_original_type<>'share' and id in (" . implode(',', $ids) . ")";
//            $order = 'created desc'; //排序  置顶只在查看特定用户的个人主页动态生效
//            $black_list = UserBlacklist::findList(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as user_id']);
//            if ($black_list) {
//                $where .= " and user_id not in (" . implode(',', array_column($black_list, 'user_id')) . ') ';
//            }
//            $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,reward_cnt,package_info',
//                'order' => $order]);
//            if ($list) {
//                $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
//                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
//                $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合
//
//                $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
//                $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合
//                //是否点过赞 收藏过
//                $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
//                $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
//                $collects = SocialFav::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //收藏集合
//
//
//                $order_column = [];//排序
//
//
//                foreach ($list as &$item) {
//                    $key = $board[$item['discuss_id']]['order_num'];
//                    $order_column[] = $key;
//
//                    $item['user_info'] = $user_info[$item['uid']];
//                    $item['user_info']['is_contact'] = 0;
//                    $item['user_info']['contact_mark'] = ($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark'])) ? $user_personal_setting[$item['uid']]['mark'] : '';
//                    $item['user_info']['is_attention'] = 0;
//                    //联系人
//                    if (isset($user_contact[$item['uid']])) {
//                        $item['user_info']['is_contact'] = 1;
//                        $item['user_info']['contact_mark'] = $user_contact[$item['uid']]['mark'];
//                        $item['user_info']['is_attention'] = 1;
//                    } //已关注
//                    elseif (isset($user_attention[$item['uid']])) {
//                        $item['user_info']['is_attention'] = 1;
//                    } else {
//                    }
//                    $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
//                    $item['is_collection'] = isset($collects[$item['discuss_id']]) ? 1 : 0;
//                    //转发的原始内容
//                    $item['original_info'] = (object)[];
//                    //显示时间
//                    $item['show_time'] = Time::formatHumaneTime($item['created']);
//                    $item = array_merge($item, $this->getOriginalInfo($uid, $item));
//
//                    $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
//
//                }
//                $res['data_list'] = $list;
//                $res['data_list'] && array_multisort($order_column, SORT_DESC, $res['data_list']);
//            }
//        }

        //带了版本号
        if ($v_id) {
            $total_discuss_hot = SocialDiscussBillboardDetail::findOne(['id=' . $v_id." and type=3", 'columns' => 'id,detail']);
            //该版本号可能已经删了
            if (!$total_discuss_hot) {
                $total_discuss_hot = SocialDiscussBillboardDetail::findOne(['type=3','columns' => 'id,detail', 'order' => 'id desc']);
            }
        } else {
            $total_discuss_hot = SocialDiscussBillboardDetail::findOne(['type=3','columns' => 'id,detail', 'order' => 'id desc']);
        }
        if ($total_discuss_hot) {
            $v_id = $total_discuss_hot['id'];
            $ids = $total_discuss_hot['detail'];
            $ids = explode(',', $ids);
            if ($last_id) {
                $start = array_search($last_id, $ids) + 1;
            } else {
                $start = 0;
            }
            $need_ids = array_slice($ids, $start, $limit);
            if ($need_ids) {
                $last_id = end($need_ids);
                $where = 'status=' . DiscussManager::STATUS_NORMAL . " and share_original_type<>'share' and id in (" . implode(',', $need_ids) . ")";
                $order = 'created desc'; //排序  置顶只在查看特定用户的个人主页动态生效
                $black_list = UserBlacklist::findList(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as user_id']);
                if ($black_list) {
                    $where .= " and user_id not in (" . implode(',', array_column($black_list, 'user_id')) . ') ';
                }
                $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,reward_cnt,package_info',
                    'order' => $order]);
                if ($list) {
                    $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
                    $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
                    $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合

                    $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
                    $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合
                    //是否点过赞 收藏过
                    $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
                    $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
                    $collects = SocialFav::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //收藏集合


                    $order_column = [];//排序


                    foreach ($list as &$item) {
                        $key = array_search($item['discuss_id'], $need_ids);
                        $order_column[] = $key;

                        $item['user_info'] = $user_info[$item['uid']];
                        $item['user_info']['is_contact'] = 0;
                        $item['user_info']['contact_mark'] = ($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark'])) ? $user_personal_setting[$item['uid']]['mark'] : '';
                        $item['user_info']['is_attention'] = 0;
                        //联系人
                        if (isset($user_contact[$item['uid']])) {
                            $item['user_info']['is_contact'] = 1;
                            $item['user_info']['contact_mark'] = $user_contact[$item['uid']]['mark'];
                            $item['user_info']['is_attention'] = 1;
                        } //已关注
                        elseif (isset($user_attention[$item['uid']])) {
                            $item['user_info']['is_attention'] = 1;
                        } else {
                        }
                        $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
                        $item['is_collection'] = isset($collects[$item['discuss_id']]) ? 1 : 0;
                        //转发的原始内容
                        $item['original_info'] = (object)[];
                        //显示时间
                        $item['show_time'] = Time::formatHumaneTime($item['created']);
                        $item = array_merge($item, $this->getOriginalInfo($uid, $item));

                        $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);

                    }
                    $res['data_list'] = $list;
                    $res['data_list'] && array_multisort($order_column, SORT_ASC, $res['data_list']);
                }

            }

        }
        $res['v_id'] = $v_id;
        $res['last_id'] = $last_id;

        return $res;


    }

    //
    /**获取周榜单
     * @param $uid
     * @param int $last_id
     * @param int $limit
     * @param int $v_id
     * @return array
     */
    public function getWeekBillboard($uid, $last_id = 0, $limit = 20, $v_id = 0)
    {
//        $res = ['v_id' => (string)$v_id, 'last_id' => $last_id, 'data_list' => []];
//        $ymd_start = date('Ymd', strtotime("-7 days"));
//        $ymd_end = date('Ymd');
//
//        $where = 'ymd >=' . $ymd_start . " and ymd<=" . $ymd_end;
//        if ($last_id) {
//            $where .= ' and discuss_id>' . $last_id;
//        }
//        $board = SocialDiscussBillboard::getByColumnKeyList([$where, 'order' => 'discuss_id asc', 'group' => 'discuss_id', 'columns' => 'discuss_id,sum(order_num) as order_num', 'limit' => $limit], 'discuss_id');
//        if ($board) {
//            $ids = array_column($board, 'discuss_id');
//            $last_id = end($board)['discuss_id'];
//            $where = 'status=' . DiscussManager::STATUS_NORMAL . " and share_original_type<>'share' and id in (" . implode(',', $ids) . ")";
//            $order = 'created desc'; //排序  置顶只在查看特定用户的个人主页动态生效
//            $black_list = UserBlacklist::findList(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as user_id']);
//            if ($black_list) {
//                $where .= " and user_id not in (" . implode(',', array_column($black_list, 'user_id')) . ') ';
//            }
//            $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,reward_cnt,package_info',
//                'order' => $order]);
//            if ($list) {
//                $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
//                $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
//                $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合
//
//                $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
//                $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合
//                //是否点过赞 收藏过
//                $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
//                $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
//                $collects = SocialFav::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //收藏集合
//
//
//                $order_column = [];//排序
//
//
//                foreach ($list as &$item) {
//                    $key = $board[$item['discuss_id']]['order_num'];
//                    $order_column[] = $key;
//
//                    $item['user_info'] = $user_info[$item['uid']];
//                    $item['user_info']['is_contact'] = 0;
//                    $item['user_info']['contact_mark'] = ($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark'])) ? $user_personal_setting[$item['uid']]['mark'] : '';
//                    $item['user_info']['is_attention'] = 0;
//                    //联系人
//                    if (isset($user_contact[$item['uid']])) {
//                        $item['user_info']['is_contact'] = 1;
//                        $item['user_info']['contact_mark'] = $user_contact[$item['uid']]['mark'];
//                        $item['user_info']['is_attention'] = 1;
//                    } //已关注
//                    elseif (isset($user_attention[$item['uid']])) {
//                        $item['user_info']['is_attention'] = 1;
//                    } else {
//                    }
//                    $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
//                    $item['is_collection'] = isset($collects[$item['discuss_id']]) ? 1 : 0;
//                    //转发的原始内容
//                    $item['original_info'] = (object)[];
//                    //显示时间
//                    $item['show_time'] = Time::formatHumaneTime($item['created']);
//                    $item = array_merge($item, $this->getOriginalInfo($uid, $item));
//
//                    $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
//
//                }
//                $res['data_list'] = $list;
//                $res['data_list'] && array_multisort($order_column, SORT_DESC, $res['data_list']);
//            }
//        }
//        $res['v_id'] = $v_id;
//        $res['last_id'] = $last_id;
//
//        return $res;
        $res = ['v_id' => $v_id, 'last_id' => $last_id, 'data_list' => []];
        //带了版本号
        if ($v_id) {
            $total_discuss_hot = SocialDiscussBillboardDetail::findOne(['id=' . $v_id." and type=4", 'columns' => 'id,detail']);
            //该版本号可能已经删了
            if (!$total_discuss_hot) {
                $total_discuss_hot = SocialDiscussBillboardDetail::findOne(['type=4','columns' => 'id,detail', 'order' => 'id desc']);
            }
        } else {
            $total_discuss_hot = SocialDiscussBillboardDetail::findOne(['type=4','columns' => 'id,detail', 'order' => 'id desc']);
        }
        if ($total_discuss_hot) {
            $v_id = $total_discuss_hot['id'];
            $ids = $total_discuss_hot['detail'];
            $ids = explode(',', $ids);
            if ($last_id) {
                $start = array_search($last_id, $ids) + 1;
            } else {
                $start = 0;
            }
            $need_ids = array_slice($ids, $start, $limit);
            if ($need_ids) {
                $last_id = end($need_ids);
                $where = 'status=' . DiscussManager::STATUS_NORMAL . " and share_original_type<>'share' and id in (" . implode(',', $need_ids) . ")";
                $order = 'created desc'; //排序  置顶只在查看特定用户的个人主页动态生效
                $black_list = UserBlacklist::findList(['owner_id=' . $uid . ' or user_id=' . $uid, 'columns' => 'if(owner_id=' . $uid . ',user_id,owner_id) as user_id']);
                if ($black_list) {
                    $where .= " and user_id not in (" . implode(',', array_column($black_list, 'user_id')) . ') ';
                }
                $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,allow_download,package_id,is_recommend,reward_cnt,package_info',
                    'order' => $order]);
                if ($list) {
                    $user_ids = implode(',', array_unique(array_column($list, 'uid'))); //发布动态用户集合
                    $user_info = UserInfo::getByColumnKeyList(['user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,grade,username,sex,avatar,is_auth'], 'uid');//用户信息集合
                    $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人备注集合

                    $user_contact = UserContactMember::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//联系人集合
                    $user_attention = UserAttention::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $user_ids . ') and enable=1', 'columns' => 'user_id as uid'], 'uid');//关注人集合
                    //是否点过赞 收藏过
                    $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
                    $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
                    $collects = SocialFav::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //收藏集合


                    $order_column = [];//排序


                    foreach ($list as &$item) {
                        $key = array_search($item['discuss_id'], $need_ids);
                        $order_column[] = $key;

                        $item['user_info'] = $user_info[$item['uid']];
                        $item['user_info']['is_contact'] = 0;
                        $item['user_info']['contact_mark'] = ($user_personal_setting && !empty($user_personal_setting[$item['uid']]['mark'])) ? $user_personal_setting[$item['uid']]['mark'] : '';
                        $item['user_info']['is_attention'] = 0;
                        //联系人
                        if (isset($user_contact[$item['uid']])) {
                            $item['user_info']['is_contact'] = 1;
                            $item['user_info']['contact_mark'] = $user_contact[$item['uid']]['mark'];
                            $item['user_info']['is_attention'] = 1;
                        } //已关注
                        elseif (isset($user_attention[$item['uid']])) {
                            $item['user_info']['is_attention'] = 1;
                        } else {
                        }
                        $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
                        $item['is_collection'] = isset($collects[$item['discuss_id']]) ? 1 : 0;
                        //转发的原始内容
                        $item['original_info'] = (object)[];
                        //显示时间
                        $item['show_time'] = Time::formatHumaneTime($item['created']);
                        $item = array_merge($item, $this->getOriginalInfo($uid, $item));

                        $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);

                    }
                    $res['data_list'] = $list;
                    $res['data_list'] && array_multisort($order_column, SORT_ASC, $res['data_list']);
                }

            }

        }
        $res['v_id'] = $v_id;
        $res['last_id'] = $last_id;

        return $res;


    }

    /*
     * 清理哪天之前的数据
     * */
    public function clearData($date)
    {
         SocialDiscussBillboard::remove("ymd<" . $date);
         SocialDiscussBillboardDetail::remove("ymd<" . $date);

    }

    //生成榜单数据
    public function createBillboard()
    {
        $day = date('Ymd');
        //24小时榜单
        $discuss_ids = SocialDiscussBillboard::getColumn(["ymd=" . $day, 'columns' => 'discuss_id,order_num', 'order' => 'order_num desc'], 'discuss_id');
        if ($discuss_ids) {
            $data = ['ymd' => $day, 'detail' => implode(',', $discuss_ids), 'modify' => time(), 'type' => 3];
            SocialDiscussBillboardDetail::insertOne($data);
        }

        //周榜单
        $ymd_start = date('Ymd', strtotime("-7 days"));
        $ymd_end = date('Ymd',strtotime("-1 days"));

        $where = 'ymd >=' . $ymd_start . " and ymd<=" . $ymd_end;
        $discuss = SocialDiscussBillboard::findList([$where, 'group' => 'discuss_id', 'columns' => 'discuss_id,sum(order_num) as order_num,count(1) as day', 'order' => 'order_num desc']);
        if ($discuss) {
            $order_num = [];
            $discuss_ids = [];
            foreach ($discuss as $item) {
                $order_num[] = $item['order_num'] / $item['day'];
                $discuss_ids[] = $item['discuss_id'];
            }
            array_multisort($order_num, SORT_DESC, $discuss_ids);
            $data = ['ymd' => $day, 'detail' => implode(',', $discuss_ids), 'modify' => time(), 'type' => 4];
            SocialDiscussBillboardDetail::insertOne($data);
        }

    }

}