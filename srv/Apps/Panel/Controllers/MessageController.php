<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/3
 * Time: 15:41
 */

namespace Multiple\Panel\Controllers;


use Components\YunPian\lib\TplOperator;
use Models\Admin\Admins;
use Models\Site\SiteKeyVal;
use Models\Site\SiteMaterial;
use Models\System\SystemMessagePush;
use Models\User\Message;
use Models\User\MessageTimingPush;
use Models\User\UserInfo;
use Models\User\Users;
use Services\Im\ImManager;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Util\Ajax;
use Util\Pagination;

class MessageController extends ControllerBase
{
    //短息模板
    public function smsAction()#短信模板#
    {
        $sms = new TplOperator();
        $data = $sms->get();
        $data = $data ? $data->responseData : [];
        $data = $data ? array_column($data, 'check_status', 'tpl_id') : [];
        $list = SiteKeyValManager::init()->getKeyGroupAll(SiteKeyValManager::KEY_PAGE_SMS_TPL, true);
        $this->view->setVar('list', $list);
        $this->view->setVar('data', $data);
    }

    //系统消息模板
    public function sysAction()#系统消息模板#
    {
        $list = SiteKeyValManager::init()->getKeyGroupAll(SiteKeyValManager::KEY_PAGE_IM_TPL, true);
        $this->view->setVar('list', $list);
    }


    //消息推送
    public function pushAction()#推送记录#
    {

        $status = $this->request->get('status', 'int', '-1');//状态
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $type = $this->request->get('type', 'string', '');//类型


        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($status != '-1') {
            $params[0][] = ' status  = "' . $status . '"';
        }

        if ($type) {
            $params[0][] = ' tpl_type  = "' . $type . '"';
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SystemMessagePush::dataCount($params[0]);
        $list = SystemMessagePush::findList($params);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('status', $status);
        $this->view->setVar('type', $type);
        $this->view->setVar('list', $list);
    }

    //发送消息
    public function pushMessageAction()#推送消息#
    {
        //最新20条图文
        $list = SiteMaterial::findList(['type = 1 and enable = 1','columns' => 'id,title','order' => 'updated desc','limit' => 20]);
        $this->view->setVar('list',$list);
    }

    //恐龙谷消息
    public function getListAction()#恐龙君#
    {
        $limit = $this->request->get("limit", 'int', 18);
        $page = $this->request->get("p", 'int', 1);
        $key = $this->request->get('key', 'string', '');//关键字
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间

        $params[] = ["(to_uid=" . ImManager::ACCOUNT_SYSTEM . " or from_uid=" . ImManager::ACCOUNT_SYSTEM . ") and gid=0 and media_type in('text','audio','picture')"];
        $params['order'] = 'send_time desc';
        $params['columns'] = 'mix_id,send_time,if(from_uid=' . ImManager::ACCOUNT_SYSTEM . ',to_uid,from_uid) as from_uid,body,media_type,count(*) as count';
        $params['group'] = 'mix_id';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username="' . $key . '" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'from_uid in (' . implode(',', $users) . ')';
            }
        }
        if ($start) {
            $params[0][] = ' send_time  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' send_time  <= ' . (strtotime($end) + 86400);
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $message_ids = Message::getColumn([$params[0], 'offset' => $params['offset'], 'limit' => $params['limit'], 'group' => 'mix_id', 'columns' => 'max(send_time) as s,if(from_uid=' . ImManager::ACCOUNT_SYSTEM . ',to_uid,from_uid) as uid,mix_id', 'order' => 's desc'], 's');
        $message = [];
        if ($message_ids) {
            $message = Message::findList(["send_time in (" . implode($message_ids, ',') . ') and ' . $params[0], 'limit' => $params['limit'], 'group' => 'mix_id', 'columns' => $params['columns'], 'order' => $params['order']]);
        }
        // echo "select count(*) as count  from(select count(1) as count from message where " . $params[0] . ' group by mix_id) as m';exit;
        $count = $this->db->query("select  count(*) as count  from(select count(1) as count from message where " . $params[0] . ' group by mix_id) as m')->fetch();
        //   var_dump($count);
        //exit;
        $count = $count['count'];
        if ($message) {
            $user_info = Users::getByColumnKeyList(["id in (" . implode(',', array_column($message, 'from_uid')) . ")", 'columns' => 'avatar,username,id as uid'], 'uid');
            $this->view->setVar('user_info', $user_info);
        }
        $this->view->setVar('limit', $limit);
        $this->view->setVar('page', $page);
        $this->view->setVar('list', $message);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('key', $key);

        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    public function converstationAction()#会话详情#
    {
        $uid = $this->request->get("uid", 'int', 0);
        $page = $this->request->get("p", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);

        if (!$uid) {
            die("无效的用户id");
        }
        $unread_msg = $this->redis->hGet(CacheSetting::KEY_UNREAD_MESSAGE, $uid);//未读消息数
        if ($unread_msg > 0) {
            //减少未读消息数
            $this->redis->hIncrBy(CacheSetting::KEY_UNREAD_MESSAGE, $uid, "-" . $unread_msg);
            $this->redis->hIncrBy(CacheSetting::KEY_UNREAD_MESSAGE,CacheSetting::KEY_UNREAD_MESSAGE_TOTAL, "-" . $unread_msg);
        }
        $message = Message::findList(["columns" => 'from_uid,to_uid,send_time,id,body,media_type', "((from_uid=" . $uid . ' and to_uid=' . ImManager::ACCOUNT_SYSTEM . ') or (from_uid=' . ImManager::ACCOUNT_SYSTEM . ' and to_uid=' . $uid . '))'." and media_type in('text','audio','video','picture','file')", 'limit' => $limit, 'order' => 'send_time desc', 'offset' => ($page - 1) * $limit]);
        $user_info = Users::findOne(["id=" . $uid, "columns" => "id as uid,username,avatar"]);
        if ($message) {
            $message = array_reverse($message);
        }

        $this->view->setVar('limit', $limit);
        $this->view->setVar('list', $message);
        $this->view->setVar('user_info', $user_info);
        $this->view->setVar('uid', $uid);

    }

    public function timingPushAction()#定时推送列表#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',20);
        $timing_day = $this->request->get('timging_day','string','');
        $status = $this->request->get('status','int',0);
        //筛选
        $where = [];
        !empty($timing_day) && $where['timing_day'] = 'timing_day = ' . $timing_day;
        !empty($status) && $where['status'] = 'status = ' . $status;
        $where =  !empty($where) ? implode(' and ', $where) : '';
        !empty($where) && $where = ' and ' . $where;

        $count = MessageTimingPush::dataCount(['enable = 1 ' . $where]);
        $list = MessageTimingPush::findList(['enable = 1 ' . $where,'limit' => $limit,'offset' => ($p -1)*$limit,'order' => 'timing desc']);

        Pagination::instance($this->view)->showPage($p,$count,$limit);
        $this->view->setVar('list',$list);


    }

    /**
     * 红包君发送推广红包
     */
    public function sendRedBagAction()#发送红包#
    {
        //获取特殊账号信息
        $account_special = [100,101,102,103,104];
        $account_special_info = UserInfo::findList(['user_id in (' . implode(',',$account_special). ')','columns' => 'user_id,username,avatar']);
        $account_special_info[] =
            [
            'user_id' => 13,
            'username' => '恐龙君',
            'avatar' => 'http://avatorimg.klgwl.com/13/13454_s_150x150.png'
            ];
        $account_special_info[] =
            [
                'user_id' => 18,
                'username' => '红包君',
                'avatar' => 'http://avatorimg.klgwl.com/13/13149_s_90x90.png'
            ];
        //获取区域
        $cities = Request::getPost(Request::REDBAG_CONFIG_PROVINCE_CITY,[],true);
        //获取短信验证码的手机
        $phone = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER,'phone_bound_for_send_redbag_from_web');
        $this->view->setVar('phone',$phone);
        $this->view->setVar('cities',$cities);
        $this->view->setVar('account_special_info',$account_special_info);
    }
}