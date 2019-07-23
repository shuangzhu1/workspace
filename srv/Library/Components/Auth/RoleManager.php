<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 4/26/15
 * Time: 5:50 PM
 */

namespace Components\Auth;


class RoleManager
{
    //管理员角色：1 超级管理员， 2 财务管理员 3 客服管理员 4 编辑管理员 5 社区管理员
    const ROLE_SUPPER = '1'; // 超级管理员
    const ROLE_FINANCE = '2'; // 财务管理员
    const ROLE_SERVICE = '3'; // 客服管理员
    const ROLE_EDITOR = '4'; // 编辑管理员
    const ROLE_FORUM = '5'; // 社区管理员


    public static $_role_map = array(
        self::ROLE_SUPPER => '超级管理',
        self::ROLE_FINANCE => '财务管理',
        self::ROLE_SERVICE => '客服管理员',
        self::ROLE_EDITOR => '内容编辑',
        self::ROLE_FORUM => '社区管理',
    );

    public function checkAuth()
    {

    }

    public static function getCurrentPermission($module, $controller, $action)
    {

    }
}