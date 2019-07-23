<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/9
 * Time: 18:16
 */

namespace Util;


use Services\Site\CurlManager;

class Ip
{
    private static $ip     = NULL;
    private static $fp     = NULL;
    private static $offset = NULL;
    private static $index  = NULL;

    //根据ip地址获取地址信息（网络）
    public static function getAddress($ip)
    {
        $res = ['country' => '', 'province' => '', 'city' => ''];
        $address = CurlManager::init()->curl_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=" . $ip);
        if ($address && $address['curl_is_success']) {
            $address = json_decode($address['data'], true);
            $res['country'] = $address['country'];
            $res['city'] = $address['city'];
            $res['province'] = $address['province'];
        }
        return $res;

    }

    /*
     * ===============以下方法用于本地获取ip信息===================
     * 本地数据来源ipip.net -> 数据下载 -> 免费版
     *
     */

    public static function find($ip)
    {
        if (empty($ip) === TRUE)
        {
            return 'N/A';
        }

        $nip   = gethostbyname($ip);
        $ipdot = explode('.', $nip);

        if ($ipdot[0] < 0 || $ipdot[0] > 255 || count($ipdot) !== 4)
        {
            return 'N/A';
        }

        if (self::$fp === NULL)
        {
            self::init();
        }

        $nip2 = pack('N', ip2long($nip));

        $tmp_offset = (int)$ipdot[0] * 4;
        $start      = unpack('Vlen', self::$index[$tmp_offset] . self::$index[$tmp_offset + 1] . self::$index[$tmp_offset + 2] . self::$index[$tmp_offset + 3]);

        $index_offset = $index_length = NULL;
        $max_comp_len = self::$offset['len'] - 1024 - 4;
        for ($start = $start['len'] * 8 + 1024; $start < $max_comp_len; $start += 8)
        {
            if (self::$index{$start} . self::$index{$start + 1} . self::$index{$start + 2} . self::$index{$start + 3} >= $nip2)
            {
                $index_offset = unpack('Vlen', self::$index{$start + 4} . self::$index{$start + 5} . self::$index{$start + 6} . "\x0");
                $index_length = unpack('Clen', self::$index{$start + 7});

                break;
            }
        }

        if ($index_offset === NULL)
        {
            return 'N/A';
        }

        fseek(self::$fp, self::$offset['len'] + $index_offset['len'] - 1024);

        $result = explode("\t", fread(self::$fp, $index_length['len']));
        return [
            'country' => $result[0] ,
            'province' => $result[1] ,
            'city' => $result[2] ,
        ];
    }

    private static function init()
    {
        if (self::$fp === NULL)
        {
            self::$ip = new self();

            self::$fp = fopen(ROOT  . '/Data/db/ip/17monipdb.dat', 'rb');
            if (self::$fp === FALSE)
            {
                throw new \Exception('Invalid 17monipdb.dat file!');
            }

            self::$offset = unpack('Nlen', fread(self::$fp, 4));
            if (self::$offset['len'] < 4)
            {
                throw new \Exception('Invalid 17monipdb.dat file!');
            }

            self::$index = fread(self::$fp, self::$offset['len'] - 4);
        }
    }

    public function __destruct()
    {
        if (self::$fp !== NULL)
        {
            fclose(self::$fp);

            self::$fp = NULL;
        }
    }

}