<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/2
 * Time: 11:41
 */

namespace Util;


class FileHandle
{
    /**
     * 删除文件夹及 下面的文件
    /**
     * @param $dir --文件夹地址【绝对路径】
     * @return bool
     */
    public static  function removeDir($dir) {
        //先删除目录下的文件：
        $dh=opendir($dir);
        while ($file=readdir($dh)) {
            if($file!="." && $file!="..") {
                $fullPath=$dir."/".$file;
                //是文件 直接删除
                if(!is_dir($fullPath)) {
                    unlink($fullPath);
                } else {
                    self::removeDir($fullPath);
                }
            }
        }
        closedir($dh);
        //删除当前文件夹：
        if(rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }
}