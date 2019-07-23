<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/16
 * Time: 15:00
 */

namespace Components;


class Time
{
    /**
     * 格式人性化时间
     *
     * @param $time int 时间戳
     * @param $more bool 是否详细显示
     * @return bool|string
     */
    public static function formatHumaneTime($time, $more = false)
    {
        $hour_time = $more ? ' ' . date("H:i", $time) : "";
        # 距离现在时间(s)
        $interval_time = time() - $time;
        # 今天凌晨到现在时间(s)
        $today_time = time() - strtotime(date('Y-m-d'));
        # 1. 今天凌晨以前
        if ($interval_time > $today_time) {
            // 多减1s,防止 00:00:00 时属于前一天
            $d = floor(($interval_time - $today_time - 1) / (24 * 60 * 60));
            if ($d == 0) {
                $str = '昨天 ' . date("H:i", $time);
            } elseif ($d == 1) {
                $str = '前天 ' . date("H:i", $time);
            } else {
                # 年份显示
                if (date("Y") > date('Y', $time)) {
                    $str = date('Y-m-d', $time) . $hour_time;
                } else { # 今年内
                    $str = date('m月d日', $time) . $hour_time;
                }
            }
        } else { #2. 今天内
            if ($interval_time < 60) {
                $str = '刚刚';
            } elseif ($interval_time < 60 * 60) {
                $min = floor($interval_time / 60);
                $str = $min . '分钟前';
            } else {
                $h = floor($interval_time / (60 * 60));
                if ($h > 8) {
                    $str = '今天 ' . date("H:i", $time);
                } else {
                    $str = $h . '小时前';
                }
            }
        }

        return $str;
    }

    /**
     * 获取俩个时间的人性化时间间隔
     *
     * @param $time1
     * @param $time2
     * @return string
     */
    public static function getBetween($time1, $time2)
    {
        $seconds = abs($time1 - $time2);
        if ($seconds <= 60) {
            $str = $seconds . '秒';
        } else if ($seconds <= 60 * 60) {
            $str = ceil($seconds / 60) . '分钟';
        } else if ($seconds > 60 && $seconds < 24 * 60 * 60) {
            $str = ceil($seconds / (60 * 60)) . '小时';
        } else {
            $str = ceil($seconds / (24 * 60 * 60)) . '天';
        }

        return $str;
    }

    /**获取星期几
     * @param $date
     * @return string
     */
    public static function getWeek($date)
    {
        $res = "";
        if (!is_numeric($date)) {
            $date = strtotime($date);
        }
        $week = date('w', $date);
        switch ($week) {
            case 1:
                $res = '星期一';
                break;
            case 2:
                $res = '星期二';
                break;
            case 3:
                $res = '星期三';
                break;
            case 4:
                $res = '星期四';
                break;
            case 5:
                $res = '星期五';
                break;
            case 6:
                $res = '星期六';
                break;
            case 0:
                $res = '星期日';
                break;
        }
        return $res;
    }
}