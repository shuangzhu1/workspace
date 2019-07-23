<?php

namespace Multiple\Panel\Api;


use Models\Statistics\ApiCallTotalCount;
use Models\User\Message;
use Util\Ajax;

class IndexController extends ApiBase
{
    public function indexAction()
    {
        echo 888888;
        exit;
    }

    public function validAction()
    {
        $wx_server = $this->di->getShared('wx_server');
        $wx_server->valid($this->request);
    }

    public function apiCallCountAction()
    {
        $count = ApiCallTotalCount::findOne(['ymd=' . date('Ymd'), 'columns' => 'count']);
        $this->ajax->outRight($count ? $count : 0);
    }
    public function messageCountAction()
    {
        $s_message_count = Message::dataCount("year=" . date('Y') . ' and month=' . date('m') . ' and day=' . date('d'));//总消息量
        $this->ajax->outRight($s_message_count);
    }
}
