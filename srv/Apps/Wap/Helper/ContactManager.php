<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/20
 * Time: 15:42
 */

namespace Multiple\Wap\Helper;


use Models\User\UserAttention;
use Models\User\UserInfo;
use Phalcon\Mvc\User\Plugin;

class ContactManager extends Plugin
{
    /**我的关注列表
     * @param $uid
     * @param $to_uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function followers($uid, $to_uid, $page = 0, $limit = 20)
    {
        $res = ['data_count' => 0,
            'data_list' => []];

        $params = ['owner_id=' . $to_uid /*. ' and enable=1'*/, 'columns' => 'user_id,enable,created', 'order' => 'created desc'];

        if ($page > 0) {
            $params['limit'] = $limit;
            $params['offset'] = ($page - 1) * $limit;
        }
        $res['data_count'] = UserAttention::dataCount('owner_id=' . $to_uid );
        $user_attention = UserAttention::getByColumnKeyList($params, 'user_id');
        if ($user_attention) {
            $user_ids = array_column($user_attention, 'user_id');
            $list = UserInfo::findList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'user_id as uid, status,sex,avatar,username,true_name,is_auth,company,job,introduce,signature,grade']);
            //查看自己的
            if ($uid == $to_uid) {
                foreach ($list as &$item) {
                    $item['is_contact'] = $user_attention[$item['uid']]['enable'] == 1 ? 0 : 1;
                    $res['data_list'][] = $item;
                }
            } //查看别人的
            else {
                foreach ($list as &$item) {
                    $item['is_contact'] = 0;
                    $res['data_list'][] = $item;
                }
            }

        }
        return $res;
    }

    /**我的粉丝列表
     * @param $uid
     * @param $to_uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function fans($uid, $to_uid, $page = 0, $limit = 20)
    {
        $res = ['data_count' => 0,
            'data_list' => []];
        $params = ['user_id=' . $to_uid /*. ' and enable=1'*/, 'columns' => 'enable,owner_id,created', 'order' => 'created desc'];
        if ($page > 0) {
            $params['limit'] = $limit;
            $params['offset'] = ($page - 1) * $limit;
        }
        $res['data_count'] = UserAttention::dataCount('user_id=' . $to_uid );
        // $user_ids = UserAttention::getColumn($params, 'owner_id');
        $user_attention = UserAttention::getByColumnKeyList($params, 'owner_id');
        if ($user_attention) {
            $user_ids = array_column($user_attention, 'owner_id');
            $list = UserInfo::findList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'user_id as uid,status,sex,avatar,username,true_name,is_auth,auth_type,company,job,introduce,signature,grade']);
            //查看自己的
            if ($uid == $to_uid) {
                foreach ($list as &$item) {
                    $item['is_contact'] = $user_attention[$item['uid']]['enable'] == 1 ? 0 : 1;
                    $res['data_list'][] = $item;
                }
            } //查看别人的
            else {
                foreach ($list as &$item) {
                    $item['is_contact'] = 0;
                    $res['data_list'][] = $item;
                }
            }
        }
        return $res;
    }
}