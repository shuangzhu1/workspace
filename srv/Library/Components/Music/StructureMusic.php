<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/1
 * Time: 16:46
 */

namespace Components\Music;


use Components\Music\Tools\Music163;
use Components\Music\Tools\MusicBaidu;
use Components\Music\Tools\MusicQq;
use Components\Music\Tools\MusicXiami;

class StructureMusic
{
    const DRIVER_QQ = 'qq';
    const DRIVER_163 = '163';
    const DRIVER_BAIDU = 'baidu';
    const DRIVER_XIAMI = 'xiami';

    public $driver = "";//驱动
    public $instance = null;


    public function __construct($driver)
    {
        $this->driver = $driver;
    }

    public function getInstance()
    {
        if ($this->instance !== null) {
            return $this->instance;
        } else {
            if ($this->driver == self::DRIVER_QQ) {
                $this->instance = new MusicQq();
            } else if ($this->driver == self::DRIVER_BAIDU) {
                $this->instance = new MusicBaidu();
            } else if ($this->driver == self::DRIVER_XIAMI) {
                $this->instance = new MusicXiami();
            } else {
                $this->instance = new Music163();
            }
            return $this->instance;
        }
    }
}