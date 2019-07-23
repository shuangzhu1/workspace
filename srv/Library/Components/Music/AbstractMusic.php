<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/1
 * Time: 16:31
 */

namespace Components\Music;


abstract class AbstractMusic
{
    public $page = 1;
    public $limit = 20;
    public $key = '';
    public $type = 1;
    public $song_id = '';

    public function setProperty($properties)
    {
        foreach ($properties as $key => $val) {
            $this->$key = $val;
        }
    }

    public function covert($string)
    {
        if (preg_match('/#[44032-55203]/', $string)) {
            return mb_convert_encoding($string, "utf-8", 'HTML-ENTITIES');
        }
        return $string;
    }

    //搜索
    abstract public function searchMusic();

    //获取音乐详情
    abstract public function musicDetail($song_ids);

    //随便听听
    abstract public function randomMusic();

    //新歌榜
    abstract public function newMusic();

    //推荐榜
    abstract public function recommendMusic();

    //总排行榜
    abstract public function topMusic();

    //关键字联想
    abstract public function getSearchKey($key);

    //获取歌词
    abstract public function getLyric($song_id);

    //获取歌曲地址
    abstract public function getUrl($song_id);

    //歌单排行榜
    abstract public function top_playlist($playlist_id);


}