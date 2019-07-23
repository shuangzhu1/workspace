<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/19
 * Time: 17:34
 */

namespace Services\Site;


use Util\Ajax;
use Util\CharacterPyFirst;

class SensitiveManager
{
    const TYPE_LAW = 'law'; //违法敏感词
    const TYPE_NORMAL = 'normal'; //普通敏感词

    public static function getWord($type, $refresh = false)
    {
        $content = file_get_contents(ROOT . '/Data/site/sensitive/' . $type . '.txt');
        $res = ['keys' => [], 'content' => [], 'count' => 0];
        if ($content != '') {
            $content = explode('#', $content);
            foreach ($content as $k => $item) {
                $temp = explode('|', $item);
                if (in_array($temp[2], $res['keys'])) {
                    $res['content'][$temp[2]][] = ['words' => $temp[0], 'abbr' => $temp[1], 'key' => $k, 'item' => $item];
                } else {
                    $res['keys'][] = $temp[2];
                    $res['content'][$temp[2]] = [['words' => $temp[0], 'abbr' => $temp[1], 'key' => $k, 'item' => $item]];
                }
                $res['count']++;
            }
            sort($res['keys'], SORT_ASC);
            ksort($res['content'], SORT_STRING);
        }


        return $res;
    }

    //搜索关键字
    public static function searchWord($type, $word)
    {
        $content = file_get_contents(ROOT . '/Data/site/sensitive/' . $type . '.txt');
        $content = explode('#', $content);
        $res = ['count' => 0, 'list' => []];
        foreach ($content as $k => $item) {
            $temp = explode('|', $item);
            $content = "";
            $first_key = "";
            if (($before = mb_stristr($item, $word, true)) !== false) {

                //首写字母匹配到的
                if ($pos = mb_strpos($before, '|', 0)) {
                    $str_length = mb_strlen($temp[0]);//关键字总长度
                    $start = stripos($temp[1], $word);//关键字起始位置
                    $end = $start + mb_strlen($word); //关键字结束位置
                    if ($start != 0) {
                        $content .= mb_substr($temp[0], 0, $start);
                    }
                    $content .= '<b class="red">' . mb_substr($temp[0], $start, mb_strlen($word)) . '</b>';
                    if (($end + 1) <= $str_length) {
                        $content .= mb_substr($temp[0], $end);
                    }
                } //名字里匹配到的
                else {
                    $str_length = mb_strlen($temp[0]);//关键字总长度
                    $start = mb_stripos($temp[0], $word);//关键字起始位置
                    $end = $start + mb_strlen($word); //关键字结束位置
                    if ($start != 0) {
                        $content .= mb_substr($temp[0], 0, $start);
                    }
                    $content .= '<b class="red">' . mb_substr($temp[0], $start, mb_strlen($word)) . '</b>';
                    if (($end + 1) <= $str_length) {
                        $content .= mb_substr($temp[0], $end);
                    }
                }
                $res['list'][] = ['content' => $content, 'key' => $k, 'first_abbr' => $temp[2], 'word' => $temp[0]];
                $res['count'] += 1;

            }
        }
        $res['list'] && sort($res['list'], SORT_NUMERIC);
        return $res;
    }

    public static function saveWord($type, $words)
    {

        $content = $text = file_get_contents(ROOT . '/Data/site/sensitive/' . $type . '.txt');
        $content = explode('#', $content);
        $res = [];

        foreach ($content as $item) {
            $temp = explode('|', $item);
            $res[$temp[0]] = $temp;
        }
        $words = array_filter(explode('@', $words));
        foreach ($words as $w) {
            if (!array_key_exists($w, $res)) {
                $abbr = CharacterPyFirst::instance()->getInitials($w);
                $res[$w] = $w . '|' . $abbr . '|' . substr($abbr, 0, 1);
                $text .= '#' . $w . '|' . $abbr . '|' . substr($abbr, 0, 1);
            }
        }

        if (count($content) == 1) {
            $text = substr($text, 1);
        }
        file_put_contents(ROOT . '/Data/site/sensitive/' . $type . '.txt', $text);
        self::setCache($type);
        Ajax::outRight("");
    }

    public static function removeWord($type, $words)
    {
        $file_name = ROOT . '/Data/site/sensitive/' . $type . '.txt';
        $content = file_get_contents($file_name);
        $content = str_replace("#" . $words, '', $content);
        $content = str_replace($words, '', $content);
        if (file_put_contents($file_name, $content)) {
            self::setCache($type);
            return true;
        }
        return false;

    }

    public static function getSetting()
    {
        $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "sensitive_word");
        return $setting ? json_decode($setting, true) : [];
    }

    //设置缓存
    public static function setCache($type)
    {
        $content = file_get_contents(ROOT . '/Data/site/sensitive/' . $type . '.txt');
        $setting = self::getSetting();
        $content = explode('#', $content);

        $data = [];
        //固定的**
        if ($setting && $setting['rule'] == 2) {
            foreach ($content as $item) {
                $temp = explode('|', $item);
                $data[$temp[0]] = "**";
            }
        } //不固定的* 中间为*
        else if ($setting && $setting['rule'] == 3) {
            foreach ($content as $item) {
                $temp = explode('|', $item);
                if (mb_strlen($temp[0]) <= 2) {
                    $data[$temp[0]] = str_repeat("*", mb_strlen($temp[0]));
                } else {
                    $data[$temp[0]] = mb_substr($temp[0], 0, 1) . str_repeat("*", mb_strlen($temp[0]) - 2) . mb_substr($temp[0], -1, 1);
                }
            }
        } //不固定的* 右边为*
        else if ($setting && $setting['rule'] == 4) {
            foreach ($content as $item) {
                $temp = explode('|', $item);
                if (mb_strlen($temp[0]) == 1) {
                    $data[$temp[0]] = str_repeat("*", mb_strlen($temp[0]));
                } else {
                    $data[$temp[0]] = mb_substr($temp[0], 0, 1) . str_repeat("*", mb_strlen($temp[0]) - 1);
                }
            }
        } //重复的*
        else {
            foreach ($content as $item) {
                $temp = explode('|', $item);
                $data[$temp[0]] = str_repeat("*", mb_strlen($temp[0]));
            }
        }
        $cache = new CacheSetting();
        $cache->set(CacheSetting::PREFIX_SITE_SENSITIVE, $type, $data);
    }

    //获取缓存
    public static function getCache($type)
    {
        $cache = new CacheSetting();
        return $cache->get(CacheSetting::PREFIX_SITE_SENSITIVE, $type);
    }

    //过滤敏感词
    public static function filterContent($content)
    {
        $setting = self::getSetting();
        if ($setting) {
            if ($setting['enable'] == 1) {
                if ($setting['enable_normal']) {
                    $arr = self::getCache(self::TYPE_NORMAL);
                    if ($arr) {
                        $content = strtr($content, $arr);
                    }
                }
                if ($setting['enable_law']) {
                    $arr = self::getCache(self::TYPE_LAW);
                    if ($arr) {
                        $content = strtr($content, $arr);
                    }
                }
            }
        }
        return $content;
    }
}