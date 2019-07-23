<?php
/**
 *
 * 群聊相关
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/5
 * Time: 15:44
 */

namespace Multiple\Api\Controllers;

use Models\Group\Group;
use Services\User\GroupManager;
use Util\Ajax;
use Util\Debug;

class GroupController extends ControllerBase
{
    /*--创建群聊--*/
    public function addAction()
    {
        $uid = $this->uid;//创建人uid
        $to_uid = $this->request->get('to_uid', 'string', '');//加入人uid,如122,1234,7888
        $avatar = $this->request->get('avatar', 'string', '');//app端生成的群头像
        $join_mode = $this->request->get('join_mode', 'int', 0);//加入群聊限制 0-不需要验证 2-不允许主动加入
        $invite_mode = $this->request->get('invite_mode', 'int', 1);//邀请人限制 0-管理员 1-所有人

        if (!$uid || $to_uid == "" || !$avatar) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!in_array($join_mode, [0, 2])) {
            $join_mode = 0;
        }
        if (!in_array($invite_mode, [0, 1])) {
            $join_mode = 1;
        }
        $res = GroupManager::init()->addGroup($uid, $to_uid, $avatar, $join_mode, $invite_mode);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_ADD_GROUP);
        } else {
            $this->ajax->outRight($res);
        }
    }

    /*--编辑群聊信息--*/
    public function editAction()
    {
        $uid = $this->uid;//创建人uid
        $gid = $this->request->get('gid', 'int', 0);//群id
        $avatar = $this->request->get('avatar', 'string', '');//app端生成的群头像
        $name = $this->request->get("name", 'green', '');//群名称
        $join_mode = $this->request->get("join_mode", 'int', -1);//加入群聊限制 0-不需要验证 2-不允许主动加入
        $invite_mode = $this->request->get("invite_mode", 'int', -1); //邀请人限制 0-管理员 1-所有人 2-不允许任何人
        $beinvite_mode = $this->request->get("beinvite_mode", 'int', -1); //被邀请人权限 0-需要同意 1-不需要同意

        if (!$uid || !$gid || (!$avatar && !$name && $join_mode == -1 && $invite_mode == -1 && $beinvite_mode == -1)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!in_array($join_mode, [-1, 0, 1, 2]) || !in_array($invite_mode, [-1, 0, 1, 2]) || !in_array($beinvite_mode, [-1, 0, 1])) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (GroupManager::init()->edit($uid, $gid, $avatar, $name, $join_mode, $invite_mode, $beinvite_mode)) {
            $this->ajax->outRight("编辑成功", Ajax::SUCCESS_EDIT);
        } else {
            $this->ajax->outError(Ajax::FAIL_EDIT);
        }
    }

    /**
     * 禁言
     */
    public function muteAction()
    {
        $uid = $this->uid;//操作人
        $gid = $this->request->get('gid', 'int', 0);//群id
        $to_uid = $this->request->get("to_uid", 'int', 0);
        $type = $this->request->get("type", 'int', 1);//禁言 0-取消禁言
        if (!$uid || !$gid || ($type != 0 && $type != 1)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (GroupManager::init()->mute($uid, $to_uid, $gid, $type)) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        } else {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
    }

    /*--加入群聊--*/
    public function joinAction()
    {
        $uid = $this->uid;//加入人id
        $invitor = $this->request->get('invitor', 'int', 0);//邀请者
        $gid = $this->request->get('gid', 'int', 0);//群id
        $avatar = $this->request->get('avatar', 'string', '');//app端生成的群头像
        if (!$uid || !$gid || !$invitor) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->joinGroup($uid, $invitor, $gid, $avatar);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_JOIN_GROUP);
        } else {
            $this->ajax->outRight('加入成功', Ajax::SUCCESS_JOIN);
        }
    }

    /*--解散群聊--*/
    public function dissolveAction()
    {
        $uid = $this->uid;//加入人id
        $gid = $this->request->get('gid', 'int', 0);//群id
        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->dissolveGroup($uid, $gid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_DISSOLVE_GROUP);
        } else {
            $this->ajax->outRight('解散成功', Ajax::SUCCESS_DISSOLVE);
        }
    }

    /*--退出群聊--*/
    public function leaveAction()
    {
        $uid = $this->uid;//退出人id
        $gid = $this->request->get('gid', 'int', 0);//群id
        $avatar = $this->request->get('avatar', 'string', '');//更新后的群头像
        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->leaveGroup($uid, $gid, $avatar);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_LEAVE_GROUP);
        } else {
            $this->ajax->outRight('退出成功', Ajax::SUCCESS_LEAVE);
        }
    }

    /*--邀请好友加入--*/
    public function inviteAction()
    {
        $uid = $this->uid;//邀请人id
        $to_uid = $this->request->get('to_uid', 'string', '');//被邀请人id
        $gid = $this->request->get('gid', 'int', 0);//群id
        $avatar = $this->request->get('avatar', 'string', '');//群头像

        if (!$uid || !$gid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->inviteGroup($uid, $to_uid, $gid, $avatar);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_INVITE_GROUP);
        } else {
            $this->ajax->outRight('邀请成功', Ajax::SUCCESS_INVITE);
        }
    }

    /*--群主转让--*/
    public function changeOwnerAction()
    {
        $uid = $this->uid;//操作人uid
        $to_uid = $this->request->get('to_uid', 'int', '');//新群主uid
        $gid = $this->request->get('gid', 'int', 0);//群id

        if (!$uid || !$gid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->changeOwner($uid, $to_uid, $gid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_TRANSFER_GROUP);
        } else {
            $this->ajax->outRight('转让成功', Ajax::SUCCESS_TRANSFER);
        }
    }

    /*--踢人出群--*/
    public function kickAction()
    {
        $uid = $this->uid;//操作人uid
        $to_uid = $this->request->get('to_uid', 'string', '');//被踢的成员
        $gid = $this->request->get('gid', 'int', 0);//群id
        $avatar = $this->request->get('avatar', 'string', '');//群头像

        if (!$uid || !$gid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($uid == $to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->kickMember($uid, $to_uid, $gid, $avatar);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_KICK_MEMBER);
        } else {
            $this->ajax->outRight('删除成功', Ajax::SUCCESS_DELETE);
        }
    }

    /*--修改群名片--*/
    public function updateNickAction()
    {
        $uid = $this->uid;//操作人uid
        $to_uid = $this->request->get('to_uid', 'int', '');//被操作人uid
        $gid = $this->request->get('gid', 'int', 0);//群id
        $nick = $this->request->get('nick', 'green', '');//昵称

        if (!$uid || !$gid || !$to_uid || !$nick) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->updateNick($uid, $to_uid, $gid, $nick);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_EDIT);
        } else {
            $this->ajax->outRight('编辑成功', Ajax::SUCCESS_EDIT);
        }
    }

    /*--发布/修改群公告--*/
    public function setAnnouncementAction()
    {
        $uid = $this->uid;//操作人uid
        $an_id = $this->request->get('an_id', 'int', 0);//公告id  //
        $gid = $this->request->get('gid', 'int', 0);//群id
        $content = $this->request->get('content', 'string', '');//公告内容

        if (!$uid || !$gid || !$content) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->setAnnouncement($uid, $gid, $content, $an_id);
        if (!$res) {
            $this->ajax->outError($an_id ? Ajax::FAIL_EDIT : Ajax::FAIL_ADD);
        } else {
            $this->ajax->outRight('编辑成功', $an_id ? Ajax::SUCCESS_EDIT : Ajax::SUCCESS_ADD);
        }
    }

    /*--删除群公告--*/
    public function removeAnnouncementAction()
    {
        $uid = $this->uid;//操作人uid
        $an_ids = $this->request->get('an_id', 'string', '');//公告id  //多个以，分割
        $gid = $this->request->get('gid', 'int', 0);//群id

        if (!$uid || !$gid || !$an_ids) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->removeAnnouncementList($uid, $gid, $an_ids);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_DELETE);
        } else {
            $this->ajax->outRight('删除成功', Ajax::SUCCESS_DELETE);
        }
    }

    /*--获取群公告列表--*/
    public function announcementListAction()
    {
        $uid = $this->uid;//请求人uid
        $gid = $this->request->get('gid', 'int', 0);//群id

        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!GroupManager::init()->memberExists($gid, $uid)) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        $res = GroupManager::init()->announcementList($gid);
        $this->ajax->outRight($res);
    }

    /*--获取群公告详情--*/
    public function announcementDetailAction()
    {
        $uid = $this->uid;//请求人uid
        $gid = $this->request->get('gid', 'int', '');//群id
        $an_id = $this->request->get('an_id', 'int', '');//群公告id

        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!GroupManager::init()->memberExists($gid, $uid)) {
            $this->ajax->outError(Ajax::ERROR_GROUP_NOT_MEMBER);
        }
        $res = GroupManager::init()->announcementDetail($gid, $an_id);
        if (!$res) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $this->ajax->outRight($res);
    }

    /*--我的群聊列表--*/
    public function myGroupAction()
    {
        $uid = $this->uid;//获取人uid
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->getGroupList($uid);
        $this->ajax->outRight($res);
    }

    /*--群成员列表--*/
    public function groupMemberAction()
    {
        $uid = $this->uid;//获取人uid
        $page = $this->request->get('page', 'int', 0);
        $gid = $this->request->get('gid', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->groupMember($uid, $gid, $page, $limit);
        $this->ajax->outRight($res);
    }

    //单个群成员信息
    public function memberAction()
    {
        $uid = $this->uid;//获取人uid
        $gid = $this->request->get('gid', 'int', 0);
        $to_uid = $this->request->get('to_uid', 'int', 0);
        if (!$gid || !$uid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->singleMember($uid, $to_uid, $gid);
        $this->ajax->outRight($res);

    }

    /*--获取群详情--*/
    public function groupInfoAction()
    {
        $uid = $this->uid;//获取人uid
        $gid = $this->request->get('gid', 'int', 0);
        $yx_gid = $this->request->get('yx_gid', 'int', 0);
        if (!$uid || (!$gid && !$yx_gid)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->groupInfo($uid, $gid, $yx_gid);
        $this->ajax->outRight($res);
    }

    /*--编辑群名称--*/
    public function editGroupNameAction()
    {
        $uid = $this->uid;//编辑人uid
        $gid = $this->request->get('gid', 'int', 0);
        $name = $this->request->get('name', 'green', '');//群名称
        if (!$uid || !$gid || !$name) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->editGroupName($uid, $gid, mb_substr($name, 0, 20));
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_EDIT);
        }
        $this->ajax->outRight("编辑成功", Ajax::SUCCESS_EDIT);
    }

    /*--举报群--*/
    public function reportAction()
    {
        $uid = $this->uid;//举报人uid
        $to_uid = $this->request->get('to_uid', 'int', 0);//被举报人

        $gid = $this->request->get('gid', 'int', 0);//群id
        $reason_id = $this->request->get('reason_id', 'int', 0);
        $imgs = $this->request->get('imgs', 'string', '');//证据图片
        if (!$uid || !$gid || !$reason_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!GroupManager::init()->report($uid, $to_uid, $gid, $reason_id, $imgs)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        } else {
            $this->ajax->outRight("举报成功", Ajax::SUCCESS_REPORT);

        }
    }

    //群升级
    public function upgradeAction()
    {
        $uid = $this->uid;//提交人uid
        $gid = $this->request->get('gid', 'int', 0);
        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!GroupManager::init()->upgrade($gid, $uid)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
        $this->ajax->outRight(Ajax::SUCCESS_HANDLE);
    }

    //任命管理员
    public function addManagerAction()
    {
        $uid = $this->uid;//提交人uid
        $to_uid = $this->request->get("to_uid", 'int', 0);
        $gid = $this->request->get("gid", 'int', 0);
        if (!$uid || !$to_uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($uid == $to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "对象不能是自己");
        }
        if (!GroupManager::init()->addManager($uid, $to_uid, $gid)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE, "操作失败");
        } else {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        }
    }

    //移除管理员
    public function removeManagerAction()
    {
        $uid = $this->uid;//提交人uid
        $to_uid = $this->request->get("to_uid", 'int', 0);
        $gid = $this->request->get("gid", 'int', 0);
        if (!$uid || !$to_uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($uid == $to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM, "对象不能是自己");
        }
        if (!GroupManager::init()->removeManager($uid, $to_uid, $gid)) {
            $this->ajax->outError(Ajax::FAIL_HANDLE, "操作失败");
        } else {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        }
    }

    //清空群消息记录
    public function rmHistoryMsgAction()
    {
        $uid = $this->uid;//请求删除记录uid
        $gid = $this->request->get('gid','string',0);
        if (!$uid  || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if(GroupManager::init()->rmHistoryMsg($uid,$gid))
        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        else
            $this->ajax->outError(Ajax::FAIL_HANDLE, "操作失败");

    }
}
