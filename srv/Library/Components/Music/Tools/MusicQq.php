<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/1
 * Time: 16:25
 */

namespace Components\Music\Tools;


use Components\Curl\CurlManager;
use Components\Music\AbstractMusic;
use Components\Music\Tools\Tencent\TencentMusicApi;

class MusicQq extends AbstractMusic
{
    static $v2 = null;

    // private static $base_url = "http://s.music.qq.com/fcgi-bin/music_search_new_platform";
    public function __construct()
    {
        self::$v2 = new TencentMusicApi();
    }

    //搜索
    public function searchMusic()
    {
        //第一个版本
        /* $data = ['data_count' => 0, 'data_list' => []];
         $url = "http://s.music.qq.com/fcgi-bin/music_search_new_platform?t=0&n=" . $this->limit . "&aggr=1&cr=1&loginUin=0&format=json&inCharset=GB2312&outCharset=utf-8&notice=0&platform=jqminiframe.json&needNewCode=0&p=" . $this->page . "&catZhida=0&remoteplace=sizer.newclient.next_song&w=" . $this->key;
         $res = CurlManager::init()->curl_get_contents($url);
         $res = json_decode($res['data'], true);
         if ($res) {
             $res = $res['data']['song'];
             $data['data_count'] = intval($res['totalnum']);
             if ($res['list']) {
                 foreach ($res['list'] as $item) {
                     $f = explode('|', $item['f']);
                     $temp = [
                         'song_id' => "qq_" . $f[0],
                         'name' => $this->covert($item['fsong']),//mb_convert_encoding($item['fsong'], "utf-8", 'HTML-ENTITIES'),
                         "time" => $f[7],
                         'signer' => $this->covert($item['fsinger']),
                         'album' => $this->covert($f[5]),
                         'thumb' => "http://imgcache.qq.com/music/photo/album_300/" . ($f[4] % 100) . "/300_albumpic_" . $f[4] . "_0.jpg",
                         'mp3' => "http://ws.stream.qqmusic.qq.com/" . $f[0] . ".m4a?fromtag=46",
                         'lyric' => "http://music.qq.com/miniportal/static/lyric/" . ($f[0] % 100) . "/" . $f[0] . ".xml"
                     ];
                     $data["data_list"][] = $temp;
                 }
             }
         }
         return $data;*/

        //第二个版本
        $res = self::$v2->search($this->key, $this->page, $this->limit);
        /*  if ($res['data_list']) {
              foreach ($res['data_list'] as &$item) {
                  unset($item['singer_info']);
                  unset($item['album_info']);
                  unset($item['song_mid']);
              }
          }*/
        return $res;
        // exit;
    }

    //音乐详情
    public function musicDetail($song_ids)
    {
        $api = new TencentMusicAPI();
        var_dump($api->detail("003OUlho2HcRHC"));
        exit;
    }

    //随便听听
    public function randomMusic()
    {
        $random_type = 1;
        $random_number = rand(1, 100);
        return $this->parseData("http://music.qq.com/musicbox/shop/v3/data/random/$random_type/random" . $random_number . ".js");
    }

    //新歌榜
    public function newMusic()
    {
        //版本1
        //  return $this->parseData("http://music.qq.com/musicbox/shop/v3/data/hit/hit_newsong.js");
        //版本2
        $res = self::$v2->topList(26, $this->page, $this->limit);
        /* if ($res['data_list']) {
             foreach ($res['data_list'] as &$item) {
                 unset($item['singer_info']);
                 unset($item['album_info']);
                 unset($item['song_mid']);
             }
         }*/
        return $res;
    }

    //推荐榜
    public function recommendMusic()
    {

    }

    //总排行榜
    public function topMusic()
    {
        //版本1
        //return $this->parseData("http://music.qq.com/musicbox/shop/v3/data/hit/hit_all.js");
        //版本2

        $res = self::$v2->topList();
        if ($res['data_list']) {
            foreach ($res['data_list'] as &$item) {
                unset($item['singer_info']);
                unset($item['album_info']);
                unset($item['song_mid']);
            }
        }
        return $res;
    }

    public function parseData($url)
    {
        $data = ['data_list' => []];
        $res = CurlManager::init()->curl_get_contents($url);
        $res = iconv("GB2312", "UTF-8//IGNORE", str_replace('JsonCallback(', '', $res['data']));
        $pos = (strripos($res, ')'));
        $res = substr_replace($res, "", $pos, 100);
        $res = preg_replace('/{([^:]+):/', '{"\1":', $res);
        $res = preg_replace('/",[^{]{1}([^:]+):/', '","\1":', $res);
        $res = preg_replace('/url:/', '"url":', $res);
        if ($res) {
            $res = json_decode($res, true);
            if ($res) {
                $list = array_slice($res['onglist'], ($this->page - 1) * $this->limit, $this->limit);
                if ($list) {
                    foreach ($list as $item) {
                        $temp = [
                            'song_id' => "qq_" . $item['id'],
                            'name' => $item['songName'],
                            "time" => $item['playtime'],
                            'singer' => $item['singerName'],
                            'album' => $item['albumName'],
                            'thumb' => "http://imgcache.qq.com/music/photo/album_300/" . ($item['albumId'] % 100) . "/300_albumpic_" . $item['albumId'] . "_0.jpg",
                            'mp3' => "http://ws.stream.qqmusic.qq.com/" . $item['id'] . ".m4a?fromtag=46",
                            'lyric' => "http://music.qq.com/miniportal/static/lyric/" . ($item['id'] % 100) . "/" . $item['id'] . ".xml",
                            'platform' => 'qq'

                        ];
                        $data["data_list"][] = $temp;
                    }
                }
            }
        }
        return $data;
    }

    //获取搜索关键字
    public function getSearchKey($key)
    {
        $list = ['song' => ['count' => 0, 'list' => []], 'singer' => ['count' => 0, 'list' => []], 'album' => ['count' => 0, 'list' => []]];
        $data = self::$v2->getSearchKey($key);
        if ($data) {
            $list['song']['count'] = $data['song']['count'];
            $list['album']['count'] = $data['album']['count'];
            $list['singer']['count'] = $data['singer']['count'];
            foreach ($data['song']['itemlist'] as $item) {

                $list['song']['list'][] = [
                    'song_name' => $item['name'],
                    'song_id' => $item['id'],
                    'singer' => $item['singer'],
                ];
            }
            foreach ($data['album']['itemlist'] as $item) {

                $list['album']['list'][] = [
                    'album_name' => $item['name'],
                    'album_id' => $item['id'],
                    'singer' => $item['singer'],
                ];
            }
            foreach ($data['singer']['itemlist'] as $item) {

                $list['singer']['list'][] = [
                    'singer_id' => $item['id'],
                    'singer' => $item['name'],
                ];
            }
        }
        return $list;
    }

    //获取歌词
    public function getLyric($song_mid)
    {
        $res = ['lyric' => [], 'trans' => []];
        $data = self::$v2->lyric($song_mid);
        if ($data['lyric']) {
            $lyric = explode("\n", $data['lyric']);
            foreach ($lyric as $i) {
                preg_match('/^\[(\d*:\d*.\d*)\](.*?)$/', $i, $match);
                if ($match) {
                    $res['lyric'][] = ['time' => $match[1], 'word' => $match[2]];
                }
            }
        }
        if ($data['trans']) {
            $trans = explode("\n", $data['trans']);
            foreach ($trans as $i) {
                preg_match('/^\[(\d*:\d*.\d*)\](.*?)$/', $i, $match);
                if ($match) {
                    $res['trans'][$match[1]] = ['time' => $match[1], 'word' => $match[2]];
                }
            }
        }
        return $res;
    }

    //获取歌曲地址
    public function getUrl($song_id)
    {
        $res = self::$v2->url($song_id);
        if ($res) {
            return $res['320mp3'] ? $res['320mp3'] : $res['128mp3'];
        }
        return '';
    }

    //歌单排行榜
    public function top_playlist($playlist_id)
    {
        $res = self::$v2->topList($playlist_id, $this->page, $this->limit);
        return $res;
    }

    public function recommendPlayList($cat)
    {
        //$data = ['data_count' => 0, 'data_list' => []];
        $res = self::$v2->recommendPlayList($cat, $this->page, $this->limit);
        return $res;
    }
}