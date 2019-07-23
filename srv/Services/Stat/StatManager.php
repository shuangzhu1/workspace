<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/29
 * Time: 18:03
 */

namespace Services\Stat;


use Phalcon\Mvc\User\Plugin;

class StatManager extends Plugin
{
    //获取天
    public static function getDays($days, $format = 'Y-m-d')
    {
        $res = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            if ($i == 0) {
                $res[] = date($format);
            } else {
                $res[] = date($format, strtotime("-" . $i . ' days'));
            }
        }
        return $res;
    }

    //获取月
    public static function getMonths($days)
    {
        $res = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            if ($i == 0) {
                $res[] = date('Y-m');
            } else {
                $res[] = date('Y-m', strtotime("-" . $i . ' months'));
            }
        }
        return $res;
    }

    //获取小时
    public static function getHour()
    {
        $res = [];
        for ($i = 0; $i < 24; $i++) {
            if ($i < 10) {
                $res[] = '0' . $i;
            } else {
                $res[] = $i;
            }
        }
        return $res;
    }

    //获取时间段
    public static function getLimitDay($start, $end,$split='-')
    {
        $res = [];
        $start_arr = explode('-', $start);
        $end_arr = explode('-', $end);
        //同一年
        if ($start_arr[0] == $end_arr[0]) {
            //同一个月
            if ($start_arr[1] == $end_arr[1]) {
                $start_day = intval($start_arr[2]);
                $end_day = intval($end_arr[2]);
                $start_month = strlen($start_arr[1]) == 1 ? '0' . $start_arr[1] : $start_arr[1];
                for ($i = $start_day; $i <= $end_day; $i++) {
                    $res[] = $start_arr[0] .$split . $start_month .$split . (strlen($i) == 1 ? '0' . $i : $i);
                }
            } else {
                $start_month = intval($start_arr[1]); //起始月
                $end_month = intval($end_arr[1]); //结束月
                $index_key = 0; //循环的次数
                for ($i = $start_month; $i <= $end_month; $i++) {
                    $month = $i < 10 ? '0' . $i : $i;
                    if ($index_key == 0) {
                        $res = array_merge($res, self::getMonthDay($i, $start_arr[0] .$split . $month, intval($start_arr[2]),$split));
                    } else if ($i != $end_month) {
                        $res = array_merge($res, self::getMonthDay($i, $start_arr[0] . $split . $month, 1,$split));
                    } else {
                        for ($j = 1; $j <= intval($end_arr[2]); $j++) {
                            $res[] = $start_arr[0] .$split . $month . $split . ($j < 10 ? '0' . $j : $j);
                        }
                    }
                    $index_key++;
                }
            }
        } //不是同一年 只允许跨一年
        else {
            $start_month = intval($start_arr[1]);
            $end_month = 12;
            $index_key = 0; //循环的次数
            for ($i = $start_month; $i <= $end_month; $i++) {
                $month = $i < 10 ? '0' . $i : $i;
                if ($index_key == 0) {
                    $res = array_merge($res, self::getMonthDay($i, $start_arr[0] . $split . $month, intval($start_arr[2]),$split));
                } else if ($i != $end_month) {
                    $res = array_merge($res, self::getMonthDay($i, $start_arr[0] . $split . $month, 1,$split));
                } else {
                    for ($j = 0; $j <= intval($end_arr[2]); $j++) {
                        $res[] = $start_arr[0] .$split . $month . $split . ($j < 10 ? '0' . $j : $j);
                    }
                }
                $index_key++;
            }
            $start_month = 1;
            $end_month = intval($end_arr[1]);
            for ($i = $start_month; $i <= $end_month; $i++) {
                $month = $i < 10 ? '0' . $i : $i;
                if ($i != $end_month) {
                    $res = array_merge($res, self::getMonthDay($i, $end_arr[0] . $split . $month,1,$split));
                } else {
                    for ($j = 1; $j <= intval($end_arr[2]); $j++) {
                        $res[] = $end_arr[0] .$split . $month .$split . ($j < 10 ? '0' . $j : $j);
                    }
                }
            }
        }
        return $res;
    }

    //获取月的天
    public static function getMonthDay($month, $day, $start = 1,$split='-')
    {
        $res = [];
        if (in_array($month, [1, 3, 5, 7, 8, 10, 12])) {
            $day_count = 31;
        } elseif (in_array($month, [4, 6, 9, 11])) {
            $day_count = 30;
        } //2月
        else {
            if (self::isLeapYear(intval(substr($day, 0, 4)))) {
                $day_count = 29;
            } else {
                $day_count = 28;
            }
        }
        $Ym = substr($day, 0, 7);
        for ($i = $start; $i <= $day_count; $i++) {
            $res[] = $Ym .$split . ($i < 10 ? '0' . $i : $i);
        }
        return $res;
    }

    //是否闰年
    public static function isLeapYear($year)
    {
        //能被4整除而不能被100整除或能被400整除的是闰年
        if (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0) {
            return true;
        }
        return false;
    }

    /**获取天数
     * @param $start_date
     * @param $end_date
     * @return int
     */
    public static function getDayCount($start_date, $end_date)
    {
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        $days_count = ceil(($end_time - $start_time + 86400) / 86400);
        return $days_count;

    }
}