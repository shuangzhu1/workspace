<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/17
 * Time: 17:16
 */

namespace Multiple\Wap\Api;


use Services\MiddleWare\Sl\Request;
use Services\User\OrderManager;
use Util\Ajax;

class DiamondController extends ControllerBase
{
    public function historyAction()
    {
        // $uid = $this->session->get("uid");
        $open_id = $this->session->get("open_id");
        $limit = $this->request->get('limit', 'int', 5);
        $last_id = $this->request->get("last_id", 'int', 0);
        // $data = Request::getPost(Request::VIRTUAL_COIN_RECORDS, ["uid" => intval($uid), "coin_type" => 0, "way" => 5, "lastid" => $last_id, 'limit' => $limit], true);
        $data = OrderManager::init()->list(0, $open_id, $limit, $last_id);
        $result = [];
        if ($data) {
            foreach ($data['data_list'] as $item) {
                $result[] = [$this->getFromOB('diamond/partial/history', array('item' => $item))];
                // $last_id = $item['id'];
            }
        }
//        if ($last_id == 0 && !$result) {
//            $count = 0;
//        } else {
//            $count = 1;
//        }
        $result = array('count' => $data['data_count'], "limit" => $limit, 'data_list' => $result, 'last_id' => $data['last_id']);
        Ajax::outRight($result);
    }
}