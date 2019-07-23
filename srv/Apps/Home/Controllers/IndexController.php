<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/28
 * Time: 16:53
 */
namespace Multiple\Home\Controllers;

use Models\Site\SiteAppVersion;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Mvc\Controller;
use Util\Pagination;

class IndexController extends Controller
{
    public function indexAction()
    {
        /*$version = SiteAppVersion::findOne(['os="android" and is_deleted=0', 'order' => 'version desc,id desc,download_url']);
        $this->view->setVar('download_url', $version['download_url']);*/
        //获取最新问题和最新动态
//        $connection =  new Sqlite([
//            'dbname' => ROOT . '/Data/db/home/home.sqlite'
//        ]);

//        $newest_ques = $connection->query("select * from guides where type= 2 order by created desc limit 5")->fetchAll(\PDO::FETCH_ASSOC);
//        $newest_dynamic = $connection->query("select * from dynamics order by created desc limit 5")->fetchAll(\PDO::FETCH_ASSOC);
//        $this->view->setVar("newest_ques",$newest_ques);
//        $this->view->setVar("newest_dynamic",$newest_dynamic);
//        $this->view->pick('index/v3/index');
    }
    public function aboutAction()
    {
        $this->view->pick('index/v3/about');

    }
    public function agreementAction()
    {
        $this->view->pick('index/v3/agreement');

    }

    public function dynamicAction()
    {
        $id = $this->dispatcher->getParam('id');
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);
        if( is_null($id) )//动态列表
        {
            $p = $this->request->get('p','int',1);
            $limit = $this->request->get('limit','int',10);
            $p <= 0 && $p =1;
            $limit <= 0 && $limit =10;

            $count = $connection->query("select COUNT(1) as total from dynamics ")->fetch(\PDO::FETCH_ASSOC)['total'];
            $list = $connection->query("select * from dynamics order by created desc limit " . ($p -1)*$limit .",$limit" )->fetchAll(\PDO::FETCH_ASSOC);
            Pagination::instance($this->view)->showPage($p,$count,$limit);
            $this->view->setVar('isList',true);
        }else//动态详情
        {
            $next = $connection->query("select * from dynamics where id > " . $id . " order by id")->fetch(\PDO::FETCH_ASSOC);
            $prev = $connection->query("select * from dynamics where id < " . $id . " order by id desc")->fetch(\PDO::FETCH_ASSOC);
            $list = $connection->query("select * from dynamics where id = " . $id)->fetchAll(\PDO::FETCH_ASSOC);
            $this->view->setVar('prev',$prev);
            $this->view->setVar('next',$next);
        }
        $this->view->setVar('list',$list);
        $this->view->pick('index/v3/dynamic');

    }
    public function serviceAction()
    {
        $this->view->pick('index/v3/service');
    }
    public function guideAction()
    {
        $this->view->pick('index/v3/guide');
    }

}