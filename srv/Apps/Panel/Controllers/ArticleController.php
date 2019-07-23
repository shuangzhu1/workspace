<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/4
 * Time: 14:06
 */

namespace Multiple\Panel\Controllers;


use Models\Site\SiteArticle;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Util\Pagination;

class ArticleController extends ControllerBase
{
    //文档列表
    public function listAction()#文档列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $list = SiteArticle::findList(["", 'order' => 'view_cnt desc', "limit" => $limit, "offset" => ($page - 1) * $limit]);
        $count = SiteArticle::dataCount("");
        $this->view->setVar('limit', $limit);
        $this->view->setVar('list', $list);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //添加文档
    public function addAction()#添加文档#
    {
        $params = $this->router->getParams();
        if ($params) {
            $article = SiteArticle::findOne("id=" . $params[0]);
            if ($article) {
                $this->view->setVar('article', $article);
            }
        }
    }

    //官网动态管理
    public function dynamicAction()#官网动态#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',20);
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);
        $count = $connection->query("select count(1) as sum from dynamics  ")->fetch(\PDO::FETCH_ASSOC)['sum'];
        $sql = "select * from dynamics order by created desc limit " . ($p-1)*$limit . ",$limit";
        $list = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        Pagination::instance($this->view)->showPage($p,$count,$limit);
        $this->view->setVar('list',$list);
    }

    //官网问题管理
    public function guideAction()#官网问题#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',20);
        $cate = [
            1 => '最新问题',
            2 => '热门问题',
            3 => '账号与账号安全',
            4 => '功能介绍',
        ];
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);
        $count = $connection->query("select count(1) as sum from guides ")->fetch(\PDO::FETCH_ASSOC)['sum'];
        $sql = "select * from guides order by created desc limit " . ($p-1)*$limit . ",$limit";
        $list = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        Pagination::instance($this->view)->showPage($p,$count,$limit);
        $this->view->setVar('list',$list);
        $this->view->setVar('cate',$cate);
    }
}