<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/3
 * Time: 16:19
 */

namespace Multiple\Panel\Controllers;


use Models\User\UserInfo;
use Multiple\Open\Helper\Ajax;
use Services\Site\SiteKeyValManager;
use Util\Pagination;

class DiamondController extends ControllerBase
{
    public function indexAction()#充值列表#
    {
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            foreach ($post['money_coin'] as &$item) {
                $item['money'] = (int)$item['money'] * 100;
                $item['coin'] = (int)$item['coin'];
            }
            $this->postApi('wallet/vc/diamond/recharge_list/update', ['recharge_list' => json_encode($post, JSON_UNESCAPED_UNICODE)]);
            \Util\Ajax::init()->outRight();
        }
        $res = $this->postApi('wallet/vc/diamond/recharge_list/check', []);
        $this->view->setVar('list', $res['money_coin']);
    }

    public function wechatAction()#微信充值#
    {
        $rule = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "diamond_wechat_rules");
        $data = [];
        if ($rule) {
            $data = json_decode($rule, true);
        }
        $this->view->setVar('data', $data);
    }

    public function recordAction()#龙钻记录#
    {
        $uid = $this->request->get('uid','int',0);
        $start = $this->request->get('start', 'string', date('Y/m/d',time()));
        $end   = $this->request->get('end', 'string', date('Y/m/d',time()));
        $sort = $this->request->get('sort', 'string', 'created desc');
        $type   = $this->request->get('type', 'int', -1);
        $p     = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $where = "where coin_type = 0";
        $type >= 0 && $where .= " and type = $type";
        if( !empty($uid) )
        {
            $where .= " and uid = " . $uid;
        }
        if ( !empty($start) )
        {
            $start = strtotime($start);
            $where .= " and created >= $start ";
        }
        if ( !empty($end) )
        {
            $end = strtotime($end) + 86400;
            $where .= " and created <= $end ";
        }
        $count = $this->virtual_coin->query("select count(1) as num from virtual_record $where")->fetch(\PDO::FETCH_ASSOC)['num'];
        $res = $this->virtual_coin->query("select * from virtual_record $where  order by $sort  limit " . ($p -1)*$limit . ",$limit")->fetchAll(\PDO::FETCH_ASSOC);
        $uids = array_column($res,'uid');
        if( !empty($uids) )
        {
            $user_info = UserInfo::findList(['user_id in (' . implode(',',array_unique($uids) ) . ")",'columns' => 'user_id as uid,username,avatar']);
            $user_info = array_combine(array_column($user_info,'uid'),$user_info);
            $this->view->setVar('user_info',$user_info);
        }
        $this->view->setVar('list',$res);
        $this->view->setVar('type',$type);
        $this->view->setVar('sort',$sort);
        Pagination::instance($this->view)->showPage($p,$count,$limit);
    }
}