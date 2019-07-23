<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/18
 * Time: 15:12
 */

namespace Components\Music\Tools;


use Components\Music\AbstractMusic;

class MusicXiami extends AbstractMusic
{
    // General
    protected $_USERAGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36';
    protected $_COOKIE = 'user_from=2;XMPLAYER_addSongsToggler=0;XMPLAYER_isOpen=0;_xiamitoken=cb8bfadfe130abdbf5e2282c30f0b39a;';
    protected $_REFERER = 'http://h.xiami.com/';

    // CURL
    protected function curl($url, $data = null, $need_decode = true)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($data) {
            if (is_array($data)) $data = http_build_query($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_POST, 1);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_REFERER, $this->_REFERER);
        curl_setopt($curl, CURLOPT_COOKIE, $this->_COOKIE);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->_USERAGENT);
        $result = curl_exec($curl);
        return $result ? ($need_decode ? json_decode($result, true) : $result) : [];
    }

    //搜索
    public function searchMusic()
    {
        $data = ['data_count' => 0, 'data_list' => []];
        $url = 'http://api.xiami.com/web?';
        $params = array(
            'v' => '2.0',
            'app_key' => '1',
            'key' => $this->key,
            'page' => $this->page,
            'limit' => $this->limit,
            'r' => 'search/songs',
        );
        $res = $this->curl($url . http_build_query($params));
        // var_dump($res);exit;
        if ($res) {
            if (isset($res['data'])) {
                $data['data_count'] = intval($res['data']['total']);
                // $song_ids = [];
                foreach ($res['data']['songs'] as $item) {
                    $temp = [
                        'song_id' =>/* "xiami_" .*/
                            $item['song_id'],
                        'name' => $item['song_name'],
                        "time" => 0, //(string)ceil($item['duration'] / 1000),
                        'singer' => $item['artist_name'],
                        'album' => $item['album_name'],
                        'thumb' => $item['album_logo'],
                        'mp3' => $item["listen_file"],
                        //   'size'=>'',
                        'lyric' => $item['lyric'],
                        "vid" => 0,//$item['mv'],
                        'platform' => 'xiami'
                    ];
                    //  $song_ids[] = $item['song_id'];
                    $data["data_list"][$item['song_id']] = $temp;
                }
                $data["data_list"] = array_values($data["data_list"]);
            }
        }
        return $data;
    }

    //音乐详情
    public function musicDetail($song_ids)
    {
        $url = 'http://www.xiami.com/song/gethqsong/sid/' . $song_ids;
        $data = array(
            'v' => '2.0',
            'app_key' => '1',
            'id' => $song_ids,
            'r' => 'song/detail',
        );
        return $this->curl($url . "?" . http_build_query($data));
    }

    //随便听听
    public function randomMusic()
    {

    }

    //新歌榜
    public function newMusic()
    {
        return $this->top_playlist(101);
    }

    //推荐榜
    public function recommendMusic()
    {

    }

    //总排行榜
    public function topMusic()
    {
//        $data = ['data_list' => [], 'data_count' => 0];
//        $url = 'http://api.xiami.com/web?';
//        $params = array(
//            'v' => '2.0',
//            'id'=>1,
//            'type'=>9,
//            'app_key' => '1',
//            'key' => $this->key,
//            'page' => $this->page,
//            'limit' => $this->limit,
//            'r' => 'collect/detail',
//        );
//        $res = $this->curl($url . http_build_query($params));
//        var_dump($res);exit;
//        if ($res) {
//            if (isset($res['data'])) {
//                foreach ($res['data']['trackList'] as $item) {
//                    $data['data_list'][] = $item;
//                    $data['data_count']++;
//                }
//            }
//        }
//        var_dump($data);
//        exit;

    }

    //获取搜索关键字
    public function getSearchKey($key)
    {
        $tag = [
            '艺人' => 'singer',
            '歌曲' => 'song',
            '专辑' => 'album',
        ];
        //  $url = 'http://api.xiami.com/web?';
//        $data = array(
//            'v' => '2.0',
//            'app_key' => '1',
////            'limit' => $this->limit,
////            'page' => $this->page,
//            'key' => $key,
//            'r' => 'search/hot',
////            'r' => 'search/all',
//        );
//        $res = $this->curl($url . http_build_query($data));
//        var_dump($res);
//        exit;
        $list = ['song' => ['count' => 0, 'list' => []], 'singer' => ['count' => 0, 'list' => []], 'album' => ['count' => 0, 'list' => []]];
        $url = 'http://www.xiami.com/ajax/search-index?key=' . $key;
        $res = $this->curl($url, [], false);
        $res = '<meta http-equiv="Content-Type" content="text/html;charset=utf-8">' . $res;
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->loadHTML($res);
        $xpath = new \DOMXPath($dom);
        $table = $xpath->query('//table')->item(0);
        $rows = $table->getElementsByTagName("tr");
        $type = [];
        foreach ($rows as $k => $row) {
            $th = $row->getElementsByTagName('th');
            if ($th->length > 0) {
                foreach ($th as $cell) {
                    $type[$k] = trim($cell->nodeValue);
                }
            }
        }
        foreach ($rows as $k => $row) {
            $td = $row->getElementsByTagName('td');
            if ($td->length > 0) {
                foreach ($td as $t) {
                    $li = $t->getElementsByTagName('li');
                    if ($li->length > 0) {
                        foreach ($li as $cell) {
                            $a = $cell->getElementsByTagName('a');
                            if ($a->length > 0) {
                                if (key_exists($type[$k], $tag)) {
                                    $t_k = $tag[$type[$k]];
                                    $title = $a->item(0)->getAttribute("title");
                                    $href = $a->item(0)->getAttribute("href");
                                    $href = explode("?", $href)[0];
                                    $pos = strrpos($href, '/');
                                    $id = substr($href, $pos + 1);

                                    // $img = $a->item(0)->getElementsByTagName('img');
                                    //$src = $img->length>0 ? $img->item(0)->getAttribute('src') : '';

                                    if ($t_k == 'song') {
                                        $list['song']['count'] += 1;
                                        $list['song']['list'][] = ['song_name' => $title, 'song_id' => $id, 'singer' => trim(substr($a->item(0)->nodeValue, strrpos($a->item(0)->nodeValue, '-') + 1))];
                                    } else if ($t_k == 'singer') {
                                        $list['singer']['count'] += 1;
                                        $list['singer']['list'][] = ['singer' => $title, 'singer_id' => $id];
                                    } elseif ($t_k == 'album') {
                                        $list['album']['count'] += 1;
                                        $list['album']['list'][] = ['album_name' => $title, 'album_id' => $id];
                                    }
                                } else {
                                    continue;
                                }
                            }


                        }
                    }
                }
            }
        }
        return $list;
    }

    //获取歌词
    public function getLyric($song_id)
    {
        $res = ['lyric' => [], 'trans' => []];
        $data = $this->detail($song_id);
        $data = $data['data']['song']['lyric'];
        $lyric = file_get_contents($data);
        $lyric = $this->clearBOM($lyric);
        $lyric = explode("\n", $lyric);
        $time = '';
        foreach ($lyric as $i) {
            preg_match('/^\[(\d*:\d*.\d*)\](.*?)$/', $i, $match);
            preg_match('/^\[x\-trans\](.*?)$/', $i, $match2);
            if ($match) {
                $word = preg_replace('/<[\d]*>/', '', $match[2]);
                $res['lyric'][] = ['time' => $match[1], 'word' => $word];
                $time = $match[1];
            }
            if ($match2) {
                $word = preg_replace('/<[\d]*>/', '', $match2[2]);
                $res['trans'][] = ['time' => $time, 'word' => $word];
            }
        }
        return $res;
    }

    //获取歌曲地址
    public function getUrl($song_id)
    {
        $res = $this->detail($song_id);
        return $res['data']['song']['listen_file'];
    }

//    //歌单排行榜
//    public function top_playlist($playlist_id)
//    {
//        return [];
//    }

    public function artist($artist_id)
    {
        $url = 'http://api.xiami.com/web?';
        $data = array(
            'v' => '2.0',
            'app_key' => '1',
            'id' => $artist_id,
            'page' => 1,
            'limit' => 30,
            'r' => 'artist/hot-songs',
        );
        return $this->curl($url . http_build_query($data));
    }

    public function album($album_id)
    {
        $url = 'http://api.xiami.com/web?';
        $data = array(
            'v' => '2.0',
            'app_key' => '1',
            'id' => $album_id,
            'r' => 'album/detail',
        );
        return $this->curl($url . http_build_query($data));
    }

    public function detail($song_id)
    {
        $url = 'http://api.xiami.com/web?';
        $data = array(
            'v' => '2.0',
            'app_key' => '1',
            'id' => $song_id,
            'r' => 'song/detail',
        );
        return $this->curl($url . http_build_query($data));
    }

    public function url($song_id)
    {
        //  $url = 'http://www.xiami.com/song/playlist/id/' . $song_id . '/object_name/default/object_id/0/cat/json';
        // return $this->curl($url);
        $res = $this->detail($song_id);
        return $res;
    }

    public function playlist($playlist_id)
    {
        $url = 'http://api.xiami.com/web?';
        $data = array(
            'v' => '2.0',
            'app_key' => '1',
            'id' => $playlist_id,
            'r' => 'collect/detail',
        );
        return $this->curl($url . http_build_query($data));
    }

    //排行榜
    public function top_playlist($play_list)
    {
        $url = 'http://api.xiami.com/web?';
        $data = array(
            'v' => '2.0',
            'app_key' => '1',
            'id' => $play_list,
            'limit' => $this->limit,
            'page' => $this->page,
            'r' => 'rank/song-list',
        );
        $res = $this->curl($url . http_build_query($data));
        $data = ['data_list' => [], 'data_count' => 0];
        if ($res['data']) {

            foreach ($res['data'] as $k => $item) {
                $detail = ($this->detail($item['song_id']));
                // print_r($detail);exit;
                $temp = [
                    'song_id' => /*'163_' . */
                        $item['song_id'],
                    'name' => $item['song_name'],
                    "time" => 0, //(string)ceil($item['dt'] / 1000),
                    'singer' => $item['singers'],
                    'album' => $detail['data']['song']['album_name'],
                    'thumb' => $detail['data']['song']['logo'],
                    'mp3' => $detail['data']['song']['listen_file'],
                    'lyric' => $detail['data']['song']['lyric'],
                    "vid" => 0,
                    'platform' => 'xiami'
                ];
                $data['data_count'] += 1;
                $data['data_list'][] = $temp;
            }
        }
        return $data;
    }

    private function xiami_url($result)
    {
        $data = $result;//json_decode($result, 1);
        if (!empty($data['location'])) {
            $location = $data['location'];
            $num = (int)$location[0];
            $str = substr($location, 1);
            $len = floor(strlen($str) / $num);
            $sub = strlen($str) % $num;
            $qrc = array();
            $tmp = 0;
            $urlt = '';
            for (; $tmp < $sub; $tmp++) {
                $qrc[$tmp] = substr($str, $tmp * ($len + 1), $len + 1);
            }
            for (; $tmp < $num; $tmp++) {
                $qrc[$tmp] = substr($str, $len * $tmp + $sub, $len);
            }
            for ($tmpa = 0; $tmpa < $len + 1; $tmpa++) {
                for ($tmpb = 0; $tmpb < $num; $tmpb++) {
                    if (isset($qrc[$tmpb][$tmpa])) {
                        $urlt .= $qrc[$tmpb][$tmpa];
                    }
                }
            }
            $urlt = str_replace('^', '0', urldecode($urlt));
            $url = array(
                'url' => str_replace('http://', 'https://', urldecode($urlt)),
                'br' => 320,
            );
        } else {
            $url = array(
                'url' => '',
                'br' => -1,
            );
        }
        return json_encode($url);
    }

    //清除文件bom头
    private function clearBOM($contents)
    {
        $charset[1] = substr($contents, 0, 1);
        $charset[2] = substr($contents, 1, 1);
        $charset[3] = substr($contents, 2, 1);
        if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
            $contents = substr($contents, 3);
        }
        return $contents;

    }
}