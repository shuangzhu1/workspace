<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/11
 * Time: 16:26
 */

namespace Multiple\Panel\Controllers;


use Models\User\UserVideoQuestion;
use Services\Site\SiteKeyValManager;
use Util\Pagination;

class PaidqaController extends ControllerBase
{
    /**
     * 随机问题
     */
    public function hotQuestionAction()#随机问题#
    {
        $list = SiteKeyValManager::init()->getOneByKey(SiteKeyValManager::KEY_HOT_QUESTION);
        $id = $list['id'];
        $list && $list = json_decode($list['val'],true);
        if( is_array($list) )//依次按权重和添加日期排序
        {
            $w = [];
            $c = [];
            foreach ( $list as $item )
            {
                $w[] = $item['weight'];
                $c[] = $item['created'];
            }
            array_multisort($w, SORT_DESC , $c, SORT_DESC , $list);

        }

        $this->view->setVar('list', $list);
        $this->view->setVar('data_id', $id);
    }

    /**
     * 用户提问问题列表
     */
    public function qListAction()#提问列表#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',20);
        $user_type = $this->request->get('user_type');
        $user_id = $this->request->get('user_id');
        $status = $this->request->get('status');
        $where = '';
        !empty($user_id) ? $where = $user_type . '=' . $user_id : '';
        if( !empty( $where) )
            !empty($status) ? $where .= " and status = " . $status : '';
        else
            !empty($status) ? $where = "status = " . $status : '';

        $count = UserVideoQuestion::init()->dataCount($where);
        $qList = UserVideoQuestion::init()->findList([$where,'offset' => ($p -1)*$limit,'limit' => $limit,'order' => 'created desc']);
        $this->view->setVar('qList',$qList);
        $this->view->setVar('user_type',$user_type);
        $this->view->setVar('user_id',$user_id);
        $this->view->setVar('status_back',$status);
        Pagination::instance($this->view)->showPage($p,$count,$limit);
    }

}