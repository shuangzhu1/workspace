<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/4
 * Time: 16:13
 */

namespace Multiple\Wap\Controllers;


use Models\Site\SiteArticle;
use Models\Site\SiteMaterial;
use Phalcon\Di;
use Phalcon\Mvc\View;
use Services\Site\CacheSetting;
use Util\Debug;

class ArticleController extends ControllerBase
{
    //文章详情
    public function detailAction()
    {
        $id = $this->dispatcher->getParam(0);
        if (!$id) {
            return $this->error404();
        }
        try{
            //访问记录
            $redis = $this->di->get('redis');
            $redis->hIncrBy(CacheSetting::KEY_SITE_ARTICLE_VIEW_LOG . ':count',$id,1);
            $microTime = (int) ( microtime(true) * 1000 );
            $redis->hSet(CacheSetting::KEY_SITE_ARTICLE_VIEW_LOG . ':list',$microTime,json_encode(['article' => $id,'viewer' => $this->uid]));
        }catch (\Exception $e){
            Debug::log("article访问记录：" . var_export($e,true),'article');
        }finally{
            $article = SiteArticle::findOne(["param='" . $id . "'", 'columns' => 'content,title']);
            if (!$article) {
                return $this->error404();
            }
            $this->view->title=$article['title'];
            $this->view->setVar('hide_footer',true);
            $this->view->setVar('content', $article['content']);
        }

    }

    //图文详情
    public function materialAction()
    {

        $this->view->disableLevel([
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true,
        ]);
        $link = $this->dispatcher->getParam(0);
        $item = SiteMaterial::findOne(['link = "' . $link . '"']);
        if( ($item && $item['enable'] == 0) ||  empty($item) )//错误错误
        {
            $this->dispatcher->forward([
                'controller' => 'errors',
                'action' => 'show404'
            ]);

        }else
        {
            $this->view->setVar('title',$item['title']);
            $this->view->setVar('created',$item['created']);
            $this->view->setVar('content',$item['content']);
        }
    }

}