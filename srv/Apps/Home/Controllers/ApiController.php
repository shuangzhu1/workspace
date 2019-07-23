<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/17
 * Time: 14:22
 */

namespace Multiple\Home\Controllers;


use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Mvc\Controller;
use Util\Ajax;

class apiController extends Controller
{
    public function guideAction()
    {
        $type = $this->request->get('type');
        $cid = $this->request->get('cid','int',1);
        $kw = $this->request->get('kw','string','');
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);

        switch ($type){
            //获取问题列表
            case 'list':
                $res = $connection->query("select id,question from guides where type = $cid order by created desc")->fetchAll(\PDO::FETCH_ASSOC);
                break;
            case 'detail':
                $res = $connection->query("select * from guides where id = $cid")->fetch(\PDO::FETCH_ASSOC);
                break;
            case 'search':
                if( !empty($kw) )
                    $sql = "select * from guides where question like '%" . $kw ."%'";
                else
                    $sql = "select * from guides ";
                $res = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                //关键词高亮
                foreach( $res as &$item )
                {
                    $item['question'] = preg_replace('/' . $kw . '/','<span style="color:#ffa800">' . $kw . '</span>',$item['question']);
                }
                break;
        }
        Ajax::init()->outRight($res);
    }
}