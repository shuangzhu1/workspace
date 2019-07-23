<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/9
 * Time: 11:03
 */

namespace Services\User\Behavior;


use Services\Site\CacheSetting;
use Util\Ajax;
use Util\GetClient;

class Behavior extends BehaviorDefine
{
    public $behavior = '';

    public static $instance = null;
    public $redis = null;
    public $uid = 0;
    public $config = [];

    public function __construct($behavior, $uid)
    {
        $this->behavior = $behavior;
        $this->redis = $this->di->get('redis_behavior');
        $this->uid = $uid;
        $this->config = json_decode(file_get_contents(ROOT . "/Data/site/api.json"), true);
    }

    public static function init($behavior, $uid)
    {
        if (!self::$instance) {
            self::$instance = $instance = new self($behavior, $uid);
        }
        return self::$instance;
    }

    /**获取动作数据
     * @return mixed
     */
    public function getBehavior()
    {
        return $this->redis->hGet(CacheSetting::KEY_USER_BEHAVIOR . $this->uid, $this->behavior);
    }

    /**设置动作数据
     * @param $data
     * @return mixed
     */
    public function setBehavior($data)
    {
        return $this->redis->hSet(CacheSetting::KEY_USER_BEHAVIOR . $this->uid, $this->behavior, $data);
    }

    /**
     * 加入黑名单
     * @return bool
     */
    public function joinBlacklist()
    {
        if ($this->config['black_enable']) {
            $ip =  GetClient::Getip();
            //已经拉黑了
            if ($this->redis->hGet(CacheSetting::KEY_IP_BLACKLIST, $ip)) {
                return true;
            }
            $count = $this->redis->hIncrBy(CacheSetting::KEY_IP_FREQUENCY, $ip, 1);
            if ($count >= $this->config['black_limit']) {
                $this->redis->hSet(CacheSetting::KEY_IP_BLACKLIST, $ip, json_encode(['time' => time()]));
                return true;
            }
        }
        return false;
    }

    /**
     * @param $ip
     * 删除黑名单
     */
    public function cancelBlacklist($ip)
    {
        $this->redis->hDel(CacheSetting::KEY_IP_BLACKLIST, $ip);
        $this->redis->hDel(CacheSetting::KEY_IP_FREQUENCY, $ip);
    }

    /**检测黑名单
     * @param $ip
     * @return bool
     */
    public function checkIpBlacklist($ip)
    {
        if ($this->redis->hGet(CacheSetting::KEY_IP_BLACKLIST, $ip)) {
            return true;
        }
        return false;
    }

    /**检测操作是否过于频繁
     * @param bool $need_return
     * @return bool
     */
    public function checkBehavior($need_return = false)
    {
        //开启了ip频控 并且动作需要频控 并且每分/时/天有至少一个有限制
        $enable = $this->config['enable'];
        $limit_enable = $this->config['limit'][$this->behavior]['enable'];
        $m_limit = $this->config['limit'][$this->behavior]['m_limit'];
        $h_limit = $this->config['limit'][$this->behavior]['h_limit'];
        $d_limit = $this->config['limit'][$this->behavior]['d_limit'];

        if ($enable && $limit_enable && ($m_limit > 0 || $h_limit > 0 || $d_limit > 0)) {
            $result = $this->getBehavior();
            if (!$result) {
                $data = [];
                if ($m_limit > 0) {
                    $data['m_count'] = $m_limit - 1;
                    $data['m_time'] = sprintf("%.3f", microtime(true)) * 1000;
                }
                if ($h_limit > 0) {
                    $data['h_count'] = $h_limit - 1;
                    $data['h_time'] = sprintf("%.3f", microtime(true)) * 1000;
                }
                if ($d_limit > 0) {
                    $data['d_count'] = $d_limit - 1;
                    $data['d_time'] = sprintf("%.3f", microtime(true)) * 1000;
                }
                $this->setBehavior(json_encode($data));
                //  $this->redis->hSet(CacheSetting::KEY_USER_BEHAVIOR . $this->uid, $this->behavior, json_encode($data));
                return true;
            } else {
                $now = sprintf("%.3f", microtime(true)) * 1000;
                $result = json_decode($result, true);

                $data = $result;
                //24小时有限制
                if ($d_limit > 0) {
                    //刚开启
                    if (empty($result['d_time'])) {
                        $data['d_count'] = $d_limit - 1;
                        $data['d_time'] = $now;
                    } else {
                        //离上次记录 超过了24小时
                        if ($now - $result['d_time'] >= 24 * 60 * 60 * 1000) {
                            $data['d_count'] = $d_limit - 1;
                            $data['d_time'] = $now;
                        } else {
                            if ($result['d_count'] <= 0) {
                                $this->joinBlacklist();
                                return false;
                            } else {
                                $data['d_count'] = $data['d_count'] - 1;
                            }
                        }
                    }
                }
                //1小时有限制
                if ($h_limit > 0) {
                    //刚开启
                    if (empty($result['h_time'])) {
                        $data['h_count'] = $h_limit - 1;
                        $data['h_time'] = $now;
                    } else {
                        //离上次记录 超过了1小时
                        if ($now - $result['h_time'] >= 60 * 60 * 1000) {
                            $data['h_count'] = $h_limit - 1;
                            $data['h_time'] = $now;
                        } else {
                            if ($result['h_count'] <= 0) {
                                $this->joinBlacklist();
                                return false;
                            } else {
                                $data['h_count'] = $data['h_count'] - 1;
                            }
                        }
                    }
                }
                if ($m_limit > 0) {
                    //刚开启
                    if (empty($result['m_time'])) {
                        $data['m_count'] = $m_limit - 1;
                        $data['m_time'] = $now;
                    } else {
                        //离上次记录 超过了1分钟
                        if ($now - $result['m_time'] >= 60 * 1000) {
                            $data['m_count'] = $m_limit - 1;
                            $data['m_time'] = $now;
                        } else {
                            if ($result['m_count'] <= 0) {
                                $this->joinBlacklist();
                                return false;
                            } else {
                                $data['m_count'] = $data['m_count'] - 1;
                            }
                        }
                    }
                }
                $this->setBehavior(json_encode($data));
                //   $this->redis->hSet(CacheSetting::KEY_USER_BEHAVIOR . $this->uid, $this->behavior, json_encode($data));
                /*  if ($now - $result['time'] >= 60 * 1000) {
                      $data = ['count' => $this->config['limit'][$this->behavior]['m_limit'] - 1, 'time' => sprintf("%.3f", microtime(true)) * 1000];
                      $this->setBehavior(json_encode($data));
                      //$this->redis->hSet(CacheSetting::KEY_USER_BEHAVIOR . $this->uid, $this->behavior, json_encode($data));
                      return true;
                  } else {
                      if ($result['count'] <= 0) {
                          $this->joinBlacklist();
                          return false;
                      } else {
                          $data = ['count' => $result['count'] - 1, 'time' => $result['time']];
                          $this->setBehavior(json_encode($data));
                          //   $this->redis->hSet(CacheSetting::KEY_USER_BEHAVIOR . $this->uid, $this->behavior, json_encode($data));
                          return true;
                      }
                  }*/
            }
        }
        return true;

        /*
                if ($result && time() - $result < self::$expired[$this->behavior]) {
                    if ($need_return) {
                        return false;
                    } else {
                        Ajax::outError(Ajax::ERROR_REQUEST_FREQUENCY);
                    }
                }
                if ($need_return) {
                    return true;
                } else {
                    $this->setBehavior();
                }*/
    }
}