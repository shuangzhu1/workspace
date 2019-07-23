<?php
namespace Components\Auth;

use Models\Admin\AdminGroupRight;
use Models\Admin\AdminMenus;
use Models\Admin\AdminRight;
use Models\Admin\Admins;
use Phalcon\Mvc\User\Component;

/**
 * oauth for admin module
 *  ---- use session
 *
 * Class Authentication
 * @package App\Admin\Lib
 */
class Authentication extends Component
{
    public static $instance = null;

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getCurrentPermission($uid, $menu_id)
    {
        $menu = AdminRight::findOne('uid = "' . $uid . '"  and menu_id=' . $menu_id);
        $admin_group = Admins::findOne(['id="' . $uid . '"']);
        if ($admin_group['group']) {
            $group_right = AdminGroupRight::findOne(array('group_id=' . $admin_group['group'] . ' and menu_id=' . $menu_id));
            if ($group_right && $group_right['right_type'] == 1) {
                return true;
            }
        }
        if ($menu) {
            if ($menu['right_type'] == 1) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    // 获取menu_id
    public static function getCurMenuInfo($module, $controller, $action)
    {
        $menu = AdminMenus::findOne(array(
                'module="' . $module . '" and controller="' . $controller . '" and action="' . $action . '"',
                'columns' => 'id,cid,title')
        );
        if ($menu) {
            return $menu;
        }

        return false;
    }

    public static function getMenuInfo($id)
    {
        $menus = AdminMenus::findOne(array(
            'id=' . $id,
        ));

        if ($menus) {
            return $menus;
        }

        return false;
    }

    public static function getCurMenus($cid)
    {
        $menus = AdminMenus::findList(array(
            'cid=' . $cid, 'order' => 'sort asc'
        ));
        if ($menus) {
            return $menus;
        }
        return [];
    }

}