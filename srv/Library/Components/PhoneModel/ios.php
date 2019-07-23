<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/12
 * Time: 16:01
 */

namespace Components\PhoneModel;


class ios extends phoneModelAbstract implements phoneModelAdapter
{
    private static $phone = 'iPad1,1/iPad|iPad2,1/iPad2|iPad2,2/iPad2|iPad2,3/iPad2|iPad2,4/iPad2|iPad3,1/iPad (3rd generation)|iPad3,2/iPad (3rd generation)|iPad3,3/iPad (3rd generation)|iPad3,4/iPad (4th generation)|iPad3,5/iPad (4th generation)|iPad3,6/iPad (4th generation)|iPad4,1/iPad Air|iPad4,2/iPad Air|iPad4,3/iPad Air|iPad5,3/iPad Air 2|iPad5,4/iPad Air 2|iPad6,7/iPad Pro (12.9-inch)|iPad6,8/iPad Pro (12.9-inch)|iPad6,3/iPad Pro (9.7-inch)|iPad6,4/iPad Pro (9.7-inch)|iPad6,11/iPad (5th generation)|iPad6,12/iPad (5th generation)|iPad7,1/iPad Pro (12.9-inch, 2nd generation)|iPad7,2/iPad Pro (12.9-inch, 2nd generation)|iPad7,3/iPad Pro (10.5-inch)|iPad7,4/iPad Pro (10.5-inch)|iPad7,5/iPad (6th generation)|iPad7,6/iPad (6th generation)|iPad2,5/iPad mini|iPad2,6/iPad mini|iPad2,7/iPad mini|iPad4,4/iPad mini 2|iPad4,5/iPad mini 2|iPad4,6/iPad mini 2|iPad4,7/iPad mini 3|iPad4,8/iPad mini 3|iPad4,9/iPad mini 3|iPad5,1/iPad mini 4|iPad5,2/iPad mini 4|iPhone1,1/iPhone|iPhone1,2/iPhone 3G|iPhone2,1/iPhone 3GS|iPhone3,1/iPhone 4|iPhone3,2/iPhone 4|iPhone3,3/iPhone 4|iPhone4,1/iPhone 4S|iPhone5,1/iPhone 5|iPhone5,2/iPhone 5|iPhone5,3/iPhone 5c|iPhone5,4/iPhone 5c|iPhone6,1/iPhone 5s|iPhone6,2/iPhone 5s|iPhone7,2/iPhone 6|iPhone7,1/iPhone 6 Plus|iPhone8,1/iPhone 6s|iPhone8,2/iPhone 6s Plus|iPhone8,4/iPhone SE|iPhone9,1/iPhone 7|iPhone9,3/iPhone 7|iPhone9,2/iPhone 7 Plus|iPhone9,4/iPhone 7 Plus|iPhone10,1/iPhone 8|iPhone10,4/iPhone 8|iPhone10,2/iPhone 8 Plus|iPhone10,5/iPhone 8 Plus|iPhone10,3/iPhone X|iPhone10,6/iPhone X';

    public function getName($model)
    {
        $name = $model;
        $start = strpos(self::$phone, $model . "/");
        if ($start !== false) {
            $end = strpos(self::$phone, "|", $start);
            $name = substr(self::$phone, $start + strlen($model) + 1, ($end - $start - strlen($model) - 1));
        }
        return $name;
    }
}