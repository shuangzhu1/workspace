<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/7
 * Time: 10:19
 */

namespace Multiple\Api\Controllers;


use Models\Customer\CustomerGame;
use Models\Customer\CustomerUser;
use Models\Site\SiteGame;
use Services\User\UserStatus;
use Util\Ajax;

class GameController extends ControllerBase
{
    //H5游戏列表
    public function listAction()
    {
        $uid = $this->uid;
        $page = $this->request->get("page", 'int', 1);
        $limit = $this->request->get("limit", 'int', 20);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //$list = CustomerGame::findList(['status=1', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'sort_num asc, created desc', 'columns' => 'name,thumb,url,app_id,support_login']);
        $list = SiteGame::findList(['enable = 1 and status = 1', 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'view_cnt desc', 'columns' => 'name,logo as thumb,link as url,view_cnt']);

        $this->ajax->outRight(['data_list' => $list]);
    }

    //获取token
    public function getTokenAction()
    {
        $uid = $this->uid;
        $app_id = $this->request->get("app_id", 'string', '');
        if (!$uid || !$app_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $game = CustomerGame::findOne(['app_id="' . $app_id . '" and status=1 and support_login=1']);
        if (!$game) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        Ajax::outRight(UserStatus::getInstance()->createToken($uid, $app_id));
    }
}