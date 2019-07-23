<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/28
 * Time: 16:16
 */

namespace Util;


class Linux
{
    public static $mem_name = [
        "total" => '总内存',
        "used" => '已使用',
        "free" => '空闲',
        "shared" => '共享',
        "buff/cache" => '缓存',
        "available" => '可用',
    ];
    public static $cpu_name = [
        "us" => '用户空间占用',
        "sy" => '内核空间占用',
        "ni" => '用户进程空间内改变过优先级的进程',
        "id" => '空闲',
        "wa" => '等待输入输出',
        "hi" => '硬件中断',
        "si" => '软件中断',
        "st" => '实时',
    ];
    public static $disk_name = [
        "total" => '总大小',
        "used" => '已使用',
        "available" => '可用',
        "use" => '已使用',
    ];

    //是否window
    public static function isWindow()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        }
        return false;
    }

    //当前内存使用情况
    public static function getMemoryUse()
    {
        /*
         *               total        used        free      shared  buff/cache   available
              Mem:       16005272     2716812      244664       13592    13043796    12986116
              Swap:             0           0           0
         * */
        $result = [];
        //windows 操作系统
        if (static::isWindow()) {
            return $result;
        }

        exec("free -m", $rs);
        $rs_columns = array_values(array_filter(explode(' ', $rs[0])));

        $rs = array_values(array_filter(explode(' ', str_replace('Mem:', '', $rs[1]))));
        foreach ($rs_columns as $k => $item) {
            $tmp = $rs[$k] > 1024 ? round($rs[$k] / 1024, 2) . "G" : $rs[$k] . 'M';
            $result[$item] = $tmp;
        }
        return $result;
    }

    //当前内存使用情况
    public static function getCpuUse()
    {
        /**
         *  array(8){[0]=>
         * string(76) "top - 17:58:57 up 239 days, 23:33,  3 users,  load average: 0.06, 0.28, 0.43"
         * [1]=>
         * string(68) "Tasks: 144 total,   2 running, 142 sleeping,   0 stopped,   0 zombie"
         * [2]=>
         * string(79) "%Cpu(s):  1.0 us,  0.3 sy,  0.0 ni, 98.1 id,  0.6 wa,  0.0 hi,  0.0 si,  0.0 st"
         * [3]=>
         * string(75) "KiB Mem : 16005272 total,   253144 free,  2737584 used, 13014544 buff/cache"
         * [4]=>
         * string(74) "KiB Swap:        0 total,        0 free,        0 used. 12964592 avail Mem"
         * }
         */
        $result = [];
        //windows 操作系统
        if (static::isWindow()) {
            return $result;
        }
        exec("cat /proc/cpuinfo |grep 'cpu cores'", $rs2);
        $result['core'] = str_replace('cpu cores	: ', '', $rs2[0]);


        exec('top n 1 b i', $rs);
        $rs = explode(',', str_replace('%Cpu(s):', '', $rs[2]));

        foreach ($rs as $item) {
            $tmp = explode(" ", trim($item));
            $result[$tmp[1]] = $tmp[0];
        }
        return $result;
    }

    //硬盘使用
    public static function getDiskUse()
    {
        /**
         * array(8) {
         * [0]=>
         * string(48) "Filesystem      Size  Used Avail Use% Mounted on"
         * [1]=>
         * string(39) "/dev/xvda1       40G   17G   21G  46% /"
         * [2]=>
         * string(42) "devtmpfs        7.8G     0  7.8G   0% /dev"
         * [3]=>
         * string(46) "tmpfs           7.7G  316K  7.7G   1% /dev/shm"
         * [4]=>
         * string(42) "tmpfs           7.7G  784K  7.7G   1% /run"
         * [5]=>
         * string(52) "tmpfs           7.7G     0  7.7G   0% /sys/fs/cgroup"
         * [6]=>
         * string(49) "tmpfs           1.6G     0  1.6G   0% /run/user/0"
         * [7]=>
         * string(43) "/dev/xvdb1       99G   49G   45G  52% /data"
         * }
         */
        $result = [];
        //windows 操作系统
        if (static::isWindow()) {
            return $result;
        }
        exec("df -h", $rs);

        $system_disk = $rs[1];//系统盘
        $system_disk = array_values(array_filter(explode(' ', $system_disk)));

        $result['system'] = ['total' => trim($system_disk[1]), "used" => trim($system_disk[2]), 'available' => trim($system_disk[3]), 'use' => trim($system_disk[4])];
        $mount = array_values(array_filter(explode(' ', $rs[count($rs) - 1])));
        if (substr($mount[1], -1) == 'G' && intval(substr($mount[1], 0, -1)) > 40) {
            $result['mount'][] = ['total' => trim($mount[1]), "used" => trim($mount[2]), 'available' => trim($mount[3]), 'use' => trim($mount[4])];
        }
        return $result;
    }
}