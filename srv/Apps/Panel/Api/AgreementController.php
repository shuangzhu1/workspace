<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/4/27
 * Time: 11:04
 */

namespace Multiple\Panel\Api;


use Services\Admin\AdminLog;
use Services\Site\SiteKeyValManager;
use Util\Ajax;

class AgreementController extends ApiBase
{
    public function saveAction()
    {
        $key = $this->request->getPost("key", 'string', '');
        $content = $this->request->getPost("content");
        $title = $this->request->getPost("title");

        if (!$key || !$content || !$title) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ["pri_key" => SiteKeyValManager::KEY_PAGE_DOCUMENT, "sub_key" => $key, 'name' => '注册协议', 'val' => $content, 'remark' => $title];
        if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_DOCUMENT, $key, $data)) {
            $this->ajax->outRight("保存成功");
        }
        AdminLog::init()->add('更新协议', AdminLog::TYPE_ARTICLE, 0, array('type' => "update", 'id' => $key, 'data' => $data));

        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "保存失败");
    }
}