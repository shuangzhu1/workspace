<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/16
 * Time: 16:14
 */

namespace Multiple\Wap\Controllers;


use Models\Other\OutsideUrlVisit;
use Services\Site\CacheSetting;
use Util\Uri;
use Util\Validator;

class LinkController extends ControllerBase
{
    static $white_host = [
        'wap.klgwl.com',
        '120.78.182.253:8182'
    ];

    //链接地址
    public function toAction()
    {
        //非自己app访问
        if (!strpos($this->request->getUserAgent(), 'KLG')) {
            exit();
        }
        $url = ($this->request->get("url", 'string', ''));
        if (!$url) {
            exit;
        }
        $url = urldecode($url);
        if (!Validator::validateUrl($url)) {
            exit;
        }
        $uri = new Uri();
        //自己访问自己 死循环
        if ($uri->fullUrl() == $url) {
            exit;
        }
        //记录访问次数 todo 改成异步
        $md5 = md5($url);
        if (OutsideUrlVisit::exist("url_md5='" . $md5 . "'")) {
            OutsideUrlVisit::updateOne(["visit_count" => "visit_count+1"], "url_md5='" . $md5 . "'");
        } else {
            OutsideUrlVisit::insertOne(["visit_count" => "1", "created" => time(), 'url' => $url, 'url_md5' => $md5]);
        }

        $request_uri = parse_url($url);
        $host = $request_uri['host'];
        //检测白名单
        if (in_array($host, self::$white_host)) {
            $this->response->redirect($url);
        }
        //检测黑名单

        $redis = $this->di->get('redis');
        if ($redis->hGet(CacheSetting::KEY_URL_SHIELD, $host)) {
            $this->response->redirect('link/w110')->send();
        } else {
            $this->response->redirect($url)->send();
        }
        exit;
        // $this->di->get('redis')->hSet(CacheSetting::KEY_URL_SHIELD,)
    }

    public function w110Action()
    {
        if (!strpos($this->request->getUserAgent(), 'KLG')) {
            exit();
        }
        $this->view->title = "提示";
    }
}