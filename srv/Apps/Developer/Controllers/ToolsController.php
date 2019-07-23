<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/29
 * Time: 17:25
 */

namespace Multiple\Developer\Controllers;


use Components\PhpReader\IniReader;
use Multiple\Api\Merchant\Helper\Ajax;
use Services\Site\CacheSetting;
use Services\User\UserStatus;
use Util\ImgSize;

class ToolsController extends ControllerBase
{
    public function indexAction()
    {
        $redis = $this->getDI()->get('redis');
        $admin = $this->admin;
        $token = UserStatus::getInstance()->getRandToken($admin['id']);
        $redis->hset(CacheSetting::KEY_DEVELOPER_DEBUG_TOKEN, $admin['id'], json_encode(['token' => $token, 'expire' => time() + 3600, 'uid' => $this->admin['id'], 'name' => $this->admin['name']], JSON_UNESCAPED_UNICODE));//一小时有效期
        //Ajax::init()->outRight($token);
    }


    public function debugTokenAction()
    {
        $redis = $this->getDI()->get('redis');
        if ($this->request->isPost())//获取新token
        {
            $admin = $this->admin;
            $token = UserStatus::getInstance()->getRandToken(time());
            $redis->hSet(CacheSetting::KEY_DEVELOPER_DEBUG_TOKEN, $admin['id'], json_encode(['token' => $token, 'expire' => time() + 3600, 'uid' => $this->admin['id'], 'name' => $this->admin['name']], JSON_UNESCAPED_UNICODE));//一小时有效期
            Ajax::init()->outRight($token);
        }
        $tokens = $redis->hGetAll(CacheSetting::KEY_DEVELOPER_DEBUG_TOKEN);
        foreach ($tokens as $k => &$v) {
            $v = json_decode($v, true);
            if ($v['expire'] <= time())//过期删除
                $redis->hDel(CacheSetting::KEY_DEVELOPER_DEBUG_TOKEN, $k);
        }
        foreach ($tokens as $token) {
            $expire[] = $token['expire'];
        }
        array_multisort($expire, SORT_DESC, $tokens);
        $this->view->setVar('tokens', $tokens);
    }

    //黑名单列表
    public function ipBlacklistAction()
    {
        $redis = $this->di->get("redis_behavior");
        $ip = $this->request->get("ip", 'string', '');
        if ($ip && preg_match('/^(([\d]){1,3}\.){3}[\d]{1,3}$/', $ip)) {
            $res = $redis->hGet(CacheSetting::KEY_IP_BLACKLIST, $ip);
            $res = $res ? [$ip => $res] : [];
        } else {
            $res = ($redis->hGetAll(CacheSetting::KEY_IP_BLACKLIST));
        }
        $this->view->setVar('ips', $res);
        $this->view->setVar('ip', $ip);
    }

    //api频控配置
    public function apiRateAction()
    {
        $config = json_decode(file_get_contents(ROOT . "/Data/site/api.json"), true);
        $this->view->setVar('config', $config);
        //var_dump($config);
    }

    //域名黑名单
    public function urlShieldAction()
    {
        $redis = $this->di->get('redis');
        // var_dump($redis->hSet(CacheSetting::KEY_URL_SHIELD, "www.baidu.com", time()));
        // exit;
        $host = $this->request->get("host", 'string', '');
        if ($host && preg_match('/^([a-zA-Z0-9]+)(\.[a-zA-Z0-9]+)*\.([a-zA-Z]{2,})$/', $host)) {
            $res = $redis->hGet(CacheSetting::KEY_URL_SHIELD, $host);
            $res = $res ? [$host => $res] : [];
        } else {
            $res = ($redis->hGetAll(CacheSetting::KEY_URL_SHIELD));
        }
        $this->view->setVar('hosts', $res);
        $this->view->setVar('host', $host);
    }

    //数据库备份
    public function dbBackupAction()
    {
        $reader = new IniReader("/data/shell/mysql/config/main.ini");
        $conf = $reader->readFile();
        $result = [];
        $path = "/data/shell/mysql/backup/";
        $dh = opendir($path);
        if ($dh) {
            while ((($file = readdir($dh)) !== false)) {
                if ($file != '.' && $file != '..' && $file != '.gz') {
                    //目录
                    if (is_dir($path . '/' . $file)) {
                    } else {
                        $result[] = ['name' => $file, 'size' => ImgSize::format_bytes(filesize($path . "/" . $file)), 'time' => filemtime($path . "/" . $file)];
                    }
                }
                // var_dump($file);
            }
            closedir($dh);
        } else {
            echo "打开文件夹失败";
            exit;
        }
        if ($result) {
            array_multisort(array_column($result, 'name'), SORT_DESC, $result);
        }
        $this->view->setVar('list', $result);
        $this->view->setVar('conf', $conf);
    }

    //业务服务器
    public function sign1Action()
    {

    }

    //红包服务器
    public function sign2Action()
    {

    }

    //开放平台
    public function sign3Action()
    {

    }
}