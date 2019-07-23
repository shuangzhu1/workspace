<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/4
 * Time: 17:28
 */

namespace Multiple\Panel\Controllers;


use Models\Site\AreaCity;
use Models\Site\SiteKeyVal;
use Models\Site\SiteMaterial;
use Models\Square\RedPackage;
use Models\Square\RedPackageFestival;
use Models\Square\RedPackagePickLog;
use Models\Square\RedPackageTaskLog;
use Models\Square\RedPackageTaskRules;
use Models\System\SystemRedPackageAds;
use Models\User\UserInfo;
use Models\User\Users;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\User\Square\SquareTask;
use Services\User\SquareManager;
use Services\User\UserStatus;
use Util\LatLng;
use Util\Pagination;

class PackageController extends ControllerBase
{
    public function listAction()#红包列表#
    {
        $status = $this->request->get("status", 'int', 1); //红包状态 -1-全部 1-未过期 2-已过期
        $pick_status = $this->request->get("pick_status", 'int', 1); //红包领取状态 -1-全部 1-派发中 2-已领完

        $type = $this->request->get("type", 'int', -1); //红包类型 -1-全部 1-商品红包 2-普通红包
        $key = $this->request->get("key", 'int', 0); //红包id
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $uid = $this->request->get('uid', 'int', 0);
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序
        $start = $this->request->get("start", 'string', date('Y-m-d'));
        $end = $this->request->get("end", 'string', date('Y-m-d'));
        $robot = $this->request->get("robot", 'int', 0);

        $order = 'created desc';
        $where = "";
        if ($status == -1) {

        } else if ($status == 1) {
            $where .= "and deadline>=" . time() . " ";
        } else {
            $where .= "and deadline<" . time() . " ";
        }
        if ($pick_status != -1) {
            $where .= "and status=" . $pick_status . " ";
        }

        if ($type != -1) {
            $where .= "and type=" . $type . " ";
        }

        if ($key) {
            $where .= "and package_id=" . $key . " ";
        }
        if ($uid) {
            $where .= "and user_id=" . $uid . " ";
        }
        if ($start) {
            $where .= "and created>=" . strtotime($start) * 1000 . " ";
        }
        if ($end) {
            $where .= "and created<=" . (strtotime($end) * 1000 + 86400000) . " ";
        }
        if ($robot != 1) {
            $where .= "and is_rob=$robot";
        }
        //排序
        if ($sort) {
            if ($sort == 'created') {
                $order = " created $sort_order";
            } else if ($sort == 'deadline') {
                $order = "deadline $sort_order, created desc";
            } else if ($sort == 'package') {
                $order = "money $sort_order, created desc";
            }
        }
        $where = $where ? substr($where, 3) : '';

        $list = RedPackage::findList([$where, 'offset' => ($page - 1) * $limit, 'columns' => 'id,package_id,user_id,created,deadline,status,money,num,lng,lat,range_type,is_rob,type', 'limit' => $limit, 'order' => $order]);


        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);


//            $area_codes = array_filter(array_unique(array_column($list, 'area_code')));
//            $areas = AreaCity::getByColumnKeyList(["area_code in ('" . implode("','", $area_codes) . "')", 'columns' => 'area_code,name'], 'area_code');
//            $this->view->setVar('areas', $areas);

            foreach ($list as &$item) {
                if ($item['range_type'] == SquareManager::RANGE_DEFAULT) {
                    $item['address'] = LatLng::getAddress($item['lng'], $item['lat']);
                } else {
                    $item['address'] = '';
                }
            }
        }

        $count = RedPackage::dataCount($where);
        $this->view->setVar('status', $status);
        $this->view->setVar('key', $key);
        $this->view->setVar('type', $type);
        $this->view->setVar('list', $list);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);
        $this->view->setVar('pick_status', $pick_status);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('robot', $robot);
        Pagination::instance($this->view)->showPage($page, $count, $limit);

    }

    public function mapListAction()#地图聚合#
    {
        $list = RedPackage::findList(["created_ymd>=" . (date('Ymd')), 'columns' => 'lng,lat', 'limit' => 10000]);
        $res = [];
        foreach ($list as $item) {
            $res[] = [$item['lng'], $item['lat']];
        }
        unset($list);
        $this->view->setVar('list', $res);

    }

    public function detailAction()#红包详情#
    {
        $package_id = $this->request->get("p_id", 'int', 0);
        if (!$package_id) {
            $this->err('404', "参数错误");
        }
        $package = RedPackage::findOne(['package_id=' . $package_id]);
        if ($package) {
            $info = Request::getPost(Base::PACKAGE_DETAIL, ['uid' => 13, 'redid' => $package_id]);
            if ($info && $info['curl_is_success']) {
                $content = json_decode($info['data'], true);
                $package['package_info'] = $content['data'];
            }
            $info = Request::getPost(Base::PACKAGE_PICKER, ['uid' => 13, 'redid' => $package_id, 'limit' => 500]);
            if ($info && $info['curl_is_success']) {
                $content = json_decode($info['data'], true);
                $list = $content['data'];
                $this->view->setVar('list', $list);
            }
        }
        $this->view->setVar('item', $package);
        //  var_dump($package);exit;
    }

    public function settingAction()#基本设置#
    {
        $val = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "square_package_setting");
        $val = $val ? json_decode($val, true) : [];
        $this->view->setVar('setting', $val);
    }

    public function adsAction()#红包广告#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $kw = $this->request->get('kw', 'string', '');
        $where = ["status=1"];
        if (!empty($kw)) {
            $where[] = "content like '%" . $kw . "%'";
        }
        $where = implode(' and ', $where);
        $list = SystemRedPackageAds::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc']);
        if ($list) {
            foreach ($list as &$item) {
                $item['content'] = str_replace($kw, '<span class="red">' . $kw . '</span>', $item['content']);
            }
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $count = SystemRedPackageAds::dataCount($where);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('kw', $kw);
    }

    public function adsAddAction()#添加/编辑广告#
    {
        $id = $this->request->get("id", 'int', 0);
        if ($id) {
            $ads = SystemRedPackageAds::findOne(['id="' . $id . '"', 'columns' => '']);
            if ($ads) {
                $this->view->setVar('ads', $ads);
                $this->view->setVar('id', $id);
            }
        }
    }

    public function pickLogAction()#领取记录#
    {
        $uid = $this->request->get("uid", 'int', 0);
        $device_id = $this->request->get("device_id", 'string', '');

        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 12);
        $package_id = $this->request->get('package_id', 'string', '');
        $start = $this->request->get('start', 'string', date('Y-m-d'));//开始时间
        $end = $this->request->get('end', 'string', date('Y-m-d'));//结束时间
        $where = [];
        if ($package_id) {
            $where [] = "package_id='" . $package_id . "'";
        }
        if ($start) {
            $where[] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $where[] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($device_id) {
            $where[] = ' device_id  = "' . $device_id . '"';
        }
        if ($uid) {
            $where[] = " user_id=" . $uid;
            $where = implode(" and ", $where);
            $dn = 'dn' . (($uid % 10) + 1);

            $count = RedPackagePickLog::dataCount($where, false, $dn);
            $list = RedPackagePickLog::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc'], false, $dn);
        } else {
            $where = implode(" and ", $where);
            $count = RedPackagePickLog::dataCount($where);
            $list = RedPackagePickLog::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc']);
        }
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('package_id', $package_id);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('device_id', $device_id);
    }

    /**
     * 广场公告列表
     */
    /*public function noticeListAction()#广场公告#
    {
        $p = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $count = SiteMaterial::dataCount(['type = 1 and enable = 1']);
        $list = SiteMaterial::findList(['type = 2 and enable = 1', 'columns' => 'id,title,link,thumb,created,updated', 'limit' => $limit, 'offset' => ($p - 1) * $limit]);
        Pagination::instance($this->view)->showPage($p, $count, $limit);
        $this->view->setVar('list', $list);
        $this->view->pick('package/noticeList');

    }*/

    /**
     * 添加广场公告
     */
    /*public function noticeAddAction()#添加公告#
    {
        $id = $this->request->get('id', 'int', 0);//编辑时传入的id
        $item = [];
        $id > 0 && $id !== 0 && $item = SiteMaterial::findOne(['id = ' . $id]);
        $this->view->setVar('item', $item);
    }*/

    public function festivalAction()#节日红包#
    {
        $status = $this->request->get("status", 'int', 1); //红包状态 -1-全部 1-待发布 2-已发布 3-发布失败
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $start = $this->request->get("start", 'string', '');
        $end = $this->request->get("end", 'string', '');
        $order = 'send_time desc';
        $where = "1=1";
        if ($status != -1) {
            $where .= " and status=" . $status . " ";
        }
        if ($status == 1) {
            $order = 'send_time asc';
        }
        if ($start) {
            $where .= " and created>=" . strtotime($start) . " ";
        }
        if ($end) {
            $where .= " and created<=" . (strtotime($end) + 86400) . " ";
        }

        $list = RedPackageFestival::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => $order]);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $count = RedPackageFestival::dataCount($where);
        $this->view->setVar('status', $status);
        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //添加家假日红包
    public function festivalAddAction()#添加假日红包#
    {
        $users = Users::findList(['user_type=' . UserStatus::USER_TYPE_OFFICIAL, 'columns' => 'username,id,avatar']);
        $this->view->setVar('users', $users);
        $id = $this->request->get("id", 'int', 0);
        if ($id) {
            $item = RedPackageFestival::findOne(['id="' . $id . '"', 'columns' => '']);
            if ($item) {
                $this->view->setVar('item', $item);
                $this->view->setVar('id', $id);
            }
        }
    }

    public function blacklistAction()#黑名单#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        $redis = $this->di->getShared('redis');
        if ($user_id) {
            $list = $redis->hGet(CacheSetting::KEY_RED_PACKAGE_BLACKLIST, $user_id);
            $list = $list ? [$user_id => $list] : [];
        } else {
            $list = $redis->hGetAll(CacheSetting::KEY_RED_PACKAGE_BLACKLIST);
        }
        $list = $list ? $list : [];
        if ($list) {
            $uids = array_keys($list);
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'username,avatar,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('list', $list);
    }

    public function taskAction()#任务配置#
    {
        $where = [];
        \Phalcon\Tag::setTitle("红包广场任务规则");
        $behaviorNameMap = SquareTask::$behaviorNameMap;
        $rule = new SquareTask();
        $termNameMap = $rule->termNameMap;
        $pointTypeMap = $rule->actionNameMap;

        $list = RedPackageTaskRules::findList($where);

        $exits_rules = array_map(function ($row) {
            return $row['behavior'];
        }, $list);
        $new_add = array_diff_key($behaviorNameMap, array_flip($exits_rules));

        $this->view->data = $list;
        $this->view->new_add = $new_add;
        $this->view->behaviorNameMap = $behaviorNameMap;
        $this->view->termNameMap = $termNameMap;
        $this->view->pointTypeMap = $pointTypeMap;
    }

    public function taskLogAction()#任务日志#
    {
        $uid = $this->request->get("uid", 'int', 0);
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $task_key = $this->request->get('task_key', 'int', -1);//任务类型
        $type = $this->request->get('type', 'int', 0);//聚合
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序

        $where = [];
        if ($start) {
            $where[] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $where[] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($uid) {
            $where[] = ' user_id= ' . $uid;
        }
        if ($task_key != -1) {
            $where[] = ' action= "' . $task_key . '"';
        }
        $where = $where ? implode(" and ", $where) : '';

        //排序
        $order = 'created desc';
        if ($sort) {
            if ($sort == 'value') {
                $order = " value $sort_order, created desc";
            }
        }
        //以用户和任务类型聚合
        if ($type == 1) {
            $count = RedPackageTaskLog::dataCount([$where, 'group' => 'user_id,value']);
            $list = RedPackageTaskLog::findList([$where, 'columns' => 'id,max(created) as created,action,action_desc,sum(value) as value,user_id', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => $order, 'group' => 'user_id,action']);
        } //以任务类型聚合
        else if ($type == 2) {
            $count = RedPackageTaskLog::dataCount([$where, 'group' => 'action']);
            $list = RedPackageTaskLog::findList([$where, 'columns' => 'id,max(created) as created,action,action_desc,sum(value) as value,user_id', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => $order, 'group' => 'action']);
        } //以用户聚合
        else if ($type == 3) {
            $count = RedPackageTaskLog::dataCount([$where, 'group' => 'user_id']);
            $list = RedPackageTaskLog::findList([$where, 'columns' => 'id,max(created) as created,action,action_desc,sum(value) as value,user_id', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => $order, 'group' => 'user_id']);
        } else {
            $count = RedPackageTaskLog::dataCount([$where]);
            $list = RedPackageTaskLog::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => $order]);
        }
        if ($list) {
            $uids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'username,avatar,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('list', $list);
        $this->view->setVar('task_key', $task_key);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('type', $type);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //地图上附近店铺筛选按钮
    public function filterBtnsAction()#店铺筛选按钮#
    {
        //获取配置按钮
        $btns = json_decode(SiteKeyValManager::init()->getValByKey('other', 'shop_filter_btns'), true);
        //获取店铺分类中一级分类
        $shop_cids = $this->original_mysql->query("select * from shop_category where parent_id = 0 and enable =1")->fetchAll(\PDO::FETCH_ASSOC);
        $shop_cids = array_combine(array_column($shop_cids, 'id'), $shop_cids);
        $this->view->setVar('shop_cids', $shop_cids);
        $this->view->setVar('btns', $btns);
    }
}