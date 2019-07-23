<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/1
 * Time: 16:36
 */

namespace Components\Music;


use Models\Music\MusicDownloadHistory;
use Models\Music\MusicInfo;

class Music
{
    private static $instance = null;
    public $music = StructureMusic::DRIVER_163;

    public function __construct($driver = StructureMusic::DRIVER_163)
    {
        $struct_music = new StructureMusic($driver);
        $this->music = $struct_music->getInstance();
    }

    public static function init($driver = StructureMusic::DRIVER_163)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($driver);
        }
        return self::$instance;
    }

    /**第三方音乐搜索
     * @param $key
     * @param $type
     * @param $page
     * @param $limit
     * @return array|mixed
     */
    public function search($key, $type, $page, $limit)
    {
        if ($key) {
            $this->music->setProperty(["key" => $key, 'type' => $type, 'limit' => $limit, 'page' => $page]);
            $res = $this->music->searchMusic();
        } else {
            $this->music->setProperty(["page" => $page, 'limit' => $limit]);
            $res = $this->music->newMusic();
        }
        /*  if ($res['data_list']) {
              foreach ($res['data_list'] as $item) {
                  if (!MusicInfo::exist('song_id="' . $item['song_id'] . '"')) {
                      MusicInfo::insertOne(['song_id' => $item['song_id'], 'info' => json_encode($item, JSON_UNESCAPED_UNICODE)]);
                  } else {
                      // MusicInfo::updateOne(['info' => json_encode($item, JSON_UNESCAPED_UNICODE)], 'song_id="' . $item['song_id'] . '"');
                  }
              }
          }*/
        return $res;
    }

    //获取搜索关键字
    public function getSearchKey($key)
    {
        $res = ['data_count' => 0, 'data_list' => []];
        if ($key) {
            $res = $this->music->getSearchKey($key);
        }
        return $res;
    }


    /**平台内搜索
     * @param $key
     * @param int $cat_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function searchPlatform($key, $cat_id = 0, $page = 1, $limit = 20)
    {
        $res = ['data_count' => 0, 'data_list' => []];
        $where = "enable=1";
        if ($key) {
            $where .= " and (name like '%" . $key . "%' or album like '%" . $key . "%' or singer like '%" . $key . "%')";
        }
        if ($cat_id) {
            $where .= " and (LOCATE('" . $cat_id . ",',concat(cat_id,','))>0)";
        }
        $res['data_count'] = \Models\Music\Music::dataCount($where);
        $list = \Models\Music\Music::findList([$where, 'order' => 'is_hot desc,sort_num asc,created desc', 'columns' => 'id as song_id,mp3,name,singer,album,time,thumb', 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
        if ($list) {
            foreach ($list as $item) {
                $tmp = $item;
                $tmp['time'] = intval($item['time']);
                $res['data_list'][] = $tmp;
            }
        }
        return $res;
    }

    public function detail($song_id)
    {
        $res = $this->music->musicDetail($song_id);
    }

    /**下载成功 记录历史
     * @param $uid
     * @param $song_id
     * @return bool|int
     */
    public function downloadSuccess($uid, $song_id)
    {
        $song = MusicInfo::findOne(['song_id="' . $song_id . '"', 'columns' => 'info']);
        if (!$song) {
            return false;
        }
        //之前下载过
        if ($id = MusicDownloadHistory::findOne(['user_id=' . $uid . ' and song_id="' . $song_id . '"', 'columns' => 'id'])) {
            return MusicDownloadHistory::updateOne(['created' => time()], 'id=' . $id['id']);
        } else {
            return MusicDownloadHistory::insertOne(['created' => time(), 'user_id' => $uid, 'song_id' => $song_id]);
        }
    }

    /**下载历史
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function downloadHistory($uid, $page = 1, $limit = 20)
    {
        $data = ['data_list' => []];
        $list = MusicDownloadHistory::findList(['user_id=' . $uid, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'columns' => 'song_id,created', 'order' => 'created desc']);
        if ($list) {
            $song_ids = array_column($list, 'song_id');

            $music = MusicInfo::getByColumnKeyList(['song_id in("' . implode('","', $song_ids) . '")', 'columns' => 'info,song_id'], 'song_id');
            foreach ($list as &$item) {
                $item['song_info'] = json_decode($music[$item['song_id']]['info'], true);
                $data['data_list'][] = $item;
            }
        }
        return $data;
    }

    /**删除下载历史
     * @param $uid
     * @param $song_id
     * @return array
     */
    public function removeDownloadHistory($uid, $song_id)
    {
        $song_id = array_unique(array_filter(explode(',', $song_id)));
        return MusicDownloadHistory::remove('user_id=' . $uid . " and song_id in ('" . implode("','", $song_id) . "')");
    }

    public function lyric($song_id)
    {
        $res = $this->music->getLyric($song_id);
        return $res;
    }

    public function mp3Url($song_id)
    {
        $res = $this->music->getUrl($song_id);
        return $res;
    }

    public function topList($top_id, $page, $limit)
    {
        $this->music->setProperty(["page" => $page, 'limit' => $limit]);
        $res = $this->music->top_playlist($top_id);
        return $res;
    }

    public function recommendPlaylist($cat, $page, $limit)
    {
        $this->music->setProperty(["page" => $page, 'limit' => $limit]);
        $res = $this->music->recommendPlayList($cat);
        return $res;
    }
}