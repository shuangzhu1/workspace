<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/25
 * Time: 16:01
 */

namespace Multiple\Panel\Controllers;


use Models\Music\Music;
use Models\Music\MusicCategory;
use Util\Pagination;

class MusicController extends ControllerBase
{
    //音乐分类
    public function catAction()#音乐分类#
    {
        $list = MusicCategory::findList(['', 'order' => 'sort_num asc,created desc']);
        if ($list) {
            foreach ($list as &$item) {
                $item['music_count'] = Music::dataCount("(LOCATE('" . $item['id'] . ",',concat(cat_id,','))>0)");
            }
        }
        $this->view->setVar('list', $list);
    }

    //音乐列表
    public function listAction()#音乐列表#
    {

        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $key = $this->request->get('key', 'string', '');//关键字
        $cat_id = $this->request->get("cat_id", 'int', 0);
        $type = $this->request->get("type", 'int', 1);
        $where = 'enable=' . $type;
        if ($cat_id) {
            $where .= " and (LOCATE('" . $cat_id . ",',concat(cat_id,','))>0)";
        }
        if ($key) {
            $where .= " and (name like '%" . $key . "%' or singer like  '%" . $key . "%' or album like '%" . $key . "%')";
        }

        $list = Music::findList([$where,'order'=>'is_hot desc,sort_num asc,created desc', 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
        if ($list) {
            if ($key) {
                foreach ($list as &$item) {
                    $item['name'] = preg_replace("/(" . $key . ")/", "<span class='red bold'>$1</span>", $item['name']);
                    $item['singer'] = preg_replace("/(" . $key . ")/", "<span class='red bold'>$1</span>", $item['name']);
                    $item['album'] = preg_replace("/(" . $key . ")/", "<span class='red bold'>$1</span>", $item['name']);

                }
            }
        }

        $count = Music::dataCount('');
        $this->view->setVar('list', $list);
        $this->view->setVar('type', $type);
        $this->view->setVar('key', $key);
        $category = MusicCategory::getColumn(['enable=1', 'order' => 'sort_num asc,created desc', 'columns' => 'id,name'], 'name', 'id');
        $this->view->setVar('category', $category);
        $this->view->setVar('cat', $cat_id);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //网络音乐
    public function onlineAction()#网络音乐#
    {
        /*   $platform = $this->request->get("platform", 'string', 'qq');
           $key = $this->request->get("key", 'string', '');
           $page = $this->request->get("p", 'int', 1);
           $limit = $this->request->get("limit", 'int', 20);
           $res = \Components\Music\Music::init()->search($key, 1, $page, $limit, $platform);

           $this->view->setVar('list', $res['data_list']);
           Pagination::instance($this->view)->showPage($page, $res['data_count'], $limit);
           $this->view->setVar('platform', $platform);
           $this->view->setVar('key', $key);
           $this->view->setVar('limit', $limit);*/

    }

    //音乐裁剪
    public function cutAction()#音乐裁剪#
    {
        $item = $this->request->get("item");
        //var_dump($item);exit;
        $this->view->setVar('music', $item);
        $item = json_decode(base64_decode($item), true);
        $music = new \Components\Music\Music();
        $url = $music->mp3Url(isset($item['song_mid']) ? $item['song_mid'] : $item['song_id'], $item['platform']);
        $url && $item['mp3'] = $url;
        $this->view->setVar('item', $item);
        $category = MusicCategory::getColumn(['enable=1', 'order' => 'sort_num asc,created desc', 'columns' => 'id,name'], 'name', 'id');
        $this->view->setVar('category', $category);

    }

}