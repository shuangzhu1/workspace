<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/12
 * Time: 18:33
 */

namespace Multiple\Panel\Api;


use Models\System\SystemApiCallLog;
use Services\Admin\AdminLog;
use Util\Ajax;

class LogController extends ApiBase
{
    public function removeAction()
    {
        $data = $this->request->getPost('data');
        if ($this->db2->execute("delete from system_api_call_log where id in (" . implode(',', $data) . ')')) {
            //记录日志
            AdminLog::init()->add('删除日志', AdminLog::TYPE_API_LOG, json_encode($data), array('type' => "update", 'id' => json_encode($data)));
            $this->ajax->outRight("删除成功");
        }
        $this->ajax->outError("删除失败");

    }
}