<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/12
 * Time: 19:08
 */

namespace Components\PhpReader;


class FileReader
{
    //**按行读取文件**/
    public static function getFileByLines($filename, $startLine = 1, $endLine = 50, $method = 'rb')
    {
        $content = array();
        if (version_compare(PHP_VERSION, '5.1.0', '>=')) { // 判断php版本（因为要用到SplFileObject，PHP>=5.1.0）
            $count = $endLine - $startLine;
            $fp = new \SplFileObject($filename, $method);
            $fp->seek($startLine - 1); // 转到第N行, seek方法参数从0开始计数
            for ($i = 0; $i <= $count; ++$i) {
                $content[] = $fp->current(); // current()获取当前行内容
                $fp->next(); // 下一行
            }
        } else { //PHP<5.1
            $fp = fopen($filename, $method);
            if (!$fp)
                return 'error:can not read file';
            for ($i = 1; $i < $startLine; ++$i) { // 跳过前$startLine行
                fgets($fp);
            }

            for ($i; $i <= $endLine; ++$i) {
                $content[] = fgets($fp); // 读取文件行内容
            }
            fclose($fp);
        }
        return array_filter($content); // array_filter过滤：false,null,''
    }

    /*获取文件的行数*/
    public static function getFileLineCount($filename, $method = 'rb')
    {
        $fp = new \SplFileObject($filename, $method);
        $fp->seek(filesize($filename)); //
        return $fp->key();
        /*   $line = 0;
           $fp = fopen($filename, 'r') or die("open file failure!");
           if ($fp) {
               while (stream_get_line($fp, 8192, "\n")) {
                   $line++;
               }
               fclose($fp);
           }
           return $line;*/
    }
}