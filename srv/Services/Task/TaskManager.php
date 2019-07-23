<?php

namespace Services\Task;

use Components\Passport\Identify;
use Services\Site\CurlManager;
use Util\Debug;

/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/6
 * Time: 10:28
 */
class TaskManager
{
    private static $baseUrl = 'http://127.0.0.1:4343/';
    public static $instance = null;
    public static $task_type = [
        'cron' => "cron",
        'date' => '一次性',
        'interval' => '周期性'
    ];
    public static $key_name = [
        'run_date' => '指定时间',
        "second" => '秒',
        "seconds" => '秒',
        "minute" => '分',
        "minutes" => '分',
        "hour" => '小时',
        "hours" => '小时',
        "day" => '天',
        "days" => '天',
        "week" => '星期',
        "weeks" => '星期',
        "month" => '月',
        "months" => '月',
        "year" => '年',
        "years" => '年',
    ];
    public static $trigger_args = [
        'cron' => ['year', 'month', 'day', 'week', 'day_of_week', 'hour', 'minute', 'second', 'start_date', 'end_date'],
        'interval' => ['weeks', 'days', 'hours', 'minutes', 'seconds', 'start_date', 'end_date'],
        'date' => ['run_date']
    ];


    public static function init($baseUrl = '')
    {
        if (self::$instance == null) {
            self::$instance = new self($baseUrl);
        }
        return self::$instance;
    }

    public function __construct($baseUrl = '')
    {
        if ($baseUrl) {
            self::$baseUrl = $baseUrl;
        }
    }

    public static function send_request($url, $data)
    {
        Debug::log("task_url:" . $url, 'task');
        $data['timestamp'] = time();
        //除去待签名参数数组中的空值和签名参数
        $para_filter = Identify::init()->paraFilter($data);


        //对待签名参数数组排序
        $para_sort = Identify::init()->argSort($para_filter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = Identify::init()->createLinkstring($para_sort);
        $prestr = $prestr . "&klg_mini_task@2017";
        $mysign = Identify::init()->md5Sign($prestr, '');
        $data['sign'] = $mysign;
        $res = CurlManager::init()->CURL_POST($url, $data);
        return json_decode($res['data'], true);
    }

    //获取任务列表
    /**获取任务列表
     * @return array
     */
    public function get_jobs()
    {
        $res = ['data_count' => 0, 'data_list' => []];
        $r = $this->send_request(self::$baseUrl . "gets", []);
        if ($r && $r['result'] == 1) {
            $res['data_count'] = $r['data']['data_count'];
            $res['data_list'] = $r['data']['data_list'];
        }
        return $res;
    }

    /**添加活动
     * @param $trigger
     * @param $trigger_args
     * @param string $id
     * @param string $name
     * @param string $callback
     * @param $callback_data
     * @param string $cmd
     * @return array
     */
    public function add_job($trigger, $trigger_args, $id = '', $name = '', $callback = '', $callback_data, $cmd = '')
    {
        $data = [
            'trigger' => $trigger,
            'trigger_args' => json_encode($trigger_args)
        ];
        if ($id) {
            $data['task_id'] = $id;
        }
        if ($name) {
            $data['task_name'] = $name;
        }
        if ($callback) {
            $data['url'] = $callback;
        }
        if ($callback_data) {
            $data['params'] = json_encode($callback_data);
        }
        if ($cmd) {
            $data['cmd'] = $cmd;
        }

        $r = $this->send_request(self::$baseUrl . "add", $data);
        if ($r && $r['result'] == 1) {
            return ['data' => $r['data'], 'result' => 1];
        } else {
            return $r;
        }
    }

    /**编辑任务
     * @param $id
     * @param $trigger
     * @param $trigger_args
     * @param string $name
     * @param string $callback
     * @param $callback_data
     * @param string $cmd
     * @return array|mixed
     */
    public function edit_job($id, $trigger, $trigger_args, $name = '', $callback = '', $callback_data, $cmd = '')
    {
        $data = [
            'task_id' => $id,
            'trigger' => $trigger,
            'trigger_args' => json_encode($trigger_args)
        ];
        if ($name) {
            $data['task_name'] = $name;
        }
        if ($callback) {
            $data['url'] = $callback;
        }
        if ($callback_data) {
            $data['params'] = json_encode($callback_data);
        }
        if ($cmd) {
            $data['cmd'] = $cmd;
        }
        $r = $this->send_request(self::$baseUrl . "modify", $data);
        if ($r && $r['result'] == 1) {
            return ['data' => $r['data'], 'result' => 1];
        } else {
            return $r;
        }
    }

    /**删除任务
     * @param $task_id
     * @return array|mixed
     */
    public function remove_job($task_id)
    {
        $r = $this->send_request(self::$baseUrl . "remove", ['task_id' => $task_id]);
        if ($r && $r['result'] == 1) {
            return ['data' => $r['data'], 'result' => 1];
        } else {
            return $r;
        }
    }

    /**暂停任务
     * @param $task_id
     * @return array|mixed
     */
    public function pause_job($task_id)
    {
        $r = $this->send_request(self::$baseUrl . "pause", ['task_id' => $task_id]);
        if ($r && $r['result'] == 1) {
            return ['data' => $r['data'], 'result' => 1];
        } else {
            return $r;
        }
    }

    /**恢复任务
     * @param $task_id
     * @return array|mixed
     */
    public function resume_job($task_id)
    {
        $r = $this->send_request(self::$baseUrl . "resume", ['task_id' => $task_id]);
        if ($r && $r['result'] == 1) {
            return ['data' => $r['data'], 'result' => 1];
        } else {
            return $r;
        }
    }


}