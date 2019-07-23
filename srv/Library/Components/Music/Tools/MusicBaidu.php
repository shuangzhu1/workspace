<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/1
 * Time: 17:11
 */

namespace Components\Music\Tools;


use Components\Curl\CurlManager;
use Components\Music\AbstractMusic;
use Components\Music\Tools\Baidu\BaiduMusicApi;
use Util\Time;

class MusicBaidu extends AbstractMusic
{
    const AREA_ALL = 0;//全部地区
    const AREA_CHINIESE = 6;//华语
    const AREA_EU = 3;//欧美
    const AREA_KOREA = 7;//韩国
    const AREA_JAPAN = 60;//日本
    const AREA_OTHER = 5;//其他

    const SEX_NONE = 0;//无选择
    const SEX_MALE = 1;//男
    const SEX_FEMALE = 2;//女
    const SEX_GROUP = 3;//组合

    private static $baseUrl = "http://tingapi.ting.baidu.com/v1/restserver/ting?from=webapp_music&version=2.1.0&format=json";

    //搜索
    public function searchMusic()
    {
        $data = ['data_count' => 0, 'data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.search.common&query=" . $this->key . "&page_no=" . $this->page . "&page_size=" . $this->limit;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $data['data_count'] = intval($res['pages']['total']);
            if ($res['song_list']) {
                $song_ids = [];
                foreach ($res['song_list'] as $item) {
                    $temp = [
                        'song_id' => $item['song_id'],
                        'name' => strip_tags($item['title']),
                        "time" => "",
                        'singer' => strip_tags($item['author']),
                        'album' => strip_tags($item['album_title']),
                        'thumb' => "",
                        'mp3' => "",
                        'lyric' => $item['lrclink'] ? ("http://tingapi.ting.baidu.com" . $item['lrclink']) : '',
                        'platform' => 'baidu'
                    ];
                    $data["data_list"][] = $temp;
                    $song_ids[] = $item['song_id'];
                }

                //10首歌一批。百度最大支持
                $time = ceil(count($song_ids) / 10);
                for ($i = 1; $i <= $time; $i++) {
                    $ids = array_slice($song_ids, ($i - 1) * 10, 10);
                    //  var_dump($ids);
                    $url = "http://music.baidu.com/data/music/links?songIds=" . implode(',', $ids);
                    $res = CurlManager::init()->curl_get_contents($url);
                    $res = json_decode($res['data'], true);
                    // var_dump($res);
                    if ($res) {
                        $res = $res['data']['songList'];
                        $song_list = array_combine(array_column($res, 'songId'), $res);
                        foreach ($data["data_list"] as &$item) {
                            if (isset($song_list[$item['song_id']])) {
                                $item['time'] = (string)$song_list[$item['song_id']]['time'];
                                $item['thumb'] = $song_list[$item['song_id']]['songPicRadio'] ? $song_list[$item['song_id']]['songPicRadio'] : $song_list[$item['song_id']]['songPicBig'];
                                $item['mp3'] = $song_list[$item['song_id']]['songLink'];
                                $item['lyric'] = $song_list[$item['song_id']]['lrcLink'];

                                //  $item['song_id'] = $item['song_id'];/*"bd_" .*/
                            }
                        }
                    }
                }

            }
        }
        return $data;
    }

    //音乐详情
    public function musicDetail($song_ids)
    {
        $res = [];
        $base_url = self::$baseUrl . "&method=baidu.ting.song.baseInfos&song_id=" . $song_ids;
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            $song_ids = [];
            foreach ($data['result']['items'] as $item) {
                $res[$item['song_id']] = [
                    'song_id' => $item['song_id'],
                    'name' => strip_tags($item['song_title']),
                    "time" => "",
                    'singer' => strip_tags($item['author']),
                    'album' => strip_tags($item['album_title']),
                    'thumb' => "",
                    'mp3' => "",
                    'lyric' => $item['lrclink'] ? ("http://tingapi.ting.baidu.com" . $item['lrclink']) : '',
                    'platform' => 'baidu'
                ];
                $song_ids[] = $item['song_id'];
            }
            //10首歌一批。百度最大支持
            $time = ceil(count($song_ids) / 10);
            for ($i = 1; $i <= $time; $i++) {
                $ids = array_slice($song_ids, ($i - 1) * 10, 10);
                //  var_dump($ids);
                $url = "http://music.baidu.com/data/music/links?songIds=" . implode(',', $ids);
                $data = CurlManager::init()->curl_get_contents($url);
                $data = json_decode($data['data'], true);
                if ($data) {
                    $data = $data['data']['songList'];
                    $song_list = array_combine(array_column($data, 'songId'), $data);
                    foreach ($res as &$item) {
                        if (isset($song_list[$item['song_id']])) {
                            $item['time'] = (string)$song_list[$item['song_id']]['time'];
                            $item['thumb'] = $song_list[$item['song_id']]['songPicRadio'] ? $song_list[$item['song_id']]['songPicRadio'] : ($song_list[$item['song_id']]['songPicBig'] ? $song_list[$item['song_id']]['songPicBig'] : $item['thumb']);
                            $item['mp3'] = $song_list[$item['song_id']]['songLink'];
                            $item['lyric'] = $song_list[$item['song_id']]['lrcLink'];

                            //  $item['song_id'] = $item['song_id'];/*"bd_" .*/
                        }
                    }
                }
            }
        }
        return $res;
    }

    //歌曲信息和下载地址
    public function songInfo($song_id)
    {
        $res = [];
        $str = "songid=" . $song_id . "&ts=" . Time::getMillisecond();
        $baidu = new BaiduMusicApi();
        $e = $baidu->encrypt($str);
        $base_url = (self::$baseUrl . "&method=baidu.ting.song.getInfos&" . $str . "&e=" . $e);
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            $res = [
                'song_id' => $data['songinfo']['song_id'],
                'name' => $data['songinfo']['title'],
                "time" => $data['songinfo']['file_duration'],
                'singer' => ($data['songinfo']['author']),
                'album' => ($data['songinfo']['album_title']),
                'thumb' => ($data['songinfo']['pic_big']),
                'mp3' => $data['songurl']['url']['0']['file_link'],
                'lyric' => $data['songinfo']['lrclink'] ? $data['songinfo']['lrclink'] : '',
                'platform' => 'baidu'
            ];
        }
        return $res;
    }

    //歌曲伴奏
//    public function accompanyInfo($song_id)
//    {
//        $res = [];
//        $str = "song_id=" . $song_id . "&ts=" . Time::getMillisecond();
//        $baidu = new BaiduMusicApi();
//        $e = $baidu->encrypt($str);
//        $base_url = (self::$baseUrl . "&method=baidu.ting.learn.down&" . $str . "&e=" . $e);
//        $data = CurlManager::init()->curl_get_contents($base_url);
//        $data = json_decode($data['data'], true);
//        var_dump($data);
////        if ($data) {
////            $res = [
////                'song_id' => $data['songinfo']['song_id'],
////                'name' => $data['songinfo']['title'],
////                "time" => $data['songinfo']['file_duration'],
////                'singer' => ($data['songinfo']['author']),
////                'album' => ($data['songinfo']['album_title']),
////                'thumb' => ($data['songinfo']['pic_big']),
////                'mp3' => $data['songurl']['url']['0']['file_link'],
////                'lyric' => $data['songinfo']['lrclink'] ? $data['songinfo']['lrclink'] : '',
////                'platform' => 'baidu'
////            ];
////        }
////        return $res;
//    }
//相似歌曲
//    public function recommendSongList($song_id, $num)
//    {
//        $data = ['data_count' => 0, 'data_list' => []];
//        $base_url = self::$baseUrl . "&method=baidu.ting.search.common&song_id=" .$song_id . "&page_no=" . $this->page . "&page_size=" . $this->limit;
//        echo $base_url;exit;
//        $res = CurlManager::init()->curl_get_contents($base_url);
//
//        $res = json_decode($res['data'], true);
//    }

    //---艺术家相关---

    //艺术家列表
    /**
     * 获取艺术家列表
     * @param $area -地区：0不分,6华语,3欧美,7韩国,60日本,5其他
     * @param $sex -性别：0不分,1男,2女,3组合
     * @param $order -排序：1按热门，2按艺术家id
     * @param $abc -艺术家名首字母：a-z,other其他
     * @return array
     */
    public function artistList($area = 0, $sex = 0, $order = 1, $abc = null)
    {
        $data = ['data_count' => 0, 'data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.artist.getList&limit=" . $this->limit . "&offset=" . ($this->page - 1) * $this->limit . "&area=" . $area . "&sex=" . $sex . "&order=" . $order . "&abc=" . $abc;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $data['data_count'] = $res['nums'];
            foreach ($res['artist'] as $item) {
                $tmp = [
                    'artist_id' => $item['artist_id'],
                    'name' => $item['name'],
                    'songs_total' => $item['songs_total'],
                    'albums_total' => $item['albums_total'],
                    'avatar' => $item['avatar_big'],
                    'gender' => $item['gender'],
                    'country' => $item['country'],
                ];
                $data['data_list'][] = $tmp;
            }
        }
        return $data;
    }

    //艺术家歌曲
    public function artistSongList($artist_id)
    {
        $data = ['data_count' => 0, 'data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.artist.getSongList&limits=" . $this->limit . "&offset=" . ($this->page - 1) * $this->limit . "&artistid=" . $artist_id;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $data['data_count'] = $res['songnums'];
            $song_ids = [];
            foreach ($res['songlist'] as $item) {
                $temp = [
                    'song_id' => $item['song_id'],
                    'name' => strip_tags($item['title']),
                    "time" => "",
                    'singer' => strip_tags($item['author']),
                    'album' => strip_tags($item['album_title']),
                    'thumb' => "",
                    'mp3' => "",
                    'lyric' => $item['lrclink'] ? ("http://tingapi.ting.baidu.com" . $item['lrclink']) : '',
                    'platform' => 'baidu'
                ];
                $data["data_list"][] = $temp;
                $song_ids[] = $item['song_id'];
            }
            //10首歌一批。百度最大支持
            $time = ceil(count($song_ids) / 10);
            for ($i = 1; $i <= $time; $i++) {
                $ids = array_slice($song_ids, ($i - 1) * 10, 10);
                //  var_dump($ids);
                $url = "http://music.baidu.com/data/music/links?songIds=" . implode(',', $ids);
                $res = CurlManager::init()->curl_get_contents($url);
                $res = json_decode($res['data'], true);
                if ($res) {
                    $res = $res['data']['songList'];
                    $song_list = array_combine(array_column($res, 'songId'), $res);
                    foreach ($data["data_list"] as &$item) {
                        if (isset($song_list[$item['song_id']])) {
                            $item['time'] = (string)$song_list[$item['song_id']]['time'];
                            $item['thumb'] = $song_list[$item['song_id']]['songPicRadio'] ? $song_list[$item['song_id']]['songPicRadio'] : $song_list[$item['song_id']]['songPicBig'];
                            $item['mp3'] = $song_list[$item['song_id']]['songLink'];
                            !$item['lyric'] && $item['lyric'] = $song_list[$item['song_id']]['lrcLink'];
                            //  $item['song_id'] = $item['song_id'];/*"bd_" .*/
                        }
                    }
                }
            }
        }
        return $data;
    }

    //艺术家信息
    public function artistInfo($artist_id)
    {
        $data = [];
        $base_url = self::$baseUrl . "&method=baidu.ting.artist.getinfo&limits=" . $this->limit . "&artistid=" . $artist_id;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $data = [
                'artist_id' => $artist_id,
                'avatar' => $res['avatar_big'],
                'name' => $res['name'],
                'gender' => $res['gender'],
                'constellation' => $res['constellation'],
                'weight' => $res['weight'],
                'country' => $res['country'],
                'intro' => $res['intro'],
                'songs_total' => $res['songs_total'],
                'albums_total' => $res['albums_total'],
                'mv_total' => $res['mv_total'],
                'listen_num' => $res['listen_num'],

            ];
        }
        return $data;
    }

    // ---音乐榜----
    //所有音乐榜类别
    public function billCategory()
    {
        $data = [];
        $base_url = self::$baseUrl . "&method=baidu.ting.billboard.billCategory&&kflag=1";
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            foreach ($res['content'] as $item) {
                $data[] = ['name' => $item['name'], 'type' => $item['type']];
            }
        }
        return $data;
    }

    //电台
    //录制电台
    public function recChannel()
    {
        $data = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.radio.getRecChannel&page_no=" . $this->page . "&page_size=" . $this->limit;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            foreach ($res['result']['songlist'] as $item) {
                $data['data_list'][] = [
                    'name' => $item['name'],
                    'channel_id' => $item['channelid'],
                    'thumb' => $item['thumb'],
                    'ch_name' => $item['ch_name'],
                    'cate_sname' => $item['cate_sname'],
                    'listen_num' => $item['listen_num'],
                ];
            }
        }
        return $data;
    }

    //推荐电台
    public function recommendRadioList()
    {
        $data = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.radio.getRecommendRadioList&num=" . $this->limit;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            foreach ($res['list'] as $item) {
                $data['data_list'][] = [
                    'channel_id' => $item['channelid'],
                    'thumb' => $item['pic'],
                    'album_id' => $item['album_id'],
                    'item_id' => $item['item_id'],
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'desc' => $item['desc'],
                ];
            }
        }
        return $data;
    }

    //频道歌曲
    public function channelSong($channel_name)
    {
        $data = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.radio.getChannelSong&pn=0&rn=" . $this->limit . "&channelname=" . $channel_name;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $song_ids = [];
            foreach ($res['result']['songlist'] as $item) {
                $temp = [
                    'song_id' => $item['songid'],
                    'name' => strip_tags($item['title']),
                    "time" => "",
                    'singer' => strip_tags($item['artist']),
                    'album' => '',
                    'thumb' => "",
                    'mp3' => "",
                    'lyric' => $item['lrclink'] ? ("http://tingapi.ting.baidu.com" . $item['lrclink']) : '',
                    'platform' => 'baidu'
                ];
                $data["data_list"][] = $temp;
                $song_ids[] = $item['songid'];
            }
            //10首歌一批。百度最大支持
            $time = ceil(count($song_ids) / 10);
            for ($i = 1; $i <= $time; $i++) {
                $ids = array_slice($song_ids, ($i - 1) * 10, 10);
                //  var_dump($ids);
                $url = "http://music.baidu.com/data/music/links?songIds=" . implode(',', $ids);
                $res = CurlManager::init()->curl_get_contents($url);
                $res = json_decode($res['data'], true);

                if ($res) {
                    $res = $res['data']['songList'];
                    $song_list = array_combine(array_column($res, 'songId'), $res);
                    foreach ($data["data_list"] as &$item) {
                        if (isset($song_list[$item['song_id']])) {
                            $item['time'] = (string)$song_list[$item['song_id']]['time'];
                            $item['thumb'] = $song_list[$item['song_id']]['songPicRadio'] ? $song_list[$item['song_id']]['songPicRadio'] : $song_list[$item['song_id']]['songPicBig'];
                            $item['mp3'] = $song_list[$item['song_id']]['songLink'];
                            !$item['album'] && $item['album'] = $song_list[$item['song_id']]['albumName'];
                            !$item['lyric'] && $item['lyric'] = $song_list[$item['song_id']]['lrcLink'];
                            //  $item['song_id'] = $item['song_id'];/*"bd_" .*/
                        }
                    }
                }
            }
        }
        return $data;
    }

    //----------乐播电台--------
    //
    /**电台标签
     * @return array
     */
    public function LeboChannelTag()
    {
        $data = ['data_list' => [], 'data_count' => 0];
        $base_url = self::$baseUrl . "&method=baidu.ting.lebo.getChannelTag&page_no=" . $this->page . "&page_size=" . $this->limit;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $data['data_count'] = $res['result']['total'];
            foreach ($res['result']['taglist'] as $item) {
                $data['data_list'][] = [
                    'tag_id' => $item['tagid'],
                    'name' => $item['tag_name'],
                    'thumb' => $item['tag_pic'],
                ];
            }
        }
        return $data;
    }

    //
    /**电台歌曲列表
     * @param $tag_id
     * @return array
     */
    public function LebochannelSongList($tag_id)
    {
        $data = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.lebo.channelSongList&tag_id=$tag_id&num=" . $this->limit;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            foreach ($res['result']['list'] as $item) {
                $data['data_list'][] = [
                    'song_id' => $item['song_id'],
                    'name' => $item['song_name'],
                    'time' => $item['song_duration'],
                    'listen_cnt' => $item['listen_cnt'],
                    'thumb' => $item['songpic'][0]['pic_url'],
                    "share_cnt" => $item['share_cnt'],
                    "zan_cnt" => $item['zan_cnt'],
                    "download_cnt" => $item['download_cnt'],
                    "mp3" => $item['songfile'][0]['file_link'],
                    'album_id' => $item['album_id'],
                    'album_name' => $item['album_name'],
                ];
            }
        }
        return $data;
    }

    //
    /**电台节目信息
     * @param $album_id
     * @return array
     */
    public function LeboAlbumInfo($album_id)
    {
        $data = [];
        $base_url = self::$baseUrl . "&method=baidu.ting.lebo.albumInfo&album_id=$album_id";
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $data = $res["result"];
        }
        return $data;
    }

    //随便听听
    public function randomMusic()
    {

    }

    //新歌榜
    public function newMusic()
    {
        //1-新歌榜,2-热歌榜,6-KTV热歌榜,8-Billboard,11-摇滚榜,12-爵士,16-流行,18-Hito中文榜,21-欧美金曲榜,22-经典老歌榜,23-情歌对唱榜,24-影视金曲榜,25-网络歌曲榜
        $base_url = self::$baseUrl . "&method=baidu.ting.billboard.billList&type=1&size=" . $this->limit . "&offset=" . (($this->page - 1) * $this->limit);
        return $this->parseData($base_url);
    }

    //推荐榜
    public function recommendMusic()
    {

    }

    //总排行榜
    public function topMusic($top_id = 2)
    {
        $base_url = self::$baseUrl . "&method=baidu.ting.billboard.billList&type=" . $top_id . "&size=" . $this->limit . "&offset=" . (($this->page - 1) * $this->limit);
        return $this->parseData($base_url);
    }

    //获取标签列表
    public function getTags()
    {
        $base_url = self::$baseUrl . "&method=baidu.ting.diy.gedanCategory";
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        return $res['content'];
    }

    //所有音乐标签
    public function allSongTags()
    {
        $res = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.tag.getAllTag";
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            foreach ($data['taglist'] as $k => $item) {
                foreach ($item as $i) {
                    $res['data_list'][$k] .= ',' . $i['title'];
                }
                $res['data_list'][$k] = substr($res['data_list'][$k], 1);
            }
        }
        return $res;
    }

    //热门歌曲标签
    public function hotSongTags()
    {
        $res = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.tag.getHotTag&nums=100";
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            foreach ($data['taglist'] as $k => $item) {
                $res['data_list'][] = $item['title'];
            }
        }
        return $res;
    }

    //标签为tagname的歌曲
    public function tagSongs($tag)
    {
        $this->page = 1;
        $this->limit = 10;
        $res = ['song_list' => [], 'data_count' => 0];
        $base_url = self::$baseUrl . "&method=baidu.ting.tag.songlist&offset=" . ($this->page - 1) * $this->limit . "&limit=" . $this->limit . "&tagname=" . $tag;
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            $song_ids = [];
            $res['data_count'] = $data['taginfo']['count'];
            foreach ($data['taginfo']['songlist'] as $item) {
                $res['song_list'][] = [
                    'song_id' => $item['song_id'],
                    'name' => strip_tags($item['title']),
                    "time" => $item['file_duration'],
                    'singer' => strip_tags($item['author']),
                    'album' => strip_tags($item['album_title']),
                    'thumb' => $item['pic_big'],
                    'mp3' => "",
                    'lyric' => $item['lrclink'] ? ($item['lrclink']) : '',
                    'platform' => 'baidu'
                ];
                $song_ids[] = $item['song_id'];
            }
            //10首歌一批。百度最大支持
            $time = ceil(count($res['song_list']) / 10);
            for ($i = 1; $i <= $time; $i++) {
                $ids = array_slice($song_ids, ($i - 1) * 10, 10);
                //  var_dump($ids);
                $url = "http://music.baidu.com/data/music/links?songIds=" . implode(',', $ids);
                $data = CurlManager::init()->curl_get_contents($url);
                $data = json_decode($data['data'], true);
                if ($data) {
                    $data = $data['data']['songList'];
                    $song_list = array_combine(array_column($data, 'songId'), $data);
                    foreach ($res["song_list"] as &$item) {
                        if (isset($song_list[$item['song_id']])) {
                            $item['time'] = (string)$song_list[$item['song_id']]['time'];
                            $item['thumb'] = $song_list[$item['song_id']]['songPicRadio'] ? $song_list[$item['song_id']]['songPicRadio'] : ($song_list[$item['song_id']]['songPicBig'] ? $song_list[$item['song_id']]['songPicBig'] : $item['thumb']);
                            $item['mp3'] = $song_list[$item['song_id']]['songLink'];
                            !$item['lyric'] && $item['lyric'] = $song_list[$item['song_id']]['lrcLink'];

                            //  $item['song_id'] = $item['song_id'];/*"bd_" .*/
                        }
                    }
                }
            }
        }
        return $res;
    }


    public function parseData($url)
    {
        $data = ['data_list' => [], 'data_count' => 0];

        $base_url = $url;
        $res = CurlManager::init()->curl_get_contents($base_url);

        $res = json_decode($res['data'], true);
        if ($res) {
            if ($res['song_list']) {
                $data['data_count'] = count($res['song_list']);
                $song_ids = [];
                foreach ($res['song_list'] as $item) {
                    $temp = [
                        'song_id' => $item['song_id'],
                        'name' => strip_tags($item['title']),
                        "time" => "",
                        'singer' => strip_tags($item['author']),
                        'album' => strip_tags($item['album_title']),
                        'thumb' => $item['pic_small'],
                        'mp3' => "",
                        'lyric' => $item['lrclink'] ? ($item['lrclink']) : '',
                        'platform' => 'baidu'
                    ];
                    $data["data_list"][] = $temp;
                    $song_ids [] = $item['song_id'];
                }
                //10首歌一批。百度最大支持
                $time = ceil(count($res['song_list']) / 10);
                for ($i = 1; $i <= $time; $i++) {
                    $ids = array_slice($song_ids, ($i - 1) * 10, 10);
                    //  var_dump($ids);
                    $url = "http://music.baidu.com/data/music/links?songIds=" . implode(',', $ids);
                    $res = CurlManager::init()->curl_get_contents($url);
                    $res = json_decode($res['data'], true);
                    if ($res) {
                        $res = $res['data']['songList'];
                        $song_list = array_combine(array_column($res, 'songId'), $res);
                        foreach ($data["data_list"] as &$item) {
                            if (isset($song_list[$item['song_id']])) {
                                $item['time'] = (string)$song_list[$item['song_id']]['time'];
                                $item['thumb'] = $song_list[$item['song_id']]['songPicRadio'] ? $song_list[$item['song_id']]['songPicRadio'] : ($song_list[$item['song_id']]['songPicBig'] ? $song_list[$item['song_id']]['songPicBig'] : $item['thumb']);
                                $item['mp3'] = $song_list[$item['song_id']]['songLink'];
                                !$item['lyric'] && $item['lyric'] = $song_list[$item['song_id']]['lrcLink'];

                                //  $item['song_id'] = $item['song_id'];/*"bd_" .*/
                            }
                        }
                    }
                }

            }
        }
        return $data;
    }

    public function lyric($song_id)
    {
        $base_url = self::$baseUrl . "&method=baidu.ting.song.lry&songid=" . $song_id;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        return $res ? $res['lrcContent'] : '';
    }

    public function mvUrl($song_id)
    {
        $base_url = self::$baseUrl . "&method=baidu.ting.song.lry&songid=" . $song_id;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        var_dump($res);
    }

    //歌曲地址
    public function mp3Url($song_id)
    {
        $data = '';
        $url = "http://music.baidu.com/data/music/links?songIds=" . $song_id;
        $res = CurlManager::init()->curl_get_contents($url);
        $res = json_decode($res['data'], true);
        // var_dump($res);
        if ($res) {
            $res = $res['data']['songList'];
            $data = $res ? $res[0]['songLink'] : '';
        }
        return $data;
    }

    //--搜索相关---

    //热词
    public function hotWord()
    {
        $base_url = self::$baseUrl . "&method=baidu.ting.search.hot";
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        return $res;
    }

    //搜索建议
    public function searchSugestion($key)
    {
        $list = ['song' => ['count' => 0, 'list' => []], 'singer' => ['count' => 0, 'list' => []], 'album' => ['count' => 0, 'list' => []]];
        $base_url = self::$baseUrl . "&method=baidu.ting.search.catalogSug&query=" . $key;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            if (!empty($res['song'])) {
                $list['song']['count'] = count($res['song']);
                foreach ($res['song'] as $item) {
                    $list['song']['list'][] = [
                        'song_name' => $item['songname'],
                        'song_id' => $item['songid'],
                        'singer' => $item['artistname'],
                    ];
                }
            }
            if (!empty($res['album'])) {
                $list['album']['count'] = count($res['album']);
                foreach ($res['album'] as $item) {
                    $list['album']['list'][] = [
                        'album_name' => $item['albumname'],
                        'album_id' => $item['albumid'],
                        'singer' => $item['artistname'],
                    ];
                }
            }
            if (!empty($res['artist'])) {
                $list['singer']['count'] = count($res['artist']);
                foreach ($res['artist'] as $item) {
                    $list['singer']['list'][] = [
                        'singer_id' => $item['artistid'],
                        'singer' => $item['artistname'],
                    ];
                }
            }

        }
        return $list;
    }

//    //搜索歌词
//    public function searchLrcPic($song_name, $singer)
//    {
//        $str = ($song_name) . "$$" . ($singer);
//        $baidu = new BaiduMusicApi();
//        $time = Time::getMillisecond();
//        $e = $baidu->encrypt("query=" . $str . "&ts=" . $time);
//        $base_url = (self::$baseUrl . "&method=baidu.ting.search.lrcpic&e=" . $e . "&query=" . $str . "&type=2&ts=" . $time);
//        echo $base_url;
//       exit;
//        $res = CurlManager::init()->curl_get_contents($base_url);
//        $res = json_decode($res['data'], true);
//        var_dump($res);
//        exit;
//
//    }
// 合并搜索结果，用于搜索建议中的歌曲
    public function searchMerge($key)
    {
        $list = ['song' => ['count' => 0, 'list' => []], 'singer' => ['count' => 0, 'list' => []], 'album' => ['count' => 0, 'list' => []]];
        $base_url = self::$baseUrl . "&method=baidu.ting.search.merge&query=" . $key . "&page";
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $res = $res['result'];
            if (!empty($res['song_info']['song_list'])) {
                $list['song']['count'] = $res['song_info']['total'];
                foreach ($res['song_info']['song_list'] as $item) {
                    $list['song']['list'][] = [
                        'song_name' => $item['songname'],
                        'song_id' => $item['songid'],
                        'singer' => $item['artistname'],
                    ];
                }
            }
            if (!empty($res['album_info']['album_list'])) {
                $list['album']['count'] = $res['album_info']['total'];
                foreach ($res['album_info']['album_list'] as $item) {
                    $list['album']['list'][] = [
                        'album_name' => $item['albumname'],
                        'album_id' => $item['albumid'],
                        'singer' => $item['artistname'],
                    ];
                }
            }
            if (!empty($res['artist_info']['artist_list'])) {
                $list['singer']['count'] = $res['artist_info']['total'];
                foreach ($res['artist_info']['artist_list'] as $item) {
                    $list['singer']['list'][] = [
                        'singer_id' => $item['artistid'],
                        'singer' => $item['artistname'],
                    ];
                }
            }
        }
        return $list;
    }

    //搜索伴奏
    public function searchAccompany($key)
    {
        $data = ['data_count' => 0, 'data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.learn.search&query=" . $key . "&page_no=" . $this->page . "&page_size=" . $this->limit;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res) {
            $data['data_count'] = $res['result']['total'];
            foreach ($res['result']['items'] as $item) {
                $data['data_list'][] = [
                    'song_name' => $item['song_title'],
                    'song_id' => $item['song_id'],
                    'singer' => $item['artist_name'],
                ];
            }
        }
        return $data;
    }

    //搜索建议
    public function getSearchKey($key)
    {
        $list = ['song' => ['count' => 0, 'list' => []], 'singer' => ['count' => 0, 'list' => []], 'album' => ['count' => 0, 'list' => []]];
        $base_url = "http://sug.music.baidu.com/info/suggestion?format=json&word=$key&version=2&from=0&third_type=0&client_type=0&_=";
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        if ($res && $res['data']) {
            foreach ($res['data']['song'] as $item) {
                $list['song']['count'] += 1;
                $list['song']['list'][] = [
                    'song_name' => $item['songname'],
                    'song_id' => $item['songid'],
                    'singer' => $item['artistname'],
                ];
            }
            foreach ($res['data']['album'] as $item) {
                $list['album']['count'] += 1;
                $list['album']['list'][] = [
                    'album_name' => $item['albumname'],
                    'album_id' => $item['albumid'],
                    'singer' => $item['artistname'],
                ];
            }
            foreach ($res['data']['artist'] as $item) {
                $list['singer']['count'] += 1;
                $list['singer']['list'][] = [
                    'singer_id' => $item['artistid'],
                    'singer' => $item['artistname'],
                ];
            }
        }
        return $list;
    }

    //获取歌词
    public function getLyric($song_id)
    {
        $res = ['lyric' => [], 'trans' => ''];
        $data = $this->lyric($song_id);
        if ($data) {
            $data = explode("\n", $data);
            foreach ($data as $i) {
                preg_match('/^\[(\d*:\d*.\d*)\](.*?)$/', $i, $match);
                if ($match) {
                    $res['lyric'][] = ['time' => $match[1], 'word' => $match[2]];
                }
            }
        }
        return $res;
    }

    //获取歌曲地址
    public function getUrl($song_id)
    {
        $data = $this->mp3Url($song_id);
        return $data;
    }

    //歌单排行榜
    public function top_playlist($playlist_id)
    {
        return $this->topMusic($playlist_id);
    }

    //热门歌单
    public function hot_playlist()
    {
        $base_url = self::$baseUrl . "&method=baidu.ting.diy.getHotGeDanAndOfficial";
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        return $res['content']['list'];
    }

    //热门歌单
    public function playlist()
    {
        $base_url = self::$baseUrl . "&method=baidu.ting.diy.gedan&page_size=" . $this->limit . "&page_no=" . $this->page;
        $res = CurlManager::init()->curl_get_contents($base_url);
        $res = json_decode($res['data'], true);
        return $res;
    }

    //推荐专辑
    public function recommendAlbum()
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $this->page = 1;
        $this->limit = 10;
        $base_url = self::$baseUrl . "&method=baidu.ting.plaza.getRecommendAlbum&offset=" . ($this->page - 1) * $this->limit . "&limit=" . $this->limit;
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        $res['data_list'] = $data['plaze_album_list']['RM']['album_list']['list'];
        $res['data_count'] = $data['plaze_album_list']['RM']['album_list']['total'];

        return $res;
    }

    //获取专辑信息
    public function getAlbumInfo($albumId)
    {
        $res = ['song_list' => [], 'detail' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.album.getAlbumInfo&album_id=" . $albumId;
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            $res['detail'] = [
                'album_id' => $data['albumInfo']['album_id'],
                'singer' => $data['albumInfo']['author'],
                'songs_total' => $data['albumInfo']['songs_total'],
                'time' => $data['albumInfo']['publishtime'],
                'singer_id' => $data['albumInfo']['artist_id'],
                'thumb' => $data['albumInfo']['pic_big'],
            ];
            $song_ids = [];
            foreach ($data['songlist'] as $item) {
                $res['song_list'][] = [
                    'song_id' => $item['song_id'],
                    'name' => strip_tags($item['title']),
                    "time" => $item['file_duration'],
                    'singer' => strip_tags($item['author']),
                    'album' => strip_tags($item['album_title']),
                    'thumb' => $item['pic_big'],
                    'mp3' => "",
                    'lyric' => $item['lrclink'] ? ($item['lrclink']) : '',
                    'platform' => 'baidu'
                ];
                $song_ids[] = $item['song_id'];
            }
            //10首歌一批。百度最大支持
            $time = ceil(count($res['song_list']) / 10);
            for ($i = 1; $i <= $time; $i++) {
                $ids = array_slice($song_ids, ($i - 1) * 10, 10);
                //  var_dump($ids);
                $url = "http://music.baidu.com/data/music/links?songIds=" . implode(',', $ids);
                $data = CurlManager::init()->curl_get_contents($url);
                $data = json_decode($data['data'], true);
                if ($data) {
                    $data = $data['data']['songList'];
                    $song_list = array_combine(array_column($data, 'songId'), $data);
                    foreach ($res["song_list"] as &$item) {
                        if (isset($song_list[$item['song_id']])) {
                            $item['time'] = (string)$song_list[$item['song_id']]['time'];
                            $item['thumb'] = $song_list[$item['song_id']]['songPicRadio'] ? $song_list[$item['song_id']]['songPicRadio'] : ($song_list[$item['song_id']]['songPicBig'] ? $song_list[$item['song_id']]['songPicBig'] : $item['thumb']);
                            $item['mp3'] = $song_list[$item['song_id']]['songLink'];
                            !$item['lyric'] && $item['lyric'] = $song_list[$item['song_id']]['lrcLink'];

                            //  $item['song_id'] = $item['song_id'];/*"bd_" .*/
                        }
                    }
                }
            }
        }
        return $res;
    }

    //获取固定音乐场景
    public function constScene()
    {
        $res = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.scene.getConstantScene";
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            foreach ($data['result']['scene_info'] as $item) {
                $res['data_list'][] = [
                    'scene_id' => $item['scene_id'],
                    'scene_name' => $item['scene_name'],
                    'scene_icon' => $item['icon_android'],
                ];
            }
        }
        return $res;
    }

//所有场景类别
    public function sceneCategories()
    {
        $res = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.scene.getCategoryList";
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            foreach ($data['result'] as $item) {
                $scene = [];
                if ($item['result']) {
                    foreach ($item['result'] as $s) {
                        $scene[] = [
                            'scene_id' => $s['scene_id'],
                            'scene_name' => $s['scene_name'],
                            'scene_icon' => $s['icon_android'],
                        ];
                    }
                }
                $tmp = [
                    'category_id' => $item['category_id'],
                    'category_name' => $item['category_name'],
                    'scene' => $scene,
                ];
                $res['data_list'][] = $tmp;
            }
        }
        return $res;
    }

    //场景类别下的所有场景
    public function categoryScenes($category_id)
    {
        $res = ['data_list' => []];
        $base_url = self::$baseUrl . "&method=baidu.ting.scene.getCategoryScene&category_id=" . $category_id;
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            foreach ($data['result'] as $item) {
                $res['data_list'][] = [
                    'scene_id' => $item['scene_id'],
                    'scene_name' => $item['scene_name'],
                    'scene_icon' => $item['icon_android'],
                ];
            }
        }
        return $res;
    }

    //获取推荐歌单
    public function recommendPlayList($cat)
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $base_url = self::$baseUrl . "&method=baidu.ting.diy.search&page_size=" . $this->limit . "&page_no=" . $this->page . "&query=" . $cat;
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            $res['data_count'] = $data['total'];
            foreach ($data['content'] as $item) {
                $res['data_list'][] = [
                    'playlist_id' => $item['listid'],
                    'time' => '',
                    'name' => $item['title'],
                    'thumb' => $item['pic_300'],
                    'play_count' => $item['listenum'],
                    'song_count' => 0,
//                    'creator' => $item['creator']['name'],
                ];
            }
        }
        return $res;
    }

    //获取歌单详情
    public function playlistDetail($playlist_id)
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $base_url = self::$baseUrl . "&method=baidu.ting.diy.gedanInfo&listid=" . $playlist_id;
        $data = CurlManager::init()->curl_get_contents($base_url);
        $data = json_decode($data['data'], true);
        if ($data) {
            $res['data_count'] = count($data['content']);
            $song_ids = [];
            foreach ($data['content'] as $item) {
                $temp = [
                    'song_id' => $item['song_id'],
                    'name' => strip_tags($item['title']),
                    "time" => "",
                    'singer' => strip_tags($item['author']),
                    'album' => strip_tags($item['album_title']),
                    'thumb' => $item['pic_big'],
                    'mp3' => "",
                    'lyric' => '',
                    'platform' => 'baidu'
                ];
                $res["data_list"][] = $temp;
                $song_ids [] = $item['song_id'];
            }
            //10首歌一批。百度最大支持
            $time = ceil(($res['data_count']) / 10);
            for ($i = 1; $i <= $time; $i++) {
                $ids = array_slice($song_ids, ($i - 1) * 10, 10);
                //  var_dump($ids);
                $url = "http://music.baidu.com/data/music/links?songIds=" . implode(',', $ids);
                $data = CurlManager::init()->curl_get_contents($url);
                $data = json_decode($data['data'], true);
                if ($data) {
                    $data = $data['data']['songList'];
                    $song_list = array_combine(array_column($data, 'songId'), $data);
                    foreach ($res["data_list"] as &$item) {
                        if (isset($song_list[$item['song_id']])) {
                            $item['time'] = (string)$song_list[$item['song_id']]['time'];
                            $item['thumb'] = $song_list[$item['song_id']]['songPicRadio'] ? $song_list[$item['song_id']]['songPicRadio'] : ($song_list[$item['song_id']]['songPicBig'] ? $song_list[$item['song_id']]['songPicBig'] : $item['thumb']);
                            $item['mp3'] = $song_list[$item['song_id']]['songLink'];
                            !$item['lyric'] && $item['lyric'] = $song_list[$item['song_id']]['lrcLink'];
                            //  $item['song_id'] = $item['song_id'];/*"bd_" .*/
                        }
                    }
                }
            }
        }
        return $res;
    }
}