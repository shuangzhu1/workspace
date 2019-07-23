<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/9
 * Time: 11:06
 */

namespace Multiple\Developer\Api;


use Components\PhpReader\IniReader;
use Services\Admin\AdminLog;
use Services\System\MysqlBackUp;
use Util\Ajax;
use Util\Debug;

class DbController extends ApiBase
{
    //设置配置信息
    public function settingAction()
    {
        $host = $this->request->get("host", 'string', '');
        $user = $this->request->get("user", 'string', '');
        $password = $this->request->get("password", 'string', '');
        $databases = $this->request->get("databases", 'string', '');
        if (!$host) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "主机名不能为空");
        }
        if (!$user) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "用户名为不能空");
        }
        if (!$password) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "密码不能为空");
        }
        if (!$databases) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "需要备份的数据库不能为空");
        }
        $backup = MysqlBackUp::init();
        $res = $backup->updateSetting(['db' => ['host' => $host, 'user' => $user, 'password' => $password, 'databases' => $databases]]);
        if ($res) {
            Ajax::outRight("编辑成功");
        }
        Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $backup->getErrMsg()[0]);
    }

    //**删除备份文件
    public function removeAction()
    {
        $file = $this->request->get("file", 'string', '');
        $backup = MysqlBackUp::init();
        $res = $backup->removeBackupFile($file);
        if (!$res) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $backup->getErrMsg()[0]);
        } else {
            Debug::log("删除数据库备份：【后台管理员" . $this->session->get('admin')['id'] . "】：【" . $file . '】', 'backup');

            Ajax::outRight("删除成功");
        }
    }

    //恢复备份
    public function recoveryAction()
    {
        $file = $this->request->get("file", 'string', '');
        $backup = MysqlBackUp::init();
        $res = $backup->recoveryBackupFile($file);
        if (!$res) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $backup->getErrMsg()[0]);
        } else {
            Ajax::outRight("恢复请求发送成功");
        }
    }

    //生成备份
    public function backupAction()
    {
        $backup = MysqlBackUp::init();
        $res = $backup->createBackupFile();
        if (!$res) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $backup->getErrMsg()[0]);
        } else {
            Ajax::outRight("生成备份请求发送成功");
        }
    }
}