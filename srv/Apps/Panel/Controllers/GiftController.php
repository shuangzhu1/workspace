<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/26
 * Time: 10:36
 */

namespace Multiple\Panel\Controllers;


use Models\Site\SiteGift;
use Util\Pagination;

class GiftController extends ControllerBase
{
    public function listAction()#礼物列表#
    {
        /*    $page = $this->request->get('p', 'int', 1);
            $limit = $this->request->get('limit', 'int', 20);
            $key = $this->request->get('key', 'string', '');//关键字
            $status = $this->request->get('status', 'int', '-1');//状态
            $vip = $this->request->get('vip', 'int', '-1');//是否vip

            $params[] = [];
            $params['order'] = 'sort_num asc,created desc';
            $params['offset'] = ($page - 1) * $limit;
            $params['limit'] = $limit;
            if ($key) {
                $params[0][] = 'name like "%' . $key . '%"';
            }
            if ($status != -1) {
                $params[0][] = ' enable = ' . $status;
            }
            if ($vip != -1) {
                $params[0][] = ' is_vip = ' . $vip;
            }
            $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
            $count = SiteGift::dataCount($params[0]);
            $list = SiteGift::findList($params);
            $this->view->setVar('list', $list);
            $this->view->setVar('key', $key);
            $this->view->setVar('status', $status);
            $this->view->setVar('vip', $vip);

            Pagination::instance($this->view)->showPage($page, $count, $limit);*/
    }

    public function recordAction()#礼物记录#
    {
        $owner_id = $this->request->get("owner_id", 'string', '');
        $user_id = $this->request->get("user_id", 'string', '');

        $gift = SiteGift::getColumn(["", "columns" => 'id,name'], 'name', 'id');
        $this->view->setVar('owner_id', $owner_id);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('gift', $gift);
    }
}