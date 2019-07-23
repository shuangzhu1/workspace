<?php
/**
 * Created by PhpStorm.
 *
 * ini文件写入和读取
 *
 * User:ykuang
 * Date: 2018/3/8
 * Time: 15:04
 */

namespace Components\PhpReader;


class IniReader
{
    private static $path = '';
    private $error = [];

    function __construct($file_path)
    {
        self::$path = $file_path;
    }

    /**获取文件内容
     * @return array
     */
    public function readFile()
    {
        $arr = [];
        if (file_exists(self::$path)) {
            $fp = fopen(self::$path, "r");
            $pre_key = '';
            while (!feof($fp)) {
                $str = fgets($fp);
                $str = str_replace(["\r", "\n", "\n\r", "\r\n"], '', $str);
                if (preg_match('/^\[(\S+.*\S+)\]$/', $str, $match)) {
                    /**
                     * [network]
                     */
                    $pre_key = trim($match[1]);
                    $arr[$pre_key] = [];
                } else if (preg_match('/^(.+)\[\](.)*=(.*)(.*)$/', $str, $match)) {
                    /**
                     * user[]=1
                     * user[]=2
                     */
                    $sub_key = trim($match[1]);
                    $val = trim(substr($str, strrpos($str, '=') + 1));
                    if ($pre_key) {
                        if (isset($arr[$pre_key][$sub_key])) {
                            $arr[$pre_key][$sub_key][] = $val;
                        } else {
                            $arr[$pre_key][$sub_key] = [$val];
                        }
                    } else {
                        if (isset($arr[$sub_key])) {
                            $arr[$sub_key][] = $val;
                        } else {
                            $arr[$sub_key] = [$val];
                        }
                    }
                } else if (preg_match('/^[^=\[]+=(.)+$/', $str)) {
                    /**
                     *
                     * user=1
                     */
                    $str = explode('=', $str);
                    if (count($str) == 2) {
                        $sub_key = trim($str[0]);
                        $val = trim($str[1]);
                        if ($pre_key) {
                            $arr[$pre_key][$sub_key] = $val;
                        } else {
                            $arr[$sub_key] = $val;
                        }
                    }

                }

            }
        } else {
            $this->error[] = '配置文件不存在';
        }
        return $arr;
    }

//    /**获取文件内容
//     * @return array
//     */
//    public function readFile2()
//    {
//        if (file_exists(self::$path)) {
//            return parse_ini_file(self::$path);
//        }
//        return [];
//    }
    /**写配置文件
     * @param $data
     * @return int
     */
    public function writeFile($data)
    {
        if (!file_exists(self::$path)) {
            $this->error[] = '配置文件不存在';
            return false;
        }
        $new_data = "";
        foreach ($data as $k => $item) {
            $new_data .= "[$k]\n";
            foreach ($item as $s_k => $s_item) {
                if (is_array($s_item)) {
                    foreach ($s_item as $ss_item) {
                        $new_data .= "$s_k" . "[] = $ss_item\n";
                    }
                } else {
                    $new_data .= "$s_k" . " = $s_item\n";
                }
            }
        }
        return file_put_contents(self::$path, substr($new_data, 0, -1));
    }

    public function getErrMsg()
    {
        return $this->error;
    }
}