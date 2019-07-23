<?php
/**
 *
 * 阿里绿网 图片鉴黄
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/23
 * Time: 11:51
 */

namespace Services\Aliyun;


use Green\Core\DefaultAcsClient;
use Green\Core\Profile\DefaultProfile;
use Green\ImageSyncScanRequest;
use Models\Social\SocialComment;
use Models\Social\SocialCommentReply;
use Models\Social\SocialDiscuss;
use Models\System\SystemImageCheck;
use Models\User\UserInfo;
use Models\User\Users;
use Models\User\UserStorage;
use Phalcon\Mvc\User\Plugin;
use Services\Discuss\DiscussManager;
use Services\Site\CacheSetting;
use Services\Site\PornManager;
use Services\Site\SiteKeyValManager;
use Util\Debug;

class GreenManager extends Plugin
{
    private static $instance = null;
    public static $access_key = "LTAI2dwXPSgmshoz";
    public static $access_key_secret = "5gNndxmZyq8moDSN2Ig8477GEg8wbk";
    public static $region = "cn-shanghai";

    //场景
    const SCENE_PORN = 'porn'; //图片鉴黄
    const SCENE_TERRORISM = 'terrorism'; //图片暴恐识别
    const SCENE_OCR = 'ocr'; //图片文字识别
    const SCENE_SFACE = 'sface'; //图片人脸识别
    // const SCENE_KEYWORD = 'keyword'; //文本关键词

    //分类
    const LABEL_NORMAN = 'normal'; //正常图片  无色情 /正常图片不含暴恐 /正常图片 不含文字/ 正常文本
    const LABEL_SEXY = 'sexy'; //性感图片
    const LABEL_PORN = 'porn'; //色情图片

    const LABEL_TERRORISM = 'terrorism'; //暴恐图片 含暴恐信息
    const LABEL_OCR = 'ocr'; //含文字图片
    const LABEL_SFACE = 'sface'; //含人脸图片


    //const LABEL_SPAM = 'spam'; //含违规信息
    // const LABEL_AD = 'ad'; //广告
    // const LABEL_POLITICS = 'politics'; //涉政
    // const LABEL_CONTRABAND = 'contraband'; //违禁
    // const LABEL_CUSTOMIZED = 'customized'; //自定义

    private static $code = [
        "200",//OK，表示请求成功
        "280",//PROCESSING，表示任务正在执行中，建议用户等待一段时间后再查询结果（比如5s）
        "400",//BAD_REQUEST， 请求有误
        "480",//DOWNLOAD_FAILED，下载失败
        "500",//GENERAL_ERROR，一般是服务端临时出错
        "580",//DB_FAILED，数据库操作失败
        "581",//TIMEOUT，超时
        "585",//CACHE_FAILED，缓存出错
        "586",//ALGO_FAILED，算法出错
        "587",//MQ_FAILED，中间件出错
        "588",//EXCEED_QUOTA，超出配额
    ];


    /**
     * @return  GreenManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //检测图片 鉴黄  --同步调用最多100张
    public static function checkImg($images)
    {
        $res = ["fail" => [], 'porn' => []];
        $tasks = [];
        $task_key = [];
        $time = round(microtime(true) * 1000);
        $user_storage = UserStorage::getColumn(["url in ('" . implode("','", $images) . "') and is_checked=1", 'columns' => 'is_porn,url'], 'is_porn', 'url');
        foreach ($images as $i => $item) {
            if (!isset($user_storage[$item])) {
                $tasks[] = ['url' => $item, 'time' => $time];
                $task_key[] = $i;
            } //之前有检测过 过滤
            else {
                $checkResult = SystemImageCheck::findOne(["url='" . $item . "'", 'columns' => 'status,rate,modify']);
                //后台编辑过 已认为是黄图且被删除
                if ($checkResult['modify'] && $checkResult['status'] == PornManager::STATUS_DELETED) {
                    $res['porn'][$i] = ['url' => $item, 'rate' => $checkResult['rate']];
                } //后台没有编辑 但是已被标识为黄图
                else {
                    if ($checkResult['status'] == PornManager::STATUS_PORN) {
                        $res['porn'][$i] = ['url' => $item, 'rate' => $checkResult['rate']];
                    }
                }
            }

        }
        if ($tasks) {
            $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, "img_check");
            $setting = json_decode($setting, true);
            $score = $setting ? $setting['score'] : 0;//最低分值

            require_once ROOT . "/Library/Components/LvWang/Core/Regions/EndpointConfig.php";
            //请替换成你自己的accessKeyId、accessKeySecret
            $iClientProfile = DefaultProfile::getProfile(self::$region, self::$access_key, self::$access_key_secret);
            DefaultProfile::addEndpoint("cn-shanghai", "cn-shanghai", "Green", "green.cn-shanghai.aliyuncs.com");
            $client = new DefaultAcsClient($iClientProfile);
            $request = new ImageSyncScanRequest();
            $request->setMethod("POST");
            $request->setAcceptFormat("JSON");
            $request->setContent(json_encode(array("tasks" => $tasks,
                "scenes" => array(self::SCENE_PORN))));
            try {
                $response = $client->getAcsResponse($request);
                /*  var_dump($response);
                  var_dump($response->data[0]);
                  var_dump($response->data[1]);*/
                if (200 == $response->code) {
                    $taskResults = $response->data;
                    $i = 0;
                    foreach ($taskResults as $taskResult) {
                        if (200 == $taskResult->code) {
                            $sceneResults = $taskResult->results;
                            foreach ($sceneResults as $sceneResult) {
                                //  $scene = $sceneResult->scene;
                                //$suggestion = $sceneResult->suggestion;
                                //  //根据scene和suggetion做相关的处理
                                //do something
                                if ($sceneResult->label == self::LABEL_PORN && $sceneResult->rate >= $score) {
                                    $res['porn'][$task_key[$i]] = ['url' => $taskResult->url, 'rate' => $sceneResult->rate];
                                }
                            }
                        } else {
                            //图片没找到 则放弃
                            if (!($taskResult->code == 480 && $taskResult->msg = '404 Not Found')) {
                                $res['fail'][$task_key[$i]] = $taskResult->url;
                            }
                            Debug::log("图片鉴黄失败:url:" . $taskResult->url . ",code" . $taskResult->code, "error");
                        }
                        $i++;
                    }
                } else {
                    Debug::log("图片鉴黄失败:code" . $response->code, "error");
                }
            } catch (\Exception $e) {
                Debug::log("图片鉴黄失败" . var_export($e->getMessage(), true), "error");
                return false;
            }
        }


        return $res;
    }

    //动态图片鉴黄检测
    public function discussCheck()
    {
        $redis = $this->di->get("redis_queue");
        while ($images = $redis->lRanges(CacheSetting::KEY_IMAGE_CHECK_DISCUSS_LIST, 0, 50)) {
            $check_images = []; //图片地址
            $discuss_ids = []; //动态id
            $img_index = []; //图片索引

            foreach ($images as $k => $v) {
                $tmp = explode('|', $v);
                $check_images[$k] = $tmp[2];
                $discuss_ids[$k] = $tmp[0];
                $img_index[$k] = $tmp[1];
                $redis->lPop(CacheSetting::KEY_IMAGE_CHECK_DISCUSS_LIST);
            }
            $not_porn_images = $check_images; //非黄图

            $res = self::checkImg(array_values($check_images));

            if ($res) {
                $need_update = [];//需要更新的数据
                $porn_images = [];//涉黄图片

                //存在黄色图片
                if ($res['porn']) {
                    foreach ($res['porn'] as $k => $v) {
                        $need_update[$discuss_ids[$k]][] = $img_index[$k];
                        $porn_images[$v['url']] = $v['rate'];
                        unset($not_porn_images[$k]); //过滤黄图
                    }

                }
                //失败的 放到队列最后面
                if ($res['fail']) {
                    foreach ($res['fail'] as $k => $v) {
                        $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_DISCUSS_LIST, $discuss_ids[$k] . '|' . $v);
                        unset($not_porn_images[$k]); //过滤失败的
                    }
                }
                if ($need_update) {
                    $discuss = SocialDiscuss::getByColumnKeyList(["id in (" . implode(",", array_keys($need_update)) . ")", 'columns' => 'media,id,user_id'], "id");
                    $uids = [];
                    foreach ($need_update as $k => $item) {
                        $media = explode(',', $discuss[$k]["media"]);
                        foreach ($item as $i) {
                            $log = new SystemImageCheck();
                            $log->insertOne([
                                "user_id" => $discuss[$k]["user_id"],
                                "item_id" => $k,
                                "type" => "discuss",
                                "url" => $media[$i],
                                'created' => time(),
                                'rate' => $porn_images[$media[$i]],
                                'url_md5' => md5($media[$i])
                            ]);
                            //把图片设为黄图
                            if ($user_storage = UserStorage::findOne("url='" . $media[$i] . "' and is_checked=0")) {
                                UserStorage::updateOne(['is_porn' => 1, 'is_checked' => 1, 'count' => $user_storage['count'] + 1], ['id' => $user_storage['id']]);
                            }
                            $media[$i] = $media[$i] . "|porn";
                        }
                        $media = implode(",", $media);
                        $this->db->execute("update social_discuss set media='" . $media . "' where id=" . $k);
                        $this->db->execute("update social_discuss_media set media='" . $media . "' where discuss_id=" . $k);
                        $uids[$discuss[$k]["user_id"]] = $discuss[$k]["user_id"];

                    }
                    //更新最新动态
                    foreach ($uids as $u) {
                        DiscussManager::getInstance()->updateNewestDiscussPic($u);
                    }
                }
            }
            if ($not_porn_images) {
                foreach ($not_porn_images as $item) {
                    //把图片设为非黄图
                    $this->db->execute("update user_storage set is_porn=0,is_checked=1,count=count+1 where url='" . $item . "'");
                }
            }
        }
    }

    //评论鉴黄检测
    public function commentCheck()
    {
        $redis = $this->di->get("redis_queue");
        while ($images = $redis->lRanges(CacheSetting::KEY_IMAGE_CHECK_COMMENT_LIST, 0, 50)) {
            $check_images = []; //图片地址
            $comment_ids = []; //评论id
            $img_index = []; //图片索引

            foreach ($images as $k => $v) {
                $tmp = explode('|', $v);
                $check_images[$k] = $tmp[2];
                $comment_ids[$k] = $tmp[0];
                $img_index[$k] = $tmp[1];
                $redis->lPop(CacheSetting::KEY_IMAGE_CHECK_COMMENT_LIST);
            }
            $not_porn_images = $check_images; //非黄图

            $res = self::checkImg(array_values($check_images));
            if ($res) {
                $need_update = [];//需要更新的数据
                $porn_images = [];//涉黄图片
                //存在黄色图片
                if ($res['porn']) {
                    foreach ($res['porn'] as $k => $v) {
                        $need_update[$comment_ids[$k]][] = $img_index[$k];
                        $porn_images[$v['url']] = $v['rate'];
                        unset($not_porn_images[$k]); //过滤黄图
                    }
                }
                //失败的 放到队列最后面
                if ($res['fail']) {
                    foreach ($res['fail'] as $k => $v) {
                        $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_COMMENT_LIST, $comment_ids[$k] . '|' . $v);
                        unset($not_porn_images[$k]); //过滤失败的
                    }
                }
                if ($need_update) {
                    $comment = SocialComment::getByColumnKeyList(["id in (" . implode(",", array_keys($need_update)) . ")", 'columns' => 'images,id,user_id'], "id");
                    $uids = [];
                    foreach ($need_update as $k => $item) {
                        $log = new SystemImageCheck();
                        $media = explode(',', $comment[$k]["images"]);
                        foreach ($item as $i) {
                            $log->insertOne([
                                "user_id" => $comment[$k]["user_id"],
                                "item_id" => $k,
                                "type" => 'comment',
                                "url" => $media[$i],
                                'created' => time(),
                                'rate' => $porn_images[$media[$i]],
                                'url_md5' => md5($media[$i])
                            ]);
                            //把图片设为黄图
                            if ($user_storage = UserStorage::findOne("url='" . $media[$i] . "'")) {
                                UserStorage::updateOne(['is_porn' => 1, 'count' => $user_storage['count'] + 1, 'is_checked' => 1], ['id' => $user_storage['id']]);
                            }
                            $media[$i] = $media[$i] . "|porn";
                        }
                        $media = implode(",", $media);
                        $this->db->execute("update social_comment set images='" . $media . "' where id=" . $k);
                        $uids[$comment[$k]["user_id"]] = $comment[$k]["user_id"];

                    }
                }
            }
            if ($not_porn_images) {
                foreach ($not_porn_images as $item) {
                    //把图片设为非黄图
                    $this->db->execute("update user_storage set is_porn=0,is_checked=1,count=count+1 where url='" . $item . "'");
                }
            }
        }

    }

    //回复鉴黄检测
    public function replyCheck()
    {
        $redis = $this->di->get("redis_queue");
        while ($images = $redis->lRanges(CacheSetting::KEY_IMAGE_CHECK_REPLY_LIST, 0, 50)) {
            $check_images = []; //图片地址
            $comment_ids = []; //评论id
            $img_index = []; //图片索引

            foreach ($images as $k => $v) {
                $tmp = explode('|', $v);
                $check_images[$k] = $tmp[2];
                $comment_ids[$k] = $tmp[0];
                $img_index[$k] = $tmp[1];
                $redis->lPop(CacheSetting::KEY_IMAGE_CHECK_REPLY_LIST);
            }
            $not_porn_images = $check_images; //非黄图

            $res = self::checkImg(array_values($check_images));
            if ($res) {
                $need_update = [];//需要更新的数据
                $porn_images = [];//涉黄图片
                //存在黄色图片
                if ($res['porn']) {
                    foreach ($res['porn'] as $k => $v) {
                        $need_update[$comment_ids[$k]][] = $img_index[$k];
                        $porn_images[$v['url']] = $v['rate'];
                        unset($not_porn_images[$k]); //过滤黄图

                    }
                }
                //失败的 放到队列最后面
                if ($res['fail']) {
                    foreach ($res['fail'] as $k => $v) {
                        $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_REPLY_LIST, $comment_ids[$k] . '|' . $v);
                        unset($not_porn_images[$k]); //过滤失败的
                    }
                }
                if ($need_update) {
                    $comment = SocialCommentReply::getByColumnKeyList(["id in (" . implode(",", array_keys($need_update)) . ")", 'columns' => 'images,id,user_id'], "id");
                    $uids = [];
                    foreach ($need_update as $k => $item) {
                        $log = new SystemImageCheck();
                        $media = explode(',', $comment[$k]["images"]);
                        foreach ($item as $i) {
                            $log->insertOne([
                                "user_id" => $comment[$k]["user_id"],
                                "item_id" => $k,
                                "type" => 'reply',
                                "url" => $media[$i],
                                'created' => time(),
                                'rate' => $porn_images[$media[$i]],
                                'url_md5' => md5($media[$i])
                            ]);
                            //把图片设为黄图
                            if ($user_storage = UserStorage::findOne("url='" . $media[$i] . "'")) {
                                UserStorage::updateOne(['is_porn' => 1, 'count' => $user_storage['count'] + 1, 'is_checked' => 1], ['id' => $user_storage['id']]);
                            }
                            $media[$i] = $media[$i] . "|porn";
                        }
                        $media = implode(",", $media);
                        $this->db->execute("update social_comment_reply set images='" . $media . "' where id=" . $k);
                        $uids[$comment[$k]["user_id"]] = $comment[$k]["user_id"];

                    }
                }
            }
            if ($not_porn_images) {
                foreach ($not_porn_images as $item) {
                    //把图片设为非黄图
                    $this->db->execute("update user_storage set is_porn=0,is_checked=1,count=count+1 where url='" . $item . "'");
                }
            }
        }
    }

    //头像鉴黄
    public function avatarCheck()
    {
        $redis = $this->di->get("redis_queue");

        while ($images = $redis->lRanges(CacheSetting::KEY_IMAGE_CHECK_AVATAR_LIST, 0, 50)) {
            $uids = []; //用户id
            $check_images = []; //图片地址

            foreach ($images as $k => $v) {
                $tmp = explode('|', $v);
                $uids[$k] = $tmp[0];
                $check_images[$k] = $tmp[1];
                $redis->lPop(CacheSetting::KEY_IMAGE_CHECK_AVATAR_LIST);
            }
            $not_porn_images = $check_images; //非黄图

            $res = self::checkImg(array_values($check_images));
            if ($res) {
                $need_update = [];//需要更新的数据
                $images_rate = [];//涉黄图片
                //存在黄色图片
                if ($res['porn']) {
                    foreach ($res['porn'] as $k => $v) {
                        $need_update[$uids[$k]][] = $v['url'];
                        $images_rate[$v['url']] = $v['rate'];
                        unset($not_porn_images[$k]); //过滤黄图
                    }
                }
                //失败的 放到队列最后面
                if ($res['fail']) {
                    foreach ($res['fail'] as $k => $v) {
                        $redis->rPush(CacheSetting::KEY_IMAGE_CHECK_REPLY_LIST, $uids[$k] . '|' . $v);
                        unset($not_porn_images[$k]); //过滤失败的
                    }
                }
                if ($need_update) {
                    $photos = UserInfo::getByColumnKeyList(["user_id in (" . implode(",", array_keys($need_update)) . ")", 'columns' => 'user_id,photos'], "user_id");

                    foreach ($need_update as $uid => $urls) {

                        $log = new SystemImageCheck();
                        foreach( $urls as $url)
                        {
                            $photos[$uid]['photos'] = str_replace($url,$url . '|porn',$photos[$uid]['photos']);
                            if( !SystemImageCheck::exist(['url' => $url]) )
                            {
                                $log->insertOne([
                                    "user_id" => $uid,
                                    "item_id" => $uid,
                                    "type" => 'avatar',
                                    "url" => $url,
                                    'created' => time(),
                                    'rate' => $images_rate[$url],
                                    'url_md5' => md5($url)
                                ]);
                            }else
                            {
                                $log->updateOne(['user_id' => $uid,'item_id' => $uid,'rate' => $images_rate[$url],'type' => 'avatar'],['url' => $url]);
                            }
                            if ($user_storage = UserStorage::findOne("url='" . $url . "'")) {
                                UserStorage::updateOne(['is_porn' => 1, 'count' => $user_storage['count'] + 1, 'is_checked' => 1], ['id' => $user_storage['id']]);
                            }
                            //照片墙
                            $this->original_mysql->execute("update user_info set photos='" . $photos[$uid]['photos'] . "' where user_id=" . $uid);
                            //头像
                            if ( strpos($photos[$uid]['photos'],$url) === 0 )
                                Users::updateOne(['avatar' => $url . '|porn'],['id' => $uid]);

                        }



                    }
                }
            }
            if ($not_porn_images) {
                foreach ($not_porn_images as $item) {
                    //把图片设为非黄图
                    $this->db->execute("update user_storage set is_porn=0,is_checked=1,count=count+1 where url='" . $item . "'");
                }
            }
        }
    }

}