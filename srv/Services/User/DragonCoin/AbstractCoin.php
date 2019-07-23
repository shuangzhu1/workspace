<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/3/15
 * Time: 15:19
 */

namespace Services\User\DragonCoin;


use Models\User\UserDragonCoinLog;
use Phalcon\Mvc\User\Plugin;
use Services\User\DragonCoin;

abstract class AbstractCoin extends Plugin
{
    /*-------------收支类型---------------*/
    const IN_OUT_IN = 1;//收入
    const IN_OUT_OUT = 2;//支出

    /*--------------记录类型---------------*/
    const TYPE_RECEIVE_GIFT = 1;//收到礼物
    const TYPE_CHANGE_CASH = 2;//兑换现金
    const TYPE_CHANGE_DIAMOND = 3;//兑换龙钻
    const TYPE_CHANGE_DRAGON_BEANS = 4;//购买龙豆

    protected $type;//类型
    protected $in_out;//收支类型
    protected $val = 0;//变化值
    protected $uid = 0;//用户ID
    protected $current_val = 0;//当前龙币值
    protected $extra_info = null;//当前的额外信息


    public static $type_desc = [
        self::TYPE_RECEIVE_GIFT => '收到礼物',
        self::TYPE_CHANGE_CASH => '兑换现金',
        self::TYPE_CHANGE_DIAMOND => '兑换龙钻',
        self::TYPE_CHANGE_DRAGON_BEANS => '兑换龙豆',
    ];
    public static $err_code_arr = [
        1 => '龙币不足',
    ];

    private $err_msg = [];
    private $err_code = 0;

    /**
     * @param $type
     * @return DragonCoin $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param $uid
     * @return DragonCoin $this
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * @param $value
     * @return DragonCoin $this
     */
    public function setVal($value)
    {
        $this->val = $value;
        return $this;
    }

    /**
     * @param $in_out
     * @return DragonCoin $this
     */
    public function setInOut($in_out)
    {
        $this->in_out = $in_out;
        return $this;
    }

    public function execute()
    {
    }

    /**设置错误信息
     * @param $msg
     */
    protected function setError($msg)
    {
        $this->err_msg[] = $msg;
    }

    /**返回错误信息
     * @return array
     */
    public function getMsg()
    {
        return $this->err_msg;
    }

    /**设置错误信息
     * @param $code
     */
    protected function setErrorCode($code)
    {
        $this->err_code = $code;
    }

    /**返回错误信息
     * @return int
     */
    public function getErrorCode()
    {
        return $this->err_code;
    }

    /**检测参数
     * @return bool
     */
    protected function check()
    {
        if (!$this->type || !$this->in_out || !$this->val || !$this->uid) {
            $this->setError("参数错误");
            return false;
        }
        return true;
    }

    /**写日志
     * @param $desc
     * @param $extra
     * @return bool
     */
    protected function writeLog($desc, $extra)
    {
        if (!UserDragonCoinLog::insertOne(
            [
                'user_id' => $this->uid,
                'coins' => $this->val,
                'in_out' => $this->in_out,
                'type' => $this->type,
                'brief' => $desc == '' ? self::$type_desc[$this->type] : $desc,
                'created' => time(),
                'current_coins' => $this->current_val,
                'extra' => $extra ? $extra : ($this->extra_info ? json_encode($this->extra_info) : '')
            ])
        ) {
            return false;
        };
        return true;
    }
}