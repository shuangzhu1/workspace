<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/4/9
 * Time: 11:05
 */

namespace Services\Vip;


use Phalcon\Mvc\User\Plugin;

abstract class AbstractVip extends Plugin
{
    protected $month = 0; //购买几个月的vip
    protected $money = 0;//扣除的金额
    protected $diamond = 0;//赠送的龙钻

    protected $uid = 0;//用户id

    protected $msg = '';
    protected $msg_code = 0;

    protected $config = [];
    protected $topic = "uums_update";//消息队列的主题
    protected $producer = null;//消息队列
    const STATUS_NORMAL = 1;//正常vip
    const STATUS_DEADLINE = 0;//已过期
    const STATUS_TRANSFER = 2;//续费转移

    public static $privileges = [
        'add_group_count' => "创建群聊个数",
        'group_member_count' => "群成员个数",
        'package_pick_count' => "红包广场领取次数",
        'shop_visitor' => "店铺访客数",
        'user_visitor' => "用户访客数",
    ];


    //设置属性
    public function setProperty($param)
    {
        foreach ($param as $k => $val) {
            $this->$k = $val;
        }
        return $this;
    }

    //获取属性
    public function getProperty($k)
    {
        return $this->$k;
    }

    /**设置错误信息
     * @param $msg
     * @param int $code
     */
    protected function setMsg($msg, $code = 0)
    {
        $this->msg = $msg;
        if ($code) {
            $this->msg_code = $code;
        }
    }

    /**获取错误信息
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**获取错误码
     * @return int
     */
    public function getCode()
    {
        return $this->msg_code;
    }

    /**获取某个时间加几个月后自然月时间
     * @param $start_date
     * @param $month
     * @param $split
     * @return string
     */
    public static function getMonthDay($start_date, $month, $split = '-')
    {
        //获取当前的时间
        $first_date = $start_date;
        $timestamp = strtotime($first_date);
        $start_month = intval(date("m", $timestamp));//月
        $start_year = intval(date("Y", $timestamp));//年
        $start_day = intval(date("d", $timestamp));//日
        //1号
        if ($start_day == 1) {
            if ($start_month + $month > 13) {
                $start_year += intval(($start_month + $month - 1) / 12);
            }
            $start_month = intval(($start_month + $month - 1) % 12);
            $start_month = $start_month == 0 ? 12 : $start_month;
            $day_mount = date("t", strtotime("$start_year" . '-' . "$start_month"));
            $start_day = $day_mount;
        } else {
            if ($start_month + $month > 12) {
                $start_year += intval(($start_month + $month) / 12);
            }
            $start_month = intval(($start_month + $month) % 12);
            $start_month = $start_month == 0 ? 12 : $start_month;
            $day_mount = date("t", strtotime("$start_year" . '-' . "$start_month"));
            if ($start_day > $day_mount) {
                $start_day = $day_mount - 1;
            } else {
                $start_day--;
            }
        }
        $start_month = $start_month < 10 ? '0' . $start_month : $start_month;
        $start_day = $start_day < 10 ? '0' . $start_day : $start_day;
        return $start_year . $split . $start_month . $split . $start_day;
    }

}