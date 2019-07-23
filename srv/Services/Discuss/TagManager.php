<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/15
 * Time: 11:11
 */

namespace Services\Discuss;


use Models\Site\SiteTags;
use Models\Social\SocialTagsAttention;
use Phalcon\Mvc\User\Plugin;
use Services\Site\CacheSetting;
use Services\User\Square\SquareTask;

/**
 * @property \Phalcon\Db\AdapterInterface $original_mysql
 **/
class TagManager extends Plugin
{
    private static $instance = null;

    const TYPE_DISCUSS = 1;
    const TYPE_USER = 2;

    static $user_tag_group = [
        "1" => "自我描述",
        "2" => '爱好特长'
    ];

    /**
     * @return  TagManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /*--获取标签列表--*/
    public function list($refresh = false, $type = self::TYPE_DISCUSS)
    {
        $cacheSetting = new CacheSetting();
        $data = $cacheSetting->get(CacheSetting::PREFIX_TAGS, $type); /*缓存数据读取*/
        if (!$data || $refresh) {
            $data = SiteTags::getByColumnKeyList(['type=' . $type . ' and enable=1', 'columns' => 'id,name,attention_cnt as attention_count,thumb', 'order' => 'sort_num asc'], 'id');
            $cacheSetting->set(CacheSetting::PREFIX_TAGS, $type, $data);
        }
        return $data;
    }

    //获取系统用户标签
    public function getUserTag($refresh = false)
    {
        $cacheSetting = new CacheSetting();
        $data = $cacheSetting->get(CacheSetting::PREFIX_TAGS, self::TYPE_USER); /*缓存数据读取*/
        if (!$data || $refresh) {
            $data = SiteTags::getByColumnKeyList(['type=' . self::TYPE_USER . ' and enable=1', 'columns' => 'id,name,attention_cnt as attention_count,extra', 'order' => 'sort_num asc,created desc'], 'id');
            if ($data) {
                $res = [];
                foreach ($data as $item) {
                    if (!key_exists($item['extra'], $res)) {
                        $res[$item['extra']] = ['title' => self::$user_tag_group[$item['extra']], 'tags_name' => $item['name']];
                    } else {
                        $res[$item['extra']]['tags_name'] .= "," . $item['name'];
                    }
                }
                $data = array_values($res);
                $cacheSetting->set(CacheSetting::PREFIX_TAGS, self::TYPE_USER, $data);
            }

        }

        return $data;
    }

    /*--获取标签名称--*/
    public function getTagNames($tag_ids, $index = false)
    {
        if (!$tag_ids) {
            return '';
        }

        $list = self::list();
        $tag_ids = is_string($tag_ids) ? explode(',', $tag_ids) : $tag_ids;
        if ($index) {
            $name = [];
            foreach ($tag_ids as $item) {
                isset($list[$item]) && $name[] = ['id' => $list[$item]['id'], 'name' => $list[$item]['name'], "attention_count" => $list[$item]['attention_count']];
            }
            return $name;
        } else {
            $name = '';
            foreach ($tag_ids as $item) {
                isset($list[$item]) && $name .= ',' . $list[$item]['name'];
            }
        }

        return $name ? substr($name, 1) : '';
    }

    //设置标签
    public function setTags($uid, $tags = '')
    {
        $tags_attention = SocialTagsAttention::findOne('user_id=' . $uid);
        //清空掉
        if (!$tags) {
            //之前有添加标签
            if ($tags_attention['tag_ids'] != '') {
                if ($this->original_mysql->execute("update site_tags set attention_cnt=attention_cnt-1 where id in (" . $tags_attention['tag_ids'] . ") and attention_cnt>0")) {
                    SocialTagsAttention::updateOne(['tag_ids' => ''], ['id' => $tags_attention['id']]);
                }
            }
        } else {
            //之前有添加标签
            if ($tags_attention) {
                //标签内容不为空
                if ($tags_attention->tag_ids != '') {
                    $old = explode(',', $tags_attention->tag_ids); //旧标签
                    $new = explode(',', $tags);//新标签

                    $need_plus = array_diff($old, $new);//关注数减1的标签集合
                    $need_minus = array_diff($new, $old);//关注数加1的标签集合
                    if ($need_plus) {
                        $this->original_mysql->execute("update site_tags set attention_cnt=attention_cnt-1 where id in (" . implode(',', $need_plus) . ") and attention_cnt>0");
                    }
                    if ($need_minus) {
                        $this->original_mysql->execute("update site_tags set attention_cnt=attention_cnt+1 where id in (" . implode(',', $need_minus) . ")");
                    }
                } else {
                    $this->original_mysql->execute("update site_tags set attention_cnt=attention_cnt+1 where id in (" . $tags . ")");
                }
                SocialTagsAttention::updateOne(['tag_ids' => $tags], ['id' => $tags_attention['id']]);

            } else {
                $this->original_mysql->execute("update site_tags set attention_cnt=attention_cnt+1 where id in (" . $tags . ")");
                $tags_attention = new SocialTagsAttention();
                $tags_attention->insertOne(['tag_ids' => $tags, 'user_id' => $uid]);
            }
        }
        $cacheSetting = new CacheSetting();
        $cacheSetting->remove(CacheSetting::PREFIX_TAGS, self::TYPE_DISCUSS); /*清除缓存数据*/

        return true;
    }

    //获取标签
    public static function getTags($uid)
    {
        $res = ['data_list' => []];
        $tags_attention = SocialTagsAttention::findOne(['user_id=' . $uid, 'columns' => 'tag_ids']);
        if ($tags_attention && $tags_attention['tag_ids']) {
            $res['data_list'] = self::getTagNames($tags_attention['tag_ids'], true);
        }
        return $res;
    }

}