<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 10:26
 */

namespace Multiple\Wap\Controllers;
use Models\Site\SiteMaterial;
use Phalcon\Mvc\View;

/**
 * Class NoticeController
 * @package Multiple\Wap\Controllers
 * 红包广场公告
 */
class NoticeController extends ControllerBase
{
    public function detailAction()
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
    //公告列表
    public function listAction()
    {
        $list = SiteMaterial::findList(['type = 2 and enable = 1','columns' => 'id,title,thumb,link,content,created','order' => 'created desc']);
        $data = [];
        if( $list )
        {
            foreach( $list as $item )
            {
                $dom = new \DOMDocument();
                $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>' . $item['content']);//防止中文乱码
                $parts = $dom->getElementsByTagName('p');
                //$parts = \DOMDocument::loadHtml($item['content'])->getElementsByTagName('p');
                $content = '';
                foreach($parts as $part)
                {
                    $content .= $part->textContent;
                }
                $item['content'] = mb_substr($content,0,30);

                $data[date('Y-m-d',$item['created'])][] = $item;
            }

        }else{
            $data = [];
        }
        $this->view->title = '广场公告';
        $this->view->setVar('list',$data);
    }
}