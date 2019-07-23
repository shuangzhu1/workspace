<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/29
 * Time: 11:35
 */

namespace Services\User;


use Models\Site\SiteSkins;
use Models\User\UserSkin;
use Phalcon\Mvc\User\Plugin;

class SkinManager extends Plugin
{
    const TYPE_THEME_SKIN = 'theme_skin';//主题皮肤
    const TYPE_CHAT_BUBBLE = 'chat_bubble';//聊天气泡
    const TYPE_THEME_COVER = 'theme_cover';//主题背景

    //皮肤类型
    public static $type = [
        self::TYPE_THEME_SKIN,
        self::TYPE_CHAT_BUBBLE,
        self::TYPE_THEME_COVER,
    ];
    //类型对应字段
    public static $type_column = [
        self::TYPE_THEME_SKIN => 1,
        self::TYPE_CHAT_BUBBLE => 2,
        self::TYPE_THEME_COVER => 3,
    ];

    //设置皮肤
    public static function setSkin($uid, $type, $code)
    {

        $user_skin = UserSkin::findOne(['user_id=' . $uid]);
        $type_column = self::$type_column[$type];
        //设置过皮肤
        if ($user_skin) {
            //以前设置的和当前相同 返回true
            if ($user_skin[$type_column] == $code) {
                return true;
            }
            $site_skin = SiteSkins::findOne(['type="' . $type_column . '" and code="' . $code . '"']);
            if (!$site_skin) {
                return false;
            }
            //
            if ($site_skin['enable'] == 0) {
                return false;
            }
            //vip检测 //暂不做处理
            /*  if ($site_skin->is_vip == 1) {
                  return false;
              }*/
            if (UserSkin::updateOne([$type => $code, 'modify' => time()], ['id' => $user_skin['id']])) {
                return true;
            }
        } else {
            $site_skin = SiteSkins::findOne(['type="' . $type_column . '" and code="' . $code . '"']);
            if (!$site_skin) {
                return false;
            }
            //
            if ($site_skin['enable'] == 0) {
                return false;
            }
            //vip检测 //暂不做处理
            /*  if ($site_skin->is_vip == 1) {
                  return false;
              }*/
            $user_skin = new UserSkin();
            if ($user_skin->insertOne(['user_id' => $uid, $type => $code, 'created' => time()])) {
                return true;
            }
        }
        return false;

    }

    //获取皮肤
    public static function getSkin($uid, $type = '')
    {
        if ($type) {
            $res = [$type => ''];
            $column = $type;
        } else {
            $res = ["theme_skin" => '', 'chat_bubble' => '', 'theme_cover' => ''];
            $column = 'theme_skin,chat_bubble,theme_cover';
        }
        $user_skin = UserSkin::findOne(['user_id=' . $uid, 'columns' => $column]);
        if ($user_skin) {
            $res = $user_skin;
        }
        return $res;

    }

}