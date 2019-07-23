<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/15
 * Time: 11:08
 */

namespace Multiple\Panel\Api;


use Components\Ffmpeg\Video;
use Models\Site\SiteAppVersion;
use Models\Site\SiteStorage;
use OSS\OssClient;
use Services\Im\ImManager;
use Services\Upload\OssManager;
use Services\User\UserStatus;
use Upload\Upload;
use Util\Ajax;
use Util\Config;
use Util\Debug;

class UploadController extends ApiBase
{
    /**
     * upload img
     */
    public function imgAction()
    {
        // 初始化配置
        $conf = Config::getSite('upload', 'pic');
        $upload = new Upload();
        $upload->upExt = $conf['ext'];
        $upload->maxAttachSize = $conf['maxAttachSize'];
        $buff = $upload->upOne();
        $file = $upload::checkFile($buff['md5'], 'md5,ext,url,size,name');
        if ($file) {
            $this->ajax->outRight($file);
        } else {
            $bucket = OssManager::BUCKET_USER_AVATOR;
            $name = ImManager::ACCOUNT_SYSTEM . '/' . ImManager::ACCOUNT_SYSTEM . rand(0, 1000) . "_s_" . $buff['wh'][0] . 'x' . $buff['wh'][1] . "." . $buff['ext'];
            if (isset($_REQUEST['img_type']) && $_REQUEST['img_type'] == 'music') {
                $bucket = OssManager::BUCKET_MUSIC;
                $name = "thumb/" . date('Ymd') . '/' . time() . rand(0, 1000) . "_s_" . $buff['wh'][0] . 'x' . $buff['wh'][1] . "." . $buff['ext'];
            }
            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
            $res = $oss->putObject($bucket, $name, $buff['buff']);
            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[$bucket], OssManager::$bind_domain[$bucket], $res['info']['url']);
                Upload::syncDb(['md5' => $buff['md5'], 'folder' => date('Ym'), 'ext' => $buff['ext'], 'type' => 'img', 'size' => $buff['size'], 'name' => $name, 'url' => $url, 'created' => time()]);
            }
        }
        if ($this->request->get('from') == 'xheditor') {
            echo $msg = "{'msg':'" . $url . "','err':''}"; //id参数固定不变，仅供演示，实际项目中可以是数据库ID
            exit;
        }
        $this->ajax->outRight($url);
    }

    //用户头像
    public function avatarAction()
    {
        // 初始化配置
        $conf = Config::getSite('upload', 'pic');
        $upload = new Upload();
        $uid = $this->request->get('uid');
        $upload->upExt = $conf['ext'];
        $upload->maxAttachSize = $conf['maxAttachSize'];
        $buff = $upload->upOne();
        $file = $upload::checkFile($buff['md5'], 'md5,ext,url,size,name');
        if ($file) {
            $this->ajax->outRight($file);
        } else {
            $bucket = OssManager::BUCKET_USER_AVATOR;
            $name = $uid . '/' . $uid . rand(0, 1000) . "_s_" . $buff['wh'][0] . 'x' . $buff['wh'][1] . "." . $buff['ext'];
            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
            $res = $oss->putObject($bucket, $name, $buff['buff']);
            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[$bucket], OssManager::$bind_domain[$bucket], $res['info']['url']);
                Upload::syncDb(['md5' => $buff['md5'], 'folder' => date('Ym'), 'ext' => $buff['ext'], 'type' => 'img', 'size' => $buff['size'], 'name' => $name, 'url' => $url, 'created' => time()]);
            }
        }
        if ($this->request->get('from') == 'xheditor') {
            echo $msg = "{'msg':'" . $url . "','err':''}"; //id参数固定不变，仅供演示，实际项目中可以是数据库ID
            exit;
        }
        $this->ajax->outRight($url);

    }

    public function videoAction()
    {
        @ini_set('memory_limit', '2560M');
        // 判断上传方式
        if (isset($_SERVER['HTTP_CONTENT_DISPOSITION'])
            && preg_match('/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i', $_SERVER['HTTP_CONTENT_DISPOSITION'], $info)
        ) {
        } else if (isset($_FILES['file'])) {
            //2. 普通方式上传
            $upfile = isset($_FILES['file']) ? $_FILES['file'] : null;
            if (!$upfile) {
                $this->_error(Ajax::UPLOAD_ERR_FILE_FIELD_NOT_RECEIVED, '表单文件域' . $this->inputField . '未接收到数据');
            }
            // 上传出错
            $errno = isset($upfile['error']) ? $upfile['error'] : 0;
            if ($errno > 0) {
                if (is_array($errno)) {
                    $this->_error(Ajax::UPLOAD_ERR_BATCH_IS_NOT_ALLOWED, '请确认filedata文件域参数');
                } else {
                    $this->_error(2020 + $errno);
                }
            }

            if (empty($upfile['tmp_name']) || $upfile['tmp_name'] == null) {
                $this->_error(Ajax::UPLOAD_ERR_TMP_NAME_NOT_EXIST, '无文件上传');
            }
            // 文件太大 200M
            if (20971520000 < $upfile['size']) {
                $this->_error(Ajax::UPLOAD_ERR_UPLOAD_FILE_IS_TOO_LARGE, '最大不能超过：200M');
            };


            # 缓存文件及后缀
            $buff = file_get_contents($upfile['tmp_name']);
            //获取文件后缀
            preg_match('/[^.]*\.(.*)/', $_POST['name'], $matches);


            //时长
            $duration = '';
            if (!empty($_POST['duration'])) {
                $duration = '_t_' . ($_POST['duration']);
            }

            /********************生成视频缩略图********************/
            //   $app_uid = $this->request->getPost('app_uid', 'int', 0);//app_uid
            $thumb_name = date('Ymd') . '/' . rand();
            $video_path = ROOT . '/upload/' . $thumb_name . '.mp4';
            $this->mkDir($video_path); //创建目录

            file_put_contents($video_path, $buff); //保存文件
            //  $video = Video::init(ROOT, "F:/php/ffmpeg/bin/ffmpeg.exe");
            $video = Video::init(ROOT, "ffmpeg");
            $video->setInput('upload/' . $thumb_name . '.mp4')
                ->setThumb('upload/' . $thumb_name . '.png')
                ->thumb();
            $thumb_local_path = ROOT . '/upload/' . $thumb_name . '.png'; //缩略图本地路径
            $img_info = getimagesize($thumb_local_path);
            $video->destroyInput(); //清空视频


            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);

            $img_name = date('Ymd') . '/' . time() . rand(0, 1000) . "_s_" . $img_info[0] . "x" . $img_info[1] . ".png";
            $img_md5 = md5(file_get_contents($thumb_local_path));
            //检测图片是否存在
            if ($url = Upload::checkFile($img_md5)) {
                $img_url = $url;
            } else {
                $res = $oss->uploadFile(OssManager::BUCKET_CIRCLE_IMG, $img_name, $thumb_local_path);

                if ($res && !empty($res['info']['url'])) {
                    $img_url = str_replace(OssManager::$original_domain[OssManager::BUCKET_CIRCLE_IMG], OssManager::$bind_domain[OssManager::BUCKET_CIRCLE_IMG], $res['info']['url']);
                    Upload::syncDb(['md5' => $img_md5, 'folder' => date('Ym'), 'ext' => 'png', 'type' => 'img', 'size' => filesize($thumb_local_path), 'name' => $img_name, 'url' => $img_url, 'created' => time()]);
                    $video->destroyThumb(); //清空缩略图
                } else {
                    $video->destroyThumb(); //清空缩略图
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");
                }
            }


            /**********************视频上传至oss***************************/

            //检测是否上传过
            $md5 = md5($buff);
            if ($url = Upload::checkFile($md5)) {
                $this->ajax->outRight(['video' => $url, 'thumb' => $img_url]);
            }
            $name = date('Ymd') . '/' . time() . rand(0, 1000) . $duration . (isset($matches[1]) ? "." . $matches[1] : '');
            $res = $oss->putObject(OssManager::BUCKET_VIDEO, $name, $buff);
            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_VIDEO], OssManager::$bind_domain[OssManager::BUCKET_VIDEO], $res['info']['url']);
                Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $matches[1], 'type' => 'video', 'size' => $upfile['size'], 'name' => $name, 'url' => $url, 'created' => time()]);
                file_put_contents(ROOT . '/upload/' . date('Ymd') . '/' . rand() . '.mp4', $buff);
                $this->ajax->outRight(['video' => $url, 'thumb' => $img_url]);
            }
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");

        } else {
            $stream = $this->request->getRawBody();
            if (empty($stream)) {
                $this->_error(Ajax::UPLOAD_ERR_FILE_FIELD_NOT_RECEIVED, '没有上传任何数据');
            }
            $data1 = explode("Content-Type:", $stream);

            //获取文件内容
            $content = substr($data1[1], stripos($data1[1], PHP_EOL));
            //rowBody获取到的有回车换行等，且window和linux下有所区别
            $content = str_replace(array("\r\n", "\r", "\n"), "", substr($content, 0, 10)) . substr($content, 10);

            //获取文件后缀
            // preg_match('/filename\=\".*\.([^\"]+)/', $data1[0], $matches);
            preg_match('/[^.]*\.(.*)/', $_POST['name'], $matches);


            //时长
            $duration = '';
            if (!empty($_POST['duration'])) {
                $duration = '_t_' . $_POST['duration'];
            }

            /********************生成视频缩略图********************/
            // $app_uid = $this->request->getPost('app_uid', 'int', 0);//app_uid
            $thumb_name = date('Ymd') . '/' . rand();
            $video_path = ROOT . '/upload/' . $thumb_name . '.mp4';
            $this->mkDir($video_path); //创建目录
            file_put_contents($video_path, $content); //保存文件
            $video = Video::init(ROOT, "F:/php/ffmpeg/bin/ffmpeg.exe");
            $video->setInput('upload/' . $thumb_name . '.mp4')
                ->setThumb('upload/' . $thumb_name . '.png')
                ->thumb();
            $thumb_local_path = ROOT . '/upload/' . $thumb_name . '.png'; //缩略图本地路径
            $img_info = getimagesize($thumb_local_path);
            $video->destroyInput();

            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);

            $img_name = date('Ymd') . '/' . time() . rand(0, 1000) . "_s_" . $img_info[0] . "x" . $img_info[1] . ".png";
            $img_md5 = md5(file_get_contents($thumb_local_path));
            //检测图片是否存在
            if ($url = Upload::checkFile($img_md5)) {
                $img_url = $url;
            } else {
                $res = $oss->uploadFile(OssManager::BUCKET_CIRCLE_IMG, $img_name, $thumb_local_path);
                if ($res && !empty($res['info']['url'])) {
                    $img_url = str_replace(OssManager::$original_domain[OssManager::BUCKET_CIRCLE_IMG], OssManager::$bind_domain[OssManager::BUCKET_CIRCLE_IMG], $res['info']['url']);
                    Upload::syncDb(['md5' => $img_md5, 'folder' => date('Ym'), 'ext' => 'png', 'type' => 'img', 'size' => filesize($thumb_local_path), 'name' => $img_name, 'url' => $img_url, 'created' => time()]);
                    $video->destroyThumb();
                } else {
                    $video->destroyThumb();
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");
                }
            }

            /**********************视频上传至oss***************************/
            //检测是否上传过
            $md5 = md5($content);
            if ($url = Upload::checkFile($md5)) {
                $this->ajax->outRight(['video' => $url, 'thumb' => $img_url]);
            }

            $name = date('Ymd') . '/' . time() . rand(0, 1000) . $duration . (isset($matches[1]) ? "." . $matches[1] : '');
            $res = $oss->putObject(OssManager::BUCKET_VIDEO, $name, $content);

            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_VIDEO], OssManager::$bind_domain[OssManager::BUCKET_VIDEO], $res['info']['url']);
                Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $matches[1], 'type' => 'video', 'size' => strlen($content), 'name' => $name, 'url' => $url, 'created' => time()]);
                file_put_contents(ROOT . '/upload/' . date('Ymd') . '/' . rand() . '.mp4', $content);

                $this->ajax->outRight(['video' => $url, 'thumb' => $img_url]);
            }
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");
            // var_dump();


            //  var_dump($content);
            // exit;
            //   file_put_contents($name, $content);
            /*  if ($data) {
                  var_dump(json_decode($data, true));
                  exit;
              }*/

        }

    }

    //上传音频
    public function audioAction()
    {
        // 初始化配置
        $conf = Config::getSite('upload', 'audio');
        $upload = new Upload();
        $upload->upExt = $conf['ext'];
        $upload->maxAttachSize = $conf['maxAttachSize'];
        $buff = $upload->upOne();
        $file = $upload::checkFile($buff['md5'], 'md5,ext,url,size,name');
        if ($file) {
            $this->ajax->outRight($file);
        } else {
            //时长
            $duration = '';
            if (!empty($_POST['duration'])) {
                $duration = '_t_' . $_POST['duration'];
            }

            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
            $name = 'music/' . date('Ymd') . '/' . time() . rand(0, 1000) . $duration . "." . $buff['ext'];
            $res = $oss->putObject(OssManager::BUCKET_MUSIC, $name, $buff['buff']);
            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_MUSIC], OssManager::$bind_domain[OssManager::BUCKET_MUSIC], $res['info']['url']);
                Upload::syncDb(['md5' => $buff['md5'], 'folder' => date('Ym'), 'ext' => $buff['ext'], 'type' => 'audio', 'size' => $buff['size'], 'name' => $name, 'url' => $url, 'created' => time()]);
                $this->ajax->outRight($url);
            }
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");
    }

    //上传礼物动画文件
    public function giftAction()
    {
        // 初始化配置
        $conf = Config::getSite('upload', 'file');
        $upload = new Upload();
        $upload->upExt = $conf['ext'];
        $upload->maxAttachSize = $conf['maxAttachSize'];
        $buff = $upload->upOne();
//        $file = $upload::checkFile($buff['md5'], 'md5,ext,url,size,name');
//        if ($file) {
//            $this->ajax->outRight($file);
//        } else {
        $gift_id = $_POST['id'];
        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        $name = 'gift/' . $gift_id . "." . $buff['ext'];
        $res = $oss->putObject(OssManager::BUCKET_CIRCLE_IMG, $name, $buff['buff']);
        if ($res && !empty($res['info']['url'])) {
            $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_CIRCLE_IMG], OssManager::$bind_domain[OssManager::BUCKET_CIRCLE_IMG], $res['info']['url']);
            // Upload::syncDb(['md5' => $buff['md5'], 'folder' => date('Ym'), 'ext' => $buff['ext'], 'type' => 'file', 'size' => $buff['size'], 'name' => $name, 'url' => $url, 'created' => time()]);
            $this->ajax->outRight($url);
        }
        // }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");
    }

    public function appAction()
    {
        @ini_set('memory_limit', '2560M');
        // 判断上传方式
        if (isset($_SERVER['HTTP_CONTENT_DISPOSITION'])
            && preg_match('/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i', $_SERVER['HTTP_CONTENT_DISPOSITION'], $info)
        ) {
        } else if (isset($_FILES['file'])) {
            //2. 普通方式上传
            $upfile = isset($_FILES['file']) ? $_FILES['file'] : null;
            if (!$upfile) {
                $this->_error(Ajax::UPLOAD_ERR_FILE_FIELD_NOT_RECEIVED, '表单文件域' . $this->inputField . '未接收到数据');
            }

            // 上传出错
            $errno = isset($upfile['error']) ? $upfile['error'] : 0;
            if ($errno > 0) {
                if (is_array($errno)) {
                    $this->_error(Ajax::UPLOAD_ERR_BATCH_IS_NOT_ALLOWED, '请确认filedata文件域参数');
                } else {
                    $this->_error(2020 + $errno);
                }
            }

            if (empty($upfile['tmp_name']) || $upfile['tmp_name'] == null) {
                $this->_error(Ajax::UPLOAD_ERR_TMP_NAME_NOT_EXIST, '无文件上传');
            }
            // 文件太大 200M
            if (20971520000 < $upfile['size']) {
                $this->_error(Ajax::UPLOAD_ERR_UPLOAD_FILE_IS_TOO_LARGE, '最大不能超过：200M');
            };

            # 缓存文件及后缀
            $buff = file_get_contents($upfile['tmp_name']);
            //  $path = ROOT . "/download/" . $upfile["name"];


            //获取文件后缀
            preg_match('/.*\.([^\"]+)\"/', $upfile['name'], $matches);
            //  move_uploaded_file($upfile["tmp_name"], $path);
            //上传至oss-
            $name = date('Ymd') . '/' . microtime(true) . '.apk';
            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);

            $res = $oss->putObject(OssManager::BUCKET_APK, $name, $buff);
            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_APK], OssManager::$bind_domain[OssManager::BUCKET_APK], $res['info']['url']);

                $this->ajax->outRight(['url' => $url, 'md5' => md5($buff)]);
            }
            // $this->ajax->outRight($path);
            // $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");

        } else {
            $stream = $this->request->getRawBody();
            if (empty($stream)) {
                $this->_error(Ajax::UPLOAD_ERR_FILE_FIELD_NOT_RECEIVED, '没有上传任何数据');
            }
            $data1 = explode("Content-Type:", $stream);

            //获取文件内容
            $content = substr($data1[1], stripos($data1[1], PHP_EOL));
            //rowBody获取到的有回车换行等，且window和linux下有所区别
            $content = str_replace(array("\r\n", "\r", "\n"), "", substr($content, 0, 10)) . substr($content, 10);

            //获取文件后缀
            preg_match('/filename\=\".*\.([^\"]+)/', $data1[0], $matches);
            $name = date('Ymd') . '/' . microtime(true) . rand(0, 1000) . '.apk';
            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);

            $res = $oss->putObject(OssManager::BUCKET_APK, $name, $content);
            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_APK], OssManager::$bind_domain[OssManager::BUCKET_APK], $res['info']['url']);
                $this->ajax->outRight(['url' => $url, 'md5' => md5($content)]);
            }
            /* //上传至oss
             $name = $this->admin->app_uid . '/' . $this->admin->app_uid . rand(0, 1000) . (isset($matches[1]) ? "." . $matches[1] : '');
             $config = $this->di->get('config')->oss;
             $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);

             $res = $oss->putObject("klg-video", $name, $content);
             if ($res && !empty($res['info']['url'])) {
                 $this->ajax->outRight($res['info']['url']);
             }*/
            //  $path = ROOT . "/download/" . rand(0, 100) . '.' . $matches[1];


            //   $this->ajax->outRight($path);
            // $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");
            // var_dump();


            //  var_dump($content);
            // exit;
            //   file_put_contents($name, $content);
            /*  if ($data) {
                  var_dump(json_decode($data, true));
                  exit;
              }*/

        }
    }

    public function mkDir($path)
    {
        $base = dirname($path);
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }
    }

    /**
     * 上传错误输出并退出
     *
     */
    function _error($code, $msg = '')
    {
        $this->ajax->outError($code, $msg);
    }

    public function getOssPolicyAction()
    {
        function gmt_iso8601($time)
        {
            $dtStr = date("c", $time);
            $mydatetime = new \DateTime($dtStr);
            $expiration = $mydatetime->format(\DateTime::ISO8601);
            $pos = strpos($expiration, '+');
            $expiration = substr($expiration, 0, $pos);
            return $expiration . "Z";
        }

        $id = OssManager::$sts_access_key;
        $key = OssManager::$sts_access_key_secret;
        $host = 'http://klg-clientdownapk.oss-cn-shenzhen.aliyuncs.com';
        $callbackUrl = "http://api.klgwl.com/upload/callback";

        $callback_param = array('callbackUrl' => $callbackUrl,
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => "application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 30; //设置该policy超时时间是30s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = gmt_iso8601($end);

        $dir = date('Ymd') . "/";

        //最大文件大小.用户可以自己设置
        $condition = array(0 => 'content-length-range', 1 => 0, 2 => 1048576000);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
        $conditions[] = $start;


        $arr = array('expiration' => $expiration, 'conditions' => $conditions);
        //echo json_encode($arr);
        //return;
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;
        Ajax::outRight($response);
        // echo json_encode($response);
    }
}