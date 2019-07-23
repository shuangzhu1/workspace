<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/8
 * Time: 15:11
 */

namespace Multiple\Wap\Api;


use Models\User\UserStorage;
use OSS\OssClient;
use Services\Upload\OssManager;
use Upload\Upload;
use Util\Ajax;
use Util\Config;

class UploadController extends ControllerBase
{
    public function imgAction()
    {
        // 初始化配置
        $conf = Config::getSite('upload', 'pic');
        $upload = new Upload();
        $upload->upExt = $conf['ext'];
        $upload->maxAttachSize = $conf['maxAttachSize'];
        $buff = $upload->upOne();
        $file = UserStorage::findOne(["md5 ='" . $buff['md5'] . "'", 'columns' => 'url']);
        if ($file) {
            $this->ajax->outRight($file['url']);
        } else {
            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
            $name = 'register/' . time() . rand(10000, 9999999) . "." . $buff['ext'];
            $res = $oss->putObject(OssManager::BUCKET_USER_AVATOR, $name, $buff['buff']);
            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_USER_AVATOR], OssManager::$bind_domain[OssManager::BUCKET_USER_AVATOR], $res['info']['url']);
                $data = ['md5' => $buff['md5'], 'url' => $url, 'count' => 1, 'created' => time(), 'url_md5' => md5($url)];
                UserStorage::insertOne($data);
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "头像上传失败");
            }
        }
        if ($this->request->get('from') == 'xheditor') {
            echo $msg = "{'msg':'" . $url . "','err':''}"; //id参数固定不变，仅供演示，实际项目中可以是数据库ID
            exit;
        }
        $this->ajax->outRight($url);
        //$this->ajax->outRight(array('url' => $url, 'name' => $buff['name'], 'ext' => $buff['ext']));
    }
}