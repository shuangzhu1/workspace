<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/28
 * Time: 16:46
 */

namespace Multiple\Panel\Plugins;


use Models\Admin\AdminGroupRight;
use Models\Admin\AdminMenus;
use Models\Admin\AdminRight;
use Phalcon\Mvc\User\Plugin;
use Util\Ajax;

class AdminPrivilege extends Plugin
{
    static $LEVEL_SUPER_MAN = 1;//超级管理员
    static $LEVEL_ADMIN = 2;//管理员
    static $LEVEL_NORMAL = 3;//普通用户
    static $instance = null;
    private static $file_menus = [];

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function syncMenu()
    {
        $controllers = ROOT . "/Apps/Panel/Controllers";
        $apis = ROOT . "/Apps/Panel/Api";
        $this->parseFolder($controllers, 'panel');
        $this->parseFolder($apis, 'api', '');
        # 数据库里面的
        $db_menus = AdminMenus::findList();
        $all_menus = [];
        foreach ($db_menus as $menu) {
            $all_menus[$menu['mvc_uri']] = $menu;
        }
        $file_menus_key = array_keys(self::$file_menus);
        $all_menus_key = array_keys($all_menus);
        $undo_db = array_diff($file_menus_key, $all_menus_key);
        $none_db = array_diff($all_menus_key, $file_menus_key);
        # 删除
        if ($none_db) {
            AdminMenus::remove('mvc_uri in ("' . implode('","', $none_db) . '")');
        }
        # 新增
        if ($undo_db) {
            foreach ($undo_db as $mvc_uri) {
                $data = self::$file_menus[$mvc_uri];
                # 信息未完善，隐藏先
                $data['is_hide'] = 1;
                $data['id'] = null;
                $data['mvc_uri'] = $mvc_uri;
                AdminMenus::insertOne($data);
            }
        }

        # 更新
        foreach ($file_menus_key as $_update_uri) {
            $ftitle = self::$file_menus[$_update_uri]['title'];
            if ($ftitle != $all_menus[$_update_uri]['title']) {
                AdminMenus::updateOne(['title' => $ftitle], 'mvc_uri="' . $_update_uri . '"');
            }
        }
    }

    private function parseFolder($folder, $module = 'admin', $api = '')
    {
        // Open a known directory, and proceed to read its contents
        if (is_dir($folder)) {
            if ($dh = opendir($folder)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." && $file != "..") {
                        if (is_dir(realpath($folder . '/' . $file))) {
                            $this->parseSubFolder($folder . '/' . $file, $module, $api, $file);
                        } else {
                            $this->parseMvc($folder . '/' . $file, $module, $api);
                        }
                    }
                }
                closedir($dh);
            }
        }
    }

    private function parseSubFolder($folder, $module, $api, $sub)
    {
        // Open a known directory, and proceed to read its contents
        if (is_dir($folder)) {
            if ($dh = opendir($folder)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." && $file != "..") {
                        $this->parseMvc($folder . '/' . $file, $module, $api, $sub);
                    }
                }
                closedir($dh);
            }
        }
    }


    private function parseMvc($file, $module, $api = '', $sub = '')
    {
        $file_cont = file_get_contents($file);
        $contrName = pathinfo($file, PATHINFO_FILENAME);
        if (strpos($contrName, 'Controller') !== false) {
            $contrName = lcfirst(str_replace('Controller', '', $contrName));

            // 所有action,名称(防止贪婪)
            preg_match_all('/public\s+function\s+([a-zA-Z][a-zA-Z0-9_]+)Action(\s*\(\s*\)\s*(#(.+)#)+)/U', $file_cont, $matches);
            if (isset($matches[1])) {
                $_actions = $matches[1];
                $_title = $matches[4];
                $sub_path = $sub ? '/' . $sub : '';
                foreach ($_actions as $i => $_match) {
                    if ($api) {
                        self:: $file_menus[$module . '/' . $api . $sub_path . '/' . $contrName . '/' . $_match] = array(
                            'module' => $module,
                            'api' => $api,
                            /*       'sub' => $sub,*/
                            'controller' => $contrName,
                            'action' => $_match,
                            'title' => $_title[$i],
                        );
                    } else {
                        self:: $file_menus[$module . $sub_path . '/' . $contrName . '/' . $_match] = array(
                            'module' => $module,
                            /*     'sub' => $sub,*/
                            'controller' => $contrName,
                            'action' => $_match,
                            'title' => $_title[$i],
                        );
                    }
                }
            }
        }
    }

    public function checkApiPermission()
    {
        $admin = $this->session->get('admin');
        //超级管理员
        if ($admin['level'] == self::$LEVEL_SUPER_MAN) {
            return;
        }
        $mvc_uri = substr($this->router->getRewriteUri(), 1);
        if (!AdminMenus::exist("mvc_uri='" . $mvc_uri . "'")) {
            return;
        }
        $perm = AdminRight::exist('uid=' . $admin['id'] . ' and mvc_uri="' . $mvc_uri . '"');
        if ($perm) {
            if ($perm['right_type'] == 0) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "权限不足");
            }
        } else {
            if (!AdminGroupRight::exist("group_id=" . $admin['group'] . ' and mvc_uri="' . $mvc_uri . '"')) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "权限不足");
            }
        }
    }
}