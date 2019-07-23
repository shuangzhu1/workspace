<?php
namespace Upload;

use FastDFS;
use Util\Ajax;

class Fdfs
{

    static $_instance; //单例
    private $_fdfs; //FastDFS 类对象
    private $tracker_host; //tracker ip_addr
    private $tracker_port; //tracker port
    private $group = null; //storage中的组名,可以为空
    private $tracker; //type:array 客户端连接跟踪器(tracker)返回的tracker服务端相关信息
    private $storage; //type:array 客户端连接存储节点(storage)返回的storage服务端相关信息

    /**
     * localfile    本地文件
     * group        组名
     * remotefile|masterfile：远程文件(服务器上的文件)
     * file_id|masterfile_id文件id : file_id(masterfile_id)= group/remotefile(或masterfile)
     * $prefixname    从文件的标识
     * file_ext    文件的后缀名,不包含点'.'
     * meta        文件属性列表，可以包含多个键值对
     */

    /**
     * 构造方法
     */
    private function __construct($config)
    {
        if (!extension_loaded('fastdfs_client')) {
            return Ajax::init()->outError(Ajax::ERROR_RUN_TIME_ERROR_OCCURRED, '系统未安装FastDFS扩展');
        }
        foreach ($config as $key => $val) {
            $this->$key = $val;
        }
        $this->_fdfs = new FastDFS();
        $this->tracker = $this->_fdfs->connect_server($this->tracker_host, $this->tracker_port);
        if (is_array($this->tracker) && $this->group) {
            $this->storage = $this->_fdfs->tracker_query_storage_store($this->group, $this->tracker);
            if (!is_array($this->storage)) {
                $this->halt();
            }
        } else {
            if (!is_array($this->tracker)) $this->halt();
        }
    }

    public static function getInstance($config)
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($config);
        }
        return self::$_instance;
    }

    private function  __clone()
    {
    }

    /**
     * @param $localfile 本地文件(若文件为完整文件名+后缀的形式，$file_ext可以为空)
     * @param null $group 文件组名
     * @param null $file_ext 文件的后缀名,不包含点'.'(例：'png')
     * @param array $meta 文件的附加信息,数组格式,array('hight'=>'350px');
     * @return Array    $file_info     返回包含文件组名和文件名的数组,array('group_name'=>'ab','localfile'=>'kajdsf');
     */
    public function upload_filename($localfile, $group = null, $file_ext = null, $meta = array())
    {
        $bool = $this->check_string($localfile);
        if (!$bool) return false;
        $file_info = $this->_fdfs->storage_upload_by_filename($localfile, $file_ext, $meta, $group, $this->tracker);
        return $file_info;
    }

    /**
     * 上传文件
     * @param $localfile 文件存放位置(若文件为完整文件名+后缀的形式，$file_ext可以为空)
     * @param null $group 文件组名
     * @param null $file_ext 文件的后缀名,不包含点'.'
     * @param array $meta 文件的附加信息,数组格式,array('hight'=>'350px','author'=>'bobo');
     * @return string 返回file_id(文件组名/文件名)
     */
    public function upload_filename1($localfile, $group = null, $file_ext = null, $meta = array())
    {
        $bool = $this->check_string($localfile);
        if (!$bool) return false;
        $file_id = $this->_fdfs->storage_upload_by_filename1($localfile, $file_ext, $meta, $group, $this->tracker);
        if ($file_id) {
            return $file_id;
        } else {
            $this->halt();
        }
    }

    /**
     * 上传从文件
     *
     * @param $localfile 从文件名
     * @param $group 主文件组名
     * @param $masterfile 主文件名
     * @param $prefixname 从文件的标识符;    例如,主文件为abc.jpg,从文件需要大图,添加'_b',则$prefixname = '_b';
     * @param null $file_ext 从文件后缀名
     * @param array $meta 文件的附加信息,数组格式,array('hight'=>'350px','author'=>'bobo');
     * @return bool 返回包含文件组名和文件名的数组,array('group_name'=>'ab','localfile'=>'kajdsf');
     */
    public function upload_slave_filename($localfile, $group, $masterfile, $prefixname, $file_ext = null, $meta = array())
    {
        $bool = $this->check_string($localfile, $group, $masterfile, $prefixname);
        if (!$bool) return false;
        if (!$file_ext) $file_ext = null;
        $file_info = $this->_fdfs->storage_upload_slave_by_filename($localfile, $group, $masterfile, $prefixname, $file_ext, $meta, $this->tracker);
        if ($file_info) {
            return $file_info;
        } else {
            $this->halt();
        }
    }

    /**
     * 上传从文件
     *
     * @param $localfile 从文件名
     * @param $masterfile_id 主文件file_id
     * @param $prefixname 从文件的标识符; 例如,主文件为abc.jpg,从文件需要大图,添加'_b',则$prefixname    = '_b';
     * @param null $file_ext 从文件后缀名
     * @param array $meta 文件的附加信息,数组格式,array('hight'=>'350px','author'=>'bobo');
     * @return bool|array 回包含文件组名和文件名的数组,array('group_name'=>'ab','localfile'=>'kajdsf');
     */
    public function upload_slave_filename1($localfile, $masterfile_id, $prefixname, $file_ext = null, $meta = array())
    {
        $bool = $this->check_string($localfile, $masterfile_id, $prefixname);
        if (!$bool) return false;
        if (!$file_ext) $file_ext = null;
        $file_id = $this->_fdfs->storage_upload_slave_by_filename1($localfile, $masterfile_id, $prefixname, $file_ext, $meta, $this->tracker);
        if ($file_id) {
            return $file_id;
        } else {
            $this->halt();
        }
    }


    /**
     * 上传文件,通过文件流的方式（未测试）
     *
     * @param $filebuff 文件流
     * @param $file_ext 文件的后缀名,不包含点'.'
     * @param null $group 文件的附加信息,数组格式,array('hight'=>'350px','author'=>'bobo');
     * @param array $meta 文件组名
     * @return bool 返回包含文件组名和文件名的数组,array('group_name'=>'ab','filename'=>'kajdsf');
     */
    public function upload_filebuff($filebuff, $file_ext, $group = null, $meta = array())
    {
        $bool = $this->check_string($filebuff, $file_ext);
        if (!$bool) return false;
        $file_info = $this->_fdfs->storage_upload_by_filebuff($filebuff, $file_ext, $meta, $group, $this->tracker);
        if ($file_info) {
            return $file_info;
        } else {
            $this->halt();
        }
    }


    /**
     * 上传从文件,通过文件流的方式（未测试）
     *
     * @param $filebuff 文件流
     * @param $group
     * @param $masterfile
     * @param null $prefix_name 文件的前缀名
     * @param null $file_ext 文件的后缀名,不包含点'.'
     * @param array $meta 文件的附加信息,数组格式,array('hight'=>'350px','author'=>'bobo');
     * @return bool
     */
    public function upload_slave_filebuff($filebuff, $group, $masterfile, $prefix_name = null, $file_ext = null, $meta = array())
    {
        $bool = $this->check_string($filebuff, $group, $masterfile);
        if (!$bool) return false;

        // 判断原有是否存在，存在则删除.
        $file_slave = $this->_fdfs->gen_slave_filename($masterfile, $prefix_name, $file_ext);
        if ($file_slave) {
            $this->_fdfs->storage_delete_file($group, $file_slave, $this->tracker);
        }

        $file_info = $this->_fdfs->storage_upload_slave_by_filebuff($filebuff, $group, $masterfile, $prefix_name, $file_ext, $meta, $this->tracker);
        if ($file_info) {
            return $file_info;
        } else {
            $this->halt();
        }
    }

    /**文件删除**/
    /**
     * 删除文件
     * @param    (string)$group         文件组名
     * @param    (string)$remotefile     文件名
     * @param    (string)$filename     主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname      扩展后缀名
     * @return    bool        成功返回true,失败返回false;
     */
    public function delete_filename($group, $remotefile, $prefix = null, $file_ext = null)
    {
        $bool = $this->check_string($group, $remotefile);
        if (!$bool) return false;
        if ($prefix) {
            $remotefile = $this->get_slave_filename($remotefile, $prefix, $file_ext);
        }
        $bool = $this->_fdfs->storage_delete_file($group, $remotefile, $this->tracker);
        if ($bool) {
            return true;
        } else {
            $this->halt();
        }
    }

    /**
     * 删除文件
     * @param    (string)masterfile_id  文件id
     * @param    (string)$filename     主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname      扩展后缀名
     * @return    bool        成功返回true,失败返回false;
     */
    public function delete_filename1($masterfile_id, $prefix = null, $file_ext = null)
    {
        $bool = $this->check_string($masterfile_id);
        if (!$bool) return false;
        if ($prefix) {
            $masterfile_id = $this->get_slave_filename($masterfile_id, $prefix, $file_ext);
        }
        $bool = $this->_fdfs->storage_delete_file1($masterfile_id, $this->tracker);
        if ($bool) {
            return true;
        } else {
            $this->halt();
        }
    }

    /**文件下载**/
    /**
     * 下载文件到本地服务器
     * @param    (string)$group         文件组名
     * @param    (string)$remotefile       文件名
     * @param    (string)$localfile     本地文件名
     * @param    (string)$filename     主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname      扩展后缀名
     * @param    $file_offset //file start offset, default value is    0
     * @param    $download_bytes //0    (default value)    means from the file    offset to the file end
     * @return    bool            成功返回true,失败返回false
     */
    public function download_filename($group, $remotefile, $localfile, $prefix = null, $file_ext = null, $file_offset = 0, $download_bytes = 0)
    {
        $bool = $this->check_string($group, $remotefile, $localfile);
        if (!$bool) return false;
        if ($prefix) {
            $remotefile = $this->get_slave_filename($remotefile, $prefix, $file_ext);
        }
        $bool = $this->_fdfs->storage_download_file_to_file($group, $remotefile, $localfile, $file_offset, $download_bytes, $this->tracker);
        if ($bool) {
            return true;
        } else {
            $this->halt();
        }
    }

    /**
     * 下载文件到本地服务器
     * @param string $file_id 文件id
     * @param string $localfile 本地文件名
     * @param string $filename 主文件名(masterfile或file_id 两种形式)
     * @param string $prefixname 扩展后缀名
     * @param int $file_offset //file start offset, default value is    0
     * @param int $download_bytes //0    (default value)    means from the file    offset to the file end
     * @return bool           成功返回true,失败返回false
     */
    public function download_filename1($file_id, $localfile, $prefix = null, $file_ext = null, $file_offset = 0, $download_bytes = 0)
    {
        $bool = $this->check_string($file_id, $localfile);
        if (!$bool) return false;
        if ($prefix) {
            $file_id = $this->get_slave_filename($file_id, $prefix, $file_ext);
        }
        $bool = $this->_fdfs->storage_download_file_to_file1($file_id, $localfile, $file_offset, $download_bytes, $this->tracker);
        if ($bool) {
            return true;
        } else {
            $this->halt();
        }
    }

    /**
     * 下载文件流（未测试）
     * @param string $group 文件组名
     * @param string $remotefile 文件名
     * @param int $file_offset //file start offset, default value is    0
     * @param int $download_bytes //0    (default value)    means from the file    offset to the file end
     * @return bool
     */
    public function download_filebuff($group, $remotefile, $file_offset = 0, $download_bytes = 0)
    {
        $bool = $this->check_string($group, $remotefile);
        if (!$bool) return false;
        return $this->_fdfs->storage_download_file_to_buff($group, $remotefile, $file_offset, $download_bytes, $this->tracker);
    }

    /**辅助方法--判断文件是否存在**/
    /**
     * 判断文件是否存在
     * @param    (string)$remotefile     文件名
     * @param    (string)$group         文件组名
     * @param    (string)$filename     主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname     扩展后缀名
     * @return     Bool
     */
    public function    file_exist($group, $remotefile, $prefix = null, $file_ext = null)
    {
        $bool = $this->check_string($group, $remotefile);
        if (!$bool) return false;
        if ($prefix) {
            $remotefile = $this->get_slave_filename($remotefile, $prefix, $file_ext);
        }
        $bool = $this->_fdfs->storage_file_exist($group, $remotefile, $this->tracker);
        if ($bool) {
            return true;
        } else {
            $this->halt();
        }
    }

    /**
     * 判断文件是否存在
     * @param    (string)$file_id    文件id
     * @param    (string)$filename    主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname    扩展后缀名
     * @return     Bool
     */
    public function    file_exist1($file_id, $prefix = null, $file_ext = null)
    {
        $bool = $this->check_string($file_id);
        if (!$bool) return false;
        if ($prefix) {
            $file_id = $this->get_slave_filename($file_id, $prefix, $file_ext);
        }
        $bool = $this->_fdfs->storage_file_exist1($file_id, $this->tracker);
        if ($bool) {
            return true;
        } else {
            $this->halt();
        }
    }

    /**辅助方法--根扩展后缀获取从文件名**/
    /**
     * 根据扩展后缀获取从文件名(masterfile或file_id 两种形式)
     * @param    (string)$filename     主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname     扩展后缀名
     * @param    (string)$file_ext       文件后缀名(这个后缀名替换掉原有文件后缀名)
     * @return     string
     */
    public function    get_slave_filename($filename, $prefixname, $file_ext = null)
    {
        $bool = $this->check_string($filename, $prefixname);
        if (!$bool) return false;
        if (!$file_ext) $file_ext = null;
        $filename = $this->_fdfs->gen_slave_filename($filename, $prefixname, $file_ext);
        if ($filename) {
            return $filename;
        } else {
            $this->halt();
        }
    }

    /**辅助方法--反解析远程文件名**/
    /**
     * 反解析远程文件名(只解析主文件名)
     * 通过此方法可以分析文件名的组成
     * @param    (string)$remotefile     文件名
     * @param    (string)$group         文件组名
     * @return     array
    Array (	[create_timestamp] => 1346085049 [file_size] =>	1235 [source_ip_addr] => 192.168.127.6 [crc32] => -52246624	)
     */
    public function    get_file_info($group, $remotefile)
    {
        $bool = $this->check_string($group, $remotefile);
        if (!$bool) return false;
        $res = $this->_fdfs->get_file_info($group, $remotefile);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**
     * 反解析远程文件名(只解析主文件名)
     * 通过此方法可以分析文件名的组成
     * @param    (string)$file_id    文件id
     * @return     array
    Array (	[create_timestamp] => 1346085049 [file_size] =>	1235 [source_ip_addr] => 192.168.127.6 [crc32] => -52246624	)
     */
    public function    get_file_info1($file_id)
    {
        $bool = $this->check_string($file_id);
        if (!$bool) return false;
        $res = $this->_fdfs->get_file_info1($file_id);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**辅助方法--文件meta操作**/
    /**
     * 设置文件meta
     * @param    (string)$remotefile     文件名
     * @param    (string)$group         文件组名
     * @param    (array)$metadata     meta信息
     * @param    (string)$filename     主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname     扩展后缀名
     * @return     bool
     */
    public function    set_metadata($group, $remotefile, $metadata, $prefix = null, $file_ext = null)
    {

        $bool = $this->check_string($group, $remotefile, $metadata);
        if (!$bool) return false;
        if ($prefix) {
            $remotefile = $this->get_slave_filename($remotefile, $prefix, $file_ext);
        }
        $bool = $this->_fdfs->storage_set_metadata($group, $remotefile, $metadata, FDFS_STORAGE_SET_METADATA_FLAG_OVERWRITE, $this->tracker);
        if ($bool) {
            return true;
        } else {
            $this->halt();
        }
    }

    /**
     * 设置文件meta
     * @param    (string)$file_id    文件id
     * @param    (array)$metadata    meta信息
     * @param    (string)$filename    主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname    扩展后缀名
     * @return     bool
     */
    public function    set_metadata1($file_id, $metadata, $prefix = null, $file_ext = null)
    {
        $bool = $this->check_string($file_id, $metadata);
        if (!$bool) return false;
        if ($prefix) {
            $file_id = $this->get_slave_filename($file_id, $prefix, $file_ext);
        }
        $bool = $this->_fdfs->storage_set_metadata1($file_id, $metadata, FDFS_STORAGE_SET_METADATA_FLAG_MERGE, $this->tracker);
        if ($bool) {
            return true;
        } else {
            $this->halt();
        }
    }

    /**
     * 得到文件meta
     * @param    (string)$remotefile     文件名
     * @param    (string)$group         文件组名
     * @param    (string)$filename     主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname     扩展后缀名
     * @return    array
     */
    public function    get_metadata($group, $remotefile, $prefix = null, $file_ext = null)
    {
        $bool = $this->check_string($group, $remotefile);
        if (!$bool) return false;
        if ($prefix) {
            $remotefile = $this->get_slave_filename($remotefile, $prefix, $file_ext);
        }
        $res = $this->_fdfs->storage_get_metadata($group, $remotefile, $this->tracker);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**
     * 得到文件meta
     * @param    (string)$file_id    文件id
     * @param    (string)$filename    主文件名(masterfile或file_id 两种形式)
     * @param    (string)$prefixname    扩展后缀名
     * @return    array
     */
    public function    get_metadata1($file_id, $prefix = null, $file_ext = null)
    {
        $bool = $this->check_string($file_id);
        if (!$bool) return false;
        if ($prefix) {
            $file_id = $this->get_slave_filename($file_id, $prefix, $file_ext);
        }
        $res = $this->_fdfs->storage_get_metadata1($file_id, $this->tracker);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**辅助方法--获取storage server信息**/
    /**
     * 获取一个$group内的所有server信息
     * @param    (string)$group         文件组名
     * @return     array
    Array (	[0]	=> Array ( [ip_addr] =>	192.168.127.13 [port] => 23000 [sock] => -1	[store_path_index] => 0	) [1] => Array ( [ip_addr] => 192.168.127.14 [port]	=> 23000 [sock]	=> -1 [store_path_index] =>	0 )	)
     */
    public function    get_storage_store_list($group)
    {
        $bool = $this->check_string($group);
        if (!$bool) return false;
        $res = $this->_fdfs->tracker_query_storage_store_list($group, $this->tracker);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**
     * 获取一个$group内的所有server的状态信息
     * @param    (string/null)$group          文件组名
     * @return     array
     * @说明
     */
    public function    get_tracker_list_groups($group)
    {
        $bool = $this->check_string($group);
        if (!$bool) return false;
        $res = $this->_fdfs->tracker_list_groups($group, $this->tracker);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**
     * 返回当前操作到$group/$remotefile资源的storage    server信息
     * @param    (string)$remotefile       文件名
     * @param    (string)$group         文件组名
     * @return     array
    Array (	[0]	=> Array ( [ip_addr] =>	192.168.127.6 [port] =>	23000 [sock] =>	-1 ) )
     * @说明    一个group内如果存在多台storage,每台都存在'group/remotefile'相同资源,当下载或者获取数据时此方法返回的是正在操作的资源所在的storage service信息
     */
    public function    get_storage_fetch($group, $remotefile)
    {
        $bool = $this->check_string($group, $remotefile);
        if (!$bool) return false;
        $res = $this->_fdfs->tracker_query_storage_fetch($group, $remotefile, $this->tracker);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**
     * 返回当前操作到$group/$remotefile资源的storage    server信息
     * @param    (string)$file_id    文件id
     * @return     array
    Array (	[0]	=> Array ( [ip_addr] =>	192.168.127.6 [port] =>	23000 [sock] =>	-1 ) )
     * @说明    一个group内如果存在多台storage,每台都存在'group/remotefile'相同资源,当下载或者获取数据时此方法返回的是正在操作的资源所在的storage service信息
     */
    public function    get_storage_fetch1($file_id)
    {
        $bool = $this->check_string($file_id);
        if (!$bool) return false;
        $res = $this->_fdfs->tracker_query_storage_fetch1($file_id, $this->tracker);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**
     * 查找有$group/$remotefile资源的所有storage    server信息
     * @param    (string)$remotefile       文件名
     * @param    (string)$group         文件组名
     * @return     array
    Array (	[0]	=> Array ( [ip_addr] =>	192.168.127.6 [port] =>	23000 [sock] =>	-1 ) )
     * @说明    一个group内如果存在多台storage,就会返回拥有上述资源的storage 数组信息
     */
    public function    get_storage_list($group, $remotefile)
    {
        $bool = $this->check_string($group, $remotefile);
        if (!$bool) return false;
        $res = $this->_fdfs->tracker_query_storage_list($group, $remotefile, $this->tracker);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**
     * 查找有$file_id资源的所有storage server信息(返回值同上例)
     * @param    (string)$file_id    文件id
     * @return     array
    Array (	[0]	=> Array ( [ip_addr] =>	192.168.127.6 [port] =>	23000 [sock] =>	-1 ) )
     * @说明    一个group内如果存在多台storage,就会返回拥有上述资源的storage 数组信息
     */
    public function    get_storage_list1($file_id)
    {
        $bool = $this->check_string($file_id);
        if (!$bool) return false;
        $res = $this->_fdfs->tracker_query_storage_list1($file_id, $this->tracker);
        if ($res) {
            return $res;
        } else {
            $this->halt();
        }
    }

    /**
     * 判断是否是有值的字符串
     * @param    (array)$arr_str
     * @return     bool
     */
    public function check_string()
    {
        $arr_str = func_get_args();
        foreach ($arr_str as $value) {
            if (empty($value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 错误控制
     *
     */
    public function halt($message = null)
    {
        $errorinfo = $this->_fdfs->get_last_error_info();
        return Ajax::init()->outError(Ajax::UPLOAD_ERR_FASTDFS_SAVE_ERROR_OCCURRED, $errorinfo);

        return false;
    }

    /**
     * 析构方法
     * 在对象被销毁前调用这个方法,对象属性所占用的内存并销毁对象相关的资源
     */
    public function __destruct()
    {
        if (is_array($this->tracker)) {
            $this->_fdfs->disconnect_server($this->tracker);
        }
    }
}
