<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/11
 * Time: 13:41
 */

namespace Window;


use Multiple\Api\Merchant\Helper\Ajax;
use Services\User\GroupManager;

class GroupController extends ControllerBase
{
    public function listAction()
    {
        $uid = $this->request->getPost("uid", 'int', 0);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->getGroupList($uid);
        $this->ajax->outRight($res);
    }

    /*--群成员列表--*/
    public function groupMemberAction()
    {
        $uid = $this->request->getPost("uid", 'int', 0);
        $page = $this->request->get('page', 'int', 0);
        $gid = $this->request->get('gid', 'int', 0);
        $limit = $this->request->get('limit', 'int', 500);
        if (!$uid || !$gid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->groupMember($uid, $gid, $page, $limit);
        $this->ajax->outRight($res);
    }

    public function getGroupInfoAction()
    {
        $uid = $this->request->getPost("uid", 'int', 0);
        $gid = $this->request->get('gid', 'int', 0);
        $yx_gid = $this->request->get('yx_gid', 'int', 0);
        if (!$uid || (!$gid && !$yx_gid)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = GroupManager::init()->groupInfo($uid, $gid, $yx_gid);
        $this->ajax->outRight($res);
    }

}