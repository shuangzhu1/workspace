<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 3/20/15
 * Time: 4:34 PM
 */

namespace Components;


class CommentManager
{
    const TYPE_PRODUCT = 'product';
    const TYPE_DISCUSS = 'discuss';

    /**
     * 1小时之内刚刚
     * 1天内显示几小时前
     *
     * 3天内以昨天，前天显示
     * 超过3天显示具体日期
     * @param int $time
     * @return bool|string
     */
    public static function fmtTime($time)
    {
        if (!is_numeric($time)) {
            $time = strtotime($time);
        }

        $time_diff = time() - $time;
        if ($time_diff >= 259200) {
            return date('Y-m-d', $time);
        }
        if ($time_diff >= 172800) {
            return "前天 " . date('H:i', $time);
        }
        if ($time_diff >= 86400) {
            return "昨天" . date('H:i', $time);
        }

        if ($time_diff >= 3600) {
            $hour = intval($time_diff / 3600);
            $minute = intval(($time_diff % 3600) / 60);
            return sprintf("%d小时%d分钟前", $hour, $minute);
        }
        if ($time_diff >= 60) {
            $minute = intval($time_diff / 60);
            $second = $time_diff % 60;
            return sprintf("%d分%d秒前", $minute, $second);
        } else {
            return $time_diff . '秒前';
        }
    }

}