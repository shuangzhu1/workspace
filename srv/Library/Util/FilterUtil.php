<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/13
 * Time: 15:56
 */

namespace Util;


use Models\User\UserPersonalSetting;
use Models\User\Users;
use Phalcon\Mvc\User\Plugin;
use Services\User\UserStatus;

class FilterUtil extends Plugin
{
    /**
     *   $content = "<m id='50037'>@hong</m>ytiyertiyer<m id='50038'>@jjjj</m>";
     * preg_match_all('/\<m[^\<]+\<\/m\>/', $content, $match);
     * foreach ($match[0] as $i) {
     * $patten = "/\<m id='([0-9]+)'\>([\s\S]+)\<\/m\>/";
     * preg_match_all($patten, $i, $match2);
     * var_dump($match2);
     * }
     * exit;
     */

    /**
     * 匹配内容url
     * 换行
     * @param $content
     * @return mixed
     */
    public static function parseContentUrl($content)
    {
        if (!$content) return '';

        //注意，这里把上面的正则表达式中的单引号用反斜杠转义了，不然没法放在字符串里
        $pattern = '{(((http[s]{0,1}|ftp)://)?[a-zA-Z0-9\.\-]+\.([a-zA-Z]{2,4})(:\d+)?(\/[a-zA-Z0-9\.\-~!@\#$%^&*+?:_\/=]*)?)|(www.[a-zA-Z0-9\.\-]+\.([a-zA-Z]{2,4})(:\d+)?(\/[a-zA-Z0-9\.\-~!@\#$%^&*+?:_\/=]*)?)}iu';

        preg_match_all($pattern, $content, $matches);
        if ($matches[0]) {
            foreach ($matches[0] as $match) {
                $match_url = !parse_url($match, PHP_URL_SCHEME) ? 'http://' . $match : $match;

                $replacement = "<a href='" . $match_url . "' class='out-link' target='_blank'><i class='icons icon-lianjie'></i>网页链接</a>";
                $content = str_replace($match, $replacement, $content);
            }
        }
        // 换行
        $content = nl2br($content);

        return $content;
    }


    /*@功能解析 获取@的用户 app*/
    public static function packageContentTagApp($content, $owner_id)
    {
        $uid = [];
        if (!$content) {
            return $uid;
        }
        $pattern = "/<m>[0-9]+<\/m>/";
        //@匹配PC
        preg_match_all($pattern, $content, $matches);
        if ($matches[0]) {
            foreach ($matches[0] as $v) {
                $id = str_replace("<m>", "", $v);
                $id = str_replace("</m>", "", $id);
                $id = trim($id);
                if ($id) {
                    $uid[] = $id;
                }
            }
            if ($uid) {
                $uid = Users::getColumn(['id in (' . implode(',', $uid) . ')', 'columns' => 'id'], 'id');
                if ($uid) {
                    //不让他看我的动态的 都不发消息
                    $not_uid = UserPersonalSetting::getColumn(['owner_id=' . $owner_id . ' and user_id in (' . implode(',', $uid) . ') and scan_my_discuss=0', 'columns' => 'user_id'], 'user_id');
                    if ($not_uid) {
                        $uid = array_diff($uid, $not_uid);
                    }
                }
            }
        }
        return $uid;
    }

    /*@功能解析*/
    public static function unPackageContentTagApp($content, $uid)
    {
        if (!$content) {
            return $content;
        }
        $pattern = "/<m>[0-9]+<\/m>/";
        //@匹配PC
        preg_match_all($pattern, $content, $matches);
        foreach ($matches[0] as $v) {
            $id = str_replace("<m>", "", $v);
            $id = str_replace("</m>", "", $id);
            $id = trim($id);
            if ($id) {
                $user_info = UserStatus::getInstance()->getCacheUserInfo($id, false, $uid, false);
                if ($user_info) {
                    $name = "<m id='" . $user_info['uid'] . "'>@" . $user_info['username'] . "</m> ";
                } else {
                    $name = '';
                }
            } else {
                $name = '';
            }
            $content = str_replace($v, $name, $content);
        }
        return $content;
    }

    /*@功能解析 PC*/
    public static function unPackageContentTag($content, $uid, $base_url = '')
    {
        if (!$content) {
            return $content;
        }
        $pattern = "/<m>[0-9]+<\/m>/";
        //@匹配PC
        preg_match_all($pattern, $content, $matches);
        foreach ($matches[0] as $v) {
            $id = str_replace("<m>", "", $v);
            $id = str_replace("</m>", "", $id);
            $id = trim($id);
            if ($id) {
                $user_info = $user_info = UserStatus::getInstance()->getCacheUserInfo($id, false, $uid, true);
                if ($user_info) {
                    if ($base_url) {
                        $name = "<a class='blue' target='_black' href = '" . $base_url . $id . "'>@" . $user_info['username'] . " </a>";
                    } else {
                        $name = "@" . $user_info['username'] . " ";
                    }
                } else {
                    $name = '';
                }
            } else {
                $name = '';
            }
            $content = str_replace($v, $name, $content);
        }
        return $content;
    }

    /**
     * 解析内容
     * 1. 表情替换
     * 2. url 解析
     * 3. 换行替换
     * @param $uid
     * @param $content
     * @return mixed
     */
    public static function parseContent($content, $uid)
    {
        $content = self::unPackageContentTagApp($content, $uid);
        return $content;
    }

}