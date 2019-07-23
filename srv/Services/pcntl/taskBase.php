<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/14
 * Time: 17:27
 */

namespace Services\pcntl;


class taskBase
{
    protected $pid;
    protected $ppid;


    function __construct()
    {
    }

    function fork()
    {
        $pid = pcntl_fork();
        if ($pid == -1)
            throw new \Exception('fork error on Task object');
        elseif ($pid) {
            # we are in parent class
            $this->pid = $pid;
            # echo "< in parent with pid {$his->pid}\n";
        } else {
            # we are is child
            $this->run();
        }
    }

    function run()
    {
        # echo "> in child {$this->pid}\n";
        # sleep(rand(1,3));
        $this->ppid = posix_getppid();
        $this->pid = posix_getpid();
    }

    # call when a task in finished (in parent)
    function finish()
    {
        //  echo "task finished {$this->pid}\n";
    }

    function pid()
    {
        return $this->pid;
    }

}