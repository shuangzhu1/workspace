<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/11
 * Time: 10:46
 */

namespace Window;


use Multiple\Api\Merchant\Helper\Ajax;
use Services\User\ContactManager;

class ContactController extends ControllerBase
{
    public function getFriendsAction()
    {
        $uid = $this->request->getPost("uid", 'int', 0);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ContactManager::init()->friends($uid, 1, 1000);
        $this->ajax->outRight($res);
    }
}