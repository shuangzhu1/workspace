<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/22
 * Time: 14:23
 */

namespace Multiple\Panel\Api;


use Services\Admin\AdminLog;
use Services\Community\CommunityManager;
use Util\Ajax;

class CommunityController extends ApiBase
{
    //审核通过
    public function checkSuccessAction()
    {
        $id = $this->request->get('data');
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        foreach ($id as $item) {
            if (CommunityManager::getInstance()->check($item, 1, $this->session->get('admin')['id'], '')) {
                AdminLog::init()->add('社区审核通过', AdminLog::TYPE_COMMUNITY, $item, array('type' => "update", 'id' => $item));
            }
        }
        $this->ajax->outRight("");

    }

    //审核失败
    public function checkFailAction()
    {
        $id = $this->request->get('id');
        $reason = $this->request->get('reason');

        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$reason) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (CommunityManager::getInstance()->check($id, 0, $this->session->get('admin')['id'], $reason)) {
            AdminLog::init()->add('社区审核失败', AdminLog::TYPE_AGENT, $id, array('type' => "update", 'id' => $id));
        }
        $this->ajax->outRight("");
    }
}