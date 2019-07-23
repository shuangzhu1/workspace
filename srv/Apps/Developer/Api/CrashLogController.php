<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/5
 * Time: 18:20
 */

namespace Multiple\Developer\Api;


use OSS\OssClient;

class CrashLogController extends ApiBase
{
    public function loadAction()
    {
        $prefix = $this->request->get("prefix", 'string', '');
        $max_keys = $this->request->get("max_keys", 'int', 20);

        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        $res = $oss->listObjects('klg-common',['prefix' => $prefix,'max-keys'=>$max_keys,'']);
        $folder = $res->getPrefixList();//获取文件夹列表
        $oss->listObjects('klg-chatimg', ['prefix' => $prefix])->getNextMarker();

    }
}