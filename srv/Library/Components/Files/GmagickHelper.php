<?php
/**
 * Created by PhpStorm.
 * User: Arimis
 * Date: 14-7-3
 * Time: 下午5:14
 */

namespace Components\Files;


use Gmagick;
use GmagickDraw;
use GmagickPixel;

/**
 * Gmagick 压缩图片类
 * @Author Uxin <iwangq@gmail.com><jorygong@gmail.com>
 * @modify 2013-05-20 21:05:09
 *
 */
class GmagickHelper
{
    public $Gmagick;

    public function __construct()
    {
        $this->image = new \Gmagick();
        $this->image->setCompressionQuality(80);
    }

    /**
     * 获取原图按比例缩放后截图
     *
     * @param $buff
     * @param int $scale
     * @param $width
     * @param $height
     * @param int $x
     * @param int $y
     * @return array
     */
    public function crop_buff($buff, $scale = 1, $width, $height, $x = 0, $y = 0)
    {
        # 读取文件流
        $this->image->readImageBlob($buff);
        $srcImage = $this->image->getImageGeometry(); //获取源图片宽和高

        $new_width = $srcImage['width'] * $scale;
        $new_height = $srcImage['height'] * $scale;

        $this->image->scaleImage($new_width, $new_height, true);
        $this->image->cropimage($width, $height, $x, $y);
        # 获取文件流
        return $this->imginfo();
    }

    /**
     * 截取图片最大正方形区域
     *
     * @param $buff
     * @return array
     */
    public function cropMaxSquare($buff)
    {
        # 读取文件流
        $this->image->readImageBlob($buff);
        $srcImage = $this->image->getImageGeometry(); //获取源图片宽和高

        $width = $srcImage['width'];
        $height = $srcImage['height'];

        if ($width > $height) {
            $x = ($width - $height) / 2;
            $y = 0;
            $sqare = $height;
        } else {
            $x = 0;
            $y = ($height - $width) / 2;
            $sqare = $width;
        }
        $this->image->cropimage($sqare, $sqare, $x, $y);
        # 获取文件流
        return $this->imginfo();
    }


    /**
     * 缩放原图到系统默认最大
     *
     * @param $buff
     * @param $ext
     * @param $width
     * @param $height
     * @return array
     */
    public function resize_original($buff, $ext, $width, $height)
    {

        $this->image->readImageBlob($buff);

        $w = $this->image->getImageWidth();
        $h = $this->image->getImageHeight();
        // 超出范围
        if ($w > $width || $h > $height) {
            if ($ext == 'gif') {
                $this->scaleGif($width, $height);
            } else {
                $this->image->scaleimage($width, $height, true);
            }
        }

        return $this->imginfo();
    }

    /**
     * 缩放gif图片
     *
     * @param $width
     */
    private function scaleGif($width, $height)
    {
        $res = $this->image->coalesceImages();
        // 缩放每一帧
        do {
            $res->scaleimage($width, $height, true);
        } while ($res->nextImage());
        // 合并
        $this->image = $res->deconstructImages();
    }

    /**
     * 缩放图片
     *
     * @param $buff
     * @param $ext
     * @param $width
     * @param $height
     * @return array
     */
    public function thumb($buff, $ext, $width, $height)
    {
        // for gif
        $this->image->readImageBlob($buff);
        $w = $this->image->getImageWidth();
        $h = $this->image->getImageHeight();

        // can't be Enlarge
        if (!($w > $width || $h > $height)) {
            return $this->imginfo();
        }
        if ($ext == 'gif') {
            $this->scaleGif($width, $height);
        } else {
            // 神奇的事情 这里必须重新new Gmagick才能
            $this->image->scaleImage($width, $height, true);
        }

        return $this->imginfo();
    }

    /**
     * 返回图片信息
     *
     * @return array
     */
    private function imginfo()
    {
        # 获取转换后的信息
        $buff = $this->image->getImageBlob();
        $w = $this->image->getImageWidth();
        $h = $this->image->getImageHeight();
        // 释放资源
        $this->image->destroy();

        return array('buff' => $buff, 'width' => $w, 'height' => $h);
    }

    // 添加水印图片
    public function add_watermark($path, $x = 0, $y = 0)
    {
        if ($this->type == 'gif') {
            $image = $this->image;
            $canvas = new \Gmagick();
            $images = $image->coalesceImages();
            foreach ($image as $frame) {
                $img = new \Gmagick();
                $img->readImageBlob($frame);
                $img->drawImage($draw);

                $canvas->addImage($img);
                $canvas->setImageDelay($img->getImageDelay());
            }
            $image->destroy();
            $this->image = $canvas;
        } else {
            $this->image->drawImage($draw);
        }
    }

    /**
     * 图片水印
     *
     * @param $buff
     * @param $watermark
     * @param int $position
     * @param int $margin
     * @return array
     */
    public function picWaterMark($buff, $watermark, $position = 0, $margin = 10)
    {
        # 读取文件流
        $this->image->readImageBlob($buff);
        $width = $this->image->getimagewidth();
        $height = $this->image->getimageheight();

        $water = new \Gmagick($watermark);
        $w = $water->getImageWidth();
        $h = $water->getImageHeight();


        if ($position == 1) {
            $x = $width - $w - $margin;
            $y = $margin;
        } elseif ($position == 2) {
            $x = $width - $w - $margin;
            $y = $height - $h - $margin;
        } elseif ($position == 3) {
            $x = $margin;
            $y = $height - $h - $margin;
        } elseif ($position == 4) {
            $x = $y = $margin;
        } elseif ($position == 5) {
            $x = ceil(($width - $w) / 2);
            $y = ceil(($height - $h) / 2);
        } else {
            $x = rand(($margin), ($width - $w - $margin));
            $y = rand(($margin), ($height - $h - $margin));
        }

        $this->image->compositeImage($water, Gmagick::COMPOSITE_OVER, $x, $y);
        return $this->imginfo();
    }

    /**
     * 文字水印
     *
     * @param $buff
     * @param $text
     * @param int $fontSize
     * @param string $fontColor
     * @param string $fontPath
     * @param float $angle
     * @param int $position
     * @param int $margin
     * @return array
     */
    public function txtWaterMark($buff, $text, $fontSize = 20, $fontColor = '#ccc', $fontPath = '', $angle = 1.0, $position = 0, $margin = 10)
    {
        # 读取文件流
        $this->image->readImageBlob($buff);
        $width = $this->image->getimagewidth();
        $height = $this->image->getimageheight();

        $text = iconv('GBK', "UTF-8", $text);
        $draw = new \GmagickDraw();
        $fontPath ? $draw->setFont($fontPath) : null; #/usr/share/font/simsun.ttc
        $draw->setFontSize($fontSize); #字体大小
        $draw->setFillColor(new GmagickPixel($fontColor)); //设置字体颜色

        $w = strlen($text) * $fontSize;
        $h = $fontSize * 2;
        if ($position == 1) {
            $x = $width - $w - $margin;
            $y = $margin;
        } elseif ($position == 2) {
            $x = $width - $w - $margin;
            $y = $height - $h - $margin;
        } elseif ($position == 3) {
            $x = $margin;
            $y = $height - $h - $margin;
        } elseif ($position == 4) {
            $x = $y = $margin;
        } elseif ($position == 5) {
            $x = ceil(($width - $w) / 2);
            $y = ceil(($height - $h) / 2);
        } else {
            $x = rand(($margin), ($width - $w - $margin));
            $y = rand(($margin), ($height - $h - $margin));
        }
        $this->image->drawimage($draw);
        $this->image->annotateimage($draw, $x, $y, $angle, $text); // 参数说明 GmagickDraw对象 x轴 y轴 倾斜度 文字水印

        return $this->imginfo();
    }
}