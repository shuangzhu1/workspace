<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/14
 * Time: 17:28
 */

namespace Services\pcntl;


class taskMain extends taskBase
{

    public $callback;

    function __construct($callback)
    {
        $this->callback = $callback;
    }

    function run()
    {
        parent::run();
        // echo "> in child {$this->pid}\n";
        // echo $this->i;
        /* echo dirname(__FILE__) . "/file.log";
        var_dump(file_put_contents(dirname(__FILE__) . "/file.log", $this->pid . ",", FILE_APPEND));
        print_r($GLOBALS['redis']->set("pcntl_test:" . ($this->i), $this->pid));*/
        # print_r($this);
        $callback = $this->callback;
        $callback();
        // sleep(rand(1,5));
        //  echo "> child done {$this->pid}\n";
        exit(0);
    }
}