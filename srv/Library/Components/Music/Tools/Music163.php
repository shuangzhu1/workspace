<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/1
 * Time: 11:33
 */

namespace Components\Music\Tools;


use Components\Music\AbstractMusic;
use Components\Music\Tools\NeteaseCloud\NeteaseCloudMusicApi;

//http://music.163.com/api/playlist/detail?id=2884035 原创歌曲榜
//http://music.163.com/api/playlist/detail?id=19723756 飙升榜
//http://music.163.com/api/playlist/detail?id=3778678 热歌榜
//http://music.163.com/api/playlist/detail?id=3779629 新歌榜


class Music163 extends AbstractMusic
{
    const TYPE_MUSIC = 1;//音乐
    const TYPE_SIGNER = 2;//歌手
    const TYPE_ALBUM = 3;//专辑
    const TYPE_SHEET = 4;//歌单
    const TYPE_MV = 5;//MV
    const TYPE_LYRICS = 6;//歌词
    const TYPE_STATION = 7;//电台

    public static $music_type = [
        self::TYPE_MUSIC => 1,
        self::TYPE_SIGNER => 100,
        self::TYPE_ALBUM => 10,
        self::TYPE_SHEET => 1000,
        self::TYPE_MV => 1004,
        self::TYPE_LYRICS => 1006,
        self::TYPE_STATION => 1009,
    ];

    static $v2 = null;

    // private static $base_url = "http://s.music.qq.com/fcgi-bin/music_search_new_platform";
    public function __construct()
    {
        self::$v2 = new NeteaseCloudMusicApi();
    }

    function curl_get($url)
    {
        $refer = "http://music.163.com/";
        $header[] = "Cookie: " . "appver=1.5.0.75771;";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $refer);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    function curl_post($url, $data)
    {
        $refer = "http://music.163.com/";
        $header[] = "Cookie: " . "appver=1.5.0.75771;";
        $header[] = "Host:music.163.com \n";
        $header[] = "Referer:'http://music.163.com \n";
        $header[] = "Content-type: application/x-www-form-urlencoded \n";
        $header[] = "Connection: keep-alive \n";
        $header[] = "Cookie:appver=1.5.0.75771;\n";
        $header[] = "Accept:*/* \n";
        $header[] = "Accept-Language:zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $refer);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //搜索
    public function searchMusic()
    {
        $data = ['data_count' => 0, 'data_list' => []];
        $res = $this->music_search();
        if ($res) {
            if (isset($res['result'])) {
                $data['data_count'] = intval($res['result']['songCount']);
                $song_ids = [];
                foreach ($res['result']['songs'] as $item) {
                    $temp = [
                        'song_id' =>/* "163_" .*/
                            $item['id'],
                        'name' => $item['name'],
                        "time" => (string)ceil($item['duration'] / 1000),
                        'singer' => $item['artists'] ? $item['artists'][0]['name'] : '',
                        'album' => $item['album'] ? $item['album']['name'] : '',
                        'thumb' => $item['album'] ? $item['album']['blurPicUrl'] : '',
                        'mp3' => $item["mp3Url"],
                        //   'size'=>'',
                        'lyric' => "http://music.163.com/api/song/lyric?os=pc&id=" . $item['id'] . "&lv=-1&kv=-1&tv=-1",
                        "vid" => $item['mv'],
                        'platform' => '163'
                    ];
                    $song_ids[] = $item['id'];
                    $data["data_list"][$item['id']] = $temp;
                }
                $api = new NeteaseCloudMusicApi();
                $res = $api->mp3url($song_ids);

                if ($res) {
                    $res = json_decode($res, true);
                    foreach ($res['data'] as $i) {
                        if (!$i['url']) {
                            unset($data["data_list"][$i['id']]);
                        } else {
                            $data["data_list"][$i['id']]['mp3'] = $i['url'];
                            //   $data["data_list"][$i['id']]['size'] =$i['size'];
                        }
                    }
                }
                $data["data_list"] = array_values($data["data_list"]);
            }
        }
        return $data;
    }

    //音乐详情
    public function musicDetail($song_ids)
    {
        $api = new NeteaseCloudMusicApi();
        $res = $api->detail($song_ids);
        var_dump($res);

    }

    //随便听听
    public function randomMusic()
    {

    }

    //新歌榜
    public function newMusic()
    {
        //第一版
        // $data = ['data_list' => []];

//        $url = "http://music.163.com/api/playlist/detail?id=3778678";
//        $result = $this->curl_get($url);
//        if ($result) {
//            $result = json_decode($result, true);
//            if (isset($result['result']['tracks'])) {
//                $list = array_slice($result['result']['tracks'], 0, 20);
//                $song_ids = [];
//                foreach ($list as $item) {
//                    $temp = [
//                        'song_id' => "163_" . $item['id'],
//                        'name' => $item['name'],
//                        "time" => (string)ceil($item['duration'] / 1000),
//                        'signer' => $item['artists'] ? $item['artists'][0]['name'] : '',
//                        'album' => $item['album'] ? $item['album']['name'] : '',
//                        'thumb' => $item['album'] ? $item['album']['blurPicUrl'] : '',
//                        'mp3' => $item["mp3Url"],
//                        'lyric' => "http://music.163.com/api/song/lyric?os=pc&id=" . $item['id'] . "&lv=-1&kv=-1&tv=-1",
//                    ];
//                    $data["data_list"][$item['id']] = $temp;
//                    $song_ids[] = $item['id'];
//                }
//                $api = new NeteaseCloudMusicApi();
//                $res = $api->mp3url($song_ids);
//                if ($res) {
//                    $res = json_decode($res, true);
//                    foreach ($res['data'] as $i) {
//                        $data["data_list"][$i['id']]['mp3'] = $i['url'];
//                    }
//                }
//                $data["data_list"] = array_values($data["data_list"]);
//                /*  foreach ($list as $item) {
//                      $result[$k]['id'] = $v['id'];
//                      $result[$k]['mp3Url'] = $v['mp3Url'];
//                      $result[$k]['songerName'] = $v['artists'][0]['name'];
//                      $result[$k]['name'] = $v['name'];
//                      $result[$k]['size'] = $v['bMusic']['size'];
//                  }*/
//            }
//        }
        //第二版
        $data = self::$v2->playlist(3778678, $this->page, $this->limit);


        return $data;
    }

    public function getBytes($string)
    {
        $bytes = array();
        for ($i = 0; $i < strlen($string); $i++) {
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }

    //推荐榜
    public function recommendMusic()
    {

    }

    //总排行榜
    public function topMusic()
    {

    }

    /**音乐搜索
     * @return mixed|string
     */
    public function music_search()
    {
        $url = "http://music.163.com/api/search/pc";
        $post_data = array(
            's' => $this->key,
            'offset' => ($this->page - 1) * $this->limit,
            'limit' => $this->limit,
            'type' => $this->type,
        );
        $referrer = "http://music.163.com/";
        $URL_Info = parse_url($url);
        $values = [];
        $result = '';
        $request = '';
        foreach ($post_data as $key => $value) {
            $values[] = "$key=" . urlencode($value);
        }
        $data_string = implode("&", $values);
        if (!isset($URL_Info["port"])) {
            $URL_Info["port"] = 80;
        }
        $request .= "POST " . $URL_Info["path"] . " HTTP/1.1\n";
        $request .= "Host: " . $URL_Info["host"] . "\n";
        $request .= "Referer: $referrer\n";
        $request .= "Content-type: application/x-www-form-urlencoded\n";
        $request .= "Content-length: " . strlen($data_string) . "\n";
        $request .= "Connection: close\n";
        $request .= "Cookie: " . "appver=1.5.0.75771;\n";
        $request .= "\n";
        $request .= $data_string . "\n";
        $fp = fsockopen($URL_Info["host"], $URL_Info["port"]);
        fputs($fp, $request);
        //$i = 1;
        while (!feof($fp)) {
            // if ($i >= 13) {
            //     $result .= fgets($fp);
            // } else {
            //     fgets($fp);
            //     $i++;
            // }
            $result .= fgets($fp);
        }
        $result = strstr($result, '{"result":');
        $result = json_decode($result, true);
        fclose($fp);
        return $result;
    }

    function get_music_info($music_id)
    {
        $url = "http://music.163.com/api/song/detail/?id=" . $music_id . "&ids=%5B" . $music_id . "%5D";
        return $this->curl_get($url);
    }

    function get_artist_album($artist_id, $limit)
    {
        $url = "http://music.163.com/api/artist/albums/" . $artist_id . "?limit=" . $limit;
        return $this->curl_get($url);
    }

    function get_album_info($album_id)
    {
        $url = "http://music.163.com/api/album/" . $album_id;
        return $this->curl_get($url);
    }

    function get_playlist_info($playlist_id)
    {
        $url = "http://music.163.com/api/playlist/detail?id=" . $playlist_id;
        return $this->curl_get($url);
    }

    function get_music_lyric($music_id)
    {
        $url = "http://music.163.com/api/song/lyric?os=pc&id=" . $music_id . "&lv=-1&kv=-1&tv=-1";
        return $this->curl_get($url);
    }

    function get_mv_info()
    {
        $url = "http://music.163.com/api/mv/detail?id=319104&type=mp4";
        return $this->curl_get($url);
    }

    public function music_search_result()
    {
        $musicArrInfo = $this->music_search();
        $musicArr = $musicArrInfo['result']['songs'];
        $result = array();
        foreach ($musicArr as $k => $v) {
            $result[$k]['id'] = $v['id'];
            $result[$k]['mp3Url'] = $v['mp3Url'];
            $result[$k]['songerName'] = $v['artists'][0]['name'];
            $result[$k]['name'] = $v['name'];
            $result[$k]['size'] = $v['bMusic']['size'];
        }
        return $result;
    }

    //获取搜索关键字
    public function getSearchKey($key)
    {
        $list = ['song' => ['count' => 0, 'list' => []], 'singer' => ['count' => 0, 'list' => []], 'album' => ['count' => 0, 'list' => []]];
        $data = self::$v2->getSearchKey($key);
        if ($data) {
            foreach ($data['songs'] as $item) {
                $list['song']['count'] += 1;
                $list['song']['list'][] = [
                    'song_name' => $item['name'],
                    'song_id' => $item['id'],
                    'singer' => $item['artists'][0]['name'],
                ];
            }
            foreach ($data['albums'] as $item) {
                $list['album']['count'] += 1;
                $list['album']['list'][] = [
                    'album_name' => $item['name'],
                    'album_id' => $item['id'],
                    'singer' => $item['artist']['name'],
                ];
            }
            foreach ($data['artists'] as $item) {
                $list['singer']['count'] += 1;
                $list['singer']['list'][] = [
                    'singer_id' => $item['id'],
                    'singer' => $item['name'],
                ];
            }
        }
        return $list;
    }

    //获取歌词
    public function getLyric($song_id)
    {
        $res = ['lyric' => [], 'trans' => []];
        $data = self::$v2->lyric($song_id);
        $data = json_decode($data, true);
        $lyric = $data && !empty($data['lrc']['lyric']) ? ($data['lrc']['lyric']) : '';
        $trans = $data && !empty($data['tlyric']['lyric']) ? ($data['tlyric']['lyric']) : '';
        if ($lyric) {
            $lyric = explode("\n", $lyric);
            foreach ($lyric as $i) {
                preg_match('/^\[(\d*:\d*.\d*)\](.*?)$/', $i, $match);
                if ($match) {
                    $res['lyric'][] = ['time' => $match[1], 'word' => $match[2]];
                }
            }
        }
        if ($trans) {
            $trans = explode("\n", $trans);
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
        $res = '';
        $data = self::$v2->mp3url($song_id);
        if ($data) {
            $res = json_decode($data, true);
            $res = $res['data'][0]['url'];
        }
        return $res;
    }

    //歌单排行榜
    public function top_playlist($playlist_id)
    {
        $res = self::$v2->playlist($playlist_id, $this->page, $this->limit);
        return $res;
    }

    public function recommendPlayList($cat)
    {
        $data = ['data_count' => 0, 'data_list' => []];
        $res = self::$v2->recommendPlayList($cat, $this->page, $this->limit);
        if ($res && $res['playlists']) {
            $data['data_count'] = $res['totel'];
            foreach ($res['playlists'] as $item) {
                $tmp = [
                    'playlist_id' => $item['id'],
                    'name' => $item['name'],
                    'thumb' => $item['coverImgUrl'],
                    'time' => date('Y-m-d',$item['updateTime']/1000),
                    'play_count' => $item['playCount'],
                    'song_count' => $item['trackCount']
                ];
                $data['data_list'][] = $tmp;
            }
        }
        return $data;
    }
}