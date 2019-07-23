<?php
/**
 * Netease Cloud Music Api
 * @Version 2.1.1
 * @auther METO, Axhello
 * @description 推荐使用php5.5以上
 * Released under the MIT license
 */
namespace Components\Music\Tools\NeteaseCloud;

use Components\Music\Tools\NeteaseCloud\BigInteger;

class NeteaseCloudMusicApi
{
    const MODULUS = '00e0b509f6259df8642dbc35662901477df22677ec152b5ff68ace615bb7b725152b3ab17a876aea8a5aa76d2e417629ec4ee341f56135fccf695280104e0312ecbda92557c93870114af6c9d05c4f7f0c3685b7a46bee255932575cce10b424d813cfe4875d3e82047b97ddef52741d546b8e289dc6935b3ece0462db0a22b8e7';
    const NONCE = '0CoJUm6Qyw8W8jud';
    const PUBKEY = '010001';
    protected $headers = ['Accept: */*', 'Accept-Encoding: gzip,deflate,sdch', 'Accept-Language: zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4', 'Connection: keep-alive', 'Content-Type: application/x-www-form-urlencoded', 'Host: music.163.com', 'Referer: http://music.163.com/search/', 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.152 Safari/537.36'];
    protected $secretKey;

    protected static $category = [
        [
            'name' => '语种',
            'list' => [
                '华语' => '华语', '欧美' => '欧美', '韩语' => '韩语', '粤语' => '粤语', '日语' => '日语', '小语种' => '小语种',
            ]
        ],
        [
            'name' => '风格',
            'list' => [
                '流行', '轻音乐', '摇滚', '民谣', 'R&B/Soul',
                '电子', '古典', '乡村',
                '蓝调', '爵士', '拉丁', '后摇',
                '舞曲', '说唱', "民族", '英伦', '金属', "朋克", "雷鬼" => '雷鬼',
                '古风', '世界音乐',
            ]
        ],
        [
            'name' => '主题',
            'list' => [
                "影视原声", "ACG", "校园", "游戏", "70后", "80后", "90后", "网络歌曲", "KTV", "经典", "翻唱", "吉他", "钢琴", "器乐", "儿童", "榜单", "00后"
            ]
        ],
        [
            'name' => '情感',
            'list' => [
                "怀旧", "清新", "浪漫", "性感", "伤感", "治愈", "放松", "孤独", "感动", "兴奋", "快乐", "安静", "思念"
            ]
        ],
        [
            'name' => '场景',
            'list' => [
                '清晨', '夜晚', '学习', '运动', '下午茶',
                '午休', '工作', '旅行', '地铁', '驾车',
                '就酒吧', '散步',
            ]
        ]
    ];

    public function __construct()
    {
        $this->secretKey = $this->createSecretKey(16);
    }

    protected function createSecretKey($length)
    {
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $r = '';
        for ($i = 0; $i < $length; $i++) {
            $r .= $str[rand(0, strlen($str) - 1)];
        }
        return $r;
    }

    public function prepare($data)
    {

        $data['params'] = $this->aesEncrypt($data['params'], self::NONCE);
        $data['params'] = $this->aesEncrypt($data['params'], $this->secretKey);
        $data['encSecKey'] = $this->rsaEncrypt($this->secretKey);

        return $data;
    }

    protected function aesEncrypt($secretData, $secret)
    {
        return openssl_encrypt($secretData, 'aes-128-cbc', $secret, false, '0102030405060708');
    }

    /**
     * @param $text
     * @return string
     */
    protected function rsaEncrypt($text)
    {
        $rtext = strrev(utf8_encode($text));

        $keytext = $this->bchexdec($this->strToHex($rtext));
        $biText = new BigInteger($keytext);
        $biKey = new BigInteger($this->bchexdec(self::PUBKEY));
        $biMod = new BigInteger($this->bchexdec(self::MODULUS));
        $key = $biText->modPow($biKey, $biMod)->toHex();
        return str_pad($key, 256, '0', STR_PAD_LEFT);
    }

    protected function bchexdec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i])), bcpow('16', strval($len - $i - 1))));
        }
        return $dec;
    }

    protected function strToHex($str)
    {
        $hex = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $hex .= dechex(ord($str[$i]));
        }
        return $hex;
    }

    protected function curl($url, $data = null)
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
        curl_setopt($curl, CURLOPT_REFERER, 'http://music.163.com/');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_ENCODING, 'application/json');
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    /**
     * 搜索API
     * @param $s --要搜索的内容
     * @param $limit --要返回的条数
     * @param $page --设置偏移量 用于分页
     * @param $type --类型 [1 单曲] [10 专辑] [100 歌手] [1000 歌单] [1002 用户]
     * @return array
     */
    public function search($s = null, $limit = 30, $page = 1, $type = 1)
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $url = 'http://music.163.com/weapi/cloudsearch/get/web?csrf_token=';
        $data = ['params' => '{
              "s":"' . $s . '",
              "type":"' . $type . '",
              "limit":"' . $limit . '",
              "total":"true",
              "offset":"' . ($page - 1) * $limit . '",
              "csrf_token": ""
          }'];
        $list = json_decode($this->curl($url, $this->prepare($data)), true);
        if ($list && $list['result']) {
            $res['data_count'] = $list['result']['songCount'];
            if ($list['result']['songs']) {
                $song_ids = [];
                foreach ($list['result']['songs'] as $item) {
                    $temp = [
                        'song_id' => "163_" . $item['id'],
                        'name' => $item['name'],
                        "time" => (string)ceil($item['dt'] / 1000),
                        'singer' => $item['ar'] ? $item['ar'][0]['name'] : '',
                        'album' => $item['al'] ? $item['al']['name'] : '',
                        'thumb' => $item['al'] ? $item['al']['picUrl'] : '',
                        'mp3' => '',
                        'lyric' => "http://music.163.com/api/song/lyric?os=pc&id=" . $item['id'] . "&lv=-1&kv=-1&tv=-1",
                        'platform' => '163'
                    ];
                    $song_ids[] = $item['id'];
                    $res["data_list"][$item['id']] = $temp;
                }
                $urls = $this->mp3url($song_ids);
                if ($urls) {
                    $urls = json_decode($urls, true);
                    foreach ($urls['data'] as $i) {
                        if (!$i['url']) {
                            unset($res["data_list"][$i['id']]);
                        } else {
                            $res["data_list"][$i['id']]['mp3'] = $i['url'];
                        }
                    }
                }
                $res["data_list"] = array_values($res["data_list"]);
            }
        }
        return $res;
    }

    /** Mv搜索
     * @param null $s
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function mvSearch($s = null, $limit = 10, $page = 1)
    {
        $res = ['data_count' => 0, 'data_list' => []];
        $url = 'http://music.163.com/weapi/cloudsearch/get/web?csrf_token=';
        $data = ['params' => '{
              "s":"' . $s . '",
              "type":"1004",
              "limit":"' . $limit . '",
              "total":"true",
              "offset":"' . ($page - 1) * $limit . '",
              "csrf_token": ""
          }'];
        $list = json_decode($this->curl($url, $this->prepare($data)), true);
        if ($list && $list['result']) {
            $res['data_count'] = $list['result']['mvCount'];
            if ($list['result']['mvs']) {
                foreach ($list['result']['mvs'] as $item) {
                    $mv_info = $this->mv($item['id']);
                    $temp = [
                        'mv_id' => '163_' . $item['id'],
                        'vid' => $item['id'],
                        'name' => $item['name'],
                        'time' => $item['duration'] / 1000,
                        'singer' => $mv_info['data']['artistName'],
                        'thumb' => $mv_info['data']['cover'],
                        'mp4' => $mv_info['data']['brs']['480'],
                        'more_url' => $mv_info['data']['brs'],
                        'singer_list' => $mv_info['data']['artists'],
                        'platform' => '163'
                    ];
                    $res['data_list'][] = $temp;
                }
            }

        }
        return $res;
    }

    /**
     * 歌曲详情API，不带MP3链接
     * @param $song_id 歌曲id
     * @return JSON
     */
    public function detail($song_id)
    {
        $url = 'http://music.163.com/weapi/v1/song/detail';
        if (is_array($song_id)) $s = '["' . implode('","', $song_id) . '"]'; else $s = '["' . $song_id . '"]';
        $data = ['params' => '{
                "ids":' . $s . ',
                "csrf_token":""
            }'];
        return $this->curl($url, $this->prepare($data));
    }

    /**
     * 新版API歌曲链接不包含在歌曲详情API里,通过此API获取
     * @param $song_id
     * @param int $br
     * @return JSON
     */
    public function mp3url($song_id, $br = 320000)
    {
        $url = 'http://music.163.com/weapi/song/enhance/player/url?csrf_token=';
        if (is_array($song_id)) $s = '["' . implode('","', $song_id) . '"]'; else $s = '["' . $song_id . '"]';
        $data = ['params' => '{
                "ids":' . $s . ',
                "br":"' . $br . '",
                "csrf_token":""
            }'];
        return $this->curl($url, $this->prepare($data));
    }

    /**
     * 歌词API 增加了几个字段
     * @param $song_id
     * @return JSON
     */
    public function lyric($song_id)
    {
        $url = 'http://music.163.com/weapi/song/lyric?csrf_token=';
        $data = ['params' => '{
                "id":"' . $song_id . '",
                "os":"pc",
                "lv":"-1",
                "kv":"-1",
                "tv":"-1",
                "csrf_token":""
            }'];
        return $this->curl($url, $this->prepare($data));
    }

    /**
     * 歌单API
     * @param $playlist_id
     * @param $page 1
     * @param $limit 10
     * @return array
     */
    public function playlist($playlist_id, $page = 1, $limit = 10)
    {
        $url = 'http://music.163.com/weapi/v3/playlist/detail?csrf_token=';
        $data = ['params' => '{
                "id":"' . $playlist_id . '",
                "n":"' . $limit . '",
                "offset":"' . ($page - 1) * $limit . '",
                "csrf_token":""
            }'];
        $list = json_decode($this->curl($url, $this->prepare($data)), true);
        $data = ['data_list' => [], 'data_count' => 0];
        if ($list && $list['playlist']['tracks']) {
            $data['data_count'] = count($list['playlist']['tracks']);

            $list = array_slice($list['playlist']['tracks'], 0, $limit);
            $song_ids = array_column($list, 'id');
            foreach ($list as $item) {
                $temp = [
                    'song_id' => /*'163_' . */
                        $item['id'],
                    'name' => $item['name'],
                    "time" => (string)ceil($item['dt'] / 1000),
                    'singer' => $item['ar'] ? $item['ar'][0]['name'] : '',
                    'album' => $item['al'] ? $item['al']['name'] : '',
                    'thumb' => $item['al'] ? $item['al']['picUrl'] : '',
                    'mp3' => $this->mp3url($item['id']),
                    'lyric' => "http://music.163.com/api/song/lyric?os=pc&id=" . $item['id'] . "&lv=-1&kv=-1&tv=-1",
                    "vid" => $item['mv'],
                    'platform' => '163'
                ];
                $data['data_list'][$item['id']] = $temp;
            }
            $res = $this->mp3url($song_ids);
            if ($res) {
                $res = json_decode($res, true);
                foreach ($res['data'] as $i) {
                    $data["data_list"][$i['id']]['mp3'] = $i['url'];
                    //  $data["data_list"][$i['id']]['size'] = $i['size'];
                }
            }
            $data["data_list"] = array_values($data["data_list"]);
        }
        return $data;
    }

    /**
     * 根据MVid(如果有)获取MV链接
     * @param $mv_id
     * @return JSON
     */
    public function mv($mv_id)
    {
        $url = 'http://music.163.com/weapi/mv/detail/';
        $data = ['params' => '{
                "id":"' . $mv_id . '",
                "csrf_token":""
            }'];
        return json_decode($this->curl($url, $this->prepare($data)), true);
    }

    /**获取搜索关键字
     * @param $key
     * @return array|mixed
     */
    public function getSearchKey($key)
    {
        $res = [];
        if ($key) {
            $url = "http://music.163.com/weapi/search/suggest/web";
            $data = ['params' => '{
                "s":"' . $key . '",
                "csrf_token":""
            }'];
            $res = json_decode($this->curl($url, $this->prepare($data)), true);
            $res = $res['result'];
        }
        return $res;

        //  exit;
    }

    //个人推荐歌单
    public function recommendPlayList($cat = '全部', $page = 1, $limit = 10)
    {
        $url = 'http://music.163.com/weapi/playlist/list';
        $data = ['params' => '{
                "cat":"' . $cat . '",
                "offset":"' . (($page - 1) * $limit) . '",
                "n":"' . $limit . '",
                "order":"hot",
                "csrf_token":""
            }'];
        $res = json_decode($this->curl($url, $this->prepare($data)), true);
        return ($res);
    }

}