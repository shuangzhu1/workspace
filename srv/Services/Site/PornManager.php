<?php
/**
 *
 * 鉴黄处理 -后台操作
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/31
 * Time: 11:16
 */

namespace Services\Site;


use Models\Social\SocialComment;
use Models\Social\SocialCommentReply;
use Models\Social\SocialDiscuss;
use Models\System\SystemImageCheck;
use Models\User\UserInfo;
use Models\User\UserStorage;
use OSS\OssClient;
use Phalcon\Mvc\User\Plugin;
use Services\Admin\AdminLog;
use Services\Upload\OssManager;
use Util\Ajax;
use Util\Debug;

class PornManager extends Plugin
{
    private static $instance = null;

    const STATUS_PORN = 1;//黄图
    const STATUS_DELETED = 0;//已删除
    const STATUS_NORMAL = 2;//非黄图

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    //忽略图片
    public function ignore($data)
    {
        $list = SystemImageCheck::findList(["id in (" . implode(",", $data) . ')', 'columns' => 'id,user_id,item_id,url,type']);
        if (!$list) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $this->updateImg($list);
        //变更状态
        if (SystemImageCheck::updateOne(['status' => self::STATUS_NORMAL, "modify" => time()], "id in (" . implode(",", $data) . ")")) {
            //记录日志
            AdminLog::init()->add('鉴黄操作-忽略黄图', AdminLog::TYPE_PORN, 0, array('type' => "update", 'id' => $data, 'data' => []));
        }
        Ajax::outRight("");
    }

    //删除图片
    public function remove($data)
    {
        $list = SystemImageCheck::findList(["id in (" . implode(",", array_column($data,'id')) . ')', 'columns' => 'id,item_id,url,type']);
        if (!$list) {
            Ajax::outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //$list按type分组，以便删除在不同bucket中数据
        $tmp = [];
        foreach( $list as $value)
        {
            $tmp[$value['type']][] = $value;
        }
        $list = $tmp;
        //图片类型与bucket对应关系
        $typeToBucket =
            [
                'discuss' => OssManager::BUCKET_CIRCLE_IMG,
                'comment' => OssManager::BUCKET_CIRCLE_IMG,
                'reply' => OssManager::BUCKET_CIRCLE_IMG,
                'avatar' => OssManager::BUCKET_USER_AVATOR
            ];
        //oss配置
        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        //循环删除不同bucket中图片
        foreach( array_keys($list) as $key )
        {
            $objects = [];
            $update_url = "";
            foreach ($list[$key] as $item) {
                $objects[] = substr($item['url'], strpos($item['url'], "klgwl.com") + 10);
                $update_url .= ",'" . $item['url'] . "'";
            }
            //oss 直接删除
            if ($res = $oss->deleteObjects($typeToBucket[$key], $objects)) {
                Debug::log("res:" . var_export($res, true), "oss");
                //变更状态
                if (SystemImageCheck::updateOne(['status' => self::STATUS_DELETED, 'modify' => time()], 'id in (' . implode(',', array_column($data,'id')) . ')')) {
                    //记录日志
                    AdminLog::init()->add('鉴黄操作-删除黄图', AdminLog::TYPE_PORN, 0, '', array('type' => "update", 'id' => $data, 'data' => []));
                }
                if ($update_url) {
                    UserStorage::updateOne(['is_porn' => 1], ' url in (' . substr($update_url, 1) . ")");
                }
                Ajax::outRight("");
            }
        }


    }

    //更新图片
    public function updateImg($list)
    {
        $res = ['discuss' => [], 'comment' => [], 'reply' => [],'avatar' => []];//分类

        $update_url = '';
        foreach ($list as $item) {
            if (key_exists($item['item_id'], $res[$item['type']])) {
                $res[$item['type']][$item['item_id']][] = $item['url'];
            } else {
                $res[$item['type']][$item['item_id']] = [$item['url']];
            }
            $update_url .= ",'" . $item['url'] . "'";
        }

        //动态
        if ($res['discuss']) {
            foreach ($res['discuss'] as $k => $d) {
                $discuss = SocialDiscuss::findOne("id=" . $k);

                //数据不存在
                if (!$discuss) {
                    return;
                }
                foreach ($d as $i) {
                    $discuss['media'] = str_replace($i . "|porn", $i, $discuss['media']);
                }
                SocialDiscuss::updateOne(['modify' => time(), 'media' => $discuss['media']], ['id' => $k]);
                $this->db->execute("update social_discuss_media set media='" . $discuss['media'] . "' where discuss_id=" . $k);
            }
        }
        //评论
        if ($res['comment']) {
            foreach ($res['comment'] as $k => $c) {
                $comment = SocialComment::findOne("id=" . $k);
                //数据不存在
                if (!$comment) {
                    return;
                }
                foreach ($c as $i) {
                    $comment['images'] = str_replace($i . "|porn", $i, $comment['images']);
                }
                SocialComment::updateOne(['images' => $comment['images']], ['id' => $k]);

            }
        }
        //回复
        if ($res['reply']) {
            foreach ($res['reply'] as $k => $r) {
                $comment = SocialCommentReply::findOne("id=" . $k);
                //数据不存在
                if (!$comment) {
                    return;
                }
                foreach ($r as $i) {
                    $comment['images'] = str_replace($i . "|porn", $i, $comment['images']);
                }
                SocialCommentReply::updateOne(['images' => $comment['images']], ['id' => $k]);

            }
        }

        //头像
        if( $res['avatar'] )
        {
            foreach ($res['avatar'] as $k => $d) {
                $userInfo = UserInfo::findOne("user_id=" . $k,'column');

                //数据不存在
                if (!$userInfo) {
                    return;
                }
                foreach ($d as $i) {
                    $userInfo['photos'] = str_replace($i . "|porn", $i, $userInfo['photos']);
                }
                UserInfo::updateOne(['photos' => $userInfo['photos']], ['user_id' => $k]);
            }
        }

        if ($update_url) {
            $this->db->execute("update user_storage set is_porn=0 where url in (" . substr($update_url, 1) . ")");
        }
    }
}