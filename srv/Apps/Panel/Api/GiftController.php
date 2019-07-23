<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/26
 * Time: 14:10
 */

namespace Multiple\Panel\Api;


use Models\Site\SiteGift;
use Models\User\UserGiftLog;
use Models\User\UserInfo;
use Services\Admin\AdminLog;
use Util\Ajax;
use Util\Pagination;

class GiftController extends ApiBase
{
    //编辑/添加礼物
    public function editAction()
    {
        $gift_id = $this->request->getPost('gift_id', 'int', 0);//礼物id
        $name = $this->request->getPost('name', 'string', '');//礼物名称
        $enable = $this->request->getPost('enable', 'int', 1);//是否可用
        $thumb = $this->request->getPost('thumb', 'string', '');
        $is_vip = $this->request->getPost('is_vip', 'int', 0);//是否vip
        $coins = $this->request->getPost('coins', 'int', 0);//龙豆值
        $charm = $this->request->getPost('charm', 'int', 0);//魅力值

        $is_recommend = $this->request->getPost('is_recommend', 'int', 0);//是否推荐

        $sort_num = $this->request->getPost('sort_num', 'int', 50); //排序字段 越小越靠前
        if ($name == '') {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '名称不能为空');
        }
        $data = ['name' => $name, 'enable' => $enable == 1 ? 1 : 0, 'charm' => $charm, 'sort_num' => $sort_num, 'thumb' => $thumb, 'is_vip' => $is_vip, 'coins' => $coins, 'is_recommend' => $is_recommend];
        //编辑
        if ($gift_id > 0) {
            $gift = SiteGift::findOne('id=' . $gift_id);
            if (!$gift) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $res = SiteGift::updateOne($data, ['id' => $gift_id]);
        } else {
            /* if (SiteGift::findOne(['name="' . $name . '"'])) {
                 $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '该礼物已存在');
             }*/
            $tag = new SiteGift();
            $data['created'] = time();
            $res = $tag->insertOne($data);
        }
        if ($res) {
            if ($gift_id) {
                AdminLog::init()->add('修改礼物', AdminLog::TYPE_GIFT, $gift_id, ['type' => 'update', 'id' => $gift_id]);
                $this->ajax->outRight("编辑成功");
            } else {
                AdminLog::init()->add('添加礼物', AdminLog::TYPE_GIFT, $res, ['type' => 'add', 'id' => $res]);
                $this->ajax->outRight($res);
            }
        } else {
            $this->ajax->outError($gift_id ? "编辑失败" : "添加失败");
        }

    }

    public function setAnimationAction()
    {
        $gift_id = $this->request->getPost('gift_id', 'int', 0);//礼物id
        $animation = $this->request->getPost('animate', 'string', '');//动效地址
        if ($gift_id && $animation) {
            $res = SiteGift::updateOne(['animation' => $animation], ['id' => $gift_id]);
            if ($res) {
                $this->ajax->outRight("编辑成功");
            }
            $this->ajax->outRight("编辑失败");
        }
        $this->ajax->outError(Ajax::INVALID_PARAM, "无效的参数");

    }

    //禁用礼物
    public function lockAction()
    {
        $gift_id = $this->request->get('gift_id', 'int', 0);
        if (!$gift_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $gift = SiteGift::findOne('id=' . $gift_id);
        if (!$gift) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (SiteGift::updateOne(['enable' => 2], ['id' => $gift_id])) {
            AdminLog::init()->add('禁用礼物', AdminLog::TYPE_GIFT, $gift_id, ['type' => 'update', 'id' => $gift_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //删除礼物
    public function removeAction()
    {
        $gift_id = $this->request->get('gift_id', 'int', 0);
        if (!$gift_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $gift = SiteGift::findOne('id=' . $gift_id);
        if (!$gift) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (SiteGift::updateOne(['enable' => 0], ['id' => $gift_id])) {
            AdminLog::init()->add('删除礼物', AdminLog::TYPE_GIFT, $gift_id, ['type' => 'del', 'id' => $gift_id]);
            $this->ajax->outRight("删除成功");
        } else {
            $this->ajax->outError("删除失败");
        }
    }

    //解除禁用礼物
    public function unLockAction()
    {
        $gift_id = $this->request->get('gift_id', 'int', 0);
        if (!$gift_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $gift = SiteGift::findOne('id=' . $gift_id);
        if (!$gift) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (SiteGift::updateOne(['enable' => 1], ['id' => $gift_id])) {
            AdminLog::init()->add('礼物解除禁用', AdminLog::TYPE_GIFT, $gift_id, ['type' => 'update', 'id' => $gift_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //推荐/取消推荐
    public function recommendAction()
    {
        $gift_id = $this->request->get('gift_id', 'int', 0);
        $recommend = $this->request->get('recommend', 'int', 0);

        if (!$gift_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $gift = SiteGift::findOne('id=' . $gift_id);
        if (!$gift) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (SiteGift::updateOne(['is_recommend' => $recommend], ['id' => $gift_id])) {
            AdminLog::init()->add($recommend == 1 ? '礼物推荐' : '礼物取消推荐', AdminLog::TYPE_GIFT, $gift_id, ['type' => 'update', 'id' => $gift_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    public function getListAction()
    {
        $page = $this->request->getPost('page', 'int', 1);
        $limit = $this->request->getPost('limit', 'int', 20);
        $key = $this->request->getPost('key', 'string', '');//关键字
        $status = $this->request->getPost('enable', 'int', -1);//状态
        $vip = $this->request->getPost('is_vip', 'int', -1);//是否vip
        $order = $this->request->getPost('order', 'string', '');//order
        $sort = $this->request->getPost('sort', 'string', '');//sort

        $params[] = [];
        $params['order'] = 'is_recommend desc,animation,coins desc,use_count desc,created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params[0][] = 'enable<>0';
        if ($key) {
            $params[0][] = 'name like "%' . $key . '%"';
        }
        if ($status != -1) {
            $params[0][] = ' enable = ' . $status;
        }
        if ($vip != -1) {
            $params[0][] = ' is_vip = ' . $vip;
        }
        if ($order && $sort) {
            $params['order'] = $order . " " . $sort;
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SiteGift::dataCount($params[0]);
        $res = SiteGift::findList($params);
        $data = [];
        if ($res) {
            foreach ($res as $item) {
                $data[] = [$this->getFromOB('gift/partial/item', array('item' => $item))];
            }
        } else {
            $data[] .= "<tr><td colspan='12'>暂无数据</td></tr>";
        }
        $bar = Pagination::getAjaxListPageBar($count, $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $count, 'bar' => $bar]);
    }

    //记录
    public function recordAction()
    {
        $page = $this->request->getPost('page', 'int', 1);
        $limit = $this->request->getPost('limit', 'int', 20);
        $order = $this->request->getPost('order', 'string', '');//order
        $sort = $this->request->getPost('sort', 'string', '');//sort
        $start = $this->request->getPost('start', 'string', '');
        $end = $this->request->getPost('end', 'string', '');

        $owner_id = $this->request->getPost('owner_id', 'int', 0);
        $user_id = $this->request->getPost('user_id', 'int', 0);
        $gift_id = $this->request->getPost('gift_id', 'int', 0);

        $params[] = [];

        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($owner_id) {
            $params[0][] = ' owner_id  =' . $owner_id;
        }
        if ($user_id) {
            $params[0][] = ' user_id  =' . $user_id;
        }
        if ($gift_id) {
            $params[0][] = ' gift_id  =' . $gift_id;
        }
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($order && $sort) {
            $params['order'] = $order . " " . $sort;
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $res = UserGiftLog::findList([$params[0], 'offset' => $params['offset'], 'limit' => $limit, 'order' => $params['order']]);
        $count = UserGiftLog::dataCount($params[0]);
        if ($res) {

        }

        // $res = $this->db->query("select l.*,g.name,g.thumb from user_gift_log as l left join  site_gift as g on l.gift_id=g.id where " . $params[0] . " order by " . $params['order'] . " limit " . $params['offset'] . "," . $limit)->fetchAll(\PDO::FETCH_ASSOC);
        //  $count = $this->db->query("select count(*) as count from user_gift_log as l left join  site_gift as g on l.gift_id=g.id where " . $params[0])->fetch(\PDO::FETCH_ASSOC);
        //  $count = $count['count'];
        $data = [];
        if ($res) {
            $uids = array_unique(array_merge(array_column($res, 'owner_id'), array_column($res, 'user_id')));
            $gift_ids = array_unique(array_column($res, 'gift_id'));
            $gift_info = SiteGift::getByColumnKeyList(['id in(' . implode(',', $gift_ids) . ')', 'columns' => 'name,thumb,id'], 'id');
            $user_info = UserInfo::getColumn(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'username,user_id'], 'username', 'user_id');

            foreach ($res as $item) {
                $item['owner_name'] = $user_info[$item['owner_id']];
                $item['user_name'] = $user_info[$item['user_id']];
                $item['name'] = $gift_info[$item['gift_id']]['name'];
                $item['thumb'] = $gift_info[$item['gift_id']]['thumb'];

                $data[] = [$this->getFromOB('gift/partial/record', array('item' => $item))];
            }
        } else {
            $data[] .= "<tr><td colspan='12'>暂无数据</td></tr>";
        }
        $bar = Pagination::getAjaxListPageBar($count, $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $count, 'bar' => $bar]);
    }
}