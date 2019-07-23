<?php

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/9
 * Time: 16:26
 */
namespace Services\System;

use Components\PhpReader\IniReader;

class MysqlBackUp
{
    //配置文件路径
    private static $config_path = "/data/shell/mysql/config/main.ini";
    //备份文件路径
    private static $backup_file_path = "/data/shell/mysql/backup/";
    //备份脚本文件
    private static $backup_sh_path = "/data/shell/mysql/backup.sh";
    //恢复脚本文件
    private static $revocery_sh_path = "/data/shell/mysql/recovery.sh";
    //log文件目录
    private static $log_path = "/data/shell/mysql/log/";

    private static $instance = null;
    private static $config = [
        'db' => [
            'host' => 'localhost',
            'user' => '',
            'password' => '',
            'databases' => 'db1 db2'
        ]
    ];
    private $error_msg = [];

    public function __construct($config_path = '', $back_path = '')
    {
        if ($config_path) {
            self::$config_path = $config_path;
        }
        if ($back_path) {
            self::$backup_file_path = $back_path;
        }
    }

    public static function init($config_path = '', $back_path = '')
    {
        if (!self::$instance) {
            self::$instance = new self($config_path, $back_path);
        }
        return self::$instance;
    }

    //删除备份文件
    /**
     * @param $name
     * @return bool
     */
    public function removeBackupFile($name)
    {
        if (!file_exists(self::$backup_file_path . $name)) {
            $this->error_msg[] = '文件不存在';
            return false;
        }
        if (!unlink(self::$backup_file_path . $name)) {
            $this->error_msg[] = '删除文件失败';
            return false;
        }
        return true;
    }

    //恢复备份
    public function recoveryBackupFile($name)
    {
        exec("sh " . self::$revocery_sh_path . " " . self::$backup_file_path . $name . " >>" . self::$log_path . date('Y-m-d_H_i_s') . ".log 2>&1", $output, $return_val);
        return true;
    }

    //生成备份文件
    public function createBackupFile()
    {
        exec("sh " . self::$backup_sh_path . " >>" . self::$log_path . date('Y-m-d_H_i_s') . ".log 2>&1", $output, $return_val);
        return true;
    }

    //更新配置文件
    /**
     * @param $data
     * @return int
     */
    public function updateSetting($data)
    {
        $reader = new IniReader("/data/shell/mysql/config/main.ini");
        $res = $reader->writeFile($data);
        if (!$res) {
            $this->error_msg = $reader->getErrMsg();
        }
        return $res;
    }

    /**获取错误信息
     * @return array
     */
    public function getErrMsg()
    {
        return $this->error_msg;
    }


}