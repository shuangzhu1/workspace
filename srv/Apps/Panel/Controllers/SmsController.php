<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/16
 * Time: 10:20
 */

namespace Multiple\Panel\Controllers;


use Models\System\SystemSmsSendRecords;
use Models\User\Users;
use Util\Pagination;

class SmsController extends ControllerBase
{
    private static $type = [
        'register' => '账号注册',
        'bind' => '绑定手机',
        'change' => '修改手机',
        'forgot' => '忘记密码',
        'auth' => '认证',
        'login_protect' => '登录保护',
        'unlock' => '临时锁定解锁',
        'mergency_news' => '新闻抓取失败',
        'pay_password' => '找回支付密码',
        'set_pay_password' => '设置支付密码',
        'unsufficient_reward' => '奖励池金额不足',
        'unsufficient_promote' => '推广资金不足',
        'cashout' => '提现',
    ];
    public function recordAction()#短信发送记录#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',10);
        $mixed = $this->request->get('mixed','int','');
        $start = $this->request->get('start','string','');
        $end = $this->request->get('end','string','');
        $status = $this->request->get('status','int','');
        $type = $this->request->get('type','string','');
        $device = $this->request->get('device','string','');

        if( $mixed !== '')
        {
            if( strlen($mixed) == 11)//手机号搜索
                $where[]  = "phone = " . $mixed;
            else
                $where[] = 'uid = ' . $mixed;
        }
        if(!empty($start))
            $where[] = "send_time >=" . strtotime($start);
        if(!empty($end))
            $where[] = "send_time <=" . (strtotime($end) + 86400);
        if(!empty($status))
            $where[] = "status = " . $status;
        if(!empty($type))
            $where[] = "type = \"" . $type . " \"";
        if(!empty($device))
            $where[] = "device = \"" . $device . "\"";
        if(!empty($where))
            $where = implode(' and ',$where);
        else
            $where = '';
        $count = SystemSmsSendRecords::count($where);
        $res = SystemSmsSendRecords::findList([$where,'offset' => ($p-1)*$limit,'limit' => $limit,'order' => 'send_time desc']);
        $uids = array_unique(array_column($res,'uid'));
        $user = [];
        if(!empty($uids))
        {
            $userInfo = Users::findList(['id in('. implode(',',$uids) . ')','columns' => 'id,username,avatar']);
            foreach($userInfo as $k => $v)
            {
                $user[$v['id']] = $v;
            }
            unset($userInfo);
        }
        $this->view->setVar('list',$res);
        $this->view->setVar('user',$user);
        $this->view->setVar('type',self::$type);
        //搜索
        $this->view->setVar('mixed',$mixed);
        $this->view->setVar('start',$start);
        $this->view->setVar('end',$end);
        $this->view->setVar('status',$status);
        $this->view->setVar('typeBack',$type);
        $this->view->setVar('device',$device);
        Pagination::instance($this->view)->showPage($p,$count,$limit);
    }

}