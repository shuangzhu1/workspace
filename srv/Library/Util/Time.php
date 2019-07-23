<?php
/**
 * 时间功能函数
 * User: yanue
 * Date: 11/12/13
 * Time: 9:36 AM
 */

namespace Util;


class Time
{

    /**
     * 格式人性化时间
     *
     * @param $time
     * @return bool|string
     */
    static function formatHumaneTime($time)
    {
        $rtime = date("Y-m-d H:i", $time);
        $htime = date("H:i", $time);
        $time = time() - $time;
        if ($time < 60) {
            $str = '刚刚';
        } elseif ($time < 60 * 60) {
            $min = floor($time / 60);
            $str = $min . '分钟前';
        } elseif ($time < 60 * 60 * 24) {
            $h = floor($time / (60 * 60));
            $str = $h . '小时前 ' /*. $htime*/
            ;
        } elseif ($time < 60 * 60 * 24 * 3) {
            $d = floor($time / (60 * 60 * 24));
            if ($d == 1)
                $str = '昨天 '/* . $htime*/
                ;
            else
                $str = '前天 '/* . $htime*/
                ;
        } else {
            $str = $rtime;
        }
        return $str;
    }

    static function show($timeStamp, $format = "Y-m-d H:i:s")
    {
        if (empty($timeStamp) || !is_numeric($timeStamp) || !$timeStamp) {
            return '';
        }
        $d = time() - $timeStamp;
        if ($d < 0) {
            return '';
        } else {
            if ($d < 60) {
                return $d . '秒前';
            } elseif ($d < 3600) {
                return floor($d / 60) . '分钟前';
            } elseif ($d < 86400) {
                return floor($d / 3600) . '小时前';
            } elseif ($d < 259200) {
                return floor($d / 86400) . '天前';
            } else {
                return date($format, $timeStamp);
            }
        }
    }

    //将秒（非时间戳）转化成 ** 小时 ** 分
    public static function sec2time($sec = 0)
    {
        if (!($sec > 0)) return '--';
        $h = $sec >= 3600 ? intval($sec / 3600) : 0; # 小时数
        $m = ($sec - $h * 3600) >= 60 ? intval(($sec - $h * 3600) / 60) : 0;
        $s = floor($sec - $h * 3600 - $m * 60);
        # 0-9前置0
        $h = $h < 9 ? '0' . $h : $h;
        $m = $m < 9 ? '0' . $m : $m;
        $s = $s < 9 ? '0' . $s : $s;
        $timeformat = $h . '::' . $m . '::' . $s;
        return $timeformat;
    }

    //13位时间戳
    public static function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
} 