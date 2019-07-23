<?php
/**
 * Created by PhpStorm.
 * User: ykuang
 * Date: 2017/01/10
 * Time: 14:46
 */

namespace Util;

// 获取经纬度直接距离
use Services\Site\CurlManager;

class LatLng
{
    const EARTH_RADIUS = 6378137;
    const PI = 3.1415926;

    public static function init()
    {
        return new self();
    }

    /**
     * 获取两点距离(米)
     *
     * @param $lat1
     * @param $lng1
     * @param $lat2
     * @param $lng2
     * @param bool|true $format 是否格式化
     * @return string
     */
    public static function getDistance($lat1, $lng1, $lat2, $lng2, $format = false)
    {
        $radLat1 = self::rad($lat1);
        $radLat2 = self::rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = self::rad($lng1) - self::rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) +
                cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * self::EARTH_RADIUS;
        $s = round($s * 10000) / 10000;
        return $format ? self::LongFormat($s) : $s;
    }

    public static function LongFormat($long)
    {
        if ($long > 1000) {
            return round($long / 1000) . "公里";
        } else if ($long < 1000 && $long > 0) {
            return round($long) . "米";
        }

        return "附近";
    }

    private static function rad($d)
    {
        return $d * self::PI / 180.0;
    }

    /**
     * 获取两点方位(角度)
     * -- -180到180范围
     *
     * @param $lat_a
     * @param $lng_a
     * @param $lat_b
     * @param $lng_b
     * @return float
     */
    public static function getRotation($lat_a, $lng_a, $lat_b, $lng_b)
    {
        $lat_a = $lat_a * self::PI / 180;

        $lng_a = $lng_a * self::PI / 180;

        $lat_b = $lat_b * self::PI / 180;

        $lng_b = $lng_b * self::PI / 180;


        $d = sin($lat_a) * sin($lat_b) + cos($lat_a) * cos($lat_b) * cos($lng_b - $lng_a);

        $d = sqrt(1 - $d * $d);
        if ($d != 0) {
            $d = cos($lat_b) * sin($lng_b - $lng_a) / $d;

            $d = asin($d) * 180 / self::PI;
        }

        return $d;
    }

    /**根据经纬度 距离算出最大最小经纬度范围
     * @param $lat
     * @param $lng
     * @param int $distance 单位千米
     * @return array
     */
    public static function SqurePoint($lat, $lng, $distance = 2)
    {
        $dlng = 2 * asin(sin($distance / (2 * 6378.137)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = ($distance / 6378.137);
        $dlat = rad2deg($dlat);
        return array(
            'lat_max' => $lat + $dlat,
            'lat_min' => $lat - $dlat,
            'lng_max' => $lng + $dlng,
            'lng_min' => $lng - $dlng
        );
    }

    //
    /**获取一个随机点
     * @param $lat --纬度
     * @param $lng --经度
     * @param int $distance --多大范围 千米
     * @return array
     */
    public static function getRandPos($lat, $lng, $distance = 2)
    {
        $res = self::SqurePoint($lat, $lng, $distance);
        $pos = ['lat' => self::getRandFloat($res['lat_min'], $res['lat_max']), 'lng' => self::getRandFloat($res['lng_min'], $res['lng_max'])];
        return $pos;
    }

    /**获取一个固定不变的随机点
     * @param $lat --你所在纬度
     * @param $lng --你所在的经度
     * @param $re_lng --参照的经度
     * @param $re_lat --参照的纬度
     * @param int $distance --多大范围 千米
     * @return array
     */
    public static function getStaticRandPos($lng, $lat, $re_lng, $re_lat, $distance = 2)
    {
        $res = self::SqurePoint($lat, $lng, $distance);

        $plus_lat = abs($lat - $re_lat);//自己位置和指定位置的纬度差集
        $plus_lng = abs($lng - $re_lng);//自己位置和指定位置的经度差集

        $sum_lat = abs($lat + $re_lat);//自己位置和指定位置的纬度和集
        $sum_lng = abs($lng + $re_lng);//自己位置和指定位置的经度和集

        $percent_lng = $sum_lng < $plus_lng ? $sum_lng / $plus_lng : $plus_lng / $sum_lng;//经度百分比
        $percent_lat = $sum_lat < $plus_lat ? $sum_lat / $plus_lat : $plus_lat / $sum_lat;//纬度百分比

        $percent_lng = ($percent_lng * 98876 % 100) / 100;
        $percent_lat = ($percent_lat * 76755 % 100) / 100;

        $max_lng = $res['lng_max']; //最大经度
        $min_lng = $res['lng_min'];//最小经度

        $max_lat = $res['lat_max']; //最大纬度
        $min_lat = $res['lat_min'];//最小纬度
        $percent_lng_zero = self::getLeftZero($percent_lng) - 1;// 百分比
        $percent_lat_zero = self::getLeftZero($percent_lat) - 1;
        if ($percent_lng_zero) {
            $percent_lng = intval("1" . str_repeat("0", $percent_lng_zero)) * $percent_lng;
        }
        if ($percent_lat_zero) {
            $percent_lat = intval("1" . str_repeat("0", $percent_lat_zero)) * $percent_lat;
        }

        $sub_lng_1 = substr($percent_lng, -1, 1);//倒数第一个数字
        $sub_lng_2 = substr($percent_lng, -2, 1);//倒数第二个数字

        $sub_lat_1 = substr($percent_lat, -1, 1);//倒数第一个数字
        $sub_lat_2 = substr($percent_lat, -2, 1);//倒数第二个数字

//        $last_lng = ($min_lng + ($max_lng - $min_lng) * $percent_lng);
//        $last_lat = ($min_lat + ($max_lat - $min_lat) * ($percent_lat));

        if ($sub_lng_1 % 2 == 1) {
            if (in_array($sub_lng_2, [1, 3, 5, 7, 9])) {
                $last_lng = ($min_lng + ($max_lng - $min_lng) * $percent_lng);
            } else {
                $last_lng = ($min_lng + ($max_lng - $min_lng) * (1 - $percent_lng));
            }
        } else {
            if (in_array($sub_lng_2, [1, 2, 5, 6])) {
                $last_lng = ($max_lng - ($max_lng - $min_lng) * $percent_lng);
            } else {
                $last_lng = ($max_lng - ($max_lng - $min_lng) * (1 - $percent_lng));
            }
        }
        if ($sub_lat_1 % 3 == 1) {
            if (in_array($sub_lat_2, [0, 1, 2, 4])) {
                $last_lat = ($min_lat + ($max_lat - $min_lat) * ($percent_lat));
            } else {
                $last_lat = ($min_lat + ($max_lat - $min_lat) * (1 - $percent_lat));
            }
        } else {
            if (in_array($sub_lat_2, [2, 4, 5, 6, 7])) {
                $last_lat = ($max_lat - ($max_lat - $min_lat) * ($percent_lat));
            } else {
                $last_lat = ($max_lat - ($max_lat - $min_lat) * (1 - $percent_lat));
            }
        }
        return ['lng' => $last_lng, 'lat' => $last_lat];
        //  exit;
        // return $pos;
    }

    //从左匹配浮点数0的个数
    public function getLeftZero($str)
    {
        $str = (string)$str;
        $res = 0;
        $count = strlen($str);
        for ($i = 0; $i <= $count; $i++) {
            $s = $str[$i];
            if ($s == '0') {
                $res = $res + 1;
            } else if ($s == '.') {
            } else {
                break;
            }
        }
        return $res;

    }
    //随机一个指定范围的浮点数
    /**
     * @param $start
     * @param $end
     * @param int $precision
     * @return float
     */
    public static function getRandFloat($start, $end, $precision = 10)
    {
        $tmp1 = (string)sprintf("%." . $precision . "f", $end - $start);
        $tmp3 = '';//存储随机数
        //是否需要判断,例如随机数字是0.067777777，当随机到0.065时,因为0.065是恒小于0.067的。所以后面的位数都可以在0-9之间随机
        $need_judge = true;
        $tmp1_length = strlen($tmp1);//随机数字的长度
        for ($i = 0; $i < $tmp1_length; $i++) {
            $t = $tmp1[$i];
            //循环到的是小数点
            if ($t == '.') {
                $tmp3 .= $t;
            } else {
                //需要判断
                if ($need_judge) {
                    if ($t == '0') {
                        $tmp3 .= $t;
                    } else {
                        $s = (string)mt_rand(0, intval($t));
                        if ($s != $t) {
                            $need_judge = false;
                        }
                        $tmp3 .= $s;
                    }
                } else {
                    $s = (string)mt_rand(0, 9);
                    $tmp3 .= $s;
                }
            }
        }
        $tmp3 = (float)$tmp3;
        return round($end - $tmp3, $precision);

    }

//根据经纬度获取地址信息
    public static function getAddress($lng, $lat, $platform = 'baidu')
    {
        if ($platform == 'baidu') {
            $res = CurlManager::init()->CURL_POST("http://api.map.baidu.com/geocoder/v2/?ak=MWkGH8HcEfA5nbdkiYXp67VmxbgL4iGe&location=$lat,$lng&output=json&pois=1", [

            ]);
            if ($res && $res['curl_is_success']) {
                $res = json_decode($res['data'], true);
                if (!empty($res['result']['addressComponent'])) {
                    return $res['result']['addressComponent'];
                }
            }
        } else if ($platform == 'gaode') {
            $res = CurlManager::init()->CURL_POST("http://restapi.amap.com/v3/geocode/regeo?key=74e22883503970598c62f729cae05c8e&location=$lng,$lat", [
            ]);
            if ($res && $res['curl_is_success']) {
                $res = json_decode($res['data'], true);
                if (!empty($res['regeocode']['addressComponent'])) {
                    return $res['regeocode']['addressComponent'];
                }
            }
        }

        return false;
    }
}