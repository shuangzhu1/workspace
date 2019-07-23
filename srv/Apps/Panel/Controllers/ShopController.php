<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/10/18
 * Time: 14:52
 */

namespace Multiple\Panel\Controllers;


use Models\Shop\Shop;
use Models\Shop\ShopApply;
use Models\Shop\ShopGoods;
use Models\User\UserInfo;
use Models\User\Users;
use Services\Site\SiteKeyValManager;
use Util\Pagination;

class ShopController extends ControllerBase
{
    //商铺列表
    public function listAction()#店铺列表#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        $shop_id = $this->request->get("shop_id", 'int', 0);

        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("p", 'int', 1);
        $status = $this->request->get("status", 'int', 2);//状态
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $key = $this->request->get('key', 'string', '');//店铺名称

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($user_id) {
            $params[0][] = ' user_id  = ' . $user_id;
        }
        if ($status >= 0) {
            $params[0][] = ' status  = ' . $status;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($key) {
            $params[0][] = ' name like "%' . $key . '%"';
        }
        if ($shop_id) {
            $params[0][] = ' id=' . $shop_id;
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = Shop::dataCount($params[0]);
        $list = Shop::findList($params);

        if ($list) {
            foreach ($list as &$item) {
                $item['goods_count'] = ShopGoods::dataCount("shop_id=" . $item['id'] . " and status=1");
            }
            $user_ids = array_unique(array_column($list, 'user_id'));
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }

        $shop_category = $this->original_mysql->query("select * from shop_category where enable=1")->fetchAll(\PDO::FETCH_ASSOC);
        $shop_category = array_combine(array_column($shop_category,'id'),$shop_category);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('status', $status);
        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('key', $key);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('shop_id', $shop_id);
        $this->view->setVar('shop_category', $shop_category);
    }

    //店铺详情
    public function detailAction()#店铺详情#
    {
        $shop_id = $this->request->get("shop_id", 'int', 0);
        if (!$shop_id) {
            $this->err('503', "无效的参数");
            return;
        }
        $shop = Shop::findOne(['id=' . $shop_id]);
        if (!$shop) {
            $this->err('404', "数据不存在");
            return;
        }
        $user = Users::findOne(['id=' . $shop['user_id'], 'columns' => 'avatar,username']);
        $this->view->setVar('shop', $shop);
        $this->view->setVar('user', $user);
    }

    public function orderAction()#店铺申请订单#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);
        $page = $this->request->get("p", 'int', 1);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $code = $this->request->get("code", 'string', '');
        $status = $this->request->get("status", 'int', -1);
        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($user_id) {
            $params[0][] = ' user_id  = ' . $user_id;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($code) {
            $params[0][] = ' code  = "' . $code . '"';
        }
        if ($status != -1) {
            $params[0][] = ' status  = "' . $status . '"';

        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = ShopApply::dataCount($params[0]);
        $list = ShopApply::findList($params);

        if ($list) {
            $user_ids = array_unique(array_column($list, 'user_id'));
            $user_ids = array_unique(array_merge($user_ids, array_column($list, 'code_owner')));

            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('code', $code);
        $this->view->setVar('status', $status);

        $total = ShopApply::findOne(['columns' => 'sum(money) as total', 'is_paid=1']);
        $paid_user = ShopApply::dataCount(['is_paid=1']);

        $this->view->setVar('total_money', $total ? $total['total'] : 0);
        $this->view->setVar('paid_user_count', $paid_user);
    }

    public function settingAction()#基础设置#
    {
        $price = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "apply_price", true);
        $setting = $price['shop'];
        $this->view->setVar('setting', $setting);
    }

    public function categoryAction()#店铺分类配置#
    {

        /*$connection = new \Phalcon\Db\Adapter\Pdo\Mysql([
            "host" => "127.0.0.1",
            "username" => "root",
            "password" => 'root',
            "dbname" => "dvalley",
        ]);*/

        //$res = $connection->query("select id,name,`desc`,parent_id as pid from shop_category where enable = 1 order by parent_id,sort ")->fetchAll(\PDO::FETCH_ASSOC);
        $res = $this->original_mysql->query("select id,name,parent_id as pid,`desc` from dvalley.shop_category where enable = 1 order by parent_id,sort")->fetchAll(\PDO::FETCH_ASSOC);
        $tree = [];
        if ($res) {
            $keys = array_column($res, 'id');
            $res = array_combine($keys, $res);
            $tree = self::getTree($res,0,0);
        }
        $this->view->setVar('categorys', $tree);
    }

    private function getTree($data,$pid,$level)
    {
        $tree = array();
        foreach($data as $v){
            if($v['pid'] == $pid){
                $v['level'] = $level;
                $v['children'] = self::getTree($data,$v['id'],$level + 1);
                $tree[] = $v;
            }
        }
        return $tree;
    }

}