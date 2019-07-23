<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 7/28/14
 * Time: 6:49 PM
 */

namespace Multiple\Panel\Plugins;


use Models\User\Users;
use Phalcon\Mvc\User\Plugin;

class ShareStat extends Plugin
{
    public static function init()
    {
        return new self();
    }

    public function getTopItem()
    {
        $builder = new \Phalcon\Mvc\Model\Query\Builder();
        $builder->from(array('log' => 'Models\Distribution\ProductShareBackLog'));
        $builder->groupBy(array('log.spm_item'));
        $builder->where('customer_id >= ' . CUR_APP_ID);
        $builder->columns('count(*) as count,spm_item');
        $builder->orderBy('count desc');
        $builder->limit('10');
        $data = $builder->getQuery()->execute()->toArray();
        if ($data) {
            foreach ($data as &$v) {
                $item_info = \Models\Product\Product::findFirst(array('id=' . $v['spm_item'], 'columns' => 'name'));
                $v['item_name'] = $item_info ? $item_info->name : '未知';
            }
        }

        return $data;
    }

    public function getTopUser()
    {
        $builder = new \Phalcon\Mvc\Model\Query\Builder();
        $builder->from(array('log' => 'Models\Distribution\ProductShareBackLog'));
        $builder->groupBy(array('log.spm_uid'));
        $builder->where('customer_id >= ' . CUR_APP_ID);
        $builder->columns('count(*) as count,spm_uid');
        $builder->orderBy('count desc');
        $builder->limit('10');
        $data = $builder->getQuery()->execute()->toArray();
        if ($data) {
            foreach ($data as &$v) {
                $user = Users::findFirst(array('id=' . $v['spm_uid'], 'columns' => 'username'));
                $v['user_name'] = $user ? $user->username : '未知';
            }
        }

        return $data;

    }

    public function getBackByMonth()
    {
        $start = date('ymd', strtotime('-1 month'));
        $endC = isset($end) && $end ? ' and ymd<=' . $end : '';
        $builder = new \Phalcon\Mvc\Model\Query\Builder();
        $builder->from(array('log' => 'Models\Distribution\ProductShareBackLog'));
        $builder->groupBy(array('log.ymd'));
        $builder->where('customer_id=' . CUR_APP_ID . ' and ymd >= ' . $start . $endC);
        $builder->columns('count(*) as count,ymd');
        $data = $builder->getQuery()->execute()->toArray();
        $dateCount = array();
        foreach ($data as $count) {
            $key = date('Y-m-d', self::dateToTime($count['ymd']));
            $dateCount[$key] = $count['count'];
        }

        return $dateCount;
    }

    public function getOrderByMonth()
    {
        $start = date('ymd', strtotime('-1 month'));
        $endC = isset($end) && $end ? ' and ymd<=' . $end : '';
        $builder = new \Phalcon\Mvc\Model\Query\Builder();
        $builder->from(array('log' => 'Models\Distribution\ProductShareOrderLog'));
        $builder->groupBy(array('log.ymd'));
        $builder->where('customer_id = ' . CUR_APP_ID . ' and ymd >= ' . $start . $endC);
        $builder->columns('count(*) as count,ymd');
        $data = $builder->getQuery()->execute()->toArray();
        $dateCount = array();
        foreach ($data as $count) {
            $key = date('Y-m-d', self::dateToTime($count['ymd']));
            $dateCount[$key] = $count['count'];
        }

        return $dateCount;
    }

    public function getRelease()
    {
        $builder = new \Phalcon\Mvc\Model\Query\Builder();
        $builder->from(array('log' => 'Models\Distribution\ProductShareBackLog'));
        $builder->where('spm_type = "item" and customer_id=' . CUR_APP_ID);
        $builder->columns('spm,spm_uid,spm_time,spm_item,from_domain,from_url,province,system,browser');
        $builder->limit(20);
        $res = $builder->getQuery()->execute()->toArray();
        if ($res) {
            foreach ($res as &$v) {
                $item_info = \Models\Product\Product::findFirst(array('id=' . $v['spm_item'], 'columns' => 'name'));
                $user = \Models\User\Users::findFirst(array('id=' . $v['spm_uid'], 'columns' => 'username'));
                $v['item_name'] = $item_info ? $item_info->name : '未知';
                $v['user_name'] = $user ? $user->username : '未知';
            }
        }
        return $res;
    }

    /*
     * @day 日期数字130208142035表示为13年02月08日14点20分35秒（年份是简写两位数）
     * 不存在返回当前时间戳
     */
    public static function dateToTime($day = '')
    {
        if (!$day) return null;
        $dayArr = str_split($day, 2);
        $y = isset($dayArr[0]) && $dayArr[0] ? $dayArr[0] : '00';
        $m = isset($dayArr[1]) && $dayArr[1] ? $dayArr[1] : '00';
        $d = isset($dayArr[2]) && $dayArr[2] ? $dayArr[2] : '00';
        $h = isset($dayArr[3]) && $dayArr[3] ? $dayArr[3] : '00'; # 24 hour
        $i = isset($dayArr[4]) && $dayArr[4] ? $dayArr[4] : '00';
        $s = isset($dayArr[5]) && $dayArr[5] ? $dayArr[5] : '00';
        $datFmt = $y . '-' . $m . '-' . $d . ' ' . $h . ':' . $i . ':' . $s;
        return strtotime($datFmt);
    }


    //将秒（非时间戳）转化成 ** 小时 ** 分
    public static function sec2time($sec = 0)
    {
        if (!($sec > 0)) return '--';
        $h = $sec >= 3600 ? intval($sec / 3600) : 0; # 小时数
        $m = ($sec - $h * 3600) >= 60 ? intval(($sec - $h * 3600) / 60) : 0;
        $s = floor($sec - $h * 3600 - $m * 60);
        # 0-9前置0
        $h = $h < 9 ? '0' . $h : $h;
        $m = $m < 9 ? '0' . $m : $m;
        $s = $s < 9 ? '0' . $s : $s;
        $timeformat = $h . '::' . $m . '::' . $s;
        return $timeformat;
    }

} 