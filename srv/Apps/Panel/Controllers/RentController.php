<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/20
 * Time: 18:27
 */

namespace Multiple\Panel\Controllers;


use Models\User\UserInfo;
use Models\User\Users;
use Phalcon\Mvc\View;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Util\Pagination;

class RentController extends ControllerBase
{
    //星期定义
    static $week = [
        "1" => ['min' => 0, 'max' => 24, 'name' => '星期一'],
        "2" => ['min' => 24, 'max' => 48, 'name' => '星期二'],
        "3" => ['min' => 48, 'max' => 72, 'name' => '星期三'],
        "4" => ['min' => 72, 'max' => 96, 'name' => '星期四'],
        "5" => ['min' => 96, 'max' => 120, 'name' => '星期五'],
        "6" => ['min' => 120, 'max' => 144, 'name' => '星期六'],
        "7" => ['min' => 144, 'max' => 168, 'name' => '星期天'],
    ];

    static $city = [];
    public function initialize()
    {
        parent::initialize();
        if( empty(self::$city))
            self::$city = json_decode(file_get_contents(ROOT . '/Data/city.json'),true);
    }

    public static function getWeek($from, $to)
    {
        $week = floor($from / 24) + 1;
        $res = ['name' => self::$week[$week]['name'], 'start' => ($from - self::$week[$week]['min']) . ":00", 'end' => ($to - self::$week[$week]['min']) . ":00"];
        return $res;
    }


    //技能审核
    public function applyAction()#技能审核#
    {
        $page = $this->request->get("p", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $uid = $this->request->get("uid", 'string', '');
        $type = $this->request->get("type", 'int', 0);//0-待审核 1-已通过 2-已拒绝
        $data = [
            'page' => $page,
            'limit' => $limit,
            'type' => $type
        ];
        if ($uid) {
            $data['uid'] = intval($uid);
        }
        $res = Request::getPost(Base::SKILL_APPLY_LIST, $data);
        if (!$res || !$res['curl_is_success']) {
            $this->err(404, $res['curl_self_err_msg']);
            return;
        }
        $content = json_decode($res['data'], true);
        if ($content['code'] != 200) {
            $this->err(404, var_export($content['data'], true));
            return;
        }
        $list = $content['data']['list'];
        if ($list) {
            $user_ids = array_column($list, 'uid');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $skill = Request::getPost(Base::SKILL_CONFIG, []);
        $skill = json_decode($skill['data'], true);
        $skill = json_decode($skill['data']['Skill'], true);
        $new_skill = [];
        foreach ($skill as $s) {
            $new_skill[$s['type']] = ['name' => $s['title'], 'skills' => array_column($s['skills'], 'title', 'subtype')];
        }
        $this->view->setVar('limit', $limit);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('type', $type);
        $this->view->setVar('list', $list);
        $this->view->setVar('skill', $new_skill);
        Pagination::instance($this->view)->showPage($page, $content['data']['total'], $limit);

    }

    //服务申请
    public function applyServiceAction()#服务申请#
    {
        $page = $this->request->get("p", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $uid = $this->request->get("uid", 'string', '');
        $type = $this->request->get("type", 'int', 0);//0-待审核 1-已通过 2-已拒绝
        $data = [
            'page' => $page,
            'limit' => $limit,
            'type' => $type,
            'apply_type' => 1
        ];
        if ($uid) {
            $data['uid'] = intval($uid);
        }
        $res = Request::getPost(Base::SKILL_APPLY_LIST, $data);
        if (!$res || !$res['curl_is_success']) {
            $this->err(404, strip_tags($res['data']) . $res['curl_parse_err_msg']);
            return;
        }

        $content = json_decode($res['data'], true);
        if ($content['code'] != 200) {
            $this->err(404, var_export($content['data'], true));
            return;
        }
        $list = $content['data']['list'];
        if ($list) {
            $user_ids = array_column($list, 'uid');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('limit', $limit);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('type', $type);
        $this->view->setVar('list', $list);
        Pagination::instance($this->view)->showPage($page, $content['data']['total'], $limit);
    }

    //实名认证申请
    public function applyAuthAction()#实名认证申请#
    {
        $page = $this->request->get("p", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $uid = $this->request->get("uid", 'string', '');
        $type = $this->request->get("type", 'int', 0);//0-待审核 1-已通过 2-已拒绝
        $data = [
            'page' => $page,
            'limit' => $limit,
            'type' => $type,
            'apply_type' => 2
        ];
        if ($uid) {
            $data['uid'] = intval($uid);
        }
        $res = Request::getPost(Base::SKILL_APPLY_LIST, $data);
        if (!$res || !$res['curl_is_success']) {
            $this->err(404, strip_tags($res['data']) . $res['curl_parse_err_msg']);
            return;
        }

        $content = json_decode($res['data'], true);
        if ($content['code'] != 200) {
            $this->err(404, var_export($content['data'], true));
            return;
        }
        $list = $content['data']['list'];
        if ($list) {
            $user_ids = array_column($list, 'uid');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,avatar,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('limit', $limit);
        $this->view->setVar('uid', $uid);
        $this->view->setVar('type', $type);
        $this->view->setVar('list', $list);
        Pagination::instance($this->view)->showPage($page, $content['data']['total'], $limit);
    }

    //技能配置
    public function skillConfigAction()#技能配置#
    {
        $res = Request::getPost(Base::SKILL_CONFIG, []);
        if (!$res || !$res['curl_is_success']) {
            $this->err(404, $res['curl_self_err_msg']);
            return;
        }
        $content = json_decode($res['data'], true);
        if ($content['code'] != 200) {
            $this->err(404, var_export($content['data'], true));
            return;
        }
        $skill = json_decode($content['data']['Skill'], true);
        $new_skill = [];
        foreach ($skill as $s) {
            $new_skill[$s['type']] = ['name' => $s['title'], 'skills' => array_column($s['skills'], 'title', 'subtype')];
        }
        $this->view->setVar('config', $content['data']);
        $this->view->setVar('parent_skill', $new_skill);
        //  $content = json_decode($res['data'], true);
    }

    /**
     * 卖家列表
     */
    public function sellerListAction()#卖家列表#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',15);
        $uid = $this->request->get('uid');
        $recommend = $this->request->get('recommend','int',0);
        $where = 'ident = 1';

        if( !empty($uid) )
        {
            $where .= ' and uid = ' . $uid;

        }else
        {
            $where .= ' and recommend = ' . $recommend;
        }
        $count = $this->sellers->query("select 1 from rent_users where  $where")->numRows();

        $sellers = $this->sellers->query("select * from rent_users where $where  ORDER BY recommend desc,registration desc  limit " . ($p -1)*$limit. ",$limit ")->fetchAll(\PDO::FETCH_ASSOC);
if( !empty($sellers) )
{
    if( count($sellers) == 1 )
        $recommend = $sellers[0]['recommend'];
    $uids = [];
    foreach( $sellers as $seller )
    {
        $uids[] = $seller['uid'];
    }

    $uids = implode(',',$uids);

    $users = Users::findList(['id in (' . $uids .')','columns' => 'id,username,avatar']);

    foreach ( $users as $k => $v )
    {
        $tmp[$v['id']] = $v;
    }
    $users = $tmp;
    foreach ($sellers as &$seller)
    {
        $seller['city'] = $seller['city'] ? self::$city[$seller['city']]['cityName'] : '全国';
        $seller['userInfo'] = $users[$seller['uid']];
    }
}else
    $sellers = null;


        $this->view->setVar('sellers',$sellers);
        $this->view->setVar('recommend',$recommend);
        $this->view->setVar('uid',$uid);
        Pagination::instance($this->view)->showPage($p,$count,$limit);
    }


    /**
     * 城市分布统计
     */
    public function statCityAction()#城市分布统计#
    {

        $day = $this->request->get('d','int',date('Ymd',strtotime("-1 day")));
        $type = $this->request->get('type','string','');
        if(empty($type)) $type = 'order_dtb';
        $res = $this->postApi('forms/detail',['type' => 'rent','subtype' => 'city','begin'=>$day,'end'=>$day]);
        $data_province = [];
        if( $type == 'order_dtb')
        {
            foreach( $res[$type] as $k => $v )
            {
                $city[$k]['name'] = $v['city_name'];
                $city[$k]['value'] = $v['order_num'];
                $data_province[self::$city[$v['city_name']]['province']] += $v['order_num'];
                $bar['xAxis'][] = $v['city_name'] ? $v['city_name'] : '全国';//柱形图
                $bar['data'][] = $v['order_num'];//柱形图
            }
        }else
        {
            foreach( $res[$type] as $k => $v )
            {
                $city[$k]['name'] = $v['city_name'];
                $city[$k]['value'] = $v['seller_num'];
                $data_province[self::$city[$v['city_name']]['province']] += $v['seller_num'];
                $bar['xAxis'][] = $v['city_name'] ? $v['city_name'] : '全国';//柱形图
                $bar['data'][] = $v['seller_num'];//柱形图
            }
        }

        $max = max($data_province);
        foreach( $data_province as $key => $value )
        {
            $tmp[] = array(
                'name' => $key,
                'value'=> $value
            );
        }
        $this->view->setVar('province',json_encode($tmp,256));
        $this->view->setVar('citys',json_encode($city,256));
        $this->view->setVar('bar',$bar);
        $this->view->setVar('max',$max);
        $this->view->setVar('d',$day);
        $this->view->setVar('type',$type);
        //var_dump();exit;
    }

    /**
     * 出租信息统计
     */
    public function statInfoAction()#挂单统计#
    {
        $day = $this->request->get('d','int',date('Ymd',strtotime("-1 day")));
        $res = $this->postApi('forms/detail',['type' => 'rent','subtype' => 'skill','begin' => $day,'end' => $day]);
        $data['intent_time']['data'] = $res['intent_time'];
        foreach( $res['skill_dtb'] as $k => $v)
        {
            $data['skill_dtb']['xAxis'][] = $v['title'];
            $data['skill_dtb']['num'][$k]['name'] = $v['title'];
            $data['skill_dtb']['num'][$k]['value'] = $v['num'];
            $data['skill_dtb']['price'][$k]['name'] = $v['title'];
            $data['skill_dtb']['price'][$k]['value'] = $v['price'] / 100;
        }
        $this->view->setVar('data',$data);
        $this->view->setVar('d',$day);
    }

    /**
     * 下单统计
     */
    public function statOrderAction()#下单统计#
    {
        $begin = $this->request->get('start','int','20170901');
        $end = $this->request->get('end','int',date('Ymd',strtotime("-1 day")));
        $res = $this->postApi('forms/detail',['type' => 'rent','subtype' => 'buyer','begin' => $begin,'end' => $end]);

        foreach( $res['skill_dtb'] as $k => $v)
        {
            $data['skill_dtb']['xAxis'][] = $v['title'];
            $data['skill_dtb']['num'][$k]['name'] = $v['title'];
            $data['skill_dtb']['num'][$k]['value'] = $v['num'];
            $data['skill_dtb']['price'][$k]['name'] = $v['title'];
            $data['skill_dtb']['price'][$k]['value'] = $v['price'] / 100;
        }
        $data['total_order_num'] = $res['total_order_num'];
        $data['total_money'] = $res['total_money'] / 100;
        $this->view->setVar('data',$data);
        $this->view->setVar('start',$begin);
        $this->view->setVar('end',$end);
    }

    /**
     * 买/卖家行为
     */
    public function statBehaviorAction()#买/卖家行为#
    {
        $begin = $this->request->get('start','int','20170901');
        $end = $this->request->get('end','int',date('Ymd',strtotime("-1 day")));
        $res_seller = $this->postApi('forms/detail',['type' => 'rent','subtype' => 'seller_resp','begin' => $begin,'end' => $end]);
        $res_buyer = $this->postApi('forms/detail',['type' => 'rent','subtype' => 'buyer','begin' => $begin,'end' => $end]);
        $this->view->setVar('res_seller',$res_seller);
        $this->view->setVar('res_buyer',$res_buyer['start_time']);
        $this->view->setVar('start',$begin);
        $this->view->setVar('end',$end);
    }




}