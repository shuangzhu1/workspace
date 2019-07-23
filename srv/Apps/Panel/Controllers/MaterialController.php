<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/8
 * Time: 10:36
 */

namespace Multiple\Panel\Controllers;


use Models\Site\SiteMaterial;
use Util\Ajax;
use Util\EasyEncrypt;
use Util\Pagination;

class MaterialController extends ControllerBase
{
    public function listAction()#素材列表#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',20);
        $count = SiteMaterial::dataCount(['type = 1 and enable = 1']);
        $list = SiteMaterial::findList(['type = 1 and enable = 1','columns' => 'id,title,link,thumb,created,updated','limit' => $limit,'offset' => ($p-1)*$limit]);
        Pagination::instance($this->view)->showPage($p,$count,$limit);
        $this->view->setVar('list',$list);
    }

    /**
     * 添加素材
     */
    public function addAction()#添加素材#
    {
        $id = $this->request->get('id','int',0);//编辑时传入的id
        $item = [];
        $id > 0 && $id !== 0 && $item = SiteMaterial::findOne(['id = ' . $id]);
        $this->view->setVar('item',$item);
    }
}