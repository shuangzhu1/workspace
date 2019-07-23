<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/30
 * Time: 15:29
 */

namespace Services\Site;


use Models\Site\SiteAppVersion;
use Phalcon\Mvc\User\Plugin;

class AppVersionManager extends Plugin
{
    private static $instance = null;
    public static $path = ROOT . "/Data/site/version";
    private $os = "android";//  android,ios

    /**
     * @return AppVersionManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function setOs($os)
    {
        $this->os = $os;
        return $this;
    }


    /**获取最新的版本
     * @return array|mixed
     */
    public function latest()
    {
        $content = file_get_contents(self::$path . "/" . $this->os . ".json");
        if ($content) {
            $content = json_decode($content, true);
            krsort($content);
            return reset($content);
        } else {
            return [];
        }
    }

    /**检测版本-是否需要升级
     * @param $version
     * @return bool
     */
    public function checkVersion($version)
    {
        $last_version = self::latest();
        if (!$last_version || ($last_version && version_compare($version, $last_version['limit_version'], '>='))) {
            return true;
        }
        return false;
    }

    /**
     * 同步版本数据
     */
    public function syncVersion()
    {
        if (!$this->os) {
            return false;
        }
        $list = SiteAppVersion::findList(['is_deleted=0 and os="' . $this->os . '"']);
        if ($list) {
            $data = [];
            foreach ($list as $item) {
                $data[$item['version']] = [
                    'version' => $item['version'],
                    'limit_version' => $item['limit_version'],
                    'id' => $item['id'],
                    'detail' => $item['detail'],
                ];
            }
            file_put_contents(self::$path . $this->os . ".json", json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }

    /**版本比较
     * @param $version1 -版本号1
     * @param $version2 -版本号2
     * @param $operator -操作比较符
     * @return bool
     */
    public static function version_compare($version1, $version2, $operator)
    {
        $version1 = (string)$version1;
        $version2 = (string)$version2;

        $len1 = strlen($version1);
        $len2 = strlen($version2);

        $new_version1 = '';
        $new_version2 = '';

        //计算第一个
        $k1 = 0;
        for ($i = 0; $i < $len1; $i++) {
            //还没有碰到.
            if ($k1 == 0) {
                if ($version1[$i] == '.') {
                    $new_version1 .= ".";
                    $k1 = 1;
                } else {
                    $new_version1 .= $version1[$i];
                }
            } else {
                if ($version1[$i] == '.') {
                } else {
                    $new_version1 .= $version1[$i];
                }
            }
        }
        //计算第二个
        $k2 = 0;
        for ($i = 0; $i < $len2; $i++) {
            //还没有碰到.
            if ($k2 == 0) {
                if ($version2[$i] == '.') {
                    $new_version2 .= ".";
                    $k2 = 1;
                } else {
                    $new_version2 .= $version2[$i];
                }
            } else {
                if ($version2[$i] == '.') {
                } else {
                    $new_version2 .= $version2[$i];
                }
            }
        }
        $new_version1 = (float)($new_version1);
        $new_version2 = (float)($new_version2);

        if (($operator == '=' && $new_version1 == $new_version2)
            || ($operator == '>' && $new_version1 > $new_version2)
            || ($operator == '<' && $new_version1 < $new_version2)
            || ($operator == '>=' && $new_version1 >= $new_version2)
            || ($operator == '<=' && $new_version1 <= $new_version2)
            || ($operator == '!=' && $new_version1 != $new_version2)
        ) {
            return true;
        } else {
            return false;
        }


    }
}