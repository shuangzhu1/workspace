<?php
namespace Util;

// 作用取得客户端的ip、地理信息、浏览器
class GetClient
{
    ////获得访客浏览器类型
    public function GetBrowser()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $br = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/MicroMessenger/i', $br)) {
                $br = '微信';
            } elseif (preg_match('/QQBrowser/i', $br)) {
                $br = 'QQ浏览器';
            } elseif (preg_match('/MSIE/i', $br)) {
                $br = 'MSIE';
            } elseif (preg_match('/Firefox/i', $br)) {
                $br = 'Firefox';
            } elseif (preg_match('/Chrome/i', $br)) {
                $br = 'Chrome';
            } elseif (preg_match('/Safari/i', $br)) {
                $br = 'Safari';
            } elseif (preg_match('/Opera/i', $br)) {
                $br = 'Opera';
            } else {
                $br = 'Other';
            }
            return $br;
        } else {
            return 0;
        }
    }

    ////获得访客浏览器语言
    function GetLang()
    {
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $lang = substr($lang, 0, 5);
            if (preg_match("/zh-cn/i", $lang)) {
                $lang = "简体中文";
            } elseif (preg_match("/zh/i", $lang)) {
                $lang = "繁体中文";
            } else {
                $lang = "English";
            }
            return $lang;

        } else {
            return "获取浏览器语言失败！";
        }
    }

    ////获取访客操作系统
    public function GetOs()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $OS = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/win/i', $OS)) {
                $OS = 'Windows';
            } else if (stripos($OS, 'iPad') !== false) {
                $OS = 'iPad';
            } else if (stripos($OS, 'iPod') !== false) {
                $OS = 'iPod';
            } else if (stripos($OS, 'iPhone') !== false) {
                $OS = 'iPhone';
            } elseif (stripos($OS, 'mac') !== false) {
                $OS = 'mac';
            } elseif (stripos($OS, 'android') !== false) {
                $OS = 'android';
            } elseif (preg_match('/mac/i', $OS)) {
                $OS = 'MAC';
            } elseif (preg_match('/linux/i', $OS)) {
                $OS = 'Linux';
            } elseif (preg_match('/unix/i', $OS)) {
                $OS = 'Unix';
            } elseif (preg_match('/bsd/i', $OS)) {
                $OS = 'BSD';
            } else {
                $OS = 'Other';
            }
            return $OS;
        } else {
            return 0;
        }
    }

    public function isApp()
    {
        $os = $this->GetOs();
        if (in_array($os, ['android', 'iPad', 'mac', 'iPhone'])) {
            return true;
        }
        return false;
    }

    public function isKlg()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/KLG/i', $ua))
            return true;
        else
            return false;
    }

    public static function Getip()
    {
        //判断服务器是否允许$_SERVER
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            //不允许就使用getenv获取
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }

        return $realip;
    }

    ////获得本地真实IP
    function get_onlineip()
    {
        $mip = file_get_contents("http://www.liuzhigong.com/app/getip.php");

        if ($mip) {
            return $mip;
        } else {
            return "获取本地IP失败！";
        }
    }

    // 获取url域名信息
    public function get_url_domain($url, $exclude = array())
    {
        $url = urldecode($url);
        $domain = parse_url($url, PHP_URL_HOST);
        # 去除localhost
        $pos = strpos($domain, '.');
        if ($pos === false) {
            return false;
        }
        # 去除本地ip：192.168.xxx.xxx
        if (strstr($domain, '192.168') == true) {
            return false;
        }
        if ($exclude) {
            # 获取域名跟
            $nowDomain = substr($domain, $pos + 1);
            # 判断是否在规定域名内
            if (!in_array($nowDomain, $exclude)) {
                return false;
            }
        }
        return $domain;
    }

    // 获取关键字
    public function get_keywords($url)
    {
        $url = urldecode($url);
        $regex = "/(?:soso.+?w=|360.+?q=|baidu.+?wd=|baidu.+?kw=|baidu.+?word=|google.+?q=|sogou.+?query=|bing.+?q=|yahoo.+?[?|&]p=|lycos.+?query=|onseek.+?keyword=|search.tom.+?word=|search.qq.com.+?word=|zhongsou.com.+?word=|search.msn.com.+?q=|yisou.com.+?p=|sina.+?word=|sina.+?query=|sina.+?_searchkey=|sohu.+?word=|sohu.+?key_word=|sohu.+?query=|163.+?q=|Alltheweb.+?q=|115.+?q=|youdao.+?q=|bing.+?q=|114.+?kw=)([^&]*)/";
        $matches = array();
        if (preg_match($regex . 'i', $url, $matches)) {
            $keywords = urldecode($matches[1]) . " ";
            $keywords = mb_convert_encoding($keywords, "UTF-8", "UTF-8,GB2312,GBK");
            return $keywords;
        }
        return false;
    }


}