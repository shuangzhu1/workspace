<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/23
 * Time: 15:43
 */

namespace Multiple\Panel\Controllers;


use Models\Site\SiteMaterial;
use Util\Pagination;

class NoticeController extends ControllerBase
{
    /**
     * 添加/编辑公告
     */
    public function addAction()
    {
        $notice_id = $this->request->get('notice_id','int',0);
        $type = $this->request->get('type','int',0);
        if( $notice_id )//编辑公告
        {
            $notice = SiteMaterial::findOne(['id = ' . $notice_id]);
            $this->view->setVar('item',$notice);
        }else//添加公告
        {
            $this->view->setVar('item',[ 'type' => $type ]);
        }
    }

    public function squareAction()#红包广场公告#
    {
        $p = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $count = SiteMaterial::dataCount(['type = 2 and enable = 1']);
        $list = SiteMaterial::findList(['type = 2 and enable = 1', 'columns' => 'id,title,link,thumb,created,updated', 'limit' => $limit, 'offset' => ($p - 1) * $limit]);
        Pagination::instance($this->view)->showPage($p, $count, $limit);
        $this->view->setVar('list', $list);
    }

    public function cashEarningAction()#现金收益公告#
    {
        $p = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $count = SiteMaterial::dataCount(['type = 3 ']);
        $list = SiteMaterial::findList(['type = 3 ', 'columns' => 'id,title,link,thumb,created,updated,enable', 'limit' => $limit, 'offset' => ($p - 1) * $limit]);
        Pagination::instance($this->view)->showPage($p, $count, $limit);
        $this->view->setVar('list', $list);
    }

    public function giftEarningAction()#礼物收益公告#
    {
        $p = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $count = SiteMaterial::dataCount(['type = 4 ']);
        $list = SiteMaterial::findList(['type = 4 ', 'columns' => 'id,title,link,thumb,created,updated,enable', 'limit' => $limit, 'offset' => ($p - 1) * $limit]);
        Pagination::instance($this->view)->showPage($p, $count, $limit);
        $this->view->setVar('list', $list);
    }

}