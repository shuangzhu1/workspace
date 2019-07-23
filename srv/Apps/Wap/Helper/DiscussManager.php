<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/3
 * Time: 15:10
 */

namespace Multiple\Wap\Helper;


use Models\Shop\ShopGoods;
use Models\Social\SocialDiscuss;
use Models\Social\SocialFav;
use Models\Social\SocialLike;
use Models\User\UserAttention;
use Models\User\UserBlacklist;
use Models\User\UserContactMember;
use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\Users;
use Services\Shop\GoodManager;
use Services\Social\SocialManager;
use Util\Ajax;
use Util\FilterUtil;
use Util\Time;

class DiscussManager
{
    /**动态详情
     * @param $uid -用户id
     * @param $discuss_id -动态id
     * @return array|static
     */
    public static function detail($uid, $discuss_id)
    {
        $discuss = SocialDiscuss::findOne(['id=' . $discuss_id . ' and status=' . \Services\Discuss\DiscussManager::STATUS_NORMAL, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id,parent_item_id_str,is_top,created,address,lng,lat,scan_type,scan_user,package_id']);
        if (!$discuss) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //登陆了
        if ($uid) {
            //查看别人的动态
            if ($discuss['uid'] != $uid) {
                //对方已把自己拉黑
                if (UserBlacklist::exist('owner_id=' . $discuss['uid'] . ' and user_id=' . $uid)) {
                    die("主人设置了访问权限");
                } //对方设置了不允许查看其动态
                else if (UserPersonalSetting::exist('owner_id=' . $discuss['uid'] . ' and user_id=' . $uid . ' and scan_my_discuss=0')) {
                    die("主人设置了访问权限");
                } elseif ($discuss['scan_type'] == \Services\Discuss\DiscussManager::SCAN_TYPE_PRIVATE) {
                    die("主人设置了访问权限");
                } elseif ($discuss['scan_type'] == \Services\Discuss\DiscussManager::SCAN_TYPE_PART_FRIEND && strpos($discuss['scan_user'] . ',', $uid . ',') === false) {
                    die("主人设置了访问权限");
                } elseif ($discuss['scan_type'] == \Services\Discuss\DiscussManager::SCAN_TYPE_FORBIDDEN && strpos($discuss['scan_user'] . ',', $uid . ',') !== false) {
                    die("主人设置了访问权限");
                } else {
                    $user_info = UserInfo::findOne(['user_id=' . $discuss['uid'], 'columns' => 'user_id as uid,username,sex,avatar,grade,is_auth']);//用户信息;
                    $is_attention = 0;
                    $is_contact = 0;
                    $contact_mark = '';

                    if ($contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $discuss['uid'], 'columns' => 'mark'])) {
                        $is_attention = 1;
                        $is_contact = 1;
                        $contact_mark = $contact['mark'];
                    } else if ($attention = UserAttention::exist('owner_id=' . $uid . ' and user_id=' . $discuss['uid'])) {
                        $is_attention = 1;
                    }
                    $discuss['user_info'] = $user_info;
                    $discuss['user_info']['is_contact'] = $is_contact;
                    $discuss['user_info']['contact_mark'] = $contact_mark;
                    $discuss['user_info']['is_attention'] = $is_attention;
                }
            } else {
                $user_info = UserInfo::findOne(['user_id=' . $discuss['uid'], 'columns' => 'user_id as uid,username,sex,avatar,grade,is_auth']);//用户信息;
                $discuss['user_info'] = $user_info;
                $discuss['user_info']['is_contact'] = 0;
                $discuss['user_info']['contact_mark'] = '';
                $discuss['user_info']['is_attention'] = 0;
            }
            //是否已赞
            if (SocialLike::exist('type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id=' . $discuss_id . ' and enable=1')) {
                $discuss['is_like'] = 1;
            }
            //是否已收藏
            if (SocialFav::exist('type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id=' . $discuss_id . ' and enable=1')) {
                $discuss['is_collect'] = 1;
            }
        } //游客
        else {
            if ($discuss['scan_type'] != \Services\Discuss\DiscussManager::SCAN_TYPE_ALL) {
                die("主人设置了访问权限");
            }
            $discuss['is_like'] = 0;
            $discuss['is_collect'] = 0;
            $user_info = UserInfo::findOne(['user_id=' . $discuss['uid'], 'columns' => 'user_id as uid,username,sex,avatar,grade,is_auth']);//用户信息;
            $discuss['user_info'] = $user_info;
            $discuss['user_info']['is_contact'] = 0;
            $discuss['user_info']['contact_mark'] = '';
            $discuss['user_info']['is_attention'] = 0;

        }
        $discuss['like_users'] = [];
        if ($discuss['like_cnt'] > 0) {
            $like_users = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and item_id=' . $discuss_id . ' and enable=1', 'columns' => 'user_id as uid,created', 'order' => 'created', 'limit' => 5], 'uid');
            $user_infos = Users::findList(['id in (' . implode(',', array_column($like_users, 'uid')) . ')', 'columns' => 'id as uid,avatar']);
            $order_data = [];//排序

            foreach ($user_infos as $u) {
                $order_data[] = $like_users[$u['uid']]['created'];
            }
            array_multisort($order_data, SORT_DESC, $user_infos);
            $discuss['like_users'] = $user_infos;
        }
        //转发的原始内容
        $discuss['original_info'] = [];
        //新闻资讯
        if ($discuss['share_original_type'] == SocialManager::TYPE_NEWS) {
            $content = json_decode($discuss['content'], true);

            $discuss['content'] = $content['content'];
            $discuss['original_info'] = [
                'title' => isset($content['title']) ? $content['title'] : '',
                'news_id' => isset($content['news_id']) ? $content['news_id'] : 0,
                'media' => isset($content['media']) ? $content['media'] : '',
                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
            ];

        } elseif( $discuss['share_original_type'] == SocialManager::TYPE_GOOD)//商品
        {
            if ($discuss['parent_item_id_str']) {
                $top_discuss_id = explode(',', $discuss['parent_item_id_str'])[0];
                $content        = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content']);
                $content        = json_decode($content['content'], true);
            }else
            {
                $content =  json_decode($discuss['content'], true);
                $discuss['content'] = $content['content'];
            }


            $discuss['original_info'] = [
                'title' => isset($content['name']) ? $content['name'] : '',
                'good_id' => isset($content['good_id']) ? $content['good_id'] : 0,
                'media' => isset($content['media']) ? explode(',',$content['media'])[0] : '',
                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                'brief' => isset($content['brief']) ? $content['brief'] : 0,
                'price' => isset($content['price']) ? $content['price'] : 0,
                'shop_owner' => Users::findOne(['id = ' . $content['uid'],'columns' => 'username'])['username'],
            ];
        }
        else {
            if ($discuss['share_original_item_id']) {
                $original_info = SocialManager::init()->getShortDate($discuss['share_original_type'], $discuss['share_original_item_id'], $uid);
                if ($original_info) {
                    $discuss['original_info'] = $original_info;
                }
            }
        }
        //显示时间
        $discuss['show_time'] = Time::formatHumaneTime($discuss['created']);
        $discuss['content'] = FilterUtil::parseContentUrl($discuss['content']);
        $discuss['content'] = FilterUtil::unPackageContentTag($discuss['content'], $uid,'http://wap.klgwl.com/user?to=');

        return $discuss;
    }

    /**
     * @param $uid -用户id
     * @param $to_uid -想查看的用户id
     * @param int $type -类型 0-全部 1-图文 2-原创 3-视频
     * @param $page -第几页
     * @param int $limit -每次加载的条数
     * @return array|static
     */
    public static function list($uid, $to_uid, $type = 0, $page = 1, $limit = 20)
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $where = 'status=' . \Services\Discuss\DiscussManager::STATUS_NORMAL;
        $order = 'is_top desc,created desc'; //排序
        //查看别人或自己的动态

        //查看别人动态,检测是否在其黑名单下/用户设置了访问权限
        $personal_setting = '';
        //登陆了并且查看别人的
        if ($uid && $uid != $to_uid) {
            if (UserBlacklist::exist('owner_id=' . $to_uid . ' and user_id=' . $uid)) {
                Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
            }
            if ($personal_setting = UserPersonalSetting::findOne(['owner_id=' . $to_uid . ' and user_id=' . $uid])) {
                if ($personal_setting['scan_my_discuss'] == 0) {
                    Ajax::outError(Ajax::ERROR_HAS_NO_PRIVILEGE_DISCUSS);
                }
            }

            //查看权限检测
            //是好友
            if (UserContactMember::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                $where .= " and ((scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_ALL . ") or (scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_FRIEND . ") or (scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0))";
            } else {
                $where .= " and ((scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_ALL . ") or (scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0)) and scan_type<>" . \Services\Discuss\DiscussManager::SCAN_TYPE_FRIEND;
            }
        }
        else {
            //登陆了 查看自己动态
         if($uid){

         } else{
             $where .= " and ((scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_ALL . ") or (scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_PART_FRIEND . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))>0) or (scan_type=" . \Services\Discuss\DiscussManager::SCAN_TYPE_FORBIDDEN . " and LOCATE('" . $uid . ",', CONCAT(scan_user,','))=0)) and scan_type<>" . \Services\Discuss\DiscussManager::SCAN_TYPE_FRIEND;

         }
        }
        $where .= " and user_id=" . $to_uid;
        //图文
        if ($type == 1) {
            $where .= " and (media_type=" . \Services\Discuss\DiscussManager::TYPE_TEXT . ' or media_type=' . \Services\Discuss\DiscussManager::TYPE_PICTURE . ') ';
        } //原创
        else if ($type == 2) {
            $where .= " and share_original_item_id=0 ";
        } //视频
        else if ($type == 3) {
            $where .= " and media_type= " . \Services\Discuss\DiscussManager::TYPE_VIDEO;
        }
        $page = $page > 0 ? $page : 1;
        $list = SocialDiscuss::findList([$where, 'columns' => 'id as discuss_id,user_id as uid,tags_name,content,media,media_type,like_cnt,fav_cnt,comment_cnt,forward_cnt,view_cnt,share_original_type,share_original_item_id,parent_item_id_str,parent_item_id,is_top,created,address,lng,lat,scan_type,allow_download,package_id',
            'order' => $order, 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        $res['data_count'] = SocialDiscuss::dataCount($where);
        if ($list) {
            //自己的动态
            if ($to_uid == $uid) {
                $user_info = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'user_id as uid,username,sex,avatar,grade']);//用户信息;
                foreach ($list as &$item) {
                    $item['user_info'] = $user_info;
                    $item['user_info']['is_contact'] = 0;
                    $item['user_info']['contact_mark'] = '';
                    $item['user_info']['is_attention'] = 0;
                }
            } //某个人的动态
            else {
                $user_info = UserInfo::findOne(['user_id=' . $to_uid, 'columns' => 'user_id as uid,username,sex,avatar,grade']);//用户信息;
                $is_attention = 0;
                $is_contact = 0;
                $contact_mark = '';
                if ($uid) {
                    if ($contact = UserContactMember::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'mark'])) {
                        $is_attention = 1;
                        $is_contact = 1;
                        $contact_mark = $contact['mark'];
                    } else if ($attention = UserAttention::exist('owner_id=' . $uid . ' and user_id=' . $to_uid)) {
                        $is_attention = 1;
                    }
                }

                foreach ($list as &$item) {
                    $item['user_info'] = $user_info;
                    $item['user_info']['is_contact'] = $is_contact;
                    $item['user_info']['contact_mark'] = ($personal_setting && $personal_setting['mark']) ? $personal_setting['mark'] : $contact_mark;
                    $item['user_info']['is_attention'] = $is_attention;
                }
            }

            //是否点过赞 收藏过
            if ($uid) {
                $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
                $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
                $collects = SocialFav::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //收藏集合
            } else {
                $likes = $collects = [];
            }
            foreach ($list as &$item) {
                $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
                $item['is_collection'] = isset($collects[$item['discuss_id']]) ? 1 : 0;
                //转发的原始内容
                $item['original_info'] = [];
                //新闻资讯
                if ($item['share_original_type'] == SocialManager::TYPE_NEWS) {
                    $content = json_decode($item['content'], true);

                    $item['content'] = $content['content'];
                    $item['original_info'] = [
                        'title' => isset($content['title']) ? $content['title'] : '',
                        'news_id' => isset($content['news_id']) ? $content['news_id'] : 0,
                        'media' => isset($content['media']) ? $content['media'] : '',
                        'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                    ];

                }elseif( $item['share_original_type'] == SocialManager::TYPE_GOOD)//商品
                {
                    if ($item['parent_item_id_str']) {
                        $top_discuss_id = explode(',', $item['parent_item_id_str'])[0];
                        $content        = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content']);
                        $content        = json_decode($content['content'], true);
                    }else
                    {
                        $content =  json_decode($item['content'], true);
                        $item['content'] = $content['content'];
                    }

                    $item['original_info'] = [
                        'title' => isset($content['name']) ? $content['name'] : '',
                        'good_id' => isset($content['good_id']) ? $content['good_id'] : 0,
                        'media' => isset($content['media']) ? explode(',',$content['media'])[0] : '',
                        'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                        'brief' => isset($content['brief']) ? $content['brief'] : 0,
                        'price' => isset($content['price']) ? $content['price'] : 0,
                        'shop_owner' => Users::findOne(['id = ' . $content['uid'],'columns' => 'username'])['username'],
                        "status" => 1
                    ];
                }else {
                    if ($item['share_original_item_id']) {
                        $original_info = SocialManager::init()->getShortDate($item['share_original_type'], $item['share_original_item_id'], $uid);
                        if ($original_info) {
                            $item['original_info'] = $original_info;
                        }
                    }
                }
                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['content'] = FilterUtil::parseContentUrl($item['content']);

                $item['content'] = FilterUtil::unPackageContentTag($item['content'], $uid, 'http://wap.klgwl.com/user?to=');
            }


            $res['data_list'] = $list;
        }
        return $res;
    }


}