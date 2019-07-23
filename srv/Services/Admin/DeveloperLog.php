<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/13
 * Time: 9:26
 */

namespace Services\Admin;


use Models\Developer\AdminLogs;
use Models\Developer\Admins;
use Phalcon\Mvc\User\Plugin;

class DeveloperLog extends Plugin
{
    private static $instance = null;
    const TYPE_API_LOG = 'api_log'; //api日志
    const TYPE_LOGIN = 'login'; //登录

    const STATUS_DELETED = 0;//被删除
    const STATUS_NORMAL = 1;//正常
    const STATUS_LOCKED = 2;//被禁用


    public static $type_name = [
        self::TYPE_LOGIN => '登录',
        self::TYPE_API_LOG => 'api日志'
    ];

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**添加日志
     * @param string $param
     * @param string $action
     * @param string $type
     * @param string $item_id
     * @param array $data
     */
    public function add($action = '', $type, $item_id, $param = '', $data = [])
    {
        $data = array(
            'uid' => $this->session->get('admin')['id'],
            'user_name' => $this->session->get('admin')['name'],
            'api' => $this->request->getURI(),
            'param' => json_encode($param, JSON_UNESCAPED_UNICODE),
            'action' => $action,
            'type' => $type,
            'item_id' => $item_id,
            'created' => time(),
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $log = new AdminLogs();
        $log->insertOne($data);
        unset($param);
    }

    /*获取相关日志*/

    public function getLogs($type, $item_id)
    {
        $logs = AdminLogs::findList(['type="' . $type . '" and  LOCATE("' . $item_id . ',",concat(item_id,","))>0', 'order' => 'created desc']);
        if ($logs) {
            $uids = array_column($logs, 'uid');
            $admins = Admins::getByColumnKeyList(['id in (' . implode(',', $uids) . ')', 'columns' => 'id,name'], 'id');
            foreach ($logs as &$item) {
                $item['admin_info'] = isset($admins[$item['uid']]) ? $admins[$item['uid']] : [];
            }
        }
        return $logs;
    }

    public function getFolder($path = 'Cache')
    {
        // Open a known directory, and proceed to read its contents
        $folder = [];
        $files = [];
        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." && $file != "..") {
                        if (is_dir(realpath($path . '/' . $file))) {
                            $folder[] = array(
                                'path' => $path . '/' . $file,
                                'name' => $file,
                            );
                        } else {
                            $files[] = array(
                                'file' => $path . '/' . $file,
                                'name' => $file,
                            );
                        }
                    }
                }
                closedir($dh);
            }
        }

        return array('files' => $files, 'folders' => $folder);
    }

}