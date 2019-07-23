<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/2/5
 * Time: 14:51
 */

namespace Multiple\Api\Controllers;


use Services\Site\CacheSetting;
use Services\User\WelfareManager;
use Util\Ajax;

class WelfareController extends ControllerBase
{
    //设置推荐人
    public function setInviterAction()
    {
        $uid = $this->uid;
        $inviter = $this->request->get("inviter", 'int', 0);
        if (!$uid || !$inviter) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = WelfareManager::getInstance()->add($inviter, $uid);
        if ($res['result'] == 0) {
            $this->ajax->outError($res['code']);
        } else {
            //激活下
            $res = WelfareManager::getInstance()->activate($uid, WelfareManager::ACTIVE_CODE);
            if ($res) {
                //互相关注下
                $redis = $this->di->get("publish_queue");
                $redis->publish(CacheSetting::KEY_ATTENTION, json_encode(['uid' => $uid, 'to_uid' => $inviter, 'source' => 3]));
                $redis->publish(CacheSetting::KEY_ATTENTION, json_encode(['uid' => $inviter, 'to_uid' => $uid, 'source' => 3]));
            }
            $this->ajax->outRight("设置成功", $res['code']);
        }
    }

    //邀请记录
    public function inviteRecordAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $res = WelfareManager::getInstance()->inviteRecord($uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    //我的公益信息
    public function myInfoAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = WelfareManager::getInstance()->myInfo($uid);
        $this->ajax->outRight($res);
    }

    public function historyAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        $res = WelfareManager::getInstance()->history($uid, $page, $limit);
        $this->ajax->outRight($res);
    }
}