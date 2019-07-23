<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/8
 * Time: 11:06
 */

namespace Community;


use Multiple\Api\Controllers\ControllerBase;
use Services\Community\CommunityGroupManager;
use Util\Ajax;

class GroupController extends ControllerBase
{
    //检测社群名是否可用
    public function checkNameAction()
    {
        $uid = $this->uid;
        $name = $this->request->get("name", 'string', '');
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$name || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->checkName($uid, $comm_id, $name);
        if ($res) {
            $this->ajax->outRight(1);
        }
        $this->ajax->outRight(0);
    }

    //我创建的社群
    public function myGroupAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $to_uid = $this->request->get("to_uid", 'int', 0);
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$comm_id || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->myGroup($uid, $to_uid, $comm_id, $page, $limit);
        $this->ajax->outRight($res);
    }

    //群聊列表
    public function groupListAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $comm_id = $this->request->get("comm_id", 'int', 0);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->groupList($uid, $comm_id, $page, $limit);
        $this->ajax->outRight($res);
    }

    //创建群聊
    public function addAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        $name = $this->request->get("name", 'string', '');
        $to_uid = $this->request->get("to_uid", 'string', '');
        $avatar = $this->request->get("avatar", 'string', '');
        $private = $this->request->get("is_private", 'string', 0);
        $is_apply = $this->request->get("is_apply", 'int', 0);//普通成员只能申请建群
        $introduce = $this->request->get("introduce", 'string', '');//群简介

        if (!$uid || !$to_uid || !$comm_id || !$name || !$avatar || !in_array($private, [0, 1])) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->createGroup($uid, $to_uid, $comm_id, $avatar, $name, $private, $is_apply, $introduce);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_ADD_GROUP);
        } else {
            $this->ajax->outRight($res);
        }
    }

    //邀请好友加入群聊
    public function inviteAction()
    {
        $uid = $this->uid;//邀请人id
        $to_uid = $this->request->get('to_uid', 'string', '');//被邀请人id
        $gid = $this->request->get('gid', 'int', 0);//群id

        if (!$uid || !$gid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->inviteGroup($uid, $to_uid, $gid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_INVITE_GROUP);
        } else {
            $this->ajax->outRight('邀请成功', Ajax::SUCCESS_INVITE);
        }
    }

    /*--加入群聊--*/
    public function joinAction()
    {
        $uid = $this->uid;//加入人id
        $invitor = $this->request->get('invitor', 'int', 0);//邀请者
        $gid = $this->request->get('gid', 'int', 0);//群id
        $join_type = $this->request->get("join_type", 'int', 2);//方式 2-扫码主动加入 3-主动申请加入 4-被人邀请【邀请链接】

        if (!$uid || !$gid || !in_array($join_type, [2, 3, 4])) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->joinGroup($uid, $invitor, $gid, $join_type);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_JOIN_GROUP);
        } else {
            $this->ajax->outRight('提交成功', Ajax::SUCCESS_HANDLE);
        }
    }

    /*--退出群聊--*/
    public function leaveAction()
    {
        $uid = $this->uid;//退出人id
        $gid = $this->request->get('gid', 'int', 0);//群id
        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->leaveGroup($uid, $gid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_LEAVE_GROUP);
        } else {
            $this->ajax->outRight('退出成功', Ajax::SUCCESS_LEAVE);
        }
    }

    /*--加入社群申请审核--*/
    public function checkJoinAction()
    {
        $uid = $this->uid;//审核人
        $gid = $this->request->get('gid', 'int', 0);//群id
        $to_uid = $this->request->get('to_uid', 'int', 0);//被同意加入的人
        $pass = $this->request->get('pass', 'int', 1);//0-不同意 1-同意
        $apply_id = $this->request->get('apply_id', 'int', 0);//审核id

        if (!$uid || !$gid || !$to_uid || !$apply_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->agreeJoin($uid, $gid, $to_uid, $pass, $apply_id);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        } else {
            $this->ajax->outRight('操作成功', Ajax::SUCCESS_HANDLE);
        }
    }

    /*编辑群信息
     * */
    public function editAction()
    {
        $uid = $this->uid;//创建人uid
        $gid = $this->request->get('gid', 'int', 0);//群id
        $avatar = $this->request->get('avatar', 'string', '');//app端生成的群头像
        $name = $this->request->get("name", 'green', '');//群名称
        $is_private = $this->request->get("is_private", 'int', -1);//是否公开

        $join_mode = $this->request->get("join_mode", 'int', -1);//加入群聊限制 0-不需要验证 2-不允许主动加入
        $invite_mode = $this->request->get("invite_mode", 'int', -1); //邀请人限制 0-管理员 1-所有人 2-不允许任何人
        $beinvite_mode = $this->request->get("beinvite_mode", 'int', -1); //被邀请人权限 0-需要同意 1-不需要同意

        if (!$uid || !$gid || (!$avatar && !$name && $join_mode == -1 && $invite_mode == -1 && $beinvite_mode == -1 && $is_private == -1)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!in_array($join_mode, [-1, 0, 1, 2]) || !in_array($invite_mode, [-1, 0, 1, 2]) || !in_array($beinvite_mode, [-1, 0, 1])) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (CommunityGroupManager::getInstance()->edit($uid, $gid, $avatar, $name, $join_mode, $invite_mode, $beinvite_mode, $is_private)) {
            $this->ajax->outRight("编辑成功", Ajax::SUCCESS_EDIT);
        } else {
            $this->ajax->outError(Ajax::FAIL_EDIT);
        }
    }

    /*--解散群聊--*/
    public function dissolveAction()
    {
        $uid = $this->uid;//群主或区长
        $gid = $this->request->get('gid', 'int', 0);//群id
        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->dissolveGroup($uid, $gid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_DISSOLVE_GROUP);
        } else {
            $this->ajax->outRight('解散成功', Ajax::SUCCESS_DISSOLVE);
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
        $res = CommunityGroupManager::getInstance()->changeOwner($uid, $to_uid, $gid);
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

        if (!$uid || !$gid || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($uid == $to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->kickMember($uid, $to_uid, $gid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_KICK_MEMBER);
        } else {
            $this->ajax->outRight('删除成功', Ajax::SUCCESS_DELETE);
        }
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
        $res = CommunityGroupManager::getInstance()->groupMember($uid, $gid, $page, $limit);
        $this->ajax->outRight($res);
    }

    /**
     *普通社区成员创建社群审核
     */
    public function checkGroupCreateAction()
    {
        $uid = $this->uid;//审核人
        $pass = $this->request->get('pass', 'int', 1);//0-不同意 1-同意
        $apply_id = $this->request->get('apply_id', 'int', 0);//审核id

        if (!$uid || !$apply_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        CommunityGroupManager::getInstance()->checkGroupCreateApply($uid, $apply_id, $pass);
    }

    /**
     * 禁言
     */
    public function muteAction()
    {
        $uid = $this->uid;//操作人
        $gid = $this->request->get('gid', 'int', 0);//群id
        $to_uid = $this->request->get("to_uid", 'int', 0);
        $type = $this->request->get("type", 'int', 1);//1-禁言 0-取消禁言
        if (!$uid || !$gid || ($type != 0 && $type != 1) || !$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (CommunityGroupManager::getInstance()->mute($uid, $to_uid, $gid, $type)) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        } else {
            $this->ajax->outError(Ajax::FAIL_HANDLE);
        }
    }

    //社群详情
    public function groupInfoAction()
    {
        $uid = $this->uid;//操作人
        $gid = $this->request->get('gid', 'int', 0);//群id
        $yx_gid = $this->request->get('yx_gid', 'int', 0);
        if (!$uid || (!$gid && !$yx_gid)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityGroupManager::getInstance()->groupInfo($uid, $gid, $yx_gid);
        $this->ajax->outRight($res);
    }


}