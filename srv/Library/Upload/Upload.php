<?php
namespace Upload;

use Models\Site\SiteStorage;
use Services\User\UserStatus;
use Phalcon\Mvc\User\Plugin;
use Util\Ajax;
use Util\Debug;

/**
 * 上传基础类(获取文件流)
 *
 * Class test
 */
class Upload extends Plugin
{

    public $upExt = "";
    protected $md5;
    public $maxAttachSize = 2097152000; // 20M
    public $maxAttachWidth = 18000; // 图像最大宽度
    public $maxAttachHeight = 18000; // 图像最大高度
    public $minAttachWidth = 0; // 图像最小宽度
    public $minAttachHeight = 0; // 图像最小高度
    public $inputField = 'file'; // 表单文件域
    public $batField = 'files'; // 表单文件域
    public $attachDir = 'uploads'; //附件根目录
    protected $options;

    public function __construct()
    {
        $this->ajax = new Ajax();

        $this->attachDir = 'uploads/' . $this->getDI()->get('platform_host');

    }

    public function setAttachDir($dir)
    {
        $this->attachDir = $dir;
    }

    /**
     * 获取上传后的流数据
     * ----支持html5上传
     *
     */
    public function upOne()
    {
        // 判断上传方式
        if (isset($_SERVER['HTTP_CONTENT_DISPOSITION'])
            && preg_match('/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i', $_SERVER['HTTP_CONTENT_DISPOSITION'], $info)
        ) {
            //1. HTML5 方式上传
            // HTML5上传（firefox和chrome核心的浏览器）
            $localName = urldecode($info[2]); //上传的文件名称
            # 缓存文件及后缀
            $ext = pathinfo($localName);
            $ext = $ext['extension'];
            $buff = file_get_contents("php://input");
            $name = rtrim($localName, '.' . $ext);
            $size = strlen($buff);
            // 文件太大
            if ($this->maxAttachSize < $size) {
                return array('error' => Ajax::UPLOAD_ERR_UPLOAD_FILE_IS_TOO_LARGE . '无文件上传' . '最大不能超过：' . (round($this->maxAttachSize / 1024, 2)) . 'K');
                //return   $this->_error(Ajax::UPLOAD_ERR_UPLOAD_FILE_IS_TOO_LARGE, '最大不能超过：' . (round($this->maxAttachSize / 1024, 2)) . 'K');
            };

            if (empty($buff) || $buff == null) {
                return array('error' => Ajax::UPLOAD_ERR_TMP_NAME_NOT_EXIST . '无文件上传');
                //return  $this->_error(Ajax::UPLOAD_ERR_TMP_NAME_NOT_EXIST, '无文件上传');
            }
            $this->md5 = md5($buff);

            // 返回信息 todo size
            return array('buff' => $buff, 'ext' => $ext, 'size' => $size, 'name' => $name, 'md5' => $this->md5);
        } else {


            //2. 普通方式上传
//             $upfile = isset($_FILES[$this->inputField]) ? $_FILES[$this->inputField] : null;
            $files = $this->request->getUploadedFiles();
            //   var_dump($files);exit;
            $upfile = count($files) > 0 ? $files[0] : null;
//            $upfile = $upfile ? $upfile : (isset($_FILES[$this->inputField]) ? $_FILES[$this->inputField] : null);

            if (!$upfile instanceof \Phalcon\Http\Request\File) {
                return array('error' => Ajax::UPLOAD_ERR_FILE_FIELD_NOT_RECEIVED . '表单文件域' . $this->inputField . '未接收到数据');
                // $this->_error(Ajax::UPLOAD_ERR_FILE_FIELD_NOT_RECEIVED, '表单文件域' . $this->inputField . '未接收到数据');
            }
            // 上传出错
            /* $errno = isset($upfile['error']) ? $upfile['error'] : 0;
            if ($errno > 0) {
                if (is_array($errno)) {
                    $this->_error(UPLOAD_ERR_BATCH_IS_NOT_ALLOWED, '请确认filedata文件域参数');
                } else {
                    $this->_error(2000 + $errno);
                }
            } */

            $tempName = $upfile->getTempName();
            if (empty($tempName) || $tempName == null) {
                return array('error' => '无文件上传');
            }

            // 匹配格式
            $pattern = '/\.(' . $this->upExt . ')$/i';
            if (!preg_match($pattern, $upfile->getName(), $sExt)) {
                return array('error' => '文件类型不支持' . $this->upExt);
                //   return  $this->_error(Ajax::UPLOAD_ERR_FILE_EXT_ONLY_ALLOWED, $this->upExt);
            };

            // 文件太大
            if ($this->maxAttachSize < $upfile->getSize()) {
                return array('error' => '最大不能超过：' . (round($this->maxAttachSize / 1024, 2)) . 'K');
            };
            $this->tmpSize = $upfile->getSize();
            # 缓存文件及后缀
            $buff = file_get_contents($tempName);
            //宽高限制
            $imgSize = getimagesize($tempName);
            /*   if ($this->minAttachWidth > $imgSize[0]) {
                return array('error' => '图片最小宽度不能小于：' . $this->minAttachWidth . '像素');
            };
            if ($this->minAttachHeight > $imgSize[1]) {
                return array('error' => '图片最小高度不能小于：' . $this->minAttachHeight . '像素');
            };
            if ($this->maxAttachWidth < $imgSize[0]) {
                return array('error' => '图片最大宽度不能超过：' . $this->maxAttachWidth . '像素');
            };
            if ($this->maxAttachHeight < $imgSize[1]) {
                return array('error' => '图片最大高度不能超过：' . $this->maxAttachHeight . '像素');
            };*/

            $this->md5 = md5($buff);
            @unlink($tempName);
            return array('buff' => $buff, 'ext' => $sExt[1], 'wh' => $imgSize, 'size' => $upfile->getSize(), 'name' => rtrim($upfile->getName(), '.' . $sExt[1]), 'md5' => $this->md5);
        }

    }

    /**
     * @param $file array ('buff' => $buff, 'ext' => $sExt[1]);
     * @return array
     */
    public function saveFile($file)
    {
        $filename = $this->getLocalPath(md5($file['buff']), $file['ext']);
        if (!file_exists($filename['full_path'])) {
            file_put_contents($filename['full_path'], $file['buff']);
        }

        unset($file);

        return array('url' => $filename['url'], 'full_path' => $filename['full_path'], 'path' => $filename['full_path']);
    }


    /**
     * 根据文件md5获取文件存放路径 保证文件唯一性
     *
     * @param $md5
     * @param $sExt
     * @return array
     */
    private function getLocalPath($md5, $sExt)
    {
        $fileDir = '/' . strtolower($sExt) . '/' . strtoupper(substr($md5, 0, 2)) . '/' . strtoupper(substr($md5, 2, 2));
        $fullPath = UPLOAD_DIR . '/' . $this->attachDir . $fileDir;
        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0777, true);
        }
        $newFilename = $md5 . '.' . $sExt;
        $targetPath = $fullPath . '/' . $newFilename;
        $fileUrl = $fileDir . '/' . $newFilename;
        return array('url' => '/' . $this->attachDir . $fileUrl, 'full_path' => $targetPath);
    }

    //get remote img
    public function saveRemoteImg($sUrl, $force = true)
    {
        if (substr($sUrl, 0, 10) == 'data:image') { // base64
            //base64 空格转换
            $sUrl = str_replace(' ', '+', $sUrl);
            if (!preg_match('/^data:image\/(png|jpg|jpeg|gif)/i', $sUrl, $sExt)) return false;
            $sExt = $sExt[1];
            $imgContent = base64_decode(substr($sUrl, strpos($sUrl, 'base64,') + 7));
        } else { //
            if (!preg_match('/\.(' . $this->upExt . ')$/i', $sUrl, $sExt) && !$force) {
                return false;
            }

            $sExt = isset($sExt[1]) && $sExt ? $sExt : 'png';

            $imgContent = $this->getContentFromUrl($sUrl);
        }

        $this->md5 = md5($imgContent);

        return array('buff' => $imgContent, 'ext' => $sExt);
    }

    public function saveImgBybase64($sUrl, $uid = 0)
    {
        if (substr($sUrl, 0, 10) == 'data:image') { // base64
            //base64 空格转换
            $sUrl = str_replace(' ', '+', $sUrl);
            if (!preg_match('/^data:image\/(png|jpg|jpeg|gif)/i', $sUrl, $sExt)) return false;
            $sExt = $sExt[1];
            $imgContent = base64_decode(substr($sUrl, strpos($sUrl, 'base64,') + 7));
            $this->md5 = md5($imgContent);
            return array('buff' => $imgContent, 'ext' => $sExt);
        }
        return false;

    }

    // get file content from url
    public function getContentFromUrl($sUrl, $jumpNums = 0)
    {
        $arrUrl = parse_url(trim($sUrl));
        if (!$arrUrl) return false;
        $host = $arrUrl['host'];
        $port = isset($arrUrl['port']) ? $arrUrl['port'] : 80;
        $path = $arrUrl['path'] . (isset($arrUrl['query']) ? "?" . $arrUrl['query'] : "");
        $fp = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$fp) return false;
        $output = "GET $path HTTP/1.0\r\nHost: $host\r\nReferer: $sUrl\r\nConnection: close\r\n\r\n";
        stream_set_timeout($fp, 60);
        @fputs($fp, $output);
        $Content = '';
        while (!feof($fp)) {
            $buffer = fgets($fp, 4096);
            $info = stream_get_meta_data($fp);
            if ($info['timed_out']) return false;
            $Content .= $buffer;
        }
        @fclose($fp);

        if (preg_match('/^HTTP\/\d.\d (301|302)/is', $Content) && $jumpNums < 5) {
            if (preg_match("/Location:(.*?)\r\n/is", $Content, $murl)) return getUrl($murl[1], $jumpNums + 1);
        }
        if (!preg_match('/^HTTP\/\d.\d 200/is', $Content)) return false;
        $Content = explode("\r\n\r\n", $Content, 2);
        $Content = $Content[1];
        if ($Content) return $Content;
        else return false;
    }

    function getImg($url = "", $filename = "")
    {
        if (is_dir(basename($filename))) {
            echo "The Dir was not exits";
            return false;
        }
        //去除URL连接上面可能的引号
//        $url = preg_replace( '/(?:^['"]+|['"/]+$)/', '', $url );
        $hander = curl_init();
        $fp = fopen($filename, 'wb');
        curl_setopt($hander, CURLOPT_URL, $url);
        curl_setopt($hander, CURLOPT_FILE, $fp);
        curl_setopt($hander, CURLOPT_HEADER, 0);
        curl_setopt($hander, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($hander,CURLOPT_RETURNTRANSFER,false);//以数据流的方式返回数据,当为false是直接显示出来
        curl_setopt($hander, CURLOPT_TIMEOUT, 60);
        /*$options = array(
            CURLOPT_URL=> '/thum-f3ccdd27d2000e3f9255a7e3e2c4880020110622095243.jpg',
            CURLOPT_FILE => $fp,
            CURLOPT_HEADER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_TIMEOUT => 60
        );
        curl_setopt_array($hander, $options);
        */
        curl_exec($hander);
        curl_close($hander);
        fclose($fp);
        return true;
    }

    /**
     * check file
     * @param $md5
     * @param $columns
     * @return bool|int
     */
    public static function checkFile($md5, $columns = '')
    {
        // 插入存储
        $cus = SiteStorage::findOne(['md5="' . $md5 . '"', 'columns' => $columns ? $columns : 'url']);
        if ($cus) {
            SiteStorage::updateOne('count=count+1', 'md5="' . $md5 . '"');
            return $cus['url'];
        }
        return false;
    }

    /**
     * save to db
     * @param $tmp
     * @return bool|int
     */
    public static function syncDb($tmp)
    {
        // 插入存储
        $storage = new SiteStorage();
        return $storage->insertOne($tmp);
    }

    /**
     * 上传错误输出并退出
     *
     */
    function _error($code, $msg = '')
    {
        Ajax::init()->outError($code, $msg);
    }

    public function _getErrMsg($code)
    {
        return $this->ajax->getErrorMsg($code);
    }


}