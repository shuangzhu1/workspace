<?php
/**
 * Created by PhpStorm.
 * User: luguiwu
 * Date: 15-3-20
 * Time: 下午4:28
 */

namespace Upload;


use Util\Config;

class UploadAvatar extends Upload {
    public $upExt = "jpg|jpeg|gif|png|bmp";
    public $maxAttachSize = 3072000; // 最大 300k
    public $imgSize;

    public function __construct()
    {
        parent::__construct();
        // 初始化配置
        $imgConf = Config::getSite('upload', 'pic');

        // set for
        $this->config = $imgConf['fastDFS'];
        $this->upExt = $imgConf['ext'];
        $this->maxAttachSize = $imgConf['maxAttachSize'];
        //$this->imgSize = $imgConf['imgSize'];

        // 初始化图片处理
        $this->imgHandle = new ImgHandle();
        // 初始化fastDFS
        $this->fdfs = Fdfs::getInstance($this->config);
    }

    /**
     * 缩放后裁截图片
     *
     * @param $buff
     * @param $filename
     * @return array
     */
    public function cropSize($buff, $filename)
    {
        // 后缀
        $ext = pathinfo($filename)['extension'];
        $type = array_keys($this->imgSize);

        foreach ($type as $t) {
            if ($t != 'o') {
                // 缩放尺寸
                $imgInfo = $this->imgHandle->thumb($buff, $ext, $this->imgSize[$t][0], $this->imgSize[$t][1]);
                // 保存
                $this->fdfs->upload_slave_filebuff($imgInfo['buff'], $this->config['group'], $filename, '_' . $t, $ext);
            }
        }
    }

    /**
     * 缩放裁切原图(如果大于)
     */
    public function getOriginal($buff, $ext)
    {
        // resize to original size
        $original_buff = $this->imgHandle->resize_original($buff, $ext, 1000, 1000);
        // save to fastDFS
        $original_img = $this->fdfs->upload_filebuff($original_buff['buff'], $ext);
        // set info
        $original_img['width'] = $original_buff['width'];
        $original_img['height'] = $original_buff['height'];
        $original_img['buff'] = $original_buff['buff'];

        return $original_img;
    }

} 