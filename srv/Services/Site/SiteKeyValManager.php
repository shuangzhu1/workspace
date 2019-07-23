<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 7/14/15
 * Time: 9:33 AM
 */

namespace Services\Site;


use Models\Site\SiteKeyVal;
use Phalcon\Mvc\User\Plugin;

class SiteKeyValManager extends Plugin
{
    private static $instance = null;

    const KEY_PAGE_SMS_TPL = 'sms_tpl';//短信模板
    const KEY_PAGE_MAIL_TPL = 'mail_tpl'; //邮箱模板
    const KEY_PAGE_IM_TPL = 'im_tpl';//im通知模板
    const KEY_PAGE_DISCUSS = 'discuss';//动态相关
    const KEY_PAGE_OTHER = 'other';//其他
    const KEY_PAGE_DOCUMENT = 'document';//文档
    const KEY_APP_SETTING = 'app_setting';//app设置
    const KEY_SEQUENCE = 'sequence';//序号
    const KEY_SYSTEM_SETTING = 'system_setting';//系统设置
    const KEY_HOT_QUESTION = 'hot_question';//视频问答模块中随机问题


    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getValByKey($key, $sub_key = null)
    {
        if (!is_null($sub_key)) {
            $res = SiteKeyVal::findOne(['pri_key="' . $key . '" and sub_key="' . $sub_key . '" and enable=1', 'columns' => 'val']);
        } else {
            $res = SiteKeyVal::findOne(['pri_key="' . $key . '"', 'columns' => 'val']);
        }

        return $res ? $res['val'] : '';
    }

    public function setValByKey($key, $sub_key = null, $data)
    {

        if (!is_null($sub_key)) {
            $res = SiteKeyVal::findOne(['pri_key="' . $key . '" and sub_key="' . $sub_key . '" and enable=1', 'columns' => 'id']);
        } else {
            $res = SiteKeyVal::findOne(['pri_key="' . $key . '"', 'columns' => 'id']);
        }
        if (!$res) {
            $data['pri_key'] = $key;
            $data['sub_key'] = $sub_key;
            return SiteKeyVal::insertOne($data);
        } else {
            return SiteKeyVal::updateOne($data, ['id' => $res['id']]);
        }
    }

    /**
     * 通过key查找
     *
     * @param $key
     * @param null $sub_key 非null时返回一条数据
     * @param bool|false $refresh 是否刷新
     * @return array|mixed
     */
    public function getByKey($key, $sub_key = null, $refresh = false)
    {
        if (!is_null($sub_key)) {
            $res = SiteKeyVal::findOne(['pri_key="' . $key . '" and sub_key="' . $sub_key . '" and enable=1']);
            return $res;
        }

        $res = $this->getKeyGroupAll($key, $refresh);
        $list = $res;
        if (!$res) {
            $list = SiteKeyVal::findList(['`pri_key`="' . $key . '" and enable=1', 'order' => "sort desc,id asc"]);
        }
        return $list;
    }

    /**
     * 通过key查找一条数据
     *
     * @param $key
     * @param null $sub_key
     * @return mixed
     */
    public function getOneByKey($key, $sub_key = null)
    {
        if (!is_null($sub_key)) {
            $res = SiteKeyVal::findOne(['pri_key="' . $key . '" and sub_key="' . $sub_key . '" and enable=1']);
        } else {
            $res = SiteKeyVal::findOne(array('pri_key="' . $key . '" and enable=1'));
        }
        return $res;

    }

    /**
     * 通过主键获取所有
     *
     * @param $pri_key
     * @param bool|false $refresh
     * @return array|mixed
     */
    public function getKeyGroupAll($pri_key, $refresh = false)
    {
        $cache_key = 'site_key_val_cache_' . $pri_key;
        $data = $this->di->get('redis')->get($cache_key);
        if (!$data || $refresh) {
            $data = SiteKeyVal::findList(array('pri_key="' . $pri_key . '" and enable=1', 'order' => "sort desc,id desc"));
            if ($data) {
                $this->di->get('redis')->save($cache_key, $data);
            }
        }

        return $data;
    }

    /**
     * 通过主键获取sub_key=>val关系数组
     *
     * @param $pri_key
     * @param string $index_field 索引的字段（id或sub_key）
     * @param bool|false $refresh 是否刷新缓存
     * @return array|mixed
     */
    public function getKeyGroupColumn($pri_key, $index_field = 'id', $refresh = false)
    {
        $cache_key = 'site_key_val_cache_column_' . $pri_key;
        $data = $this->di->get('redis')->get($cache_key);
        if (!$data || $refresh) {
            $data = SiteKeyVal::findList(array('`pri_key`="' . $pri_key . '" and enable=1'));
            if ($data) {
                // 允许索引[id,sub_key]
                $index_field = in_array($index_field, ['id', 'sub_key', ""]) ? $index_field : 'id';
                if ($index_field) {
                    $data = array_column($data, 'val', $index_field);
                } else {
                    $data = array_column($data, 'val');
                }
                $this->di->get('redis')->save($cache_key, $data);
            }
        }

        return $data;
    }

    /**
     * 通过id获取
     *
     * @param $id
     * @param $is_object
     * @return mixed
     */
    public function getById($id, $is_object = false)
    {
        $res = SiteKeyVal::findOne(array('id=' . $id . ' and enable=1'));
        return $res;
    }

    /**
     * 通过val获取
     *
     * @param $val
     * @param $key
     * @param $sub_key
     * @return mixed
     */
    public function getByVal($val, $key, $sub_key = null)
    {
        if (!is_null($sub_key)) {
            $res = SiteKeyVal::findOne(['pri_key="' . $key . '" and sub_key="' . $sub_key . '" and val="' . $val . '" and enable=1']);
        } else {
            $res = SiteKeyVal::findOne(array('pri_key="' . $key . '" and val="' . $val . '" and enable=1'));
        }
        return $res;
    }

    //获取缓val值
    public function getCacheValByKey($key, $sub_key, $refresh = false)
    {
        $redis = $this->di->get("redis");
        //强制刷新缓存
        if ($refresh) {
            $res = SiteKeyVal::findOne(['pri_key="' . $key . '" and sub_key="' . $sub_key . '" and enable=1', 'columns' => 'val']);
            $data = [];
            if ($res) {
                $data = $res['val'];
                $redis->hSet(CacheSetting::KEY_SITE_KEY_VAL, $key . "_" . $sub_key, $data);
            }
        } else {
            $data = $redis->hGet(CacheSetting::KEY_SITE_KEY_VAL, $key . "_" . $sub_key);
            if (!$data) {
                $res = SiteKeyVal::findOne(['pri_key="' . $key . '" and sub_key="' . $sub_key . '" and enable=1', 'columns' => 'val']);
                $data = [];
                if ($res) {
                    $data = $res['val'];
                    $redis->hSet(CacheSetting::KEY_SITE_KEY_VAL, $key . "_" . $sub_key, $data);
                }
            }
        }
        return $data ? json_decode($data, true) : [];

    }

    public function setCacheValByKey($key, $sub_key, $data)
    {
        $redis = $this->di->get("redis");
        $redis->hSet(CacheSetting::KEY_SITE_KEY_VAL, $key . "_" . $sub_key, $data);
    }
}