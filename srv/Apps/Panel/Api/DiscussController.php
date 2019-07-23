<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/23
 * Time: 19:47
 */

namespace Multiple\Panel\Api;


use JPush\Config;
use Models\Site\SiteRewardLog;
use Models\Site\SiteStorage;
use Models\Social\SocialComment;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussBillboard;
use Models\Social\SocialDiscussMedia;
use Models\Social\SocialDiscussRecommend;
use Models\Social\SocialDiscussTagFilter;
use Models\User\UserCountStat;
use Models\User\UserInfo;
use Models\User\UserProfile;
use Models\User\Users;
use Models\Virtual\VirtualDiscuss;
use OSS\OssClient;
use Services\Admin\AdminLog;
use Services\Discuss\DiscussManager;
use Services\Discuss\TagManager;
use Services\Im\ImManager;
use Services\Site\CashRewardManager;
use Services\Site\SiteKeyValManager;
use Services\Social\SocialManager;
use Services\Upload\OssManager;
use Services\User\UserStatus;
use Upload\Upload;
use Util\Ajax;
use Util\Debug;
use Util\ImgSize;

class DiscussController extends ApiBase
{
    /*屏蔽动态*/
    public function delAction()
    {
        $id = $this->request->get('data');
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ['status' => DiscussManager::STATUS_SHIELD, 'modify' => time()];

        // $ids = implode(',', $id);
        //更新动态状态
        foreach ($id as $item) {
            $discuss = SocialDiscuss::findOne(['id=' . $item]);
            if ($discuss) {
                SocialDiscuss::updateOne($data, ['id' => $item]);
                AdminLog::init()->add('屏蔽动态', AdminLog::TYPE_DISCUSS, $item, array('type' => "update", 'id' => $item, 'data' => $data));
                if ($discuss['share_original_item_id'] == 0 && $discuss['media_type'] != DiscussManager::TYPE_TEXT) {
                    //更新最新动态相册
                    $profile = UserProfile::findOne(['user_id=' . $discuss['user_id'], 'columns' => 'newest_discuss_pic']);
                    if ($profile['newest_discuss_pic']) {
                        $newest_discuss_pic = json_decode($profile['newest_discuss_pic'], true);
                        $ids = array_unique(array_column($newest_discuss_pic, 'id'));
                        //需要更新动态相册
                        if (in_array($item, $ids)) {
                            DiscussManager::getInstance()->updateNewestDiscussPic($discuss['user_id']);
                        }
                    }
                    //更新动态相册
                    //  $media_count = substr_count($discuss->media, ',') + 1;
                    $this->db->execute("delete from social_discuss_media where discuss_id=" . $item);
                    // $this->db->execute("update user_profile set discuss_media_count=discuss_media_count-" . $media_count . ' where user_id=' . $discuss->user_id);
                }
                if ($discuss['is_top'] == 1) {
                    //删除记录
                    $this->db->execute("delete from social_discuss_top_log where user_id=" . $discuss['user_id'] . ' and discuss_id=' . $item . ' and type="' . $discuss['top_type'] . '"');
                }
                //删除推荐
                SocialDiscussRecommend::remove('discuss_id=' . $item);

                //更新搜索引擎缓存
                DiscussManager::getInstance()->notifySearchPlugin($item);
            }

        }
        $this->ajax->outRight("");

    }

    /*恢复动态*/
    public function recoveryAction()
    {
        $id = $this->request->get('data');
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //更新动态状态

        $data = ['status' => DiscussManager::STATUS_NORMAL, 'modify' => time()];
        $ids = implode(',', $id);
        foreach ($id as $item) {
            $discuss = SocialDiscuss::findOne(['id=' . $item]);
            if ($discuss) {
                SocialDiscuss::updateOne($data, ['id' => $item]);
                AdminLog::init()->add('恢复动态', AdminLog::TYPE_DISCUSS, $item, array('type' => "update", 'id' => $id, 'data' => $data));
                if ($discuss['share_original_item_id'] == 0 && $discuss['media_type'] != DiscussManager::TYPE_TEXT) {
                    //更新最新动态相册
                    DiscussManager::getInstance()->updateNewestDiscussPic($discuss['user_id']);
                    //更新动态相册
                    $media_count = substr_count($discuss['media'], ',') + 1;
                    $discuss_media = new SocialDiscussMedia();
                    $data = ['discuss_id' => $item, 'user_id' => $discuss['user_id'], 'content' => $discuss['content'], 'media' => $discuss['media'], 'is_top' => $discuss['is_top'], 'scan_type' => $discuss['scan_type'], 'scan_user' => $discuss['scan_user'], 'media_type' => $discuss['media_type'], 'media_count' => $media_count, 'created' => $discuss['created'], 'time' => date('Ymd', $discuss['created'])];
                    $discuss_media->insertOne($data);
                    // $this->db->execute("update user_profile set discuss_media_count=discuss_media_count+" . $media_count . ' where user_id=' . $discuss['user_id']);
                }

                //更新搜索引擎缓存
                DiscussManager::getInstance()->notifySearchPlugin($item, 0);
            }

        }
        $this->ajax->outRight("");

    }

    /*发布新动态*/
    public function addAction()
    {
        $tags = $this->request->get('tags', 'string', '');//标签 多个标签以，分割
        //   $is_top = $this->request->get('is_top', 'int', 0);//是否置顶
        $media_type = $this->request->get('media_type', 'int', 1);//1-纯文本 2-视频 3-图片
        $content = trim($this->request->get('content', 'string', ''));//文字内容
        $video = $this->request->get('video', 'string', '');//视频地址
        $videoThumb = $this->request->get('videoThumb', 'string', '');//视频截图

        $open_location = $this->request->get('open_location', 'int', 0);//是否公开位置 0-不公开 1-公开
        $address = $this->request->get('address', 'string', '');//具体地址
        $app_uid = $this->request->get('app_uid', 'int', 0);//用户id
        if (!$app_uid) {
            $app_uid = Users::findOne(['user_type=' . UserStatus::USER_TYPE_ROBOT . " and (id<71041 or id>71078)", 'columns' => 'id,rand() as rand', 'order' => 'rand desc']);// $this->request->get('app_uid', 'int', 0);//app_uid
            $app_uid = $app_uid['id'];
        }
        //
        $lng = $this->request->get('lng', 'string', '');//精度 公开位置才要传
        $lat = $this->request->get('lat', 'string', '');//纬度 公开位置才有传
        $original_media = $this->request->get('media');


        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        if (!$app_uid) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "无效的用户id");
        }
        //纯文本/图片
        if ($media_type == '1' || $media_type == '3') {
            $media = '';//
            //图片
            if ($media_type == 3) {
                if ($original_media == '') {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请选择图片");
                }
                foreach ($original_media as $item) {
                    $item = explode('?', $item);
                    $img = ImgSize::getBase64ImgBlob($item[0]);
                    //检测文件是否存在
                    $md5 = md5($img[0]);
                    if ($url = Upload::checkFile($md5)) {
                        $media .= ',' . $url;
                    } else {
                        $name = $app_uid . '/' . time() . rand(0, 1000) . "_s_" . $item[1] . "." . $img[1];
                        $res = $oss->putObject(OssManager::BUCKET_CIRCLE_IMG, $name, $img[0]);
                        if ($res && !empty($res['info']['url'])) {
                            $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_CIRCLE_IMG], OssManager::$bind_domain[OssManager::BUCKET_CIRCLE_IMG], $res['info']['url']);
                            $media .= ',' . $url;
                            Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $img[1], 'type' => 'img', 'size' => strlen($img[0]), 'name' => $name, 'url' => $url, 'created' => time()]);
                        }
                    }
                }
                $content == '' && $content = "分享图片";
            }
            //纯文本
            if ($media_type == '1') {
                if ($content == '') {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请输入文本内容");
                }
            }
            $media = $media ? substr($media, 1) : '';
            $discuss_id = DiscussManager::getInstance()->adminPublish($app_uid, $media_type, $content, $media, $tags, $open_location, $address, $lng, $lat);
            if (!$discuss_id) {
                $this->ajax->outError(Ajax::FAIL_PUBLISH);
            }
        } //视频
        else {
            //没有缩略图
            if ($videoThumb == '') {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请选择视频缩略图");
            }
            if ($video == '') {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请选择视频");
            }
            //$item = explode('?', $videoThumb);


            //  $img = ImgSize::getBase64ImgBlob($item[0]);
            $media = $videoThumb . "?" . $video;
            //检测文件是否存在

            //检测文件是否存在
            /*  $md5 = md5($img[0]);
              if ($url = Upload::checkFile($md5)) {
                  $media = $url . '?' . $video;
              } else {
                  $name = $app_uid . '/' . time() . rand(0, 1000) . "_s_" . $item[1] . "." . $img[1];
                  $res = $oss->putObject(OssManager::BUCKET_CIRCLE_IMG, $name, $img[0]);
                  if ($res && !empty($res['info']['url'])) {
                      $media = str_replace(OssManager::$original_domain[OssManager::BUCKET_CIRCLE_IMG], OssManager::$bind_domain[OssManager::BUCKET_CIRCLE_IMG], $res['info']['url']) . "?" . $video;
                      $url = $res['info']['url'];
                      Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $img[1], 'type' => 'img', 'size' => strlen($img[0]), 'name' => $name, 'url' => $url, 'created' => time()]);
                  }
              }*/
            if (!$media || !$discuss_id = DiscussManager::getInstance()->adminPublish($app_uid, $media_type, $content, $media, $tags, $open_location, $address, $lng, $lat)) {
                $this->ajax->outError(Ajax::FAIL_PUBLISH);
            }
            $content == '' && $content = "分享视频";
        }
        $this->db->begin();
        $this->original_mysql->begin();
        $time = time();
        $data = [
            'user_id' => $app_uid,
            'admin_id' => $this->admin['id'],
            'created' => $time,
            'discuss_id' => $discuss_id
        ];
        /*  if ($tags) {
              $data['tags_name'] = TagManager::getInstance()->getTagNames($tags);
          }*/
        $virtual_discuss = new VirtualDiscuss();
        $id = $virtual_discuss->insertOne($data);
        AdminLog::init()->add('发布动态', AdminLog::TYPE_DISCUSS, $discuss_id, array('type' => "add", 'id' => $discuss_id, 'data' => []));
        $this->db->commit();
        $this->original_mysql->commit();


        $this->ajax->outRight($discuss_id);
        /*  exit;
          if ($img) {
              $name = ROOT . '/uploads/test.' . $img[1];
              var_dump(file_put_contents($name, $img[0]));
              echo $name;
          }*/

    }

    //权重设置
    public function weightAction()
    {
        $data = $this->request->get('data');
        $new_data = [];
        while (list($key, $value) = each($data)) {
            $new_data[$value['key']] = $value['val'];
        }
        $data = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_DISCUSS, 'weight');
        $data = json_decode($data, true);
        foreach ($data as $k => &$item) {
            $item['val'] = $new_data[$k];
        }
        $new_data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $res = $this->db->query("update site_key_val set val='" . $new_data . "' where pri_key='" . SiteKeyValManager::KEY_PAGE_DISCUSS . "'and sub_key='weight'");
        if ($res) {
            AdminLog::init()->add('编辑动态权重', AdminLog::TYPE_DISCUSS, 0, array('type' => "update", 'id' => 0, 'data' => $new_data));
            $this->ajax->outRight("编辑成功");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
    }

    //推荐/取消推荐
    public function recommendAction()
    {
        $data = $this->request->get('data');
        $type = $this->request->get('type', 'int', 1);
        $update = ['modify' => time(), 'is_recommend' => $type == 0 ? 0 : 1, 'recommend_time' => $type == 0 ? 0 : time()];
        foreach ($data as $item) {
            $discuss = SocialDiscuss::findOne(['id=' . $item, 'columns' => 'id,user_id']);
            if ($discuss) {
                if (SocialDiscuss::updateOne($update, ['id' => $item])) {
                    //记录日志
                    AdminLog::init()->add($type == 0 ? '取消推荐' : '设为推荐', AdminLog::TYPE_DISCUSS, $item, array('type' => "update", 'id' => $item));

                    if ($type == 1) {
                        SocialDiscussRecommend::insertOne(["user_id" => $discuss['user_id'], 'discuss_id' => $item, 'created' => time()]);
                        $users = Users::findOne(['id=' . $discuss['user_id'], 'columns' => 'username']);

                        $CashRewardManager = new CashRewardManager();
                        $res = $CashRewardManager->reward($discuss['user_id'], CashRewardManager::TYPE_DISCUSS, $item);

                        $this->db->begin();
                        if (!$res) {
                            ImManager::init()->initMsg(ImManager::TYPE_DISCUSS_RECOMMEND, ['discuss_id' => $item, 'user_name' => $users['username'], 'to_user_id' => $discuss['user_id']]);
                        } else {
                            ImManager::init()->initMsg(ImManager::TYPE_DISCUSS_RECOMMEND, ['discuss_id' => $item, 'user_name' => $users['username'], 'to_user_id' => $discuss['user_id'], "money" => ($CashRewardManager->getMoney())]);

                            //插入日志
                            $log = [
                                'user_id' => $discuss['user_id'],
                                'platform' => 0,
                                'type' => 2,
                                'money' => $CashRewardManager->getMoney(),
                                'reward_type' => CashRewardManager::REWARD_TYPE_CASH,
                                'created' => time(),
                                'extra' => $item
                            ];
                            $log['ymd'] = date('Ymd', $log['created']);
                            SiteRewardLog::insertOne($log);
                            $this->db->commit();
                        }
                    } else {
                        SocialDiscussRecommend::remove('discuss_id=' . $item);
                    }
                }

            }
        }
        $this->ajax->outRight("设置成功");
    }

    //设置/取消 在动态标签筛选页的显示
    public function showTagAction()
    {
        $data = $this->request->get('data');
        $type = $this->request->get('type', 'int', 0);
        //禁止
        if ($type == 0) {
            foreach ($data as $item) {
                $discuss = SocialDiscuss::findOne(['id=' . $item, 'columns' => 'user_id']);
                $filter = SocialDiscussTagFilter::exist('discuss_id=' . $item);
                if (!$filter) {
                    if (SocialDiscussTagFilter::insertOne(['discuss_id' => $item, 'created' => time(), 'user_id' => $discuss['user_id']])) {
                        //记录日志
                        AdminLog::init()->add('禁止在标签页显示', AdminLog::TYPE_DISCUSS, $item, array('type' => "update", 'id' => $item));
                    }
                }
            }
        } else {
            foreach ($data as $item) {
                if (SocialDiscussTagFilter::remove("discuss_id=" . $item)) {
                    //记录日志
                    AdminLog::init()->add('设为在标签页显示', AdminLog::TYPE_DISCUSS, $item, array('type' => "update", 'id' => $item));
                }
            }
        }
        $this->ajax->outRight("设置成功");
    }

    //编辑标签
    public function editTagAction()
    {
        $tag = $this->request->get("tag", 'string', '');
        $discuss_id = $this->request->get("discuss_id", 'int', 0);
        if (!$discuss_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ['tags' => $tag, 'tags_name' => ''];

        if ($tag != '') {
            $data['tags_name'] = TagManager::getInstance()->getTagNames($tag);
        }
        if (SocialDiscuss::updateOne($data, 'id=' . $discuss_id)) {
            $this->ajax->outRight("编辑成功");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");


    }

    //获取评论列表
    public function commentListAction()
    {
        $first_id = $this->request->get("first_id", 'int', 0);
        $last_id = $this->request->get("last_id", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);
        $item_id = $this->request->get("item_id");
        //下拉加载
        if ($first_id) {
            $data = ['list' => '', 'first_id' => 0];
            $data_list = \Multiple\Panel\Plugins\SocialManager::init()->commentList(0, SocialManager::TYPE_DISCUSS, $item_id, $limit, $first_id, $last_id);

            if ($data_list) {
                foreach ($data_list as $m) {
                    $data['list'][] = $this->getFromOB('discuss/partial/comment', ['item' => $m]);
                }
                $data['first_id'] = $data_list[0]['comment_id'];
            }

        } //上拉刷新
        else if ($last_id) {
            $data = ['list' => '', 'last_id' => 0];
            $data_list = \Multiple\Panel\Plugins\SocialManager::init()->commentList(0, SocialManager::TYPE_DISCUSS, $item_id, $limit, $first_id, $last_id);
            if ($data_list) {
                foreach ($data_list as $m) {
                    $data['list'][] = $this->getFromOB('discuss/partial/comment', ['item' => $m]);
                }
                $data['last_id'] = $data_list[count($data_list) - 1]['comment_id'];
            }

        } else {
            $data = ['list' => '', 'first_id' => 0, 'last_id' => 0, 'video_ids' => []];
            $data_list = \Multiple\Panel\Plugins\SocialManager::init()->commentList(0, SocialManager::TYPE_DISCUSS, $item_id, $limit, $first_id, $last_id);
            if ($data_list) {
                foreach ($data_list as $m) {
                    $data['list'][] = $this->getFromOB('discuss/partial/comment', ['item' => $m]);
                }
                $data['last_id'] = $data_list[count($data_list) - 1]['comment_id'];
                $data['first_id'] = $data_list[0]['comment_id'];
            }
        }
        $this->ajax->outRight($data);
    }

    //获取回复列表
    public function replyListAction()
    {
        $first_id = $this->request->get("first_id", 'int', 0);
        $last_id = $this->request->get("last_id", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);
        $item_id = $this->request->get("item_id");
        //下拉加载
        if ($first_id) {
            $data = ['list' => '', 'first_id' => 0];
            $data_list = \Multiple\Panel\Plugins\SocialManager::init()->replyList(0, $item_id, $limit, $first_id, $last_id);
            if ($data_list['data_list']) {
                foreach ($data_list['data_list'] as $m) {
                    $data['list'][] = $this->getFromOB('discuss/partial/reply', ['reply' => $m]);
                }
                $data['first_id'] = $data_list['data_list'][0]['reply_id'];
            }

        } //上拉刷新
        else if ($last_id) {
            $data = ['list' => '', 'last_id' => 0];
            $data_list = \Multiple\Panel\Plugins\SocialManager::init()->replyList(0, $item_id, $limit, $first_id, $last_id);
            if ($data_list['data_list']) {
                foreach ($data_list['data_list'] as $m) {
                    $data['list'][] = $this->getFromOB('discuss/partial/reply', ['reply' => $m]);
                }
                $data['last_id'] = $data_list['data_list'][count($data_list['data_list']) - 1]['reply_id'];
            }

        } else {
            $data = ['list' => '', 'first_id' => 0, 'last_id' => 0, 'video_ids' => []];
            $data_list = \Multiple\Panel\Plugins\SocialManager::init()->replyList(0, $item_id, $limit, $first_id, $last_id);
            if ($data_list['data_list']) {
                foreach ($data_list['data_list'] as $m) {
                    $data['list'][] = $this->getFromOB('discuss/partial/reply', ['reply' => $m]);
                }
                $data['last_id'] = $data_list['data_list'][count($data_list['data_list']) - 1]['comment_id'];
                $data['first_id'] = $data_list['data_list'][0]['reply_id'];
            }
        }
        $this->ajax->outRight($data);
    }

    //今日榜单操作
    public function billboardAction()
    {
        $type = $this->request->get("type", 'int', 1);//1-推荐至今日榜单 0-取消今日榜单
        $discuss_id = $this->request->get("discuss_id", 'int', 0);//动态id
        if (!$discuss_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $discuss = SocialDiscuss::findOne(['id=' . $discuss_id, 'columns' => 'user_id']);
        $date = date('Ymd');

        $billboard = SocialDiscussBillboard::findOne(['ymd=' . $date . ' and discuss_id=' . $discuss_id, 'columns' => 'id']);

        if ($type == 1) {
            if ($billboard) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "请勿重复操作");
            }
            SocialDiscussBillboard::insertOne(['ymd' => $date, 'discuss_id' => $discuss_id, 'created' => time(), 'user_id' => $discuss['user_id']]);
            AdminLog::init()->add('设置榜单推荐', AdminLog::TYPE_DISCUSS, $discuss_id, array('type' => "remove", 'id' => $discuss_id));

        } else {
            if (!$billboard) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "请勿重复操作");
            }
            SocialDiscussBillboard::remove("id=" . $billboard['id']);
            //记录日志
            AdminLog::init()->add('取消榜单推荐', AdminLog::TYPE_DISCUSS, $discuss_id, array('type' => "remove", 'id' => $discuss_id));
        }
        Ajax::outRight("");
    }
}