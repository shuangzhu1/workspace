<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/1
 * Time: 11:44
 */

namespace Multiple\Api\Controllers;


use Components\Music\Music;
use Services\Discuss\MusicManager;
use Util\Ajax;

class MusicController extends ControllerBase
{
    //歌曲搜索
    public function searchAction()
    {
        $uid = $this->uid;
        $key = trim($this->request->get("key", 'string', ''));
        $cat_id = trim($this->request->get("cat_id", 'int', 0));

        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 10);
        $platform = trim($this->request->get("platform", 'string', 'qq'));
        $type = $this->request->get("type", 'int', 1);//1-音乐 2-歌手 3-专辑 4-歌单,5-MV,6-歌词,7-电台
        if (!$uid || !$type) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $res = Music::init()->searchPlatform($key, $cat_id, $page, $limit);//Music::init()->search($key, $type, $page, $limit, $platform);
        $this->ajax->outRight($res);

    }

    //音乐下载历史
    public function historyAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 1);//第几页 默认1
        $limit = $this->request->get("limit", 'int', 20);//每页显示的数量
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(Music::init()->downloadHistory($uid, $page, $limit));

    }

    //音乐下载完成
    public function downloadSuccessAction()
    {
        $uid = $this->uid;
        $song_id = $this->request->get("song_id", 'string', '');//音乐id
        if (!$uid || !$song_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (Music::init()->downloadSuccess($uid, $song_id)) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    //删除下载历史
    public function removeHistoryAction()
    {
        $uid = $this->uid;
        $song_id = $this->request->get("song_id", 'string', '');//音乐id 多条以英文,分割
        if (!$uid || !$song_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (Music::init()->removeDownloadHistory($uid, $song_id)) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }
}