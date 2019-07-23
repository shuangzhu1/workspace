<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/6
 * Time: 10:53
 */

namespace Multiple\Panel\Api;


use Models\Customer\Customer;
use Models\Customer\CustomerGame;
use Models\Site\SiteGame;
use OSS\OssClient;
use Services\Admin\AdminLog;
use Util\Ajax;
use Util\ImgSize;
use Util\Pagination;
use Util\Validator;

class GameController extends ApiBase
{
    //游戏列表
    public function getListAction()
    {
        $page = $this->request->getPost('page', 'int', 1);
        $limit = $this->request->getPost('limit', 'int', 20);
        $key = $this->request->getPost('key', 'string', '');//关键字
        $status = $this->request->getPost('status', 'int', -1);//状态
        $order = $this->request->getPost('order', 'string', '');//order
        $sort = $this->request->getPost('sort', 'string', '');//sort

        $params[] = [];
        $params['order'] = 'sort_num asc,created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params[0][] = 'status <>0';
        if ($key) {
            $params[0][] = 'name like "%' . $key . '%"';
        }
        if ($status != -1) {
            $params[0][] = ' status = ' . $status;
        }

        if ($order && $sort) {
            $params['order'] = $order . " " . $sort;
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = CustomerGame::dataCount($params[0]);
        $res = CustomerGame::findList($params);
        $data = [];
        if ($res) {
            $customers_ids = array_column($res, 'customer_id');
            $customers = Customer::getByColumnKeyList(['id in (' . implode(',', $customers_ids) . ')', 'columns' => 'id,name'], 'id');
            foreach ($res as $item) {
                $item['customer_info'] = $customers[$item['customer_id']];
                $data[] = [$this->getFromOB('game/partial/item', array('item' => $item))];
            }
        } else {
            $data[] .= "<tr><td colspan='12'>暂无数据</td></tr>";
        }
        $bar = Pagination::getAjaxListPageBar($count, $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $count, 'bar' => $bar]);
    }

    //编辑/添加游戏
    public function editAction()
    {
        $game_id = $this->request->getPost('game_id', 'int', 0);//游戏id
        $name = $this->request->getPost('name', 'string', '');//游戏名称
        $status = $this->request->getPost('status', 'int', 1);//状态
        //  $support_login = $this->request->getPost('support_login', 'int', 1);//状态

        $thumb = $this->request->getPost('thumb', 'string', '');
        //  $url = $this->request->getPost('url', 'string', '');//url

        $customer = $this->request->getPost('customer', 'int', 0);//提供商
        $apk_sign = strtolower($this->request->getPost('apk_sign', 'string', ''));//apk签名
        $package_id = ($this->request->getPost('package_id', 'string', ''));//安卓包名

        $bundle_id = $this->request->getPost('bundle_id', 'string', '');//bundle_id
        $dev_bundle_id = $this->request->getPost('dev_bundle_id', 'string', '');//bundle_id

        // $sort_num = $this->request->getPost('sort_num', 'int', 50); //排序字段 越小越靠前
        if ($name == '') {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '名称不能为空');
        }
        if (!$customer) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '请选择游戏供应商');
        }
        /*  if (!$apk_sign) {
              $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, 'apk签名不能为空');
          }*/
        /* if (!$url || !Validator::validateUrl($url)) {
             $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '无效的链接地址');
         }*/
        $data = ['name' => $name, 'apk_sign' => $apk_sign, 'package_id' => $package_id, 'bundle_id' => $bundle_id, 'dev_bundle_id' => $dev_bundle_id, 'status' => $status == 1 ? 1 : 2,/* 'sort_num' => $sort_num,*/
            'thumb' => $thumb, 'customer_id' => $customer,/* 'url' => $url,*/ /*'support_login' => $support_login*/];
        //编辑
        if ($game_id > 0) {
            $game = CustomerGame::findOne('id=' . $game_id);
            if (!$game) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            if ($game['app_id'] == '') {
                $data['app_id'] = md5(time() . $customer . rand(1, 100));
                $data['app_key'] = $this->createRandStr();
            }
            $res = CustomerGame::updateOne($data, ['id' => $game_id]);
        } else {
            $tag = new CustomerGame();
            $data['created'] = time();
            $data['app_id'] = md5(time() . $customer . rand(1, 100));
            $data['app_key'] = $this->createRandStr();

            $res = $tag->insertOne($data);
        }
        if ($res) {
            if ($game_id) {
                AdminLog::init()->add('修改游戏', AdminLog::TYPE_GAME, $game_id, ['type' => 'update', 'id' => $game_id]);
                $this->ajax->outRight("编辑成功");
            } else {
                AdminLog::init()->add('添加游戏', AdminLog::TYPE_GAME, $res, ['type' => 'add', 'id' => $res]);
                $this->ajax->outRight("添加成功");
            }
        } else {
            $this->ajax->outError($game_id ? "编辑失败" : "添加失败");
        }

    }

    //禁用游戏
    public function lockAction()
    {
        $game_id = $this->request->get('game_id', 'int', 0);
        if (!$game_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = CustomerGame::findOne('id=' . $game_id);
        if (!$data) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (CustomerGame::updateOne(['status' => 2], ['id' => $game_id])) {
            AdminLog::init()->add('禁用游戏', AdminLog::TYPE_GAME, $game_id, ['type' => 'update', 'id' => $game_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //解除禁用游戏
    public function unLockAction()
    {
        $game_id = $this->request->get('game_id', 'int', 0);
        if (!$game_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = CustomerGame::findOne('id=' . $game_id);
        if (!$data) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (CustomerGame::updateOne(['status' => 1], ['id' => $game_id])) {
            AdminLog::init()->add('游戏解除禁用', AdminLog::TYPE_GAME, $game_id, ['type' => 'update', 'id' => $game_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //删除游戏
    public function removeAction()
    {
        $game_id = $this->request->get('game_id', 'int', 0);
        if (!$game_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = CustomerGame::findOne('id=' . $game_id);
        if (!$data) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (CustomerGame::updateOne(['status' => 0], ['id' => $game_id])) {
            AdminLog::init()->add('删除游戏', AdminLog::TYPE_GAME, $game_id, ['type' => 'update', 'id' => $game_id]);
            $this->ajax->outRight("删除成功");
        } else {
            $this->ajax->outError("删除失败");
        }
    }

    //提供商列表
    public function getCustomerListAction()
    {

        $page = $this->request->getPost('page', 'int', 1);
        $limit = $this->request->getPost('limit', 'int', 20);
        $key = $this->request->getPost('key', 'string', '');//关键字
        $status = $this->request->getPost('status', 'int', -1);//状态
        $order = $this->request->getPost('order', 'string', '');//order
        $sort = $this->request->getPost('sort', 'string', '');//sort

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params[0][] = 'status <>0';
        if ($key) {
            $params[0][] = 'name like "%' . $key . '%"';
        }
        if ($status != -1) {
            $params[0][] = ' status = ' . $status;
        }

        if ($order && $sort) {
            $params['order'] = $order . " " . $sort;
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = Customer::dataCount($params[0]);
        $res = Customer::findList($params);
        $data = [];
        if ($res) {
            foreach ($res as $item) {
                $data[] = [$this->getFromOB('game/partial/customer', array('item' => $item))];
            }
        } else {
            $data[] .= "<tr><td colspan='12'>暂无数据</td></tr>";
        }
        $bar = Pagination::getAjaxListPageBar($count, $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $count, 'bar' => $bar]);
    }

    //编辑/添加商家
    public function editCustomerAction()
    {
        $customer_id = $this->request->getPost('customer_id', 'int', 0);//商家id
        $name = $this->request->getPost('name', 'string', '');//商家名称
        $status = $this->request->getPost('status', 'int', 1);//状态
        $thumb = $this->request->getPost('thumb', 'string', '');
        $ncbl = $this->request->getPost('ncbl', 'string', '');
        $icp = $this->request->getPost('icp', 'string', '');
        $bp = $this->request->getPost('bp', 'string', '');

        if ($name == '') {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '名称不能为空');
        }

        $data = ['name' => $name, 'status' => $status == 1 ? 1 : 2, 'thumb' => $thumb , 'ncbl' => $ncbl, 'icp' => $icp, 'bp' => $bp];
        //编辑
        if ($customer_id > 0) {
            $customer = Customer::findOne('id=' . $customer_id);
            if (!$customer) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $res = Customer::updateOne($data, ['id' => $customer_id]);
        } else {
            $tag = new Customer();
            $data['created'] = time();
            $res = $tag->insertOne($data);
        }
        if ($res) {
            if ($customer_id) {
                AdminLog::init()->add('修改商家信息', AdminLog::TYPE_GAME, $customer_id, ['type' => 'update', 'id' => $customer_id]);
                $this->ajax->outRight("编辑成功");
            } else {
                AdminLog::init()->add('添加供应商', AdminLog::TYPE_GAME, $res, ['type' => 'add', 'id' => $res]);
                $this->ajax->outRight("添加成功");
            }
        } else {
            $this->ajax->outError($customer_id ? "编辑失败" : "添加失败");
        }

    }

    //禁用商家
    public function lockCustomerAction()
    {
        $customer_id = $this->request->get('customer_id', 'int', 0);
        if (!$customer_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = Customer::findOne('id=' . $customer_id);
        if (!$data) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (Customer::updateOne(['status' => 2], ['id' => $customer_id])) {
            AdminLog::init()->add('禁用商家', AdminLog::TYPE_GAME, $customer_id, ['type' => 'add', 'id' => $customer_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //解除禁用商家
    public function unLockCustomerAction()
    {
        $customer_id = $this->request->get('customer_id', 'int', 0);
        if (!$customer_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = Customer::findOne('id=' . $customer_id);
        if (!$data) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (Customer::updateOne(['status' => 1], ['id' => $customer_id])) {
            AdminLog::init()->add('商家解除禁用', AdminLog::TYPE_GAME, $customer_id, ['type' => 'add', 'id' => $customer_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //删除游戏
    public function removeCustomerAction()
    {
        $customer_id = $this->request->get('customer_id', 'int', 0);
        if (!$customer_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = Customer::findOne('id=' . $customer_id);
        if (!$data) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (CustomerGame::updateOne(['status' => 0], ['id' => $customer_id])) {
            AdminLog::init()->add('删除商家', AdminLog::TYPE_GAME, $customer_id, ['type' => 'add', 'id' => $customer_id]);
            $this->ajax->outRight("删除成功");
        } else {
            $this->ajax->outError("删除失败");
        }
    }

    public function createRandStr()
    {
        $str = '';
        $rand_str = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@_';
        $rand_str_length = strlen($rand_str);
        $rand_count = rand(10, 20);//10-20位
        for ($i = 0; $i < $rand_count; $i++) {
            $str .= $rand_str[rand(0, $rand_str_length - 1)];
        }
        return $str;
    }

    //H5游戏列表操作 上下架、删除
    public function operateAction()
    {
        $id = $this->request->get('id');
        $action = $this->request->get('action');
        $name = $this->request->get('name','string','');
        $link = $this->request->get('link','string','');
        $logo = $this->request->get('logo','string','');
        switch( $action )
        {
            case 'game_switch':
                $res = $this->di->get('redis')->originalSet('game_switch',$this->request->get('val'));
                break;
            case 'down' ://下架
                $res = SiteGame::updateOne(['status' => 0],['id' => $id]);
                break;
            case 'up' ://上架
                $res = SiteGame::updateOne(['status' => 1],['id' => $id]);
                break;
            case 'del' ://删除
                $res = SiteGame::updateOne(['enable' => 0],['id' => $id]);
                break;
            case 'add' ://添加
                $config = $this->di->get('config')->oss;
                $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
                $img = ImgSize::getBase64ImgBlob($logo);
                $obj_name = 'gameLogo/' . md5(uniqid()) . time().  "." . $img[1];

                $res = $oss->putObject("klg-useravator", $obj_name, $img[0]);

                if( $res['info']['http_code'] == 200 )
                {
                    $logo = $res['info']['url'];
                }
                else
                {
                    Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'头像上传失败，请重试');
                }
                $res = SiteGame::insertOne(['name' => $name,'link' => $link,'logo' => $logo,'view_cnt' => rand(50,150)]);
                break;
            case 'edit' ://编辑
                if( strpos($logo,'data:image') !== false)
                {
                    $config = $this->di->get('config')->oss;
                    $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
                    $img = ImgSize::getBase64ImgBlob($logo);
                    $obj_name = 'gameLogo/' . md5(uniqid()) . time().  "." . $img[1];

                    $res = $oss->putObject("klg-useravator", $obj_name, $img[0]);

                    if( $res['info']['http_code'] == 200 )
                    {
                        $logo = $res['info']['url'];
                    }
                    else
                    {
                        Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'头像上传失败，请重试');
                    }
                }
                $res = SiteGame::updateOne(['name' => $name,'link' => $link,'logo' => $logo],['id' => $id]);
                break;
        }


        if($res)
            Ajax::init()->outRight();
        else
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG);
    }


}