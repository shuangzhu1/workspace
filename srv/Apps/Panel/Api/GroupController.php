<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/27
 * Time: 14:29
 */

namespace Multiple\Panel\Api;


use Models\Group\Group;
use Models\Group\GroupMember;
use Models\User\Users;
use Services\Admin\AdminLog;
use Services\Im\SysMessage;
use Services\User\GroupManager;
use Util\Ajax;

class GroupController extends ApiBase
{
    /*封杀群*/
    public function delAction()
    {
        $id = $this->request->get('data');
        $reason = $this->request->get('reason');

        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$reason) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ['status' => GroupManager::GROUP_STATUS_LOCKED, 'modify' => time()];
        //更新群组状态
        foreach ($id as $item) {
            $group = Group::findOne(['id=' . $item,'columns'=>'yx_gid']);
            if ($group) {
                if (Group::updateOne(['status' => $data['status'], 'modify' => time()], ['id' => $item])) {
                    AdminLog::init()->add('封杀群组', AdminLog::TYPE_GROUP, $item, array('type' => "update", 'id' => $item, 'reason' => $reason));
                    //发送群系统消息
                    SysMessage::init()->initMsg(SysMessage::TYPE_GROUP_DISMISS, ['gid' => $item, 'yx_gid' => $group['yx_gid']]);
                }
            }
        }

        $this->ajax->outRight("");

    }

    /*恢复正常*/
    public function recoveryAction()
    {
        $id = $this->request->get('data');
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //更新群组状态

        $data = ['status' => GroupManager::GROUP_STATUS_NORMAL, 'modify' => time()];
        $ids = implode(',', $id);

        if ($this->db->query("update `group` set status=" . $data['status'] . ', modify=' . $data['modify'] . ' where id in (' . $ids . ')')->execute()) {
            AdminLog::init()->add('群组恢复正常', AdminLog::TYPE_GROUP, $ids, array('type' => "update", 'id' => $id, 'data' => $data));
        }
        $this->ajax->outRight("");
    }

    public function memberListAction()
    {
        $gid = $this->request->get('gid', 'int', 1);
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $group = Group::findOne(['id=' . $gid, 'columns' => 'user_id as uid']);
        $page = $page == 0 ? 1 : $page + 1;
        $group_member = GroupMember::findList(['gid=' . $gid, 'columns' => 'user_id as uid,nick,default_nick,created', 'offset' => ($page - 1) * $limit, 'limit' => $limit]);
        $users = Users::getByColumnKeyList(['id in (' . implode(',', array_column($group_member, 'uid')) . ')', 'columns' => 'id as uid,avatar'], 'uid');
        foreach ($group_member as &$m) {
            $m['avatar'] = $users[$m['uid']]['avatar'];
        }
        $data = '';
        foreach ($group_member as $n) {
            $data .= $this->getFromOB("group/partial/member", ['member_item' => $n, 'uid' => $group['uid']]);
        }
        $this->ajax->outRight($data);
    }
}