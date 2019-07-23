<?php
/**
 * Created by PhpStorm.
 * User: wgwang
 * Date: 14-4-16
 * Time: 上午10:17
 */

namespace Multiple\Panel\Controllers;

use Models\Product\ProductComment;


class CommentController extends ControllerBase
{

    public function indexAction()
    {


        $this->assets->addCss('static/panel/css/account.steps.css');
        $this->assets->addCss('static/ace/css/datepicker.css');


        $is_show = $this->request->get('is_show', 'int', -1); //是否显示
        $name = trim($this->request->get('name', 'striptags', -1)); //产品名称
        $startdate = trim($this->request->get('startdate', 'striptags', '')); //起始时间
        $enddate = trim($this->request->get('enddate', 'striptags', '')); //结束时间
        //echo $startdate;exit;
        // $startdate= trim($startdate)=='' ? '' : strtotime(trim($startdate));
        // $enddate= trim($enddate)=='' ? '' : strtotime(trim($enddate));


        $currentPage = $this->request->get('page', 'int');
        if (empty($currentPage)) {
            $currentPage = 1;
        }

        $queryBulider = $this->modelsManager->createBuilder()
            ->addFrom('\\Models\\Product\\ProductComment', 'pc')
            ->leftJoin('\\Models\\Product\\Product', 'pc.product_id=p.id', 'p')
            ->leftJoin('Models\\User\\Users', 'pc.user_id=u.id', 'u')
            ->andWhere("p.customer_id=" . CUR_APP_ID . " AND pc.parent_id=0")
            ->columns("p.id as pid,p.name,p.customer_id,pc.id as cid,pc.content,pc.is_show,pc.created,u.id as uid,u.username")
            ->orderBy("pc.created DESC");

        if ($startdate != '') {
            //echo $startdate;exit;

            $this->view->setVar("startdate", $startdate);
            $startdate = strtotime($startdate);

            $queryBulider->andWhere("pc.created > '{$startdate}'");
        }
        if ($enddate != '') {

            $this->view->setVar("enddate", $enddate);
            $enddate = strtotime($enddate);
            $queryBulider->andWhere("pc.created < '{$enddate}'");
        }
        if ($is_show != -1) {
            $this->view->setVar("is_show", $is_show);
            $queryBulider->andWhere("pc.is_show='{$is_show}'");
        }
        if ($name != -1 && $name != '') {
            $this->view->setVar("name", $name);
            $queryBulider->andWhere("p.name LIKE '%{$name}%'");
        }

        $pagination = new \Phalcon\Paginator\Adapter\QueryBuilder(
            array("builder" => $queryBulider,
                "limit" => 20,
                "page" => $currentPage)
        );

        $res = $pagination->getPaginate();
        $this->view->setVar('list', $res);


    }

    public function lookAction()
    {
        $id = $this->dispatcher->getParam(0);
        $comment = $this->modelsManager->createBuilder()
            ->addFrom('\\Models\\Product\\ProductComment', 'pc')
            ->leftJoin('\\Models\\User\\Users', 'pc.user_id=u.id', 'u')
            ->leftJoin('\\Models\\Product\\Product', 'pc.product_id=p.id', 'p')
            ->andWhere("p.customer_id='{$this->customer->id}' AND pc.id='{$id}'")
            ->columns('pc.id as pcid,pc.product_id as pid,pc.content,pc.created,pc.is_show,u.username as name,p.name as pname')
            ->getQuery()
            ->execute()[0];

        $replay = $this->modelsManager->createBuilder()
            ->addFrom('\\Models\\Product\\ProductComment', 'pc')
            ->leftJoin('\\Models\\Customers', 'pc.user_id=u.id', 'u')
            ->leftJoin('\\Models\\Product\\Product', 'pc.product_id=p.id', 'p')
            ->andWhere("p.customer_id='{$this->customer->id}' AND pc.parent_id=" . $id)
            ->columns('pc.id as pid,pc.content,pc.created,pc.is_show,u.name,p.name as pname')
            ->orderBy('pc.created DESC')
            ->getQuery()
            ->execute();

        // $list= ProductComment::find("id='$id' OR parent_id='{$id}'")->toArray();
        $this->view->setVar('comment', $comment);
        $this->view->setVar('replay', $replay ? $replay->toArray() : []);

    }

    public function replayAction()
    {
        $id = $this->request->getPost('id');
        $content = $this->request->getPost('content');
        $pid = $this->request->getPost('pid');
        if (!($id && is_numeric($id))) {

            return $this->err('404', 'comment could not found');
        }
        $comment = ProductComment::findFirst('id = ' . $id);
        if (!$comment) {
            return $this->err('404', 'comment could not found');
        }
        $productComment = new ProductComment();
        $productComment->parent_id = $id;
        $productComment->product_id = $pid;
        $productComment->user_id = $this->customer->id;
        $productComment->content = $content;
        $productComment->created = time();
        if ($productComment->save() == false) {
            die(json_encode(array('s' => 0)));
        } else {
            die(json_encode(array('s' => 1)));
        }
    }

    public function showAction()
    {
        $id = $this->request->getPost('id');

        $show = intval($this->request->getPost('show'));

        $show = $show == 1 ? 0 : 1;

        $comment = ProductComment::findFirst('id = ' . $id);

        if (!$comment) {
            return $this->err('404', 'comment could not found');
        }

        $comment->is_show = $show;
        if ($comment->update() == false) {
            die(json_encode(array('s' => 0)));
        } else {
            die(json_encode(array('s' => 1)));
        }

    }
}