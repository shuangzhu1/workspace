<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/14
 * Time: 17:25
 */

namespace Services\pcntl;


class taskManager
{
    protected $children_count = 0;
    protected $pool;

    function __construct()
    {
        $this->pool = array();
    }

    function add_task($task)
    {
        $this->pool[] = $task;
    }

    /*  function sig_handler($sig)
      {
          echo $sig;
          switch ($sig) {
              case SIGCHLD:
                  echo 'SIGCHLD', PHP_EOL;
                  $this->children_count--;
                  echo ' $this->children_count:' . $this->children_count;
                  break;
          }
      }*/

    function run()
    {
        // pcntl_signal(SIGCHLD, array($this, "sig_handler"));
        foreach ($this->pool as $task) {
            $task->fork();
        }

        # print_r($this);
        # sleep(60);

        while (1) {
          //  echo "waiting\n";
            $pid = pcntl_wait($extra);
            if ($pid == -1)
                break;

          //  echo ": task done : $pid\n";
            $this->children_count--;
            $this->finish_task($pid);
        }
       // echo "processes done ; exiting\n";
        exit(0);
    }

    function finish_task($pid)
    {
        if ($task = $this->pid_to_task($pid))
            $task->finish();
    }

    function pid_to_task($pid)
    {
        foreach ($this->pool as $task) {
            if ($task->pid() == $pid)
                return $task;
        }
        return false;
    }
}