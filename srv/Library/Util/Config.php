<?php

/**
 * User: yanue
 * Date: 10/22/13
 * Time: 2:02 PM
 * Modified: wgwang 2013-10-25
 */
namespace Util;

class Config
{
    static $settings = null;

    /**
     * load config data into static scale
     *
     * @param string $file
     */
    public static function load($file = 'config')
    {
        if (!isset (self::$settings [$file])) {
            $appSettings = array();
            $siteSettings = array();
            $configFile = ROOT . '/Config/' . $file . '.php';
            if (file_exists($configFile)) {
                $appSettings = include($configFile);
            }
            unset ($configFile);

            self::$settings [$file] = array_merge($siteSettings, $appSettings);
            unset ($siteSettings);
            unset ($appSettings);
        }
        return self::$settings [$file];
    }

    /**
     * 获取基本配置信息
     *
     * @param
     *            $key
     * @return string
     */
    public static function getBase($key)
    {
        if (!isset (self::$settings ['config'])) {
            self::load();
        }
        return isset (self::$settings ['config'] [$key]) ? self::$settings ['config'] [$key] : null;
    }

    /**
     * 获取任意配置，如果是非config文件的配置，需要先load
     * @param $key
     * @param null $file
     * @return null|string
     */
    public static function getItem($key, $file = null)
    {
        if (empty ($key)) {
            return null;
        }
        $val = self::getBase($key);
        if (!is_null($val)) {
            return $val;
        } else {
            foreach (self::$settings as $conf) {
                if (isset ($conf [$key])) {
                    return $conf [$key];
                }
            }
            return null;
        }
    }

    /**
     * 修改一个配置，如果是在非config文件中设置的，需要先load
     *
     * @param $key
     * @param $val
     * @param null $file
     * @return bool
     */
    public static function setItem($key, $val, $file = null)
    {
        if (empty ($key) || is_null($val)) {
            return false;
        }
        if (isset (self::$settings [$key])) {
            self::$settings [$key] = $val;
            return true;
        }
        foreach (self::$settings as $kk => $conf) {
            if (isset ($conf [$key])) {
                self::$settings [$kk] [$key] = $val;
                return true;
            }
        }
        return false;
    }

    /**
     * 获取site->config配置文件信息
     *
     * @param
     *            $file
     * @param
     *            $key
     * @return null string
     */
    public static function getSite($file, $key = null)
    {
        $full_file = ROOT . '/Config/' . $file . '.php';
        if (!isset(self::$settings ['config'] [$file])) {
            self::$settings ['config'] [$file] = include($full_file);
        }
        if (!$key) {
            return self::$settings ['config'] [$file];
        }
        return isset (self::$settings ['config'] [$file] [$key]) ? self::$settings ['config'] [$file] [$key] : null;
    }
}