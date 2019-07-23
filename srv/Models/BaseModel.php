<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 5/20/14
 * Time: 10:53 AM
 */

namespace Models;


use Components\Redis\RedisComponent;
use Phalcon\Db\RawValue;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\User\Plugin;
use Services\Site\CacheSetting;
use Util\Debug;

// remove the automatic not null validation
Model::setup(array(
    'notNullValidations' => false,
    'exceptionOnFailedSave' => true,
));

class BaseModel extends Model
{

    //mycat 全局序列号
    //mycat 序列号前缀
    public static $prefix_sequence = 'next value for MYCATSEQ_';
    //分片的数据表
    public static $shard_table = [
        "user_attention" => "ATTENTION",
        "user_coin_log" => 'COINLOG',
        "user_contact_member" => "CONTACTMEMBER",
        "user_gift_log" => "GIFTLOG",
        "user_login_log" => "LOGINLOG",
        "user_storage" => "STORAGE",
        "user_online" => "ONLINE",
        "user_point_log" => "POINTLOG",
        "user_show_like" => "SHOWLIKE",
        "user_video" => "VIDEO",

        "social_comment" => "COMMENT",
        "social_comment_reply" => "COMMENTREPLY",
        "social_discuss" => "DISCUSS",
        "social_discuss_media" => "DISCUSSMEDIA",
        "social_discuss_reward" => "DISCUSSREWARD",
        "social_discuss_view_log" => "DISCUSSVIEWLOG",
        "social_fav" => "FAV",
        "social_like" => "LIKE",
        "social_share" => "SHARE",
        "social_share_back_log" => "SHAREBACKLOG",

        "group" => "GROUP",
        "group_member" => "GROUPMEMBER",
        "message" => "MESSAGE",
        "red_package_pick_log" => "REDPACKAGEPICKLOG",
        "user_visitor" => "USERVISITOR",

    ];
    protected $init = null;

    public function validation()
    {
        // 设置null为'' allow empty string
        $notNullAttributes = $this->getModelsMetaData()->getNotNullAttributes($this);
        foreach ($notNullAttributes as $field) {
            if (!isset($this->$field) || $this->$field === null) {
                $this->$field = new RawValue('DEFAULT');
            }
        }
    }

    private static function ping(BaseModel $init)
    {
        try {
            $init->getReadConnection()->query('SELECT 1')->fetch();
        } catch (\PDOException $e) {
            Debug::log("error:" . $e->getMessage(), 'sql');
            //MySQL server has gone away
            if ($e->errorInfo[1] == '2006') {
                try {
                    $init->getReadConnection()->connect();
                    //Debug::log('重连结果' . var_export($res,true),'sql');
                } catch (\PDOException $e2) {

                    Debug::log("error:" . $e2->getMessage(), 'sql');
                    //   echo "\n\r" . "error2 " . $e2->getMessage() . "" . "\n\r";
                    return false;
                    // }
                } catch (\Exception $e3) {
                    Debug::log("error:" . $e3->getMessage(), 'sql');
                    //  echo "\n\r" . "error3 " . $e3->getMessage() . "" . "\n\r";
                    return false;
                }
                return false;
            }
            return true;
        }
        return true;
    }

    //检测数据库 重连
    private static function _checkConnection(BaseModel $init)
    {
        //最多重连5次
        $res = false;
        $time = 1;
        while ($time <= 5 && !$res) {
            $res = self::ping($init);
            if (!$res) {
                sleep(1);
                //   echo "\n\r" . "reconnect " . $time . "-time" . "\n\r";
                Debug::log("error:" . "reconnect " . $time . "-time ." . $init->getSource(), 'sql');
            }
            $time++;
        }
        return $res;
    }

    //初始化
    public static function init()
    {
        $init = new static();
        //检测
        static::_checkConnection($init);
        return $init;
    }
    //原生 查找一条数据
    /**
     * @param array $data ['id=5','order'=>'created desc','group'=>'own','columns'=>'id']
     *                     [['id'=>7],'order'=>'created desc','group'=>'own','columns'=>'id']
     * @param bool $for_update
     * @param bool $from_master --是否从主库取数据
     * @param string $dataNode --指定从哪个节点读
     * @return mixed
     */
    public static function findOne($data = [], $for_update = false, $from_master = false, $dataNode = '')
    {
        $init = self::init();
        $table_name = $init->getSource();
        $columns = '';//要查找的列

        $query = "select ? ";
        //指定查询的列
        if (!empty($data['columns'])) {
            $columns = $data['columns'];
        } else {
            $columns = "*";
        }
        $query .= " from `" . $table_name . '`';
        if (is_string($data)) {
            $query .= " where " . $data;
        } else {
            //指定条件
            if (!empty($data[0])) {
                $query .= " where ";
                //数组
                if (is_array($data[0])) {
                    foreach ($data[0] as $k => $w) {
                        $query .= " $k='" . $w . "' and";
                    }
                    $query = substr($query, 0, -3);
                } //字符串
                else {
                    $query .= $data[0];
                }
            }
            //组集合
            if (!empty($data['group'])) {
                $query .= " group by " . $data['group'];
            }
            //排序
            if (!empty($data['order'])) {
                $query .= " order by " . $data['order'];
            }
            //having条件
            if (!empty($data['having'])) {
                $query .= " having " . $data['having'];
            }
        }
        $query .= " limit 1";
        $query = str_replace('?', $columns, $query);
        if ($for_update) {
            $query .= " for update";
        }
        $note = '';//Mycat 注解
        if (!TEST_SERVER) {
            //指定主库 或者 指定节点
            if ($from_master || $dataNode) {
                $note = "/*#mycat:";
                $note_arr = '';
                if ($from_master) {
                    $note_arr .= "db_type=master,";
                }
                if ($dataNode) {
                    $note_arr .= "dataNode=$dataNode,";
                }
                $note_arr = substr($note_arr, 0, -1);
                $note = $note . $note_arr . "*/ ";
            }
        }
        $query = $note . $query;
        $res = $init->getReadConnection()->query($query)->fetch(\PDO::FETCH_ASSOC);
        return $res;
    }

    /**
     * @param $data ["id=5",'offset'=>1,'limit'=>10,'order'=>'created desc','group'=>'own','columns'=>'id,own']
     *
     * @param bool $from_master --是否从主库读取
     * @param string $dataNode --指定从哪个节点读取
     * @return array
     *
     */
    public static function findList($data = [], $from_master = false, $dataNode = '')
    {
        $init = self::init();
        $table_name = $init->getSource();
        $query = "select ? ";

        $columns = '';//要查找的列

        //指定查询的列
        if (!empty($data['columns'])) {
            $columns = $data['columns'];
        } else {
            $columns = "*";
        }
        $query .= " from `" . $table_name . "`";
        if (is_string($data)) {
            $query .= " where " . $data;
        } else {
            //指定条件
            if (!empty($data[0])) {
                $query .= " where ";
                //数组
                if (is_array($data[0])) {
                    foreach ($data[0] as $k => $w) {
                        $query .= " $k='" . $w . "' and";
                    }
                    $query = substr($query, 0, -3);
                } //字符串
                else {
                    $query .= $data[0];
                }
            }
            //组集合
            if (!empty($data['group'])) {
                $query .= " group by " . $data['group'];
            }
            //排序
            if (!empty($data['order'])) {
                $query .= " order by " . $data['order'];
            }
            //having条件
            if (!empty($data['having'])) {
                $query .= " having " . $data['having'];
            }
            //limit offset 都存在数据
            if (!empty($data['offset']) && !empty($data['limit'])) {
                $query .= " limit " . $data['offset'] . "," . $data['limit'];
            } //limit 有数据 offset 为空
            else if (!empty($data['limit'])) {
                $query .= " limit " . $data['limit'];
            }
        }
        $query = str_replace('?', $columns, $query);

        $note = '';//Mycat 注解
        if (!TEST_SERVER) {
            //指定主库 或者 指定节点
            if ($from_master || $dataNode) {
                $note = "/*#mycat:";
                $note_arr = '';
                if ($from_master) {
                    $note_arr .= "db_type=master,";
                }
                if ($dataNode) {
                    $note_arr .= "dataNode=$dataNode,";
                }
                $note_arr = substr($note_arr, 0, -1);
                $note = $note . $note_arr . "*/ ";
            }
        }
        $query = $note . $query;
        try {
            $res = $init->getReadConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            return $res;
        } catch (\Exception $e) {
            if ($e->errorInfo[1] == 2006) {
                $init = self::init();
                $res = $init->getReadConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
                return $res;
            }
            //var_dump($e->getCode());
        }
    }


    //原生 查找一条数据
    /**
     * @param $data  array/string 需要更新的数据    ['content'=>'你好','item_id'=>10]
     * @param $where array/string  条件      ['id'=>'20','item_id'=>10] 或者 'id=20 and item_id="10"'
     * @return bool
     *
     */
    public static function updateOne($data, $where)
    {

        $init = self::init();
        $table_name = $init->getSource();
        $query = "update `" . $table_name . '`';
        $prepare_value = [];
        if ($data) {
            $query .= " set ";
            if (is_array($data)) {
                foreach ($data as $k => $item) {
                    //加减操作
                    if (((strpos($k, 'device') === false && strpos($k, 'phone_model') === false) && (strpos($item, $k . '+') !== false || strpos($item, $k . '-') !== false)) || is_integer($item)) {
                        $query .= " $k=" . $item . ",";
                    } else {
                        $query .= " $k=?,";
                        $prepare_value[] = $item;
                    }
                }
                $query = substr($query, 0, -1);
            } else {
                $query .= $data;
            }

            //条件
            if ($where) {
                $query .= " where ";
                //数组
                if (is_array($where)) {
                    foreach ($where as $k => $w) {
                        $query .= " $k='" . $w . "' and";
                    }
                    $query = substr($query, 0, -3);
                } //字符串
                else {
                    $query .= $where;
                }
                if ($prepare_value) {
                    $res = $init->getWriteConnection()->execute($query, $prepare_value);
                } else {
                    $res = $init->getWriteConnection()->execute($query);
                }
                return $res;
            }
        }
        return false;
    }

    //原生 插入一条数据
    /**
     * @param $data  array   ['content'=>'你好','created'=>time()]
     * @return int
     */
    public static function insertOne($data)
    {
        $init = self::init();
        $table_name = $init->getSource();

        if (isset(self::$shard_table[$table_name]) && empty($data['id'])) {
            $data['id'] = self::createSingleSequence($table_name);//self::$prefix_sequence . self::$shard_table[$table_name];
        }
        $query = "insert into `" . $table_name . '`';
        $keys = "(";
        $values = " values(";
        $prepare_value = [];
        foreach ($data as $k => $val) {
            $keys .= $k . ",";
            if (strpos($val, 'MYCATSEQ')) {
                $values .= $val . ",";
            } else {
                //$values .= "'" . $val . "',";
                $values .= "?,";
                $prepare_value[] = $val;
            }
        }
        $keys = substr($keys, 0, -1) . ")";
        $values = substr($values, 0, -1) . ")";

        $query .= $keys . $values;

        if ($prepare_value) {
            $res = $init->getReadConnection()->execute($query, $prepare_value);
        } else {
            $res = $init->getReadConnection()->execute($query);
        }
        // Debug::log($res);
        if (!$res) {
            return false;
        }
        $last_id = $init->getReadConnection()->lastInsertId();
        return $last_id;
    }

    //原生 批量插入
    public static function insertBatch($keys, $data)
    {
        $init = self::init();
        $table_name = $init->getSource();
        $ids = [];
        if (isset(self::$shard_table[$table_name]) && empty($data['id'])) {
            $ids = self::createBatchSequence($table_name, count($data));
        }
        $query = "insert into `" . $table_name . '`';
        if ($ids) {
            array_unshift($keys, 'id');
        }
        $values = [];
        $prepare_values = [];

        foreach ($data as $k => $i) {
            $tmp = [];
            if ($ids) {
                $tmp[] = '?';// $ids[$k];
                $prepare_values[] = $ids[$k];
            }
            foreach ($i as $j) {
                $tmp[] = "?";
                $prepare_values[] = $j;
            }
            $values[] = "(" . implode(',', $tmp) . ")";
        }
        $query .= "(" . implode(",", $keys) . ') values ' . implode(",", $values);
        if ($prepare_values) {
            $res = $init->getReadConnection()->execute($query, $prepare_values);
        } else {
            $res = $init->getReadConnection()->execute($query);
        }
        return $res;
    }

    //原生 删除数据
    /**
     * @param $need_rows --影响的行数
     * @param $where  array/string  ['id'=>1000] 或者 'id=200'
     * @return bool
     */
    public static function remove($where, $need_rows = false)
    {
        $init = self::init();
        $table_name = $init->getSource();
        $query = "delete from `" . $table_name . '`';
        $prepare_arr = [];
        //条件
        if ($where) {
            $query .= " where ";
            //数组
            if (is_array($where)) {
                foreach ($where as $k => $w) {
                    $query .= " $k=? and";
                    $prepare_arr[] = $w;
                }
                $query = substr($query, 0, -3);
            } //字符串
            else {
                $query .= $where;
            }
            if (!$need_rows) {
                if ($prepare_arr) {
                    return $init->getReadConnection()->execute($query, $prepare_arr);
                } else {
                    return $init->getReadConnection()->execute($query);
                }
            } else {
                if ($prepare_arr) {
                    $init->getReadConnection()->execute($query, $prepare_arr);
                    return $init->getReadConnection()->affectedRows();
                } else {
                    $init->getReadConnection()->execute($query);
                    return $init->getReadConnection()->affectedRows();
                }
            }

        }
        return false;
    }

    //原生  检测数据是否存在
    /**
     * @param $where ['id'=>1000] 或者 'id=200'
     * @param bool $from_master
     * @return bool
     */
    public static function exist($where, $from_master = false)
    {
        $init = self::init();
        $table_name = $init->getSource();
        $query = "select 1 from `" . $table_name . '`';
        //条件
        if ($where) {
            $query .= " where ";
            $prepare_value = [];
            //数组
            if (is_array($where)) {
                foreach ($where as $k => $w) {
                    $query .= " $k=? and";
                    $prepare_value[] = $w;
                }
                $query = substr($query, 0, -3);
            } //字符串
            else {
                $query .= $where;
            }
            $query .= " limit 1";

            if ($from_master && !TEST_SERVER) {
                $query = "/*#mycat:db_type=master*/ " . $query;
            }
            if ($prepare_value) {
                $res = $init->getReadConnection()->query($query, $prepare_value)->fetch();
            } else {
                $res = $init->getReadConnection()->query($query)->fetch();
            }
            return $res ? true : false;
        }
        return false;
    }
    //原生 数据条数
    /**
     * @param $data ['id=100','group'=>'time'] 或者 'id=200'
     * * @param bool $from_master --是否从主库读取
     * @param string $dataNode --指定从哪个节点读取
     * @return bool
     */
    public static function dataCount($data = '', $from_master = false, $dataNode = '')
    {
        $init = self::init();
        $table_name = $init->getSource();
        $columns = ' count(1) as count ';


        $query = "select # from `" . $table_name . '`';
        $prepare_arr = [];
        //条件
        if ($data) {
            //数组
            if (is_array($data)) {
                if (!isset($data[0])) {
                    $query .= " where ";
                    foreach ($data as $k => $w) {
                        $query .= " $k=? and";
                    }
                    $query = substr($query, 0, -3);
                } else {
                    if ($data[0]) {
                        $query .= " where ";
                    }
                    $query .= $data[0];
                    //组集合
                    if (!empty($data['group'])) {
                        $query .= " group by " . $data['group'];
                        $columns .= "," . $data['group'];
                    }
                    //having条件
                    if (!empty($data['having'])) {
                        $query .= " having " . $data['having'];
                    }
                }
            } //字符串
            else {
                $query .= " where ";
                $query .= $data;
            }
        }
        $query = str_replace('#', $columns, $query);

        $note = '';//Mycat 注解
        if (!TEST_SERVER) {
            //指定主库 或者 指定节点
            if ($from_master || $dataNode) {
                $note = "/*#mycat:";
                $note_arr = '';
                if ($from_master) {
                    $note_arr .= "db_type=master,";
                }
                if ($dataNode) {
                    $note_arr .= "dataNode=$dataNode,";
                }
                $note_arr = substr($note_arr, 0, -1);
                $note = $note . $note_arr . "*/ ";
            }
        }
        $query = $note . $query;

        if ($prepare_arr) {
            $res = $init->getReadConnection()->query($query, $prepare_arr)->fetch();
        } else {
            $res = $init->getReadConnection()->query($query)->fetch();
        }
        return $res ? intval($res['count']) : 0;
    }

    /*  public function exists()
      {
          if ($this->findFirst()) {
              return true;
          } else {
              return false;
          }
      }*/

    /**
     * 根据子段重置数组key
     * @param array $source
     * @param string $name
     * @return array
     */
    static public function rstKey($source, $name)
    {
        if (!is_array($source)) return array();
        $tmp = array();
        foreach ($source as $key => $value) {
            if (isset($value[$name])) $tmp[$value[$name]] = $value;
        }
        return $tmp;
    }

    /**
     * 显示数量
     * @return int
     */
    public static function showNum($number)
    {
        if ($number <= 0) $num = 0;
        if ($number < 10000) return $number;
        //if($num >= 1000 && $num < 10000) return floor($num / 1000).'千+';
        if ($number >= 10000) return floor($number / 10000) . '万+';
    }

    /**
     * 添加记录
     * @param array $data
     * @return bool
     */
    /* public static function add($data)
     {
         $row = static::_cookData($data);
         $row->created = time();
         if ($row->create()) {
             return $row->id;
         } else {
             return false;
         }
     }*/

    /**
     * 通过primary（id）更新数据
     * @param array $data
     * @param integer $id
     * @return bool
     */
    /* public static function edit($data, $id)
     {
         $row = static::_cookData($data, $id);
         $row->modified = time();
         return $row->update();
     }*/

    /**
     * 根据字段自减
     * @param int $id
     * @param string $field
     * @param int $step
     * @return bool
     */
    public static function increment($id, $field, $step = 1)
    {
        $row = static::_cookData(array(), $id);
        $row->$field = $row->$field + $step;
        return $row->update();
    }

    /**
     * 根据字段自增
     * @param $id
     * @param string $field
     * @param int $step
     * @return bool
     */
    public static function decrement($id, $field, $step = 1)
    {
        $row = static::_cookData(array(), $id);
        $row->$field = $row->$field - $step;
        return $row->update();
    }

    /**
     * @param $id
     * @return bool
     */
    /* public static function del($id)
     {
         $row = static::_cookData($id);
         return $row->delete();
     }*/


    /**
     * @param $data
     * @param int $id
     * @return Model|static
     */
    protected static function _cookData($data = array(), $id = 0)
    {
        $row = $id ? static::findFirst(array("id=$id")) : new static();
        if (empty($data)) {
            return $row;
        }

        foreach ($data as $key => $val) {
            $row->$key = $val;
        }
        return $row;
    }

    /**返回指定的列数组
     * @param $parameters
     * @param $column
     * @param string $index_key
     * @param bool $from_master
     * @return array|\Phalcon\Mvc\ResultsetInterface
     */
    public static function getColumn($parameters, $column, $index_key = '', $from_master = false)
    {
        $data = static::findList($parameters, $from_master);
        if (!$data) {
            return $data;
        }
        if ($index_key) {
            $res = array_column($data, $column, $index_key);
        } else {
            $res = array_column($data, $column);
        }
        return $res;
    }

    /** 返回已某列为下标 的数组
     * @param $parameters
     * @param $index_key
     * @param bool $from_master --是否从主库读取
     * @param string $dataNode --指定从哪个节点读取
     * @return array|\Phalcon\Mvc\ResultsetInterface
     *
     */
    public static function getByColumnKeyList($parameters, $index_key, $from_master = false, $dataNode = '')
    {
        $data = static::findList($parameters, $from_master, $dataNode);
        if (!$data) {
            return $data;
        }
        return array_combine(array_column($data, $index_key), $data);
    }

    /**
     *
     * 生成单个序列号
     * @return mixed
     */
    public static function createSingleSequence($table_name)
    {
        global $global_redis;
        return $global_redis->hIncrBy(CacheSetting::KEY_MYSQL_GLOBAL_SEQUENCE, self::$shard_table[$table_name], 1);
    }

    /**生成批量序列号
     * @param $table_name
     * @param $count
     * @return mixed
     */
    public static function createBatchSequence($table_name, $count)
    {
        $res = [];
        global $global_redis;
        $number = $global_redis->hIncrBy(CacheSetting::KEY_MYSQL_GLOBAL_SEQUENCE, self::$shard_table[$table_name], $count);
        while ($count > 0) {
            $res[] = $number - $count + 1;
            $count--;
        }
        return $res;
    }

}