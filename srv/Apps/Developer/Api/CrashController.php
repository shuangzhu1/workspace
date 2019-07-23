<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/5
 * Time: 18:20
 */

namespace Multiple\Developer\Api;


use OSS\OssClient;
use Util\Ajax;
use Util\ImgSize;

class CrashController extends ApiBase
{

    public function loadAction()
    {
        $prefix = $this->request->get("prefix", 'string', '');
        $max_keys = $this->request->get("max_keys", 'int', 20);
        $next_marker = $this->request->get("next_marker", 'string', '');

        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);


        $res = $oss->listObjects('klg-common', ['prefix' => $prefix, 'max-keys' => $max_keys, 'marker' => $next_marker]);
        $folder = $res->getPrefixList();//获取文件夹列表
        $file = $res->getObjectList();//获取文件列表
        $next_marker = $res->getNextMarker();

        $folders = [];
        $files = [];

        $data = '';
        $back_tr = '';
        if ($prefix != '') {
            if (strrpos($prefix, '/')) {
                // $prefix = substr($prefix, 0, -1);
                $back = substr($prefix, 0, strrpos($prefix, '/'));
                if (strrpos($back, '/') === false) {
                    $back = '';
                }
                $prefix2 = substr($prefix, 0, -1);
                $back2 = substr($prefix2, 0, strrpos($prefix2, '/') + 1);
                $back_tr = "<tr class='log_back'><td colspan='5'><a class='' href='javascript:;' data-id='" . $back2 . "'><i class='fa fa-undo'></i> &nbsp;返回上一级（" . ($back == '' ? '/' : $back . "/") . "）</a></td></tr>";
            }
        }
        if ($folder) {
            foreach ($folder as $f) {
                $data .= $this->getFromOB('log/partial/crash/folder', ['item' => $f->getPrefix()]);
            }
        }
        if ($file) {
            foreach ($file as $f) {
                $data .= $this->getFromOB('log/partial/crash/file', ['item' => ['name' => $f->getKey(), 'size' => ImgSize::format_bytes($f->getSize()), 'type' => $f->getType(), 'time' => $f->getLastModified()]]);
            }
        }
        if ($next_marker) {
            $data .= "<tr class='load_item'><td colspan='6'><a href='javascript:;' class='loadMore' data-id='" . $next_marker . "'>加载更多</a></td></tr>";
        }
        //  $data = ['folder' => $folders, 'file' => $files, 'next_mark' => $next_marker];
        Ajax::outRight(['data' => $data, 'back_tr' => $back_tr]);

    }

    //删除文件或文件夹
    public function removeAction()
    {
        set_time_limit(0);
        $data = $this->request->get("data");
        if (!$data || (count($data['dir']) == 0 && count($data['file']) == 0)) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        //删除文件
        if ($data['file']) {
            foreach ($data['file'] as $f) {
                $oss->deleteObject('klg-common', $f);
            }
        }
        //删除文件夹
        if ($data['dir']) {
            foreach ($data['dir'] as $item) {
                $this->removeDirAndFile($oss, $item);
            }
        }
        Ajax::outRight("");
    }

    public function removeDirAndFile(OssClient $oss, $prefix)
    {
        $list = $this->getDirAndFile($oss, $prefix);
        if ($list['files']) {
            foreach ($list['files'] as $f) {
                $oss->deleteObject('klg-common', $f);
            }
        }
        if ($list['dirs']) {
            foreach ($list['dirs'] as $d) {
                $this->removeDirAndFile($oss, $d);
            }
        }
    }

    //获取文件夹下 直属的文件夹及文件列表
    public function getDirAndFile(OssClient $oss, $prefix)
    {
        $objects = $oss->listObjects('klg-common', ['prefix' => $prefix]);
        $list = ['dirs' => [], 'files' => []];
        if ($dirs = $objects->getPrefixList()) {
            foreach ($dirs as $d) {
                $list['dirs'][] = $d->getPrefix();
            }
        }
        if ($files = $objects->getObjectList()) {
            foreach ($files as $f) {
                $list['files'][] = $f->getKey();
            }
        }
        if ($marker = $objects->getNextMarker()) {
            $tmp = $this->getDirAndFile($oss, $marker);
            $list['dirs'] = array_merge($list['dirs'], $tmp['dirs']);
            $list['files'] = array_merge($list['files'], $tmp['files']);
        }
        return $list;
    }

    //标记为以解决
    public function checkTagAction()
    {
        set_time_limit(0);
        $path = $this->request->get("path");
        if (!$path) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        $new_path = str_replace(".log", '_resolved.log', $path);
        $new_path = str_replace(".txt", '_resolved.txt', $new_path);

        $oss->copyObject('klg-common', $path, 'klg-common', $new_path);
        $oss->deleteObject('klg-common', $path);

        Ajax::outRight("");

    }
}