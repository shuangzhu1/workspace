<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/13
 * Time: 11:15
 */

namespace Multiple\Panel\Api;


use Models\Site\SiteAds;
use Models\Site\SiteAdsApplication;
use Models\User\Users;
use OSS\OssClient;
use Services\Admin\AdminLog;
use Services\Im\SysMessage;
use Services\Site\AdvertiseManager;
use Services\Site\CacheSetting;
use Util\Ajax;
use Util\Debug;
use Util\ImgSize;

class AdsController extends ApiBase
{
    /*  //添加广告
      public function addAction()
      {
          $data = $this->request->get('data_base', 'string', '');
          $ads_key = $data['ads_key']; //广告关键字
          $thumb = $data['thumb']; //广告图
          $title = $data['title']; //广告标题
          $link = $data['link']; //广告链接

          if (!$ads_key) {
              $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
          }
          $ads = SiteAds::findOne("ads_key='" . $ads_key . "'");
          if ($ads) {
              SiteAdsApplication::insertOne([
                  'ads_key' => $ads_key,
                  'content' => json_encode(['img' => $thumb, 'title' => $title, 'link' => $link], JSON_UNESCAPED_UNICODE),
                  'content_type' => $ads['content_type'],
                  'created' => time()]);
              AdvertiseManager::init()->getAdList($ads_key, true); //更新缓存

              //发im消息
              $this->sendMessage($ads_key);
              $this->ajax->outRight();
          } else {
              $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
          }

      }*/

    //添加app广告
    public function addAppAction()
    {
        $data = $this->request->get('data_base', 'string', '');
        $ads_key = $data['ads_key']; //广告关键字
        $thumb = $data['thumb']; //广告图
        $title = $data['title']; //广告标题
        $sort = $data['sort']; //广告标题

        $content_type = $data['content_type']; //广告模型
        $content_value = $data['content_value']; //广告内容

        if (!$ads_key) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
        }
        $ads = SiteAds::findOne("ads_key='" . $ads_key . "'");
        if ($ads) {
            $data = [
                'ads_key' => $ads_key,
                'content' => json_encode(['img' => $thumb, 'title' => $title, 'type' => $content_type, 'value' => $content_value], JSON_UNESCAPED_UNICODE),
                'content_type' => $content_type,
                'sort' => $sort ? $sort : 50,
                'created' => time()];
            $id = SiteAdsApplication::insertOne($data);
            AdvertiseManager::init()->getAdList($ads_key, true); //更新缓存

            //发im消息
            //$this->sendMessage($ads_key);
            AdminLog::init()->add('添加广告', AdminLog::TYPE_ADS, $id, array('type' => "update", 'id' => $id, 'data' => $data));

            $this->ajax->outRight();
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
        }

    }

    /* //更新广告
     public function updateAction()
     {
         $data = $this->request->get('data_base', 'string', '');
         $thumb = $data['thumb']; //广告图
         $title = $data['title']; //广告标题
         $link = $data['link']; //广告链接
         $id = $this->request->get('id', 'int', 0);
         if (!$id) {
             $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
         }
         $ads = SiteAdsApplication::findOne("id='" . $id . "'");
         if ($ads) {
             SiteAdsApplication::updateOne([
                 'content' => json_encode(['img' => $thumb, 'title' => $title, 'link' => $link], JSON_UNESCAPED_UNICODE),
                 'content_type' => $ads['content_type'],
                 'modify' => time()], ["id" => $id]);
             AdvertiseManager::init()->getAdList($ads['ads_key'], true); //更新缓存

 //发im消息
             //发im消息
             $this->sendMessage($ads['ads_key']);
             $this->ajax->outRight();
         } else {
             $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "广告不存在");
         }

     }*/

    //更新app广告
    public function updateAppAction()
    {
        $data = $this->request->get('data_base', 'string', '');
        $ads_key = $data['ads_key']; //广告关键字
        $thumb = $data['thumb']; //广告图
        $title = $data['title']; //广告标题
        $sort = $data['sort']; //排序
        $content_type = $data['content_type']; //广告模型
        $content_value = $data['content_value']; //广告内容
        $id = $this->request->get('id', 'int', 0);
        if (!$id) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
        }
        $ads = SiteAdsApplication::findOne("id='" . $id . "'");
        if ($ads) {
            $data = [
                'content' => json_encode(['img' => $thumb, 'title' => $title, 'type' => $content_type, 'value' => $content_value], JSON_UNESCAPED_UNICODE),
                'content_type' => $content_type,
                'sort' => $sort ? $sort : 50,
                'modify' => time()];
            SiteAdsApplication::updateOne($data, ["id" => $id]);
            AdvertiseManager::init()->getAdList($ads['ads_key'], true); //更新缓存

//发im消息
            //发im消息
            //$this->sendMessage($ads['ads_key']);
            AdminLog::init()->add('更新广告', AdminLog::TYPE_ADS, $id, array('type' => "update", 'id' => $id, 'data' => $data));

            $this->ajax->outRight();
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "广告不存在");
        }

    }

    //更新广告状态
    public function enableAction()
    {
        $enable = $this->request->get("enable", 'int', 0);
        $id = $this->request->get('id', 'int', 0);
        if (!$id) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
        }
        $ads = SiteAdsApplication::findOne("id='" . $id . "'");
        if ($ads) {
            SiteAdsApplication::updateOne([
                'status' => $enable,
                'modify' => time()], ["id" => $id]);
            AdvertiseManager::init()->getAdList($ads['ads_key'], true); //更新缓存

            //发im消息
            //$this->sendMessage($ads['ads_key']);
            AdminLog::init()->add('更新广告状态', AdminLog::TYPE_ADS, $id, array('type' => "update", 'id' => $id, 'data' => $enable));

            $this->ajax->outRight();
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "广告不存在");
        }

    }

    //删除广告
    public function delAction()
    {
        $id = $this->request->get('id', 'int', 0);

        if (!$id) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
        }
        $ads = SiteAdsApplication::findOne('id=' . $id);
        if (SiteAdsApplication::remove('id=' . $id)) {
            AdvertiseManager::init()->getAdList($ads['ads_key'], true); //更新缓存
           // $this->sendMessage($ads['ads_key']);
            AdminLog::init()->add('删除广告', AdminLog::TYPE_ADS, $id, array('type' => "del", 'id' => $id));

            $this->ajax->outRight();
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "删除失败");
    }

    public function sendMessage($key)
    {
        //发im消息
        $i = 1;
        while ($ids = Users::getColumn(['status=1 and user_type=1', 'order' => 'created desc', 'columns' => 'id', 'offset' => ($i - 1) * 200, 'limit' => 200], 'id')) {
            SysMessage::init()->initMsg(SysMessage::TYPE_ADS_UPDATE, ["to_user_id" => $ids, 'ads_key' => $key]);
            $i++;
        }
    }

    //首页弹窗广告
    public function operateAction()
    {
        $id = $this->request->get('id');
        $action = $this->request->get('action');
        $name = $this->request->get('name','string','');
        $btn_name = $this->request->get('btn_name','string','');
        $start = strtotime($this->request->get('start','string',''));
        $end = strtotime($this->request->get('end','string','')) + 86399;
        $cover = $this->request->get('cover','string','');
        $content = $this->request->get('content','string','');
        switch( $action )
        {
            case 'set_interval' :
                $interval = $this->request->get('interval');
                $res = $this->di->get('redis')->originalSet(CacheSetting::KEY_THE_INTERVAL_APPEAR_NEW_YEAR_AD,$interval);
                break;
            case 'del' ://删除
                $res = $this->original_mysql->execute('update site_new_year_ad set enable = 0 where id = ' . $id);
                break;
            case 'add' ://添加
                $config = $this->di->get('config')->oss;
                $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
                foreach(['cover' => $cover,'content' => $content] as $k => $v)
                {
                    $img = ImgSize::getBase64ImgBlob($v);
                    $obj_name = 'ads/' . md5(uniqid()) . time().  "." . $img[1];

                    $res = $oss->putObject("klg-useravator", $obj_name, $img[0]);

                    if( $res['info']['http_code'] == 200 )
                    {
                        $$k = $res['info']['url'];

                    }
                    else
                    {
                        Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'图片上传失败，请重试');
                    }
                }
                $res = $this->original_mysql->execute("insert into site_new_year_ad(name,btn_name,cover,content_img,period_start,period_end,created) values('$name','$btn_name','$cover','$content',$start,$end," . time() . ")");
                break;
            case 'edit' ://编辑
                $config = $this->di->get('config')->oss;
                $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
                foreach(['cover' => $cover,'content' => $content] as $k => $v)
                {
                    if( strpos($v,'data:image') !== false )
                    {
                        $img = ImgSize::getBase64ImgBlob($v);
                        $obj_name = 'ads/' . md5(uniqid()) . time().  "." . $img[1];

                        $res = $oss->putObject("klg-useravator", $obj_name, $img[0]);

                        if( $res['info']['http_code'] == 200 )
                        {
                            $$k = $res['info']['url'];
                        }
                        else
                        {
                            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'图片上传失败，请重试');
                        }
                    }

                }

                $res = $this->original_mysql->execute("update site_new_year_ad set name ='$name',btn_name = '$btn_name',cover = '$cover',content_img = '$content',period_start = $start,period_end = $end where id = $id");
                break;
        }


        if($res)
            Ajax::init()->outRight();
        else
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG);
    }
}