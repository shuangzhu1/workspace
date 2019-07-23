<?php
namespace Components\Music\Tools\Tencent;

class TencentMusicApi
{
    // General
    protected $_USERAGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.30 Safari/537.36';
    protected $_COOKIE = 'qqmusic_uin=12345678; qqmusic_key=12345678; qqmusic_fromtag=30; ts_last=y.qq.com/portal/player.html;';
    protected $_REFERER = 'http://y.qq.com/portal/player.html';
    protected static $category = [
        [
            'name' => '语种',
            'list' => [
                '165' => '国语', '167' => '英语', '168' => '韩语', '166' => '粤语', '169' => '日语', '170' => '小语种',
                '203' => '闽南语', '204' => '法语', '205' => '拉丁语',
            ]
        ],
        [
            'name' => '流派',
            'list' => [
                '6' => '流行', '15' => '轻音乐', '11' => '摇滚', '28' => '民谣', '8' => 'R&B',
                '153' => '嘻哈', '24' => '电子', '27' => '古典', '205' => '拉丁语', '18' => '乡村',
                '22' => '蓝调', '21' => '爵士', '164' => '新世纪', '25' => '拉丁', '218' => '后摇',
                '219' => '中国传统', '220' => '世界音乐',
            ]
        ],
        [
            'name' => '主题',
            'list' => [
                '39' => 'ACG', '136' => '经典', '146' => '网络歌曲', '133' => '影视', '141' => 'KTV热歌',
                '131' => '儿歌', '145' => '中国风', '194' => '古风', '148' => '情歌', '196' => '城市',
                '197' => '现场音乐', '199' => '背景音乐', '200' => '佛教音乐', '201' => 'UP主',
                '202' => '乐器', '14' => 'DJ',
            ]
        ],
        [
            'name' => '心情',
            'list' => [
                '52' => '伤感', '122' => '安静', '117' => '快乐', '116' => '治愈',
                '125' => '励志', '59' => '甜蜜', '55' => '寂寞', '126' => '宣泄', '68' => '思念',
            ]
        ],
        [
            'name' => '场景',
            'list' => [
                '78' => '睡前', '102' => '夜店', '101' => '学习', '99' => '运动', '85' => '开车',
                '76' => '约会', '94' => '工作', '81' => '旅行', '103' => '派对', '222' => '婚礼',
                '223' => '咖啡馆', '224' => '跳舞', '16' => '校园',
            ]
        ]
    ];

    // CURL
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
        curl_setopt($curl, CURLOPT_REFERER, $this->_REFERER);
        curl_setopt($curl, CURLOPT_COOKIE, $this->_COOKIE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->_USERAGENT);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    public function getSearchKey($key)
    {
        $res = [];
        if ($key) {
            $url = "https://c.y.qq.com/splcloud/fcgi-bin/smartbox_new.fcg?is_xml=0&format=json&key=" . $key . "&g_tk=5381&loginUin=0&hostUin=0&format=json&inCharset=utf8&outCharset=utf-8&notice=0&platform=yqq&needNewCode=0";
        } else {
            $url = "https://c.y.qq.com/splcloud/fcgi-bin/gethotkey.fcg?g_tk=5381&loginUin=0&hostUin=0&format=json&inCharset=utf8&outCharset=utf-8&notice=0&platform=yqq&needNewCode=0";
        }
        $res = $this->curl($url);
        $res = json_decode($res, true);
        if (!$key) {
            $res = $res["data"]['hotkey'];
        } else {
            $res = $res["data"];
        }
        return $res;
        //  exit;
    }

    // 音乐搜索
    public function search($s, $page = 1, $limit = 30, $need_more_url = false)
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $url = 'http://c.y.qq.com/soso/fcgi-bin/search_cp?';
        //  echo $limit;exit;
        $data = array(
            'p' => $page,
            'n' => $limit,
            'w' => $s,
            'aggr' => 1,
            'lossless' => 1,
            'cr' => 1,
        );
        $list = json_decode(substr($this->curl($url . http_build_query($data)), 9, -1), true);
        if ($list && $list['data']['song']) {
            $res['data_count'] = $list['data']['song']['totalnum'];
            if ($list['data']['song']['list']) {
                foreach ($list['data']['song']['list'] as $item) {
                    $temp = [
                        'song_id' => /*'qq_' . */
                            $item['songid'],
                        'name' => $item['songname'],
                        'album_info' => ['id' => $item['albumid'], 'mid' => $item['albummid'], 'name' => $item['albumname']],
                        'album' => $item['albumname'],
                        'time' => $item['interval'],
                        'singer' => $item['singer'][0]['name'],
                        'singer_info' => $item['singer'],
                        'song_mid' => $item['songmid'],
                        'thumb' => "http://imgcache.qq.com/music/photo/album_300/" . ($item['albumid'] % 100) . "/300_albumpic_" . $item['albumid'] . "_0.jpg",
                        'mp3' => "http://ws.stream.qqmusic.qq.com/" . $item['songid'] . ".m4a?fromtag=46",
                        'lyric' => "http://music.qq.com/miniportal/static/lyric/" . ($item['songid'] % 100) . "/" . $item['songid'] . ".xml",
                        'vid' => $item['vid'],
                        'platform' => 'qq'
                    ];
                    if ($need_more_url) {
                        $temp['more_url'] = $this->url($item['songmid']);
                    }
                    $res['data_list'][] = $temp;
                }
            }

        }
        return $res;
    }

    //mv搜索
    public function mvSearch($key, $page = 1, $limit = 10)
    {
        $res = ['data_count' => 0, 'data_list' => []];
        $url = 'https://c.y.qq.com/soso/fcgi-bin/client_search_cp?';
        $data = array(
            'w' => $key,
            'p' => $page,
            'n' => $limit,
            'aggr' => 1,
            'lossless' => 1,
            'cr' => 1,
            'ct' => 24,
            't' => 12
        );
        $list = json_decode(substr($this->curl($url . http_build_query($data)), 9, -1), true);
        if ($list && $list['data']['mv']) {
            $res['data_count'] = $list['data']['mv']['totalnum'];
            foreach ($list['data']['mv']['list'] as $item) {
                $temp = [
                    'mv_id' =>/* 'qq_' . */
                        $item['mv_id'],
                    'vid' => $item['v_id'],
                    'name' => $item['mv_name'],
                    'time' => $item['duration'],
                    'singer' => $item['singer_name'],
                    'thumb' => $item['mv_pic_url'],
                    'mp4' => $this->mvUrl($item['v_id']),
                    'singer_list' => $item['singer_list'],
                    'platform' => 'qq'

                ];
                $res['data_list'][] = $temp;
            }
        }
        return $res;
        // return json_decode(, true);
    }

    public function getMvKey($vid)
    {
        $url = 'https://vv.video.qq.com/getinfo?';
        $data = [
            'vid' => $vid,
            'otype' => 'json',
            'platform' => '10201',
            'sdtfrom' => 'v1010',
            '_qv_rmt' => 'jBZ5pIl8A19213wFt=',
            '_qv_rmt2' => 'o6Qa4YDm149352oVg=',
            'guid' => 'decf14f023c6b25049fdedf639d195ff',
            'appVer' => 'V2.0Build9397',
        ];
        $data = json_decode(substr($this->curl($url . http_build_query($data)), 13, -1), true);
        $fvkey = $data['vl']['vi'][0]['fvkey'];
        $fn = $data['vl']['vi'][0]['fn'];
        $fc_clip = $data['vl']['vi'][0]['fclip'];
        if ($fc_clip) {
            $file_name = str_replace('.mp4', '.' . $fc_clip . '.mp4', $fn);
        } else {
            $file_name = $fn;
        }
        $server = $data['vl']['vi'][0]['ul']['ui'][0]['url'];
        $downurl = $server . $file_name . "?vkey=" . $fvkey . "?type=mp4";
        return $downurl;
        // platform=11001&charge=0&otype=json&ehost=https%3A%2F%2Fy.qq.com&sphls=0&sb=1&nocache=0&guid=decf14f023c6b25049fdedf639d195ff&appVer=V2.0Build9397&defaultfmt=auto&sdtfrom=v3010&vid=u00222le4ox
    }

    public function mvUrl($vid)
    {
        return $vkey = $this->getMvKey($vid);
    }

    //歌手的歌
    public function artist($artist_mid)
    {
        $url = 'http://c.y.qq.com/v8/fcg-bin/fcg_v8_singer_track_cp.fcg?';
        $data = array(
            'singermid' => $artist_mid,
            'order' => 'listen',
            'begin' => 0,
            'num' => 30,
        );
        return substr($this->curl($url . http_build_query($data)), 0, -1);
    }

    //专辑详情
    public function album($album_mid)
    {
        $url = 'http://c.y.qq.com/v8/fcg-bin/fcg_v8_album_info_cp.fcg?';
        $data = array(
            'albummid' => $album_mid,
        );
        return substr($this->curl($url . http_build_query($data)), 1);
    }

    //音乐详情
    public function detail($song_mid)
    {
        $url = 'http://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg?';
        $data = array(
            'songmid' => $song_mid,
            'format' => 'json',
        );
        return json_decode($this->curl($url . http_build_query($data)), true);
    }

    //排行榜
    /**
     * @param int $top_id 3-欧美 4-流行指数 5-内地 6-港台 16-韩国 17-日本 25-中国新歌声 26-热歌 27-新歌 28-网络歌曲 36-k歌金曲 51-明日之子 108-美国公牌榜 169-vivo高品质音乐榜
     * @param int $page 第几页
     * @param int $limit
     * @return mixed
     */
    public function topList($top_id = 26, $page = 1, $limit = 10, $need_more_url = false)
    {
        $res = ['data_list' => [], 'data_count' => 0];
        $url = 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_toplist_cp.fcg?';
        $data = array(
            'tpl' => 3,
            'page' => 'detail',
            'topid' => $top_id,
            'type' => 'top',
            'song_begin' => ($page - 1) * $limit,
            'song_num' => $limit,
        );
        $list = json_decode($this->curl($url . http_build_query($data)), true);
        if ($list && $list['songlist']) {
            $res['data_count'] = $list['total_song_num'];
            // var_dump($list['songlist']);exit;
            if ($list['songlist']) {
                foreach ($list['songlist'] as $item) {
                    $temp = [
                        'album' => $item['data']['albumname'],
                        'album_info' => ['id' => $item['data']['albumid'], 'mid' => $item['data']['albummid'], 'name' => $item['data']['albumname']],
                        'time' => $item['data']['interval'],
                        'singer' => $item['data']['singer'][0]['name'],
                        'singer_info' => $item['data']['singer'],
                        'song_id' => /*'qq_' . */
                            $item['data']['songid'],
                        'song_mid' => $item['data']['songmid'],
                        'name' => $item['data']['songname'],
                        'thumb' => "http://imgcache.qq.com/music/photo/album_300/" . ($item['data']['albumid'] % 100) . "/300_albumpic_" . $item['data']['albumid'] . "_0.jpg",
                        'mp3' => "http://ws.stream.qqmusic.qq.com/" . $item['data']['songid'] . ".m4a?fromtag=46",
                        'lyric' => "http://music.qq.com/miniportal/static/lyric/" . ($item['data']['songid'] % 100) . "/" . $item['data']['songid'] . ".xml",
                        'vid' => $item['data']['vid'],
                        'platform' => 'qq'
                        // 'mp4' => '',
                        // 'lyric_data'=>$this->lyric($item['data']['songmid'])
                    ];
                    if ($need_more_url) {
                        $temp['more_url'] = $this->url($item['data']['songmid']);
                    }

                    $res['data_list'][] = $temp;
                }
            }

        }
        return $res;
    }


    private function genkey()
    {
        $this->_GUID = rand(1, 2147483647) * (microtime() * 1000) % 10000000000;
        $data = $this->curl('https://c.y.qq.com/base/fcgi-bin/fcg_musicexpress.fcg?json=3&guid=' . $this->_GUID);
        $this->_KEY = json_decode(substr($data, 13, -2), 1)['key'];
        //$this->_CDN=json_decode(substr($data,13,-2),1)['sip'][0];
        $this->_CDN = 'http://dl.stream.qqmusic.qq.com/';
    }

    public function url($song_mid)
    {
        self::genkey();
        $url = 'http://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg?';
        $data = array(
            'songmid' => $song_mid,
            'format' => 'json',
        );
        $data = $this->curl($url . http_build_query($data));
        $data = json_decode($data, 1)['data'][0]['file'];


        $type = array(
            'size_320mp3' => array('M800', 'mp3'),
            'size_128mp3' => array('M500', 'mp3'),
            'size_96aac' => array('C400', 'm4a'),
            'size_48aac' => array('C200', 'm4a'),
        );
        $url = array();
        foreach ($type as $key => $vo) {
            if ($data[$key]) $url[substr($key, 5)] = $this->_CDN . $vo[0] . $data['media_mid'] . '.' . $vo[1] .
                '?vkey=' . $this->_KEY . '&guid=' . $this->_GUID . '&fromtag=30';
        }
        return $url;
    }

    //歌单详情
    public function playlist($playlist_id)
    {
        $url = 'http://c.y.qq.com/qzone/fcg-bin/fcg_ucc_getcdinfo_byids_cp.fcg?';
        $data = array(
            'disstid' => $playlist_id,
            'utf8' => 1,
            'type' => 1,
        );
        return substr($this->curl($url . http_build_query($data)), 13, -1);
    }

    //获取歌词
    public function lyric2($song_mid)
    {
        $url = 'http://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric.fcg?';
        $data = array(
            'songmid' => $song_mid,
            //'nobase64' => '1',
        );
        // var_dump($this->curl($url . http_build_query($data)));exit;
        $data = substr($this->curl($url . http_build_query($data)), 18, -1);
        $data = json_decode($data, true);
        return base64_decode($data['lyric']);
    }

    public function lyric($song_mid)
    {
        $res = ['lyric' => '', 'trans' => ''];
        $url = 'https://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric_new.fcg?is_xml=0&';
        $data = array(
            'songmid' => $song_mid,
            'format' => 'json',
            //'nobase64' => '1',
        );
        // var_dump($this->curl($url . http_build_query($data)));exit;
        $data = substr($this->curl($url . http_build_query($data)), 18, -1);
        // $data=$this->curl($url . http_build_query($data));
        $data = json_decode($data, true);
        if ($data) {
            $res['lyric'] = base64_decode($data['lyric']);
            $res['trans'] = !empty($data['trans']) ? base64_decode($data['trans']) : '';

        }// return ;
        return $res;
    }

    //获取推荐歌单
    public function recommendPlayList($cat_id = 10000000, $page = 1, $limit = 10, $sort_id = 5)
    {
        $res = ['data_count' => 0, 'data_list' => []];
        $url = 'https://c.y.qq.com/splcloud/fcgi-bin/fcg_get_diss_by_tag.fcg?is_xml=0&';
        $data = array(
            'categoryId' => $cat_id,
            'sortId' => $sort_id,//2-自最新 5-推荐
            'format' => 'json',
            'sin' => ($page - 1) * $limit,
            'ein' => ($page) * $limit - 1,
            'outCharset' => 'utf-8'
            //'nobase64' => '1',
        );
        $data = ($this->curl($url . http_build_query($data)));
        // $data=$this->curl($url . http_build_query($data));
        $data = json_decode($data, true);
        if ($data) {
            $res['data_count'] = $data['data']['sum'];
            foreach ($data['data']['list'] as $item) {
                $res['data_list'][] = [
                    'playlist_id' => $item['dissid'],
                    'time' => $item['createtime'],
                    'name' => $item['dissname'],
                    'thumb' => $item['imgurl'],
                    'play_count' => $item['listennum'],
                    'song_count' => 0,
//                    'creator' => $item['creator']['name'],
                ];
            }
        }
        return $res;
    }

}
