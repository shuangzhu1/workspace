<?php

namespace Multiple\Panel\Controllers;

use Components\Rules\Coin\PointRule;
use Components\UserManager;
use Models\Agent\Agent;
use Models\Group\Group;
use Models\Group\GroupMember;
use Models\Site\SiteGift;
use Models\Social\SocialDiscuss;
use Models\Social\SocialReport;
use Models\User\UserAttention;
use Models\User\UserAuthApply;
use Models\User\UserBlacklist;
use Models\User\UserCoinLog;
use Models\User\UserCoinRules;
use Models\User\UserContactMember;
use Models\User\UserGift;
use Models\User\UserGiftLog;
use Models\User\UserGroups;
use Models\User\UserInfo;
use Models\User\UserLoginLog;
use Models\User\UserOnline;
use Models\User\UserPointGrade;
use Models\User\UserPointLog;
use Models\User\UserPointRules;
use Models\User\Users;
use Models\User\UserSettings;
use Models\User\UserShow;
use Models\User\UserTags;
use Models\User\UserTakecashApply;
use Models\User\UserThirdParty;
use Models\User\ZuokeUserWallet;
use Services\Site\CurlManager;
use Services\Site\SiteKeyValManager;
use Services\User\AuthManager;
use Util\EasyEncrypt;
use Util\Ip;
use Util\Pagination;

class UsersController extends ControllerBase
{

    public function editAction()
    {

    }

    public function settingAction()
    {
        /*$setting = UserSettings::findFirst("customer_id=" . CUR_APP_ID . "");
        $this->view->setVar("setting", $setting);*/
    }

    public function rulesAction()#充值规则#
    {
        $this->ruleSetting('normal');
    }

    //充值
    public function ruleChargeAction()#充值设置#
    {
        $point_rule = UserCoinRules::findOne(['behavior=' . PointRule::BEHAVIOR_CHARGE]);
        $data = [];
        if ($point_rule) {
            $rule = $point_rule['params'];
            if ($rule) {
                $data = json_decode($rule, true);
            }
        }
        $this->view->setVar('data', $data);
    }

    public function firmRulesAction()
    {
        $this->ruleSetting('firm');
    }

    protected function ruleSetting($type = 'normal')#龙豆规则#
    {
        if (empty($type)) {
            $type = 'normal';
        }
        if (!in_array($type, array('normal', 'firm'))) {
            $this->flash->error("illegal parm");
            return $this->response->send();
        }
        $where = ['behavior<>' . PointRule::BEHAVIOR_CHARGE];
        \Phalcon\Tag::setTitle("用户龙豆规则");
        $behaviorNameMap = PointRule::$behaviorNameMap;
        $rule = new PointRule(0);
        $termNameMap = $rule->termNameMap;
        $pointTypeMap = $rule->actionNameMap;

        $list = UserCoinRules::findList($where);
        $exits_rules = array_map(function ($row) {
            return $row['behavior'];
        }, $list);

        $new_add = array_diff_key($behaviorNameMap, array_flip($exits_rules));

        $this->view->data = $list;
        $this->view->new_add = $new_add;
        $this->view->behaviorNameMap = $behaviorNameMap;
        $this->view->termNameMap = $termNameMap;
        $this->view->pointTypeMap = $pointTypeMap;
        $this->view->type = $type;

        $value = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'coin_rate');
        $this->view->setVar('rate', $value ? json_decode($value, true)['val'] : 0);

    }

    public function indexAction()#用户列表#
    {
        \Phalcon\Tag::setTitle("用户列表");
        $type = $this->request->get("type", 'int', 0);
        $this->view->setVar('type', $type);

    }

    public function groupAction()#用户分组#
    {
        \Phalcon\Tag::setTitle("自定义分组列表");
        $this->assets->addCss('static/panel/modules/users/group.css');

        /*$currentPage = $this->request->get('page', 'int');
        if (empty($currentPage)) {
            $currentPage = 1;
        }
        $queryBuilder = $this->modelsManager->createBuilder()->addFrom('\\Models\\User\\UserGroups', 'grade')->andWhere("grade.customer_id=" . CUR_APP_ID . "");
        $pagination = new \Phalcon\Paginator\Adapter\QueryBuilder(array(
            "builder" => $queryBuilder,
            "limit" => 10,
            "page" => $currentPage
        ));*/


        $this->view->setVar('pageTitle', '用户等级设置');
        $this->view->setVar('description', '用户等级设置');

        $fields = UserPointGrade::findList(array("order" => 'grade ASC'));
        $this->view->setVar('data', $fields);
    }

    /**
     * @param int $gid
     * @param string $name
     * @param string $desc
     * @param string $rules
     */
    public function groupAddAction()
    {
        $this->view->disable();
        if (!$this->request->isAjax()) {
            $this->response->setJsonContent(array(
                'code' => 1,
                'message' => '只接受AJAX请求!'
            ));
            $this->response->send();
        }
        $gid = $this->request->getPost('gid', 'int');
        $name = $this->request->getPost('name', 'string');
        $desc = $this->request->getPost('desc', 'string');
        $rules = trim($this->request->getPost('rules', 'string'), ',');
        $data = array(
            'code' => 0,
            'result' => '',
            'message' => ''
        );

        if (empty($name)) {
            $data['code'] = 1;
            $data['message'][] = "等级名称不能为空！";
        }
        if (empty($desc)) {
            $data['code'] = 1;
            $data['message'][] = "等级描述不能为空！";
        }

        if ($data['code'] > 0) {
            $this->response->setJsonContent($data);
            $this->response->send();
        } else {
            $data = [];
            $rules = (array)explode(',', $rules);
            $data['name'] = $name;
            $data['rules'] = json_encode($rules);
            if ($gid) {
                $res = UserPointGrade::updateOne($data, ['id' => $gid]);
                $id = $gid;
            } else {
                $data['created'] = time();
                $ug = new UserPointGrade();
                $res = $ug->insertOne($data);
                $id = $res;
            }


            if (!$res) {
                $data['code'] = 1;
                $data['message'][] = "编辑失败";
            } else {
                $data['result'] = $id;
            }
            $this->response->setJsonContent($data);
            $this->response->send();
        }
    }

    public function groupRemoveAction()
    {
        $this->view->disable();
        if (!$this->request->isAjax()) {
            $this->response->setJsonContent(array(
                'code' => 1,
                'message' => '只接受AJAX请求!'
            ));
            $this->response->send();
        }
        $gid = $this->request->getPost('gid', 'int');
        $data = array(
            'code' => 0,
            'result' => '',
            'message' => ''
        );

        if (empty($gid)) {
            $data['code'] = 1;
            $data['message'][] = "请指定要删除的等级！";
        }

        if ($data['code'] > 0) {
            $this->response->setJsonContent($data);
            $this->response->send();
        } else {

            if (UserPointGrade::remove(['id' => $gid])) {
                $data['message'] = '删除成功!';
            } else {
                $data['code'] = 1;
                $data['message'][] = "删除失败";
            }
            $this->response->setJsonContent($data);
            $this->response->send();
        }
    }

    //积分日志
    public function pointAction()#经验值日志#
    {
        $user_id = $this->request->get('user_id', 'int', 0);
        $action = $this->request->get('action', 'int', 0);
        $username = $this->request->get('username', 'string', '');
        $start_time = $this->request->get('start_time', 'string', '');
        $end_time = $this->request->get('end_time', 'string', '');
        $page = $this->request->get('p', 'int', 1);
        $limit = 10;
        $filter = array();
        if ($user_id > 0) {
            $filter[] = "user_id =" . $user_id;
        }
        if ($action > 0) {
            $filter[] = "action =" . $action;
        }
        if ($username != "") {
            $user_ids = Users::getColumn(["username like '%" . $username . "%'"], 'id');
            if ($user_ids) {
                $filter[] = "user_id in (" . implode(',', $user_ids) . ")";
            }
        }
        if ($start_time != "") {
            $filter[] = "log.created >= '" . strtotime($start_time) . "'";
        }
        if ($end_time != "") {
            $filter[] = "log.created <= '" . (strtotime($end_time) + 86400) . "'";
        }

        if ($filter) {
            $where = " " . implode(' and ', $filter);
        } else {
            $where = "";
        }
        $list = UserPointLog::findList([$where, "limit" => $limit, 'offset' => ($page - 1) * $limit, 'order' => 'created desc']);
        $count = UserPointLog::dataCount($where);
        if ($list) {
            $uids = array_unique(array_column($list, 'user_id'));
            $user_info = Users::getByColumnKeyList(['id in (' . implode(',', $uids) . ')'], 'id');
            foreach ($list as &$item) {
                $item['username'] = $user_info[$item['user_id']]['username'];
            }
        }
        /*  $list = $this->db->query("select log.*,ifnull(u.username,'用户不存在') as username  from user_point_log as log left join users as u on log.user_id=u.id
          " . $where . " order by log.created desc limit " . (($page - 1) * $limit) . "," . $limit)->fetchAll();
          $count = $this->db->query("select count(*)  from user_point_log as log left join users as u on log.user_id=u.id
          " . $where)->fetch();*/

        Pagination::instance($this->view)->showPage($page, $count, $limit, 5);
        $this->view->setVar('list', $list);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('act', $action);
        $this->view->setVar('username', $username);
        $this->view->setVar('start_time', $start_time);
        $this->view->setVar('end_time', $end_time);
    }

    //用户详情
    public function detailAction()#用户详情
    {
        $user_id = $this->request->get('user_id', 'int', '');
        $user = [];
        if ($user_id) {
            $user = $this->db->query("select * from users as u left join user_profile as p on u.id=p.user_id where u.id=" . $user_id)->fetch(\PDO::FETCH_ASSOC);
            if ($user) {
                $user['weixin'] = '';
                $user['qq'] = '';
                //第三方账号
                $third_user = UserThirdParty::getByColumnKeyList(['user_id=' . $user_id, 'columns' => 'nick,type,open_id,union_id'], 'type');
                $user['third_party'] = $third_user;
                if ($third_user) {
                    $third_user['1'] && $user['qq'] = $third_user['1']['nick'];
                    $third_user['2'] && $user['weixin'] = $third_user['2']['nick'];
                    $user['third_type'] = $third_user['1'] && $user['qq'] ? 'QQ' : '微信';
                } else if ($user['phone']) {
                    $user['third_type'] = "手机";
                } else {
                    $user['third_type'] = "其他";
                }
                $apply = UserAuthApply::findOne(['user_id=' . $user_id, 'order' => 'created desc']);
                $user['id_card'] = '';
                if (!$apply) {
                    $user['auth_status'] = $user['is_auth'];
                } else {
                    $user['apply_info'] = $apply;
                    //已经认证
                    if ($user['is_auth'] == 1) {
                        //重新认证
                        if ($apply['status'] == AuthManager::AUTH_STATUS_SENDING) {
                            $user['auth_status'] = 2;//已认证 但又提交了认证
                        }
                        if ($apply['status'] == AuthManager::AUTH_STATUS_FAILED) {
                            $user['auth_status'] = 3;//已认证 但最后提交的认证失败了
                        }
                        if ($apply['status'] == AuthManager::AUTH_STATUS_SUCCESS) {
                            $user['auth_status'] = 1;//已认证
                        }
                    } //后台取消认证
                    else if ($user['is_auth'] == 4) {
                        $user['auth_status'] = 0;//未认证
                    } else {
                        //初次认证 认证已提交
                        if ($apply['status'] == AuthManager::AUTH_STATUS_SENDING) {
                            $user['auth_status'] = 5;
                        }
                        //初次认证 认证失败
                        if ($apply['status'] == AuthManager::AUTH_STATUS_FAILED) {
                            $user['auth_status'] = 6;//未认证 提交的认证失败了
                        }
                    }
                }
                $user['address'] = Ip::getAddress($user['last_login_ip']);
                $user['discuss_count'] = SocialDiscuss::dataCount("user_id=" . $user_id);
                $user['followers_count'] = UserAttention::dataCount("user_id=" . $user_id);
                $user['attention_count'] = UserAttention::dataCount("owner_id=" . $user_id);
                $user['friend_count'] = UserContactMember::dataCount("owner_id=" . $user_id);
                $user['report_count'] = SocialReport::dataCount("user_id=" . $user_id);
                $user['group_count'] = Group::dataCount("user_id=" . $user_id . ' and status=1');
                $user['join_group_count'] = GroupMember::dataCount("user_id=" . $user_id);
                $user['blacklist_count'] = UserBlacklist::dataCount("owner_id=" . $user_id);
                $user['to_blacklist_count'] = UserBlacklist::dataCount("user_id=" . $user_id);

                $total = UserGift::findOne(["user_id=" . $user_id, 'columns' => 'sum(own_count) as total']);
                $user['own_gift_count'] = ($total['total'] ? $total['total'] : 0);
                $user['use_gift_count'] = UserGiftLog::dataCount("owner_id=" . $user_id);
                $user['show'] = UserShow::findOne("user_id=" . $user_id);
                //   $log = UserLoginLog::findList(['user_id=' . $user_id]);
                $this->view->setVar('log', []);
            }
        }
        $agent = Agent::findOne(["user_id=" . $user_id, 'columns' => 'is_merchant,is_partner']);
        $user['agent_info'] = $agent;

        $tags = UserTags::findOne(["user_id=" . $user_id, 'columns' => 'tags_name']);
        $this->view->setVar('tags', $tags ? $tags['tags_name'] : '');
        $this->view->setVar('uid', $user_id);
        $this->view->setVar('item', $user);
    }

    //龙豆记录
    public function coinRecordAction()#龙豆记录#
    {
        $user_id = $this->request->get('user_id', 'int', 0);
        $action = $this->request->get('action', 'int', 0);
        $username = $this->request->get('username', 'string', '');
        $start_time = $this->request->get('start_time', 'string', '');
        $end_time = $this->request->get('end_time', 'string', '');
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);

        $filter = ['status=1'];
        if ($user_id > 0) {
            $filter[] = "user_id =" . $user_id;
        }
        if ($action > 0) {
            $filter[] = "action =" . $action;
        }
        if ($username != "") {

            $users = Users::getColumn(['username like "%' . $username . '%"', 'id'], 'id');
            if ($users) {
                $filter[] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($start_time != "") {
            $filter[] = "created >= '" . strtotime($start_time) . "'";
        }
        if ($end_time != "") {
            $filter[] = "created <= '" . (strtotime($end_time) + 86400) . "'";
        }

        if ($filter) {
            $where = " " . implode(' and ', $filter);
        } else {
            $where = "";
        }
        $list = UserCoinLog::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'created desc']);
        $count = UserCoinLog::dataCount($where);

        /*   $list = $this->db->query("select log.*,ifnull(u.username,'用户不存在') as username  from user_coin_log as log left join users as u on log.user_id=u.id
           " . $where . " order by log.created desc limit " . (($page - 1) * $limit) . "," . $limit)->fetchAll();
           $count = $this->db->query("select count(*)  from user_coin_log as log left join users as u on log.user_id=u.id
           " . $where)->fetch();*/

        if ($list) {
            $gift_ids = [];
            $uids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'username,user_id'], 'user_id');
            foreach ($list as &$item) {
                if ($item['action'] = PointRule::BEHAVIOR_CHARGE) {
                    $mark = json_decode($item['params'], true);
                    if ($mark['gift_id']) {
                        $gift_ids[] = $mark['gift_id'];
                    }
                }
                $item['username'] = $users[$item['user_id']]['username'];
            }
            if ($gift_ids) {
                $gift_ids = array_unique($gift_ids);
                $gift_info = SiteGift::getByColumnKeyList(['id in (' . implode(',', $gift_ids) . ')', 'columns' => 'id,name'], 'id');
                $this->view->setVar('gift_info', $gift_info);
            }
        }

        Pagination::instance($this->view)->showPage($page, $count, $limit, 5);
        $this->view->setVar('list', $list);
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('act', $action);
        $this->view->setVar('username', $username);
        $this->view->setVar('start_time', $start_time);
        $this->view->setVar('end_time', $end_time);
        $this->view->setVar('limit', $limit);
    }

    //登录日志
    public function loginLogAction()#登录日志#
    {
        $user_id = $this->request->get('user_id', 'int', 0);
        $start_time = $this->request->get('start', 'string', '');
        $end_time = $this->request->get('end', 'string', '');
        $os = $this->request->get('os', 'string', '');

        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);

        if (!$user_id) {
            $this->err("404", '无效的参数');
            return;
        }
        $params = ['user_id=' . $user_id, 'order' => 'login_time desc', 'columns' => 'login_time,client_type,os,app_version,phone_model,client_ip,login_type', 'offset' => ($page - 1) * $limit, 'limit' => $limit];

        if ($os) {
            $params[0] .= " and os='" . $os . "'";
        }
        if ($start_time) {
            $params[0] .= " and login_time >=" . strtotime($start_time);
        }
        if ($end_time) {
            $params[0] .= " and login_time <=" . strtotime($end_time);
        }
        $count = UserLoginLog::dataCount($params[0]);
        $list = UserLoginLog::findList($params);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        if ($list) {
            $ip = array_unique(array_filter(array_column($list, 'client_ip')));
            $ips = [];
            foreach ($ip as $i) {
                $ips[$i] = Ip::getAddress($i);
            }
            foreach ($list as &$item) {
                if ($item['client_ip']) {
                    $item['login_address'] = $ips[$item['client_ip']];
                } else {
                    $item['login_address'] = [];
                }
            }
        }
        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('start', $start_time);
        $this->view->setVar('end', $end_time);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('os', $os);

    }

    //在线日志
    public function onlineAction()#在线日志#
    {
        $user_id = $this->request->get('user_id', 'int', 0);
        $start_time = $this->request->get('start', 'string', '');
        $end_time = $this->request->get('end', 'string', '');
        $online = $this->request->get('online', 'string', '');

        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$user_id) {
            $this->err("404", '无效的参数');
            return;
        }
        $params = ['user_id=' . $user_id, 'order' => 'login_time desc', 'columns' => 'login_time,logout_time,login_ip,logout_ip', 'offset' => ($page - 1) * $limit, 'limit' => $limit];

        if ($online) {
            $params[0] .= " and ((login_time<=" . strtotime($online) * 1000 . " and logout_time>=" . strtotime($online) * 1000 . ") or (login_time<=" . strtotime($online) * 1000 . " and logout_time=0))";
        } else {
            if ($start_time) {
                $params[0] .= " and login_time>=" . strtotime($start_time) * 1000;
            }
            if ($end_time) {
                $params[0] .= " and logout_time <=" . strtotime($end_time) * 1000;
            }
        }

        $count = UserOnline::dataCount($params[0]);
        $list = UserOnline::findList($params);
        Pagination::instance($this->view)->showPage($page, $count, $limit);

        if ($list) {
            $ip = array_unique(array_filter(array_column($list, 'login_ip')));
            $ips = [];
            foreach ($ip as $i) {
                $ips[$i] = Ip::getAddress($i);
            }
            foreach ($list as &$item) {
                if ($item['login_ip']) {
                    $item['login_address'] = $ips[$item['login_ip']];
                } else {
                    $item['login_address'] = [];
                }
            }
        }

        $this->view->setVar('user_id', $user_id);
        $this->view->setVar('start', $start_time);
        $this->view->setVar('end', $end_time);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('list', $list);
        $this->view->setVar('online', $online);
    }

    //聊天记录
    public function messageAction()#聊天记录#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        if (!$user_id) {
            $this->err(404, "无效的用户id");
        }
        $this->view->setVar('uid', $user_id);
    }

}