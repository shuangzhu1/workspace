<?php
/**
 * Created by PhpStorm.
 * User: Arimis
 * Date: 2015/3/13
 * Time: 14:17
 */

namespace Upload;


class ArchiveHandle
{

    const ARCHIVE_TYPE_ZIP = 'zip';
    const ARCHIVE_TYPE_RAR = 'rar';
    const ARCHIVE_TYPE_GZIP = 'gzip';

    /**
     * 压缩成zip格式
     * @param string|array $target
     * @param string $destination
     * @return bool
     */
    public static function zip($target, $destination)
    {
        if (is_string($target)) {
            if (!file_exists($target)) {
                return false;
            }
            if (!file_exists($destination)) {
                if (!mkdir(dirname($destination), 0777, true)) {
                    return false;
                }
            }

            $zipObj = new \ZipArchive();
            $zipObj->open($destination);
            $zipObj->addFile($target);
            $zipObj->close();
            return true;
        } else if (is_array($target)) {
            if (!file_exists($destination)) {
                if (!mkdir(dirname($destination), 0777, true)) {
                    return false;
                }
            }
            $zipObj = new \ZipArchive();
            $zipObj->open($destination);
            foreach ($target as $t) {
                if (file_exists($t)) {
                    $zipObj->addFile($t);
                }
            }
            $zipObj->close();
            return true;
        }
    }

    /**
     * 解压zip格式压缩包
     * @param string $target
     * @param string $destination
     * @return bool
     */
    public static function unzip($target, $destination)
    {
        if (!file_exists($target)) {
            return false;
        }
        if (!file_exists($destination)) {
            if (!mkdir(dirname($destination), 0777, true)) {
                return false;
            }
        }
        $zipObj = new \ZipArchive();
        $zipObj->open($target);
        $zipObj->extractTo($destination);
        $zipObj->close();
        return true;
    }

    /**
     * 压缩成rar格式
     * @param string|array $target
     * @param string $destination
     * @return bool
     */
    public static function rar($target, $destination)
    {
        return false;
    }

    /**
     * 解压rar格式压缩包
     * @param string $target
     * @param string $destination
     * @return bool
     */
    public static function unrar($target, $destination)
    {
        if (!file_exists($target)) {
            return false;
        }
        if (!file_exists($destination)) {
            if (!mkdir(dirname($destination), 0777, true)) {
                return false;
            }
        }
        $araObj = AraArchive::open($target);
        $entryList = $araObj->getEntries();
        if (count($entryList) > 0) {
            foreach ($entryList as $entry) {
                $entry->extract($destination);
            }
        }
        $araObj->close();
        return true;
    }

    /**
     * 检查文件压缩方式
     * @param $fileName
     * @return string
     */
    public static function checkFileExt($fileName)
    {
        if (strlen($fileName) > 0) {
            return end(explode('.', $fileName));
        }
        return false;
    }

}