<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/5
 * Time: 11:40
 */

namespace Services\User;


use Models\User\UserInfo;
use Models\User\UserInviter;
use Models\User\Users;
use Models\User\UserWelfare;
use Models\User\UserWelfareLog;
use Phalcon\Mvc\User\Plugin;
use Util\Ajax;
use Util\Debug;

class WelfareManager extends Plugin
{
    private static $instance = null;
    private static $point = 10; //每邀请一个用户注册并激活送10个公益积分
    private static $register_point = 5; //每被邀请注册送5个公益积分

    const IN_OUT_IN = 1; //收入
    const IN_OUT_OUT = 2; //支出

    const TYPE_INVITE = 1;//邀请
    const TYPE_CHANGE = 2;//兑换
    const TYPE_REGISTER = 3;//注册

    const ACTIVE_CODE = 1;//扫码激活
    const ACTIVE_LOGIN = 2;//登录激活


    private static $type_name = [
        self::TYPE_INVITE => "邀请注册",
        self::TYPE_REGISTER => "邀请注册",
        self::TYPE_CHANGE => "兑换",

    ];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**设置推荐人
     * @param $inviter
     * @param $uid
     * @return array
     */
    public function add($inviter, $uid)
    {
        //目标用户不能是自己
        if ($uid == $inviter) {
            return ["result" => 0, 'code' => Ajax::ERROR_TARGET_CAN_NOT_YOURSELF];
        }
        ////你已经设置过推荐人了
        if (UserInviter::exist("user_id=" . $uid)) {
            return ["result" => 0, 'code' => Ajax::ERROR_INVITER_HAS_INVITER];
        }
        if (!Users::exist('id=' . $inviter)) {
            return ["result" => 0, 'code' => Ajax::ERROR_DATA_NOT_EXISTS];
        }
        if ($inviter_info = UserInviter::findOne(["user_id=" . $inviter, 'columns' => 'parent_inviter,top_inviter'])) {
            $parent_inviter = $inviter_info['parent_inviter'];
            //推荐人不能为下级用户
            if ($parent_inviter && strpos($parent_inviter . ",", $uid) !== false) {
                return ["result" => 0, 'code' => Ajax::ERROR_INVITER_IS_LOWER];
            }
        }
        $top_inviter = !empty($parent_inviter) ? $inviter_info['top_inviter'] : $inviter;
        $parent_inviter = !empty($parent_inviter) ? $parent_inviter . "," . $inviter : $inviter;
        $data = [
            'user_id' => $uid,
            'inviter' => $inviter,
            'parent_inviter' => $parent_inviter,
            'created' => time(),
            'top_inviter' => $top_inviter,
            'point' => self::$point,
            'register_point' => self::$register_point
        ];
        try {
            $this->di->getShared("original_mysql")->begin();
            if (!UserInviter::insertOne($data)) {
                throw  new \Exception("插入数据失败");
            }
            //更新儿子节点
            if (UserInviter::exist("top_inviter=" . $uid)) {
                if (!$this->di->getShared("original_mysql")->query("update user_inviter set parent_inviter=concat('" . $data['parent_inviter'] . "',',',parent_inviter),top_inviter=" . $data['top_inviter'] . " where top_inviter=" . $uid)) {
                    throw  new \Exception("更新子节点失败");
                }
            }
            $this->di->getShared("original_mysql")->commit();
            return ["result" => 1, 'code' => Ajax::SUCCESS_HANDLE];
        } catch (\Exception $e) {
            $this->di->getShared("original_mysql")->rollback();
            Debug::log("设置推荐人失败:" . var_export($e->getMessage(), true), 'error');
            return ["result" => 0, 'code' => Ajax::FAIL_HANDLE];
        }
    }

    //用户激活
    /**激活用户
     * @param $uid
     * @param $type -激活方式
     * @return bool
     */
    public function activate($uid, $type = self::ACTIVE_LOGIN)
    {
        try {
            $this->di->getShared("original_mysql")->begin();
            $user_inviter = UserInviter::findOne(['user_id=' . $uid . " and is_active=0", 'columns' => 'inviter,point,register_point,active_type']);
            if ($user_inviter) {

                $user_welfare = UserWelfare::findOne(['user_id=' . $user_inviter['inviter'], 'columns' => 'current_val,total_val'], true);
                if (!$user_welfare) {
                    $data = ['user_id' => $user_inviter['inviter'], 'total_val' => $user_inviter['point'], 'current_val' => $user_inviter['point'], 'created' => time()];
                    if (!UserWelfare::insertOne($data)) {
                        throw new \Exception("用户公益表user_welfare插入失败:" . var_export($data, true));
                    }
                    $total_val = 0;
                    $current_val = 0;

                } else {
                    $data = ['current_val' => 'current_val+' . $user_inviter['point'], 'total_val' => 'total_val+' . $user_inviter['point'], 'modify' => time()];
                    if (!UserWelfare::updateOne($data, 'user_id=' . $user_inviter['inviter'])) {
                        throw new \Exception("用户公益表user_welfare更新失败:" . var_export($data, true));
                    }
                    $total_val = $user_welfare['total_val'];
                    $current_val = $user_welfare['current_val'];
                }
                //插入邀请人爱心值日志
                $data_log = [
                    'user_id' => $user_inviter['inviter'],
                    'item_id' => $uid,
                    'type' => self::TYPE_INVITE,
                    'in_out' => self::IN_OUT_IN,
                    'created' => time(),
                    'total_val' => $total_val,
                    'current_val' => $current_val,
                    'point' => $user_inviter['point'],
                    'sub_type' => $type,
                ];
                if (!UserWelfareLog::insertOne($data_log)) {
                    throw new \Exception("用户公益值变化记录失败:" . var_export($data_log, true));
                }

                if ($user_inviter['register_point']) {
                    //给激活用户送爱心值
                    $register_user_welfare = UserWelfare::findOne(['user_id=' . $uid, 'columns' => 'current_val,total_val'], true);

                    if (!$register_user_welfare) {
                        $register_data = ['user_id' => $uid, 'total_val' => $user_inviter['register_point'], 'current_val' => $user_inviter['register_point'], 'created' => time()];
                        if (!UserWelfare::insertOne($register_data)) {
                            throw new \Exception("注册用户公益表user_welfare插入失败:" . var_export($register_data, true));
                        }
                        $register_total_val = 0;
                        $register_current_val = 0;
                    } else {
                        $register_data = ['current_val' => 'current_val+' . $user_inviter['register_point'], 'total_val' => 'total_val+' . $user_inviter['register_point'], 'modify' => time()];
                        if (!UserWelfare::updateOne($register_data, 'user_id=' . $uid)) {
                            throw new \Exception("注册用户公益表user_welfare更新失败:" . var_export($register_data, true));
                        }
                        $register_total_val = $register_user_welfare['total_val'];
                        $register_current_val = $register_user_welfare['current_val'];
                    }
                    //插入注册用户爱心值日志
                    $data_log = [
                        'user_id' => $uid,
                        'item_id' => $user_inviter['inviter'],
                        'type' => self::TYPE_REGISTER,
                        'in_out' => self::IN_OUT_IN,
                        'created' => time(),
                        'total_val' => $register_total_val,
                        'current_val' => $register_current_val,
                        'point' => $user_inviter['register_point'],
                        'sub_type' => $type,
                    ];
                    if (!UserWelfareLog::insertOne($data_log)) {
                        throw new \Exception("注册用户公益值变化记录失败:" . var_export($data_log, true));
                    }
                }

                //更新记录
                if (!UserInviter::updateOne(['is_active' => 1, 'modify' => time(), 'active_type' => $type], 'user_id=' . $uid)) {
                    throw new \Exception("用户公益user_inviter更新失败:" . var_export($data, true));
                }
            }
            $this->di->getShared("original_mysql")->commit();
            return true;
        } catch (\Exception $e) {
            $this->di->getShared("original_mysql")->rollback();
            Debug::log("激活用户 更新公益值失败:" . var_export($e->getMessage(), true), 'error');
            return false;
        }

    }

    //
    /**邀请记录
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function inviteRecord($uid, $page = 1, $limit = 20)
    {
        $res = ['data_list' => []];
        $where = "inviter=" . $uid;
        ///  $res['data_count'] = UserInviter::dataCount($where);
        $list = UserInviter::findList([$where, 'order' => 'created desc', 'columns' => 'user_id as uid,is_active,point,created', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        if ($list) {
            $uids = array_column($list, 'uid');
            $user_infos = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id as uid,username,phone'], 'uid');
            foreach ($list as $item) {
                $item['phone'] = $user_infos[$item['uid']]['phone'];
                $item['username'] = $user_infos[$item['uid']]['username'];
                $res['data_list'][] = $item;
            }
        }
        return $res;
    }

    //我的公益信息
    public function myInfo($uid)
    {
        $info = ['current_point' => 0, 'use_point' => 0, 'level' => 0, 'level_label' => '', 'my_inviter' => (object)[]];
        $welfare = UserWelfare::findOne(['user_id=' . $uid, 'columns' => 'total_val as total,current_val as current']);
        if ($welfare) {
            $info['current_point'] = intval($welfare['current']);
            $info['use_point'] = intval($welfare['total'] - $info['current_point']);
        }
        $inviter = UserInviter::findOne(['user_id=' . $uid, 'columns' => 'inviter,created']);
        if ($inviter) {
            $user_info = UserInfo::findOne(['user_id=' . $inviter['inviter'], 'columns' => 'user_id as uid,avatar,username']);
            $user_info['time'] = $inviter['created'];
            $info['my_inviter'] = $user_info;
        }


        return $info;
    }

    /**获取爱心值记录
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function history($uid, $page = 1, $limit = 20)
    {
        $data = ['data_list' => []];
        $where = "user_id=" . $uid;
        $list = UserWelfareLog::findList([$where, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'columns' => 'type,item_id,in_out,created,point,sub_type', 'order' => 'created desc']);
        if ($list) {
            $uids = [];//用户id集合
            $project_ids = [];//项目id集合
            foreach ($list as $item) {
                if ($item['type'] == self::TYPE_INVITE) {
                    if (!in_array($item['item_id'], $uids)) {
                        $uids[] = $item['item_id'];
                    }
                } else if ($item['type'] == self::TYPE_REGISTER) {
                    if (!in_array($item['item_id'], $uids)) {
                        $uids[] = $item['item_id'];
                    }
                } else {
                    if (!in_array($item['item_id'], $project_ids)) {
                        $project_ids[] = $item['item_id'];
                    }
                }
            }
            if ($uids) {
                $user_infos = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id as uid,username,phone'], 'uid');
            }
            if ($project_ids) {
                $project_infos = [];
            }
            foreach ($list as $item) {
                if ($item['type'] == self::TYPE_INVITE) {
                    $item['info'] = [
                        'username' => $user_infos[$item['item_id']]['username'],
                        'uid' => $user_infos[$item['item_id']]['uid'],
                        'phone' => $user_infos[$item['item_id']]['phone'],
                    ];
                } else if ($item['type'] == self::TYPE_REGISTER) {
                    $item['info'] = [
                        'username' => $user_infos[$item['item_id']]['username'],
                        'uid' => $user_infos[$item['item_id']]['uid'],
                    ];
                } else {
                    $item['info'] = [
                        'project_id' => $project_infos[$item['item_id']]['id'],
                        'project_name' => $user_infos[$item['item_id']]['name'],
                    ];
                }
                $data['data_list'][] = $item;
            }
        }
        return $data;
    }
}