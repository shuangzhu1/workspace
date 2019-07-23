<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/13
 * Time: 17:58
 */

namespace Services\Discuss;


use Components\Time;
use Models\Social\SocialDiscuss;
use Models\Social\SocialFav;
use Models\Social\SocialLike;
use Phalcon\Mvc\User\Plugin;
use Services\Social\SocialManager;
use Services\User\UserStatus;
use Util\FilterUtil;

class DiscussBase extends Plugin
{
    //列表数据格式化
    /**
     * @param $uid
     * @param $list
     * @return array
     */
    public function format($uid, $list)
    {
        if ($list) {
            $discuss_ids = implode(',', array_unique(array_column($list, 'discuss_id')));
            $likes = SocialLike::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ')  and enable=1', 'columns' => 'item_id'], 'item_id'); //点赞集合
            $collects = SocialFav::getByColumnKeyList(['type="' . SocialManager::TYPE_DISCUSS . '" and user_id=' . $uid . ' and item_id in (' . $discuss_ids . ') and enable=1', 'columns' => 'item_id'], 'item_id'); //收藏集合
            foreach ($list as &$item) {
                $item['is_like'] = isset($likes[$item['discuss_id']]) ? 1 : 0;
                $item['is_collection'] = isset($collects[$item['discuss_id']]) ? 1 : 0;
                //转发的原始内容
                $item['original_info'] = (object)[];

                //显示时间
                $item['show_time'] = Time::formatHumaneTime($item['created']);
                $item['content'] = FilterUtil::unPackageContentTagApp($item['content'], $uid);
                $item = array_merge($item, $this->getOriginalInfo($uid, $item));
            }

        }
        if ($list) {
            $list = array_values($list);
        }
        return $list;
    }

    /**获取原始数据
     * @param $uid
     * @param $item
     * @return array
     */
    public function getOriginalInfo($uid, $item)
    {
        $return_data = [];
        if ($item['share_original_type'] == SocialManager::TYPE_NEWS) {
            $content = json_decode($item['content'], true);

            $return_data['content'] = $content['content'];
            $return_data['original_info'] = [
                'title' => isset($content['title']) ? $content['title'] : '',
                'news_id' => isset($content['news_id']) ? $content['news_id'] : 0,
                'media' => isset($content['media']) ? $content['media'] : '',
                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                'logo' => isset($content['logo']) ? $content['logo'] : '',
                'author' => isset($content['author']) ? $content['author'] : '',
            ];
        } //第三方分享
        else if ($item['share_original_type'] == SocialManager::TYPE_SHARE) {
            if ($item['parent_item_id_str']) {
                $top_discuss_id = explode(',', $item['parent_item_id_str'])[0];
                $content = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content']);
                $content = json_decode($content['content'], true);
            } else {
                $content = json_decode($item['content'], true);
            }
            $return_data['content'] = $content['content'];
            $return_data['original_info'] = [
                'content' => isset($content['title']) ? $content['title'] : '',
                'link' => isset($content['link']) ? $content['link'] : '',
                'title' => isset($content['title']) ? $content['title'] : '',
                'media' => isset($content['media']) ? $content['media'] : '',
                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                'from' => isset($content['from']) ? $content['from'] : '',
            ];
        } //商铺
        else if ($item['share_original_type'] == SocialManager::TYPE_SHOP) {
            // $content = json_decode($item['content'], true);
            if ($item['parent_item_id_str']) {
                $top_discuss_id = explode(',', $item['parent_item_id_str'])[0];
                $content = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content']);
                $content = json_decode($content['content'], true);
            } else {
                $content = json_decode($item['content'], true);
                $return_data['content'] = $content['content'];
            }
            //  $item['content'] = $content['content'];
            $return_data['original_info'] = [
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
            if ($item['parent_item_id_str']) {
                $top_discuss_id = explode(',', $item['parent_item_id_str'])[0];
                $contents = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content,package_id,package_info']);
                $content = json_decode($contents['content'], true);
                $return_data['package_id'] = $contents['package_id'];
                $return_data['package_info'] = $contents['package_info'];

            } else {
                $content = json_decode($item['content'], true);
                $return_data['content'] = $content['content'];
            }
            //  $content = json_decode($item['content'], true);

            // $item['content'] = $content['content'];
            $return_data['original_info'] = [
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
                $return_data['original_info'] = $original_info;
            }
        } else {
            if ($item['share_original_item_id']) {
                $original_info = SocialManager::init()->getShortDate($item['share_original_type'], $item['share_original_item_id'], $uid);
                if ($original_info) {
                    $return_data['original_info'] = $original_info;
                }
            }
        }
        return $return_data;

    }
}