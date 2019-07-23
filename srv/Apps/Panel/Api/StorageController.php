<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 5/7/14
 * Time: 5:52 PM
 */

namespace Multiple\Panel\Api;


use Models\Customer\CustomerStorage;
use Models\Customer\CustomerStorageCount;
use Models\Site\SiteStorage;
use Phalcon\Mvc\Model\Query\Builder;
use Util\Ajax;
use Util\Config;

class StorageController extends ApiBase
{
    protected $_check_login = false;

    public function getAction()
    {
    }

    public function getFolderAction()
    {
        $res = SiteStorage::findList(['', 'columns' => 'type,folder,count(folder) as count,sum(size) as total', 'group' => 'type,folder','order'=>'created desc']);
//        $res = AdminStorageModel::find('', 'created desc', array('type', 'folder', '``', ''), 'type,folder');
        $folder = [];
        $count = [];
        $folder['img'] = [];
        foreach ($res as $v) {
            if( $v['folder']  == 1 )//图文素材文件夹
            {
                !is_array($folder[$v['type']]) && $folder[$v['type']];//处理当$folder[$v['type']]未赋值时，array_unshift异常
                array_unshift($folder[$v['type']],array('folder' => '图文素材', 'count' => $v['count'], 'total' => $v['total']));
            }
            else
                $folder[$v['type']][] = array('folder' => $v['folder'], 'count' => $v['count'], 'total' => $v['total']);
            if (isset($count[$v['type'] . "_num"])) {
                $count[$v['type'] . "_num"] += $v['count'];
            } else {
                $count[$v['type'] . "_num"] = $v['count'];
            }
        }
        $this->ajax->outRight(array('folder' => $folder, 'count' => $count));
    }

    public function getImgAction()
    {
        $this->get('img');
    }

    public function getVideoAction()
    {
        $this->get('video');
    }

    public function getVoiceAction()
    {
        $this->get('audio');
    }

    public function getFileAction()
    {
        $this->get('file');
    }

    private function get($type)
    {
        $q = $this->request->get('t');
        $q == '图文素材' && $q =1;//图文素材文件夹
        $page = $this->request->get('p');
        $curPage = $page <= 0 ? 0 : $page - 1;

        $limit = 12;
        $param = ' type = "' . $type . '"';
        $param .= $q ? ' AND folder = "' . $q . '"' : '';


        $files = SiteStorage::findList([$param, 'order' => 'created desc', 'offset' => $curPage * $limit, 'limit' => $limit]);
        $res = SiteStorage::findList(['type="' . $type . '"', 'columns' => ' type,folder,count(folder) as count,sum(size) as total', 'group' => 'type,folder']);

        $cusCount = [];

        foreach ($res as $v) {
            if (isset($cusCount[$type])) {
                $cusCount[$type] += $v['total'];
            } else {
                $cusCount[$type] = $v['total'];
            }
        }
        // file base url
        $count = SiteStorage::dataCount($param);
        $res['list'] = $files;
        $res['count'] = $count;
        $res['limit'] = $limit;
        $res['used'] = $cusCount ? $cusCount[$type] : 0;

        $this->ajax->outRight($res);
    }

}