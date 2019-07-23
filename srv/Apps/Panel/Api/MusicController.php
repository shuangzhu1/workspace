<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/25
 * Time: 17:41
 */

namespace Multiple\Panel\Api;


use Components\Ffmpeg\Audio;
use Components\Music\Music;
use Models\Music\MusicCategory;
use OSS\OssClient;
use Services\Admin\AdminLog;
use Services\Im\ImManager;
use Services\Site\CurlManager;
use Services\Upload\OssManager;
use Upload\Upload;
use Util\Ajax;
use Util\Pagination;

class MusicController extends ApiBase
{
    //编辑/添加分类
    public function editCatAction()
    {
        $cat_id = $this->request->getPost('cat_id', 'int', 0);
        $name = $this->request->getPost('name', 'string', '');
        $enable = $this->request->getPost('enable', 'int', 1);
        $thumb = $this->request->getPost('thumb', 'string', '');

        $sort_num = $this->request->getPost('sort_num', 'int', 50); //排序字段 越小越靠前
        if ($name == '') {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '名称不能为空');
        }
        $data = ['name' => $name, 'enable' => $enable == 1 ? 1 : 0, 'sort_num' => $sort_num, 'icon' => $thumb];
        //编辑
        if ($cat_id > 0) {
            $tag = MusicCategory::findOne('id=' . $cat_id);
            if (!$tag) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $res = MusicCategory::updateOne($data, ['id' => $cat_id]);
        } else {
            if (MusicCategory::findOne(['name="' . $name . '"'])) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '该分类已存在');
            }
            $tag = new MusicCategory();
            $data['created'] = time();
            $res = $tag->insertOne($data);
        }
        if ($res) {
            if ($cat_id) {
                AdminLog::init()->add('修改音乐分类', AdminLog::TYPE_MUSIC, $cat_id, ['type' => 'update', 'id' => $cat_id, 'data' => $data]);
                $this->ajax->outRight("编辑成功");
            } else {
                AdminLog::init()->add('添加音乐分类', AdminLog::TYPE_TAGS, $res, ['type' => 'add', 'id' => $res, 'data' => $data]);
                $this->ajax->outRight("添加成功");
            }
        } else {
            $this->ajax->outError($cat_id ? "编辑失败" : "添加失败");
        }

    }

    //禁用标签
    public function lockTagAction()
    {
        $cat_id = $this->request->get('cat_id', 'int', 0);
        if (!$cat_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $tag = MusicCategory::findOne('id=' . $cat_id);
        if (!$tag) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (MusicCategory::updateOne(['enable' => 0], ['id' => $cat_id])) {
            AdminLog::init()->add('禁用音乐分类', AdminLog::TYPE_MUSIC, $cat_id, ['type' => 'add', 'id' => $cat_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //解除禁用标签
    public function unLockTagAction()
    {
        $cat_id = $this->request->get('cat_id', 'int', 0);
        if (!$cat_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $tag = MusicCategory::findOne('id=' . $cat_id);
        if (!$tag) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (MusicCategory::updateOne(['enable' => 1], ['id' => $cat_id])) {
            AdminLog::init()->add('音乐分类解除禁用', AdminLog::TYPE_MUSIC, $cat_id, ['type' => 'add', 'id' => $cat_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //音乐搜索
    public function searchAction()
    {
        $platform = $this->request->get("platform", 'string', 'xiami');
        $key = $this->request->get("key", 'string', '');
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $top_id = $this->request->get("top_id", 'int', 0);
        if (!$top_id) {
            $res = Music::init($platform)->search($key, 1, $page, $limit);
        } else {
            $res = Music::init($platform)->topList($top_id, $page, $limit);
        }
        $data = '';
        if ($res['data_count'] > 0) {
            foreach ($res['data_list'] as $item) {
                $data .= $this->getFromOB('music/partial/search_item', ['item' => $item]);
            }
        } else {
            $data .= "<li>暂无数据</li>";
        }
        $bar = Pagination::getAjaxListPageBar($res['data_count'], $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $res['data_count'], 'bar' => $bar]);
    }

    //音乐搜索关键字
    public function searchWordAction()
    {
        $platform = $this->request->get("platform", 'string', 'qq');
        $key = $this->request->get("word", 'string', '');

        $res = Music::init($platform)->getSearchKey($key);
        $data = '';
        if ($res) {
            $data = $this->getFromOB('music/partial/search_key', ['item' => $res, 'key' => $key]);
        }
        $this->ajax->outRight(['list' => $data, 'count' => 1]);

    }

    public function mp3UrlAction()
    {
        $platform = $this->request->get("platform", 'string', 'qq');
        $song_id = $this->request->get("song_id", 'string', '');
        $res = Music::init($platform)->mp3Url($song_id);
        $this->ajax->outRight($res);
    }

    public function addMusicAction()
    {
        $data = $this->request->get("data");
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (empty($data['name']) ||
            empty($data['cat_id']) ||
            empty($data['singer']) ||
            empty($data['thumb']) ||
            empty($data['mp3']) ||
            empty($data['duration'])
        ) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        //音乐剪辑 第三方图片 需要上传
        if (!(strpos($data['thumb'], 'music.klgwl.com') > 0)) {
            $content = file_get_contents($data['thumb']);

            if ($content) {

                $image_size = (getimagesize($data['thumb']));

                $headerInfo = get_headers($data['thumb'], true);
                $size = $headerInfo['Content-Length'];
                $size = is_array($size) ? end($size) : $size;
                $width = $image_size[0];
                $height = $image_size[1];
                $md5 = md5($content);
                $ext = explode('/', $image_size['mime'])[1];

                $upload = new Upload();
                $file = $upload::checkFile($md5, 'url');
                if ($file) {
                    $data['thumb'] = $file;
                } else {
                    $bucket = OssManager::BUCKET_MUSIC;
                    $name = "thumb/" . date('Ymd') . '/' . time() . rand(0, 1000) . "_s_" . $width . 'x' . $height . "." . $ext;
                    $config = $this->di->get('config')->oss;
                    $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
                    $res = $oss->putObject($bucket, $name, $content);
                    if ($res && !empty($res['info']['url'])) {
                        $url = str_replace(OssManager::$original_domain[$bucket], OssManager::$bind_domain[$bucket], $res['info']['url']);
                        $data['thumb'] = $url;
                        Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $ext, 'type' => 'img', 'size' => $size, 'name' => $name, 'url' => $url, 'created' => time()]);
                    } else {
                        $data['thumb'] = "http://music.klgwl.com/default/default.jpg";
                    }
                }

            } else {
                $data['thumb'] = "http://music.klgwl.com/default/default.jpg";
            }

        }

        $song_id = isset($data['song_id']) ? $data['song_id'] : 0;
        //编辑
        if ($song_id) {
            $data = [
                'name' => $data['name'],
                'singer' => $data['singer'],
                'mp3' => $data['mp3'],
                'thumb' => $data['thumb'],
                'album' => $data['album'],
                'time' => $data['duration'],
                'cat_id' => $data['cat_id'],
                'sort_num' => $data['sort_num'] ? intval($data['sort_num']) : 50,
                'enable' => $data['enable'],
                'is_hot' => $data['is_hot'],
                'created' => time(),
            ];
            if (\Models\Music\Music::updateOne($data, 'id=' . $song_id)) {
                AdminLog::init()->add('编辑音乐', AdminLog::TYPE_MUSIC, $song_id, ['type' => 'update', 'id' => $song_id, 'data' => $data]);
                $this->ajax->outRight("编辑成功");
            }
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
        } else {
            $data = [
                'name' => $data['name'],
                'singer' => $data['singer'],
                'mp3' => $data['mp3'],
                'thumb' => $data['thumb'],
                'album' => $data['album'],
                'time' => $data['duration'],
                'cat_id' => $data['cat_id'],
                'sort_num' => $data['sort_num'] ? intval($data['sort_num']) : 50,
                'enable' => $data['enable'],
                'is_hot' => $data['is_hot'],
                'created' => time(),
            ];

            if ($id = \Models\Music\Music::insertOne($data)) {
                AdminLog::init()->add('添加音乐', AdminLog::TYPE_MUSIC, $id, ['type' => 'add', 'id' => $id, 'data' => $data]);
                $this->ajax->outRight("添加成功");
            }
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "添加失败");
        }
    }

    public function editAction()
    {
        $song_id = $this->request->get("song_id", 'int', 0);
        $type = $this->request->get("type", 'int', 0);
        if (!$song_id || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = [];
        if ($type == 1) {
            $data['enable'] = 1;
        } else if ($type == 2) {
            $data['enable'] = 0;
        } else if ($type == 3) {
            $data['is_hot'] = 1;
        } else if ($type == 4) {
            $data['is_hot'] = 0;
        }
        if ($data) {
            if (\Models\Music\Music::updateOne($data, 'id=' . $song_id)) {
                AdminLog::init()->add('编辑音乐', AdminLog::TYPE_MUSIC, $song_id, ['type' => 'update', 'id' => $song_id, 'data' => $data]);
                $this->ajax->outRight("编辑成功");
            }
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
    }

    public function getBufferAction()
    {
        $url = base64_decode($this->request->get("url", 'string'));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $r = curl_exec($ch);
        curl_close($ch);


        //  $fp = fopen($url, "r");
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Content-Disposition: attachment; filename=music.mp3");
        header("Content-Length: " . strlen($r));

        echo($r);
        exit;
        /* $buffer = 1024;
         while (!feof($fp)) {
             echo fread($fp, $buffer);
         }
         fclose($fp);
         exit;*/
        //  $content = file_get_contents($url);
        // echo $content;
        //exit;
    }

    //音频剪切
    public function cutAction()
    {
        $item = $this->request->get("item", 'string', '');
        $start = $this->request->get("start");
        $t = round($this->request->get("t"));
        $item = json_decode(base64_decode($item), true);
        //var_dump($item);exit;
        $ext = explode('?', $item['mp3'])[0];
        $ext = explode('.', $ext);
        $ext = $ext[count($ext) - 1];
        if (!is_dir(ROOT . "/upload/audio/" . date('Ymd'))) {
            mkdir(ROOT . "/upload/audio/" . date('Ymd'), 0777, true);
        }

        $input = "upload/audio/" . date('Ymd') . '/' . time() . rand() . '.' . $ext;
        $output = "upload/audio/" . date('Ymd') . '/out_' . time() . rand() . '.mp3';
        $content = file_get_contents($item['mp3']);
        if (!$content) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "资源下载失败");
        }
        if (file_put_contents(ROOT . '/' . $input, $content)) {
            $start = ceil($start);
            if ($start >= 60) {
                $m = intval($start / 60);
                $m = $m > 10 ? $m : '0' . $m;
                $s = ceil($start % 60);
            } else {
                $m = '00';
                $s = ceil($start % 60);
            }
            $start = "00:" . $m . ':' . ($s > 10 ? $s : '0' . $s);


            //检测图片是否存在

            //$video = Audio::init(ROOT, "F:/php/ffmpeg/bin/ffmpeg.exe");
            $video = Audio::init(ROOT, "ffmpeg");
            $video->setInput($input)
                ->setOutput($output)
                ->cut($start, $t);

            unlink(ROOT . '/' . $input);

            if (file_exists(ROOT . '/' . $output)) {
                $file_md5 = md5(file_get_contents(ROOT . '/' . $output));
                if ($url = Upload::checkFile($file_md5)) {
                    unlink(ROOT . '/' . $output);
                    $this->ajax->outRight($url);
                }

                //时长
                $duration = '_t_' . $t;
                $config = $this->di->get('config')->oss;
                $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
                $name = 'music/' . date('Ymd') . '/' . time() . rand(0, 1000) . $duration . ".mp3";
                $res = $oss->uploadFile(OssManager::BUCKET_MUSIC, $name, ROOT . '/' . $output);
                if ($res && !empty($res['info']['url'])) {
                    $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_MUSIC], OssManager::$bind_domain[OssManager::BUCKET_MUSIC], $res['info']['url']);
                    //if (!Upload::checkFile($file_md5)) {
                    Upload::syncDb(['md5' => $file_md5, 'folder' => date('Ym'), 'ext' => 'mp3', 'type' => 'audio', 'size' => filesize(ROOT . '/' . $output), 'name' => $name, 'url' => $url, 'created' => time()]);
                    // }
                    unlink(ROOT . "/" . $output);
                    $this->ajax->outRight($url);
                    /*  $data = [
                          'name' => $item['name'],
                          'singer' => $item['singer'],
                          'mp3' => $url,
                          'thumb' => $item['thumb'],
                          'album' => $item['album'],
                          'time' => round($t),
                          'cat_id' => 0,
                          'sort_num' => 50,
                          'enable' => 1,
                          'is_hot' => 0,
                          'created' => time(),
                      ];
                      if ($id = \Models\Music\Music::insertOne($data)) {
                          AdminLog::init()->add('添加音乐', AdminLog::TYPE_MUSIC, $id, ['type' => 'add', 'id' => $id, 'data' => $data]);
                          $this->ajax->outRight("添加成功");
                      } else {
                          $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "添加音乐到库失败");
                      }*/
                }
                unlink(ROOT . "/" . $output);
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "音频处理失败");
                unlink(ROOT . "/" . $output);
            }

        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "文件写入失败");
        }
        unlink(ROOT . "/" . $input);
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "上传失败");
    }

    //获取歌词
    public function getLyricAction()
    {
        $platform = $this->request->get("platform", 'string', "qq");
        $song_id = $this->request->get("song_id", 'string', '');
        if (!$song_id || !$platform) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $music = new Music($platform);
        $lyric = $music->lyric($song_id);
        $url = $music->mp3Url($song_id);
        $this->ajax->outRight(['lyric' => $lyric['lyric'], 'trans' => $lyric['trans'], 'url' => $url]);
    }


}