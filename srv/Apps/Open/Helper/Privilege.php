<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/11
 * Time: 10:04
 */

namespace Multiple\Open\Helper;


use Models\Customer\CustomerGame;

class Privilege
{
    private static $instance = null;
    private static $app_id = 0;

    public static $privilege = [
        "user_getUserInfo" => 1,
        "user_getFriend" => 2,
        "user_getGroup" => 3,

    ];
    public static $privilege_name = [
        1 => "获取你的公开信息(昵称，头像等)",
        2 => "获取好友列表",
        3 => "获取群聊列表"

    ];

    public static function __construct($app_id)
    {
        self::$app_id = $app_id;
    }

    public static function init($app_id)
    {
        if (!self::$instance) {
            self::$instance = new self($app_id);
        }
        return self::$instance;
    }

    public function checkPrivilege($controller, $action)
    {
        $game = CustomerGame::findOne(["app_id='" . self::$app_id . "'", 'columns' => 'privilege']);
        if (!$game) {
            return false;
        }
        //拥有所有权限
        if ($game['privilege'] == 'all') {
            return true;
        }
        //todo
    }
}