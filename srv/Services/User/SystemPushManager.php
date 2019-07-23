<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/18
 * Time: 14:51
 */

namespace Services\User;


use Phalcon\Mvc\User\Plugin;
use Services\Im\SysMessage;

class SystemPushManager extends Plugin
{
    private static $instance = null;

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //新手教程
    public function NewbieTutorial($uid)
    {
        //推送新手教程
        $guide = [
            'title' => '一份新手指引教您玩转恐龙谷~',
            'link' => 'http://wap.klgwl.com/article/material/MaT2UxynNdT2Qwy-ODA3MDU4NjE-',
            'thumb' => 'http://circleimg.klgwl.com/material/15214579736244385_s_431x270.jpg'
        ];
        SysMessage::init()->initMsg(SysMessage::TYPE_SYSTEM_PUSH, ['to_user_id' => json_encode([$uid]), 'msg' => '新手指引', 'ext' => json_encode($guide), 'tpl_type' => 2]);
    }
}