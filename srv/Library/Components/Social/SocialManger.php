<?php
/**
 * Created by PhpStorm.
 * User: luguiwu
 * Date: 15-3-25
 * Time: 下午2:52
 */

namespace Components\Social;


use Components\CommentManager;
use Models\Social\SocialCountDiscuss;
use Models\Social\SocialCountProduct;
use Models\Social\SocialFavorite;
use Models\Social\SocialLike;
use Multiple\Home\Helper\UserStatus;
use Phalcon\Mvc\User\Plugin;

class SocialManger extends Plugin {

    private static $instance = null;

    public static function init()
    {
        if (!static::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public  function  getSocialCount($type, &$item)
    {
        $user_id=UserStatus::init()->getUid();
        $count = null;
        if ($type == CommentManager::TYPE_DISCUSS) {
            $count = SocialCountDiscuss::findFirst('id=' . $item['id']);
        }

        if ($type == CommentManager::TYPE_PRODUCT) {
            $count = SocialCountProduct::findFirst('id=' . $item['id']);
        }

        // 产品评价总数直接读取
        if ($type != CommentManager::TYPE_PRODUCT) {
            $item['comment_count'] = 0;
        }

        $item['favorite_count'] = 0;
        $item['like_count'] = 0;
        $item['has_liked'] = 0;
        $item['has_favorite'] = 0;

        if ($count) {
            $item['comment_count'] = $count->comment_count;
            $item['favorite_count'] = $count->favorite_count;
            $item['like_count'] = $count->like_count;
        }

        if ($user_id && in_array($type, [CommentManager::TYPE_DISCUSS, CommentManager::TYPE_PRODUCT])) {
            // 是否赞过
            $liked = SocialLike::count('item_id=' . $item['id'] . ' and type="' . $type . '" and user_id=' . $user_id);
            $item['has_liked'] = $liked > 0 ? 1 : 0;
            // 是否收藏
            $favorite = SocialFavorite::count('item_id=' . $item['id'] . ' and type="' . $type . '"  and user_id=' . $user_id);
            $item['has_favorite'] = $favorite > 0 ? 1 : 0;
        }
    }
    /**获取社区/帖子收藏
     * @param int $user_id 用户id
     * @param string $type 收藏类型('product','discuss')
     * @param int $limit
     * @param int $page
     * @return mixed
     */
    public function getGoodsFavList($user_id,$type='product',$limit=10,$page=0)
    {

        $sql="";
        if ($type == "product") {
            $sql = 'select pc.id as pcid,pc.created,p.id as pid, p.created,p.name,p.type as p_type,p.thumb,p.sell_price from social_favorite as pc
				left join product as p on pc.item_id = p.id  where pc.type = "' . $type . '" and pc.user_id = "' . $user_id
                . '" order by pc.created desc';
        } elseif ($type == "discuss") {
            $sql = 'select pc.id as pcid,pc.created,d.* from social_favorite as pc
				left join zuoke_discuss as d on pc.item_id = d.id  where pc.type = "' . $type . '" and pc.user_id = "' . $user_id
                . '" order by pc.created desc';


        }
        $sql .= ' limit ' . ($page * $limit) . ', ' . $limit;
        return  $this->db->query($sql)->fetchAll();
    }
} 