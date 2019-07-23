<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/8/2
 * Time: 11:29
 */


/**
 *
 *    $video = new Video(ROOT, '');
 *    $video->setInput("Data/test/test.mp4");
 *    $video->setOutput("Data/test/test1.mp4");
 *    $video->copy(10, 5)
 *    $video->setThumb("Data/test/test.png");
 *    $video->setWaterMark("Data/test/boy.png");
 *    $video->addWaterMark()
 *
 */
namespace Components\Ffmpeg;


class Video
{
    static $bin_path = '';//bin文件地址
    static $root_path = '';//基础路径
    private $input = ''; //输入文件
    private $output = '';//输出文件
    private $water_mark = '';//水印路径
    private $thumb = '';//缩略图路径
    private static $instance = '';

    public static function init($root_path, $bin_path = '')
    {
        if (!self::$instance) {
            self::$instance = new self($root_path, $bin_path);
        }
        return self::$instance;
    }
    public function __construct($root_path, $bin_path = '')
    {
        self::$root_path = $root_path;
        if (!$bin_path) {
            self::$bin_path = 'ffmpeg';
        } else {
            //指定位置如:  /usr/local/bin/ffmpeg  F:/php/ffmpeg/bin/ffmpeg.exe
            self::$bin_path = $bin_path;
        }
    }

    /**设置输入路径
     * @param $input
     * @return $this
     */
    public function setInput($input)
    {
        $this->input = self::$root_path . "/" . $input;
        $this->makeDir($this->input);
        return $this;
    }

    /**设置输出路径
     * @param $output
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = self::$root_path . "/" . $output;
        $this->makeDir($this->output);
        return $this;
    }

    /**设置水印路径
     * @param $water_mark
     * @return $this
     */
    public function setWaterMark($water_mark)
    {
        $this->water_mark = self::$root_path . "/" . $water_mark;
        $this->makeDir($this->water_mark);
        return $this;
    }

    /**设置缩略图位置
     * @param $thumb
     * @return $this
     */
    public function setThumb($thumb)
    {
        $this->thumb = self::$root_path . "/" . $thumb;
        $this->makeDir($this->thumb);
        return $this;
    }

    /**截取视频
     * @param $start --起始时间
     * @param $length --长度
     * @return string
     */
    public function copy($start = 0, $length = 30)
    {
        echo self::$bin_path . " -i " . ($this->input) . " -vcodec copy -acodec copy -ss $start -t $length " . ($this->output) . " -y";
        return $this->output;
    }

    /**
     * 视频增加水印
     */
    public function addWaterMark()
    {
        exec(self::$bin_path . " -i " . ($this->input) . " -vf 'movie= " . ($this->water_mark) . " [watermark]; [in][watermark] overlay=main_w-overlay_w-10:10 [out]' " . ($this->output));
        return $this->output;
    }

    /**
     * 生成视频缩略图
     */
    public function thumb()
    {
        exec(self::$bin_path . " -i " . ($this->input) . " -vframes 1 " . ($this->thumb));
        return $this->thumb;
    }

    /**
     * 删除视频缩略图
     */
    public function destroyThumb()
    {
        return unlink($this->thumb);
    }

    /**删除原视频
     * @return bool
     */
    public function destroyInput()
    {
        return unlink($this->input);
    }

    /**删除输出文件
     * @return bool
     */
    public function destroyOutput()
    {
        return unlink($this->output);
    }

    /**创建目录
     * @param $dir
     */
    public function makeDir($dir)
    {
        $base = dirname($dir);
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }
    }
}