<?php

namespace Components\Redis;

use Phalcon\Cache\Backend\Redis;

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/3
 * Time: 10:59
 */
class RedisComponent extends Redis
{
    public function __construct(\Phalcon\Cache\FrontendInterface $frontend, $options = null)
    {
        parent::__construct($frontend, $options);
        parent::_connect();
    }
    /***--------------------------zset 操作-----------------------------**/

    /**向名称为key的zset中添加元素value，order用于排序。如果该元素已经存在，则根据order更新该元素的顺序。
     * @param $key
     * @param $score
     * @param $value
     * @return mixed
     */
    public function zAdd($key, $score, $value)
    {
        return $this->_redis->zAdd($key, $score, $value);
    }

    /**返回名称为key的zset（元素已按score从小到大排序）中的index从start到end的所有元素
     * @param $key
     * @param $start
     * @param $end
     * @return mixed
     */
    public function zRange($key, $start, $end)
    {
        return $this->_redis->zRange($key, $start, $end);
    }

    /**删除名称为key的zset中的元素value
     * @param $key
     * @param $value
     * @return mixed
     */
    public function zRem($key, $value)
    {
        return $this->_redis->zRem($key, $value);
    }

    public function zRevRange($key, $start, $end, $withscore = false)
    {

    }

    /**返回名称为key的zset中score >= star且score <= end的所有元素
     * @param $key
     * @param $start
     * @param $end
     * @param bool $withscore
     * @return mixed
     */
    public function zRangeByScore($key, $start, $end, $withscore = false)
    {
        return $this->_redis->zRangeByScore($key, $start, $end);
    }

    /**返回名称为key的zset中score >= star且score <= end的所有元素的个数
     * @param $key
     * @param $start
     * @param $end
     * @return mixed
     */
    public function zCount($key, $start, $end)
    {
        return $this->_redis->zCount($key, $start, $end);
    }

    /**删除名称为key的zset中score >= star且score <= end的所有元素，返回删除个数
     * @param $key
     * @param $start
     * @param $end
     * @return mixed
     */
    public function zRemRangeByScore($key, $start, $end)
    {
        return $this->_redis->zRemRangeByScore($key, $start, $end);
    }

    /**返回名称为key的zset的所有元素的个数
     * @param $key
     * @return mixed
     */
    public function zSize($key)
    {
        return $this->_redis->zSize($key);
    }

    /**如果在名称为key的zset中已经存在元素$value，则该元素的score增加increment；否则向集合中添加该元素，其score的值为increment
     * @param $key
     * @param $increment
     * @param $value
     * @return mixed
     */
    public function zIncrBy($key, $increment, $value)
    {
        return $this->_redis->zIncrBy($key, $increment, $value);
    }

    /**返回名称为key的zset中元素val2的score
     * @param $key
     * @param $value
     * @return mixed
     */
    public function zScore($key, $value)
    {
        return $this->_redis->zScore($key, $value);
    }

    public function zUnion()
    {

    }


    /**--------------------------------list 相关操作-------------------------------**/
    /**
     * 构建一个列表(先进后去，类似栈)
     * @param sting $key KEY名称
     * @param string $value 值
     */
    public function lPush($key, $value)
    {
        return $this->_redis->lPush($key, $value);
    }

    /**在名称为key的list右边（尾）添加一个值为value的 元素
     * 构建一个列表(先进先去，类似队列)
     * @param sting $key KEY名称
     * @param string $value 值
     */
    public function rPush($key, $value)
    {
        return $this->_redis->rPush($key, $value);
    }

    /**在名称为key的list左边(头)/右边（尾）添加一个值为value的元素,如果value已经存在，则不添加
     * @param $key
     * @param $value
     */
    public function lPushx($key, $value)
    {
        return $this->_redis->lPushx($key, $value);
    }

    /**输出名称为key的list左(头)起的第一个元素，删除该元素
     * 出栈
     * @param sting $key KEY名称
     */
    public function lPop($key)
    {
        return $this->_redis->lPop($key);
    }

    /**
     * 出栈 输出名称为key的list右（尾）起的第一个元素，删除该元素
     * @param sting $key KEY名称
     */
    public function rPop($key)
    {
        return $this->_redis->rPop($key);
    }

    /**
     * 获取所有列表数据（从头到尾取） lrange mylist 0 -1 【全部】
     * @param sting $key KEY名称
     * @param int $head 开始
     * @param int $tail 结束
     */
    public function lRanges($key, $head, $tail)
    {
        return $this->_redis->lRange($key, $head, $tail);
    }

    /**返回名称为key的list有多少个元素
     * @param $key
     * @return mixed
     */
    public function lSize($key)
    {
        return $this->_redis->lSize($key);
    }

    /**返回名称为key的list中index位置的元素
     * @param $key
     * @param $index
     * @return mixed
     */
    public function lIndex($key, $index)
    {
        return $this->_redis->lIndex($key, $index);
    }

    /**返回名称为key的list中index位置的元素
     * @param $key
     * @param $index
     * @return mixed
     */
    public function lGet($key, $index)
    {
        return $this->_redis->lGet($key, $index);
    }

    /**截取名称为key的list，保留start至end之间的元素
     * @param $key
     * @param $start
     * @param $end
     * @return mixed
     */
    public function lTrim($key, $start, $end)
    {
        return $this->_redis->lTrim($key, $start, $end);
    }

    /**删除count个名称为key的list中值为value的元素。count为0，删除所有值为value的元素，count>0从头至尾删除count个值为value的元素，count<0从尾到头删除|count|个值为value的元素
     * @param $key
     * @param $value
     * @param $count
     * @return mixed
     */
    public function lRem($key, $value, $count)
    {
        return $this->_redis->lRem($key, $value, $count);
    }

    /**在名称为为key的list中，找到值为pivot 的value，并根据参数Redis::BEFORE | Redis::AFTER，来确定，newvalue 是放在 pivot 的前面，或者后面。如果key不存在，不会插入，如果 pivot不存在，return -1
     * @param $key
     * @param string $pos
     * @param $pivot
     * @param $value
     * @return mixed
     */
    public function lInsert($key, $pos = \Redis::AFTER, $pivot, $value)
    {
        return $this->_redis->lInsert($key, $pos, $pivot, $value);
    }

    /**
     * HASH类型
     * @param string $tableName 表名字key
     * @param string $field 字段名字
     * @param string $value 值
     * @return  bool
     */
    public function hSet($tableName, $field, $value)
    {
        return $this->_redis->hSet($tableName, $field, $value);
    }

    public function hGet($tableName, $field)
    {
        return $this->_redis->hGet($tableName, $field);
    }

    /**返回名称为$tableName的hash中元素个数
     * @param $tableName
     * @return mixed
     */
    public function hLen($tableName)
    {
        return $this->_redis->hLen($tableName);
    }

    /**删除名称为$tableName的hash中键为key1的域
     * @param $tableName
     * @param $key
     * @return mixed
     */
    public function hDel($tableName, $key)
    {
        return $this->_redis->hDel($tableName, $key);
    }

    /**返回名称为$tableNamey的hash中所有键
     * @param $tableName
     * @return mixed
     */
    public function hKeys($tableName)
    {
        return $this->_redis->hKeys($tableName);
    }

    /**返回名称为$tableName的hash中所有键对应的value
     * @param $tableName
     * @return mixed
     */
    public function hVals($tableName)
    {
        return $this->_redis->hVals($tableName);
    }

    /**返回名称为$tableName的hash中所有的键（field）及其对应的value
     * @param $tableName
     * @return mixed
     */
    public function hGetAll($tableName)
    {
        return $this->_redis->hGetAll($tableName);
    }

    /**名称为$tableName的hash中是否存在键名字为$key的域
     * @param $tableName
     * @param $key
     * @return mixed
     */
    public function hExists($tableName, $key)
    {
        return $this->_redis->hExists($tableName, $key);
    }

    /**将名称为$tableName的hash中$key的value增加2
     * @param $tableName
     * @param $key
     * @param $value
     * @return mixed
     */
    public function hIncrBy($tableName, $key, $value)
    {
        return $this->_redis->hIncrBy($tableName, $key, $value);
    }

    /**向名称为key的hash中批量添加元素
     * 如 hMset('h',['username'=>'john','sex'=>1])
     * @param $tableName
     * @param $arr
     * @return mixed
     */
    public function hMset($tableName, $arr)
    {
        return $this->_redis->hMset($tableName, $arr);
    }

    /**返回名称为h的hash中field1,field2对应的value
     * hmGet('h', array('field1', 'field2'))
     * @param $tableName
     * @param $arr
     * @return mixed
     */
    public function hMGet($tableName, $arr)
    {
        return $this->_redis->hMGet($tableName, $arr);
    }


    /***-------------------------------------set 操作-----------------------------------------------------*/
    /**向名称为key的set中添加元素value,如果value存在，不写入，return false
     * @param $key
     * @param $value
     * @return mixed
     */
    public function sAdd($key, $value)
    {
        return $this->_redis->sAdd($key, $value);
    }

    /**删除名称为key的set中的元素value
     * @param $key
     * @param $value
     * @return mixed
     */
    public function sRem($key, $value)
    {
        return $this->_redis->sRem($key, $value);
    }

    /**将value元素从名称为key1的集合移到名称为key2的集合
     * @param $key1
     * @param $key2
     * @param $value
     * @return mixed
     */
    public function sMove($key1, $key2, $value)
    {
        return $this->_redis->sMove($key1, $key2, $value);
    }

    /**名称为key的集合中查找是否有value元素，有true 没有 false
     * @param $key
     * @param $value
     * @return mixed
     */
    public function sIsMember($key, $value)
    {
        return $this->_redis->sIsMember($key, $value);
    }

    /**
     * 取集合对应元素
     * @param string $setName 集合名字
     */
    public function sMembers($setName)
    {
        return $this->_redis->sMembers($setName);
    }

    /**返回名称为key的set的元素个数
     * @param $key
     * @return mixed
     */
    public function sSize($key)
    {
        return $this->_redis->sSize($key);
    }

    /**返回名称为key的set中的一个随机元素
     * @param $key
     * @return mixed
     */
    public function sPop($key)
    {
        return $this->_redis->sPop($key);
    }

    public function sRandMember()
    {

    }

    public function sInter()
    {

    }

    public function sInterStore()
    {

    }

    public function sUnion()
    {
    }

    public function sUnionStore()
    {
    }

    public function sDiff()
    {
    }

    public function sDiffStore()
    {
    }

    /**
     * 排序，分页等
     * 参数
     * [
     *   'by' => 'some_pattern_*',
     *   'limit' => array(0, 1),
     *   'get' => 'some_other_pattern_*' or an array of patterns,
     *   'sort' => 'asc' or 'desc',
     *   'alpha' => TRUE,
     *   'store' => 'external-key'
     * ]
     *
     * $this->_redis->sort('s', array('sort' => 'desc'))
     * @param $key
     * @param $arr
     */
    public function sort($key, $arr)
    {
        return $this->_redis->sort($key, $arr);
    }

    /**-------------------------------------string 操作---------------------------------------**/
    /**返回原来key中的值，并将value替换成key
     * @param $key
     * @param $value
     * @return mixed
     */
    public function getSet($key, $value)
    {
        return $this->_redis->getSet($key, $value);
    }

    /**string，名称为key的string的值在后面加上value
     * @param $key
     * @param $value
     * @return mixed
     */
    public function append($key, $value)
    {
        return $this->_redis->append($key, $value);
    }

    /**得到key的string的长度
     * @param $key
     * @return mixed
     */
    public function strlen($key)
    {
        return $this->_redis->strlen($key);
    }

    /**得到key的string的二进制信息
     * @param $key
     * @return mixed
     */
    public function getBit($key)
    {
        return $this->_redis->getBit($key);
    }

    public function setBit()
    {
    }


    /**
     * 设置多个值
     * @param array $keyArray KEY名称
     * @param string|array $value 获取得到的数据
     * @param int $timeOut 时间
     * @return  bool
     */
    public function sets($keyArray, $timeout)
    {
        if (is_array($keyArray)) {
            $retRes = $this->_redis->mset($keyArray);
            if ($timeout > 0) {
                foreach ($keyArray as $key => $value) {
                    $this->_redis->expire($key, $timeout);
                }
            }
            return $retRes;
        } else {
            return "Call  " . __FUNCTION__ . " method  parameter  Error !";
        }
    }

    public function originalSet($key, $val, $timeout = null)
    {
        $res = $this->_redis->set($key, $val);
        if ($timeout) {
            $this->_redis->expire($key, $timeout);
        }
        return $res ? true : false;
    }

    public function originalExists($key)
    {
        return $this->_redis->exists($key);
    }

    public function originalGet($key)
    {
        return $this->_redis->get($key);
    }

    /**
     * 同时获取多个值
     * @param ayyay $keyArray 获key数值
     * @return  bool
     */
    public function gets($keyArray)
    {
        if (is_array($keyArray)) {
            return $this->_redis->mget($keyArray);
        } else {
            return "Call  " . __FUNCTION__ . " method  parameter  Error !";
        }
    }

    /**给key重命名
     * @param $key1
     * @param $key2
     */
    public function rename($key1, $key2)
    {
        $this->_redis->rename($key1, $key2);
    }

    /**
     * 获取所有key名，不是值
     */
    public function keyAll()
    {
        return $this->_redis->keys('*');
    }

    /**查看现在数据库有多少key
     * @return mixed
     */
    public function dbSize()
    {
        return $this->_redis->dbSize();
    }

    /**清空当前数据库
     * @return mixed
     */
    public function flushDB()
    {
        return $this->_redis->flushDB();
    }

    /**清空所有数据库
     * @return mixed
     */
    public function flushAll()
    {
        return $this->_redis->flushAll();
    }

    /**设置过期时间
     * @param $key
     * @param $expiration
     * @return mixed
     */
    public function expire($key, $expiration)
    {
        return $this->_redis->expire($key, $expiration);
    }

    /**删除
     * @param $key
     */
    public function del($key)
    {
        return $this->_redis->del($key);
    }

    //发布
    public function publish($channel, $val)
    {
        return $this->_redis->publish($channel, $val);
    }

    //取消发布
    public function unPublish($channel, $val)
    {
        return $this->_redis->unPublish($channel, $val);
    }

    ///取消订阅
    public function unSubscribe($channels)
    {
        return $this->_redis->unSubscribe($channels);
    }

    //订阅
    public function subscribe($channels, $callback)
    {
        $this->_redis->subscribe($channels, $callback);
    }

    public function pSubscribe($channels, $callback)
    {
        $this->_redis->psubscribe($channels, $callback);
    }


    //用于监视一个(或多个) key ，如果在事务执行之前这个(或这些) key 被其他命令所改动，那么事务将被打断
    public function watch($keys)
    {
        return $this->_redis->watch($keys);
    }

    // Unwatch 命令用于取消 WATCH 命令对所有 key 的监视
    public function unwatch($keys)
    {
        return $this->_redis->unwatch($keys);
    }

    //开启事务
    public function multi()
    {
        return $this->_redis->multi();
    }

    //提交事务
    public function exec()
    {
        return $this->_redis->exec();
    }

    //取消事务
    public function discard()
    {
        return $this->_redis->discard();
    }

    //原子操作 保证操作时锁住
    public function setNX($key, $val, $time = 60)
    {
        $res = $this->_redis->set($key, $val, array('nx', 'ex' => $time));
        return $res;
    }

    //获取redis信息
    public function info($info)
    {
        if ($info) {
            return $this->_redis->info()[$info];
        } else {
            return $this->_redis->info();
        }
    }

}