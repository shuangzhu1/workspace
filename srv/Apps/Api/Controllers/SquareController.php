<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/12/26
 * Time: 11:36
 */

namespace Multiple\Api\Controllers;


use Models\Site\SiteMaterial;
use Models\Square\RedPackageTaskRules;
use Models\User\UserInfo;
use Services\Shop\ShopManager;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\User\Square\SquareTask;
use Services\User\SquareManager;
use Services\User\UserStatus;
use Util\Ajax;
use Util\Debug;
use Util\Validator;

class SquareController extends ControllerBase
{
    //附近在线用户
    public function onlineAction()
    {
        $uid = $this->uid;
        $lng = $this->request->get('lng', 'string', '');//精度
        $lat = $this->request->get('lat', 'string', '');//纬度
        if (!$lng || !$lat) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $uids = $this->redis->hKeys(CacheSetting::KEY_USER_ONLINE_LIST);

        $data = ['data_list' => []];
        if ($uids) {
            $uids = array_splice($uids, 0, 100);
            $limit = 6;//取多少个用户头像
            $where = 'u.id<>' . $uid . " and l.user_id in (" . implode(',', $uids) . ') and u.avatar<>"' . UserStatus::$default_avatar . '"';
            $list = $this->di->get("original_mysql")->query("select GetDistances(lat,lng,$lat,$lng) as distance,l.user_id as uid,u.avatar from user_location as l left join users as u on l.user_id=u.id where " . $where . ' order by distance asc,rand() desc limit ' . $limit)->fetchAll(\PDO::FETCH_ASSOC);
            if ($list) {
                foreach ($list as $k => $item) {
                    unset($list[$k]['distance']);
                }
                if (count($list) < 6) {
                    $users = UserInfo::findList(['user_type=' . UserStatus::USER_TYPE_NORMAL . " and avatar<>'" . UserStatus::$default_avatar . "'", 'limit' => $limit - count($list), 'columns' => 'user_id as uid,avatar', 'order' => 'rand()']);
                    $list = array_merge($list, $users);
                }
                $data['data_list'] = $list;
            }
        }
        $this->ajax->outRight($data);
    }

    //发布广场红包
    public function sendPackageAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("type", 'int', 1);//广场红包类型 1-商品 2-普通广告
        $item_id = $this->request->get("item_id", 'int', 0);//对应的id 商品id/
        $media_type = $this->request->get("media_type", 'int', 0);//媒体类型
        $media = $this->request->get("media", 'string', '');//媒体
        $content = $this->request->get("content");//描述
        $package_id = $this->request->get("package_id");//红包id
        $package_info = $this->request->get("package_info");//红包信息
        $lng = $this->request->get("lng", 'float', 0);//经度
        $lat = $this->request->get("lat", 'float', 0);//纬度
        $area_code = $this->request->get("area_code", "string", '');//区域码
        //   $range_type = $this->request->get("range_type", "int", 0);//0-系统默认距离 1-全国 2-全市
        $range_type = 1;

        if (!$uid || !$type || !$package_id || !$package_info) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "缺少必须参数");
        }
        if (!in_array($range_type, SquareManager::$range_type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "红包范围数据有误");
        }
        $res = SquareManager::init()->addPackage($uid, $type, $item_id, $content, $media_type, $media, $package_id, $package_info, $lng, $lat, $area_code, $range_type);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight("发布成功", Ajax::SUCCESS_HANDLE);
    }

    //发布配置
    public function configAction()
    {
        $setting = ['range_type' => implode(',', SquareManager::$range_type), 'distance' => 2000];
        $config = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting');
        if ($config) {
            if (isset($config['range_type'])) {
                $setting['range_type'] = implode(',', $config['range_type']);
            }
            if (isset($config['distance_limit'])) {
                $setting['distance'] = intval($config['distance_limit']);
            }
        }

        $this->ajax->outRight($setting);
    }

    //发起抢红包请求/查询红包状态
    public function pickAction()
    {
        $uid = $this->uid;
        $package_id = $this->request->get("package_id", 'int', 0);//红包id
        $is_pick = $this->request->get("is_pick", 'int', 1);//是否抢
        $extend = $this->request->get("extend");//用户拓展信息，抢红包成功后，将原样推送回app，请严格按照json格式传递，否则将失效

        if (!$uid || !$package_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!SquareManager::init()->pick($uid, $package_id, $extend, $is_pick)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
    }

    //红包列表
    public function packageListAction()
    {
        $uid = $this->uid;
        $lng = $this->request->get("lng", 'float', '');//经度
        $lat = $this->request->get("lat", 'float', '');//纬度
        $area_code = $this->request->get("area_code", 'string', ''); //地区码
        $res = SquareManager::init()->packageList($uid, $lng, $lat, $area_code);
        $this->ajax->outRight($res);
    }

    //红包详情
    public function packageDetailAction()
    {
        $uid = $this->uid;
        $package_id = $this->request->get("package_id", 'int', '');//红包id
        $package_info = $this->request->get("package_info", 'int', 0);//是否需要查看红包信息
        $is_pick = $this->request->get("is_pick", 'int', 1);//兼容安卓
        $new_user =(int) $this->request->get('new_user','int',0);//新手

        if (!$uid || !$package_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SquareManager::init()->packageDetail($uid, $package_id, $package_info, $is_pick,$new_user);

        $this->ajax->outRight($res);
    }

    //红包收发历史
    public function packageHistoryAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("tab", 'int', 1);//1-我发出的 2-我抢到的
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);

        $res = SquareManager::init()->packageHistory($uid, $type, $page, $limit);
        $this->ajax->outRight($res);
    }

    /**
     * 获取最近五天公告
     */
    public function getNoticeAction()
    {
        $lastId = $this->request->get('lastId', 'int', 0);//客户端存储的所有公告id
        $data = [];//返回数据
        $where = 'type = 2 and enable = 1';
        $start = strtotime(date('Y/m/d', strtotime('-4 days')));
        $where .= ' and created >= ' . $start;
        $notices = SiteMaterial::findList([$where]);
        if (!empty($notices)) {
            //取出最新一条记录
            $lastNotice = $notices[count($notices) - 1];
            if ($lastId != 0 && $lastId == $lastNotice['id'])
                $data['hasNew'] = 0;
            else
                $data['hasNew'] = 1;
            foreach ($notices as $k => $v) {
                $data['notices'][$k]['id'] = (int)$v['id'];
                $data['notices'][$k]['title'] = $v['title'];
            }
            $data['notices'] = array_values($data['notices']);
        } else//最近五天无公告
        {
            $notices = SiteMaterial::findOne(['type = 2 and enable = 1', 'columns' => 'id,title', 'order' => 'created desc']);
            if ($notices) {
                $data['hasNew'] = $lastId == $notices['id'] ? 0 : 1;
                $data['notices'][] = $notices;
            } else//表中无数据
            {
                $data['hasNew'] = 0;
                $data['notices'] = [];
            }
        }

        Ajax::init()->outRight($data);
    }

    //红包详情页领取赠送的龙钻
    public function getTheDiamondAction()
    {
        $uid = $this->request->get('uid', 'int', 0);
        $package_id = $this->request->get('package_id', 'string', '');
        if (!$uid || !$package_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SquareManager::init()->getTheDiamond($uid, $package_id);
        $this->ajax->outRight($res);
    }

    //每日任务
    public function dailyTaskAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SquareManager::init()->dailyTask($uid);
        $this->ajax->outRight($res);
    }

    //获取地图店铺分类按钮
    public function getFilterBtnsAction()
    {
        $btns = json_decode(SiteKeyValManager::init()->getValByKey('other', 'shop_filter_btns'), true);
        $res = [];
        foreach ($btns as $k => $v) {
            $res[$k]['id'] = (int)$v['id'];
            $res[$k]['name'] = $v['name'];
            $res[$k]['icon'] = $v['icon'];
        }
        $res = array_values($res);
        $this->ajax->outRight($res);
    }


    //地图展示一定范围内店铺
    public function nearShopAction()
    {
        $uid = $this->uid;
        $category = $this->request->get('category', 'int', 0);//
        $lng = $this->request->get("lng", 'float', '');//经度
        $lat = $this->request->get("lat", 'float', '');//纬度
        $area_code = $this->request->get("area_code", 'string', ''); //空位全国 地区码
        if (!$uid || !$lng || !$lat ) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = SquareManager::init()->nearShopList($uid, $category, $lng, $lat, $area_code);
        $this->ajax->outRight($res);
    }

    //地图上点击店铺显示概要信息
    public function shopOutlineInfoAction()
    {
        $uid = $this->uid;
        $shop_id = $this->request->get('shop_id', 'int', 0);
        if (!$uid || !$shop_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ShopManager::init()->outlineInfo($uid, $shop_id);
        $this->ajax->outRight($res);
    }

}