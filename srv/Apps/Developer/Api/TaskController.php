<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/5
 * Time: 11:13
 */

namespace Multiple\Developer\Api;


use Services\Site\CurlManager;
use Services\Task\TaskManager;
use Util\Ajax;

class TaskController extends ApiBase
{

    //获取任务列表
    public function getListAction()
    {
        $port = $this->request->get("port", 'string', '');
        $res = TaskManager::init($port)->get_jobs();
        $data = [];
        if ($res['data_list']) {
            foreach ($res['data_list'] as $i) {
                $data[] = [$this->getFromOB('task/partial/item', array('item' => $i))];
            }
        }
        Ajax::outRight(['list' => $data, 'count' => $res['data_count']]);
    }

    //添加任务
    public function addAction()
    {
        $port = $this->request->get("port", 'string', '');

        $task_id = $this->request->get("task_id");//任务id
        $task_name = $this->request->get("task_name");//任务名称
        $trigger = $this->request->get("trigger");//任务类型
        $trigger_args = $this->request->get("trigger_args");//时间参数
        $callback = $this->request->get("callback_url");//回调地址
        $callback_data = $this->request->get("task_callback");//回调参数
        $cmd = $this->request->get("cmd");//执行命令

        if (!$trigger || !key_exists($trigger, TaskManager::$task_type)) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "非法的任务类型");
        }
        if ($task_name == '') {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "请填写任务名称");
        }
        if (!$trigger_args) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "无效的时间参数");
        }
        foreach ($trigger_args as $k => &$ta) {
            if (!in_array($k, TaskManager::$trigger_args[$trigger])) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "无效的时间参数:" . $k);
            }
            if (in_array($k, ['weeks', 'days', 'hours', 'minutes', 'seconds'])) {
                $ta = intval($ta);
            }
        }
        $res = TaskManager::init($port)->add_job($trigger, $trigger_args, $task_id, $task_name, $callback, $callback_data, $cmd);
        if ($res['result'] == 1) {
            Ajax::outRight("添加成功");
        } else {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $res['error']['msg']);
        }
    }

    //编辑任务
    public function editAction()
    {
        $port = $this->request->get("port", 'string', '');

        $task_id = $this->request->get("task_id");//任务id
        $trigger = $this->request->get("trigger");//任务类型
        $trigger_args = $this->request->get("trigger_args");//时间参数
        $task_name = $this->request->get("task_name");//任务名称
        $callback = $this->request->get("callback_url");//回调地址
        $callback_data = $this->request->get("task_callback");//回调参数
        $cmd = $this->request->get("cmd");//执行命令

        if (!$task_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        if (!$trigger || !key_exists($trigger, TaskManager::$task_type)) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "非法的任务类型");
        }
        if ($task_name == '') {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "请填写任务名称");
        }
        if (!$trigger_args) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "无效的时间参数");
        }
        foreach ($trigger_args as $k => &$ta) {
            if (!in_array($k, TaskManager::$trigger_args[$trigger])) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "无效的时间参数:" . $k);
            }
            if (in_array($k, ['weeks', 'days', 'hours', 'minutes', 'seconds'])) {
                $ta = intval($ta);
            }
        }
        $res = TaskManager::init($port)->edit_job($task_id, $trigger, $trigger_args, $task_name, $callback, $callback_data, $cmd);
        if ($res['result'] == 1) {
            Ajax::outRight("编辑成功");
        } else {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $res['error']['msg']);
        }
    }

    //删除任务
    public function removeAction()
    {
        $port = $this->request->get("port", 'string', '');
        $task_id = $this->request->get("id");//任务id
        if (!$task_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $res = TaskManager::init($port)->remove_job($task_id);
        if ($res['result'] == 1) {
            Ajax::outRight("删除成功");
        } else {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $res['error']['msg']);
        }
    }

    //暂停任务
    public function pauseAction()
    {
        $port = $this->request->get("port", 'string', '');
        $task_id = $this->request->get("id");//任务id
        if (!$task_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $res = TaskManager::init($port)->pause_job($task_id);
        if ($res['result'] == 1) {
            Ajax::outRight("删除成功");
        } else {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $res['error']['msg']);
        }
    }

    //恢复任务
    public function startAction()
    {
        $port = $this->request->get("port", 'string', '');
        $task_id = $this->request->get("id");//任务id
        if (!$task_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $res = TaskManager::init($port)->resume_job($task_id);
        if ($res['result'] == 1) {
            Ajax::outRight("删除成功");
        } else {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, $res['error']['msg']);
        }
    }
}