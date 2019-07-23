<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/30
 * Time: 16:04
 */

namespace Components\Ffmpeg;


class Audio
{
    static $bin_path = '';//bin文件地址
    static $root_path = '';//基础路径
    private $input = ''; //输入文件
    private $output = '';//输出文件
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
            //指定位置如:  /usr/local/bin/ffmpeg  D://ffmpeg.exe
            self::$bin_path = $bin_path;
        }
    }

    /**
     * 截取音频
     * @param $start_time -起始时间  00:00:20
     * @param int $t -截取多长时间【秒】 30
     * @param string $to -截取到什么时候 00:01:20
     * @return $this
     */
    public function cut($start_time, $t = 30, $to = '')
    {
      //  echo self::$bin_path . " -i " . ($this->input) . " -ss " . $start_time . " -t $t " . " -acodec copy " . ($this->output);exit;
        if ($t) {
            exec(self::$bin_path . " -i " . ($this->input) . " -ss " . $start_time . " -t $t " . " -acodec libmp3lame " . ($this->output));
        } else {
            exec(self::$bin_path . " -i " . ($this->input) . " -ss " . $start_time . " -to $to " . " -acodec libmp3lame " . ($this->output));
        }
        return $this;
    }

    /**删除原音频
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