<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/12
 * Time: 18:33
 */

namespace Multiple\Developer\Api;


use Components\PhpReader\FileReader;
use Models\System\SystemApiCallLog;
use Services\Admin\DeveloperLog as AdminLog;
use Util\Ajax;

class LogController extends ApiBase
{
    public function removeAction()
    {
        $data = $this->request->getPost('data');
        if ($this->db2->execute("delete from system_api_call_log where id in (" . implode(',', $data) . ')')) {
            //记录日志
            AdminLog::init()->add('删除日志', AdminLog::TYPE_API_LOG, json_encode($data), array('type' => "update", 'id' => json_encode($data)));
            Ajax::outRight("删除成功");
        }
        Ajax::outError("删除失败");

    }

    public function getLogFileAction()
    {
        $path = $this->request->get('path', 'string');

        if (!$path) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, '路径未找到');
        }
        $list = AdminLog::init()->getFolder($path);
        $files = $list['files'];
        $files && array_multisort(array_column($files, 'name'), SORT_DESC, $files);

        $folders = $list['folders'];
        $folders && array_multisort(array_column($folders, 'path'), SORT_DESC, $folders);

        $data = '';
        if ($folders) {
            foreach ($folders as $item) {
                $data .= $this->getFromOB('log/partial/folder', ['item' => $item]);
            }
        }
        if ($files) {
            foreach ($files as $item) {
                $data .= $this->getFromOB('log/partial/file', ['item' => $item]);
            }
        }

        Ajax::outRight($data);
    }


    public function openFileAction()
    {
        $path = $this->request->get('file', 'string');
        $start_line = $this->request->get('start_line', 'int', 1);//从多少行开始
        $end_line = $this->request->get('end_line', 'int', 0);//截止到多少行
        $end_row = $this->request->get('end_row', 'int', 0);//读取文件末尾的行数
        $limit = 200;

        if (!$path) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, '路径未找到');
        }
        if ($end_row > 0) {
            $total_count = FileREader::getFileLineCount(ROOT . '/' . $path);
            /*起始行*/
            if ($start_line >= $total_count) {
                Ajax::outRight(array('data_list' => [], 'line_count' => $total_count, "count" => 0));
            }
            if ($total_count - $end_row >= 0) {
                $start_line = $total_count - $end_row + 1;
            } else {
                $start_line = 1;
            }
            $end_line = 0;
        }
        $end_line = $end_line ? $end_line : $start_line + $limit - 1;

        $content = FileReader::getFileByLines(ROOT . '/' . $path, $start_line, $end_line);
        $data = [];
        $count = 0;
        if ($content) {
            foreach ($content as $k => $item) {
                $item = urldecode(str_replace(' ', '&nbsp;', $item));
                $data[] = '<li><span class="line-left"><b class="line_count">' . ($start_line + $k) . '</b></span><span class="content">' . nl2br($item) . '</span></li>';
                $count++;
            }
        }
        Ajax::outRight(array('data_list' => $data, 'line_count' => FileREader::getFileLineCount(ROOT . '/' . $path), "count" => $count));
        //  $data = file_get_contents($path);

        //   Ajax::outRight(nl2br($data));
    }

    public function getFolderAction()
    {
        $tab = $this->request->get("tab", 'string', '');
        if (!$tab) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $folders = AdminLog::init()->getFolder("Cache" . '/' . $tab)['folders'];
        $data = '';
        if ($folders) {
            array_multisort(array_column($folders, 'path'), SORT_DESC, $folders);
            foreach ($folders as $k => $item) {
                $data .= $this->getFromOB('log/partial/folder', ['item' => $item]);
            }
        }
        Ajax::outRight($data);
    }

    //nginx日志
    public function nginxAction()
    {
        $key = $this->request->get("key", 'string', '');
        $start_line = $this->request->get("start_line", 'int', 1);
        $end_line = $this->request->get("end_line", 'int', 0);
        $end_row = $this->request->get("end_row", 'int', 0);
        $limit = $this->request->get("limit", 'int', 200);
        $exec_query = "";
        $key_str = "";
        $file = "/opt/logs/api.klgwl.com.log ";
        //带关键字搜索
        if ($key) {
            $keys = explode('|', $key);
            foreach ($keys as $k => $v) {
                $exec_query .= 'grep "' . str_replace('[', '\\[', $v) . '" ' . ($k == 0 ? $file : '') . '|';
                $key_str .= $exec_query;
            }
        }
        if ($end_row) {
            $exec_query .= "tail -$end_row " . ($key ? "" : $file);
        } else if ($start_line) {
            if ($end_line) {
                $exec_query .= "sed -n '" . "$start_line,$end_line" . "p' " . (!$key ? $file : '');
            } else {
                $exec_query .= "sed -n '" . "$start_line,+" . ($limit - 1) . "p' " . (!$key ? $file : '');
            }
        } else {
            $exec_query .= $key ? "sed -n '1," . ($limit - 1) . "p'" : "tail -$limit $file";
        }
        exec($exec_query, $output);
        $data = [];
        if ($output) {
            $count = 0;
            foreach ($output as $k => $item) {
                $item = urldecode(str_replace(' ', '&nbsp;', $item));
                $data[] = '<li><span class="line-left"><b class="line_count">' . ($start_line + $k) . '</b></span><span class="content">' . nl2br($item) . '</span></li>';
                $count++;
            }
        }
        !$key ? exec("wc -l $file", $count) : exec($key_str . "wc -l", $count);
        Ajax::outRight(['data_list' => $data, 'data_count' => explode(' ', $count[0])[0]]);
        exit;
    }

}