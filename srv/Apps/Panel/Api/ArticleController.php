<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/4
 * Time: 15:04
 */

namespace Multiple\Panel\Api;


use Models\Site\SiteArticle;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Services\Admin\AdminLog;
use Util\Ajax;
use Util\EasyEncrypt;

class ArticleController extends ApiBase
{
    //添加、更新文档
    public function saveAction()
    {
        $id = $this->request->getPost("id", 'int', 0);
        $title = $this->request->getPost("title", 'string', '');
        $content = $this->request->getPost("content");
        if (!$title) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "标题不能为空");
        }
        if (!$content) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "文章内容不能为空");
        }
        if (!$id) {
            $article = new SiteArticle();
            $data = ["title" => $title, 'content' => $content, 'param' => EasyEncrypt::encode(time()), 'created' => time()];
            $res = $article->insertOne($data);
        } else {
            $article = SiteArticle::findOne("id=" . $id);
            if (!$article) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $data = ["title" => $title, 'content' => $content, 'modify' => time()];
            $res = SiteArticle::updateOne($data, ['id' => $id]);
        }
        if ($res) {
            AdminLog::init()->add($id ? '更新文章' : '添加文章', AdminLog::TYPE_ARTICLE, $id, array('type' => "update", 'id' => $id, 'data' => $data));
            $this->ajax->outRight($article->id);
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "保存失败");
    }

    //可用不可用
    public function enableAction()
    {
        $enable = $this->request->getPost("enable", 'int', 1);
        $id = $this->request->getPost("id", 'int', 0);
        if (!$id || ($enable != 0 && $enable != 1)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $article = SiteArticle::findOne("id=" . $id);
        if (!$article) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $data = ['modify' => time(), 'enable' => $enable];
        if (SiteArticle::updateOne($data, ['id' => $id])) {
            AdminLog::init()->add('更新文章', AdminLog::TYPE_ARTICLE, $id, array('type' => "update", 'id' => $id, 'data' => $data));

            $this->ajax->outRight("");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "保存失败");

    }

    public function testAction()
    {
        echo 888;
        exit;
    }

    //保存官网动态
    public function saveDynamicAction()
    {
        $id = $this->request->getPost('id','int',0);
        $title = $this->request->getPost('title','string','恐龙谷新动态');
        $html_kw = $this->request->getPost('html_kw');
        $html_desc = $this->request->getPost('html_desc');
        $content = $this->request->getPost('content');
        $content = preg_replace("/'/",'"',$content);
        $brief = $this->request->getPost('brief');
        $brief = preg_replace("/'/",'"',$brief);
        $time = time();
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);
        if( $id )//编辑
            $sql = "update dynamics set title = '$title',html_kw='$html_kw',html_desc='$html_desc',biref='$brief' ,content = '$content'  where id = $id";//(null,'$ques','$content',$type,$time)
        else
            $sql = "insert into dynamics values(null,'$title','$html_kw','$html_desc','$brief','$content',$time)";

        $res = $connection->execute($sql);
        if( $res )
            $this->ajax->outRight($res);
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'操作失败');
    }
    //获取动态详情
    public function getDynamicInfoAction()
    {
        $id = $this->request->get('id');
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);

        $res = $connection->query("select * from dynamics where id = " . $id)->fetch(\PDO::FETCH_ASSOC);
        if( $res )
            $this->ajax->outRight($res);
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'获取信息失败');
    }
    //删除动态
    public function delDynamicAction(){
        $id = $this->request->get('id');
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);
        $res = $connection->execute('delete from dynamics where id = ' . $id);
        if( $res )
            $this->ajax->outRight();
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG);
    }

    //保存官网问题
    public function saveGuidesAction()
    {
        $id = $this->request->getPost('id','int',0);
        $ques = $this->request->getPost('ques','string','如何获得龙钻');
        $type = $this->request->getPost('type','int',1);
        $content = $this->request->getPost('content');
        $time = time();
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);
        if( $id )//编辑
            $sql = "update guides set question = '$ques',answer = '$content',type='$type'  where id = $id";//(null,'$ques','$content',$type,$time)
        else
            $sql = "insert into guides values(null,'$ques','$content',$type,$time)";

        $res = $connection->execute($sql);
        if( $res )
            $this->ajax->outRight($res);
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'操作失败');

    }

    //删除问题
    public function delGuideAction(){
        $id = $this->request->get('id');
        $connection =  new Sqlite([
            'dbname' => ROOT . '/Data/db/home/home.sqlite'
        ]);
        $res = $connection->execute('delete from guides where id = ' . $id);
        if( $res )
            $this->ajax->outRight();
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG);
    }
}