<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 9:19
 */

namespace Multiple\Api\Controllers;


use Services\Site\IndustryManager;
use Util\Ajax;

class SiteController extends ControllerBase
{
    /*--获取行业--*/
    public function getIndustriesAction()
    {
      //  $uid = $this->uid;
        $page = $this->request->get('page', 'int', 0);
        $limit = $this->request->get('limit', 'int', 20);
      /*  if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }*/
        $data = array_values(IndustryManager::instance()->industries(true));
        $res = ['data_count' => count($data), 'data_list' => []];
        if ($page > 0) {
            $data = array_splice($data, ($page - 1) * $limit, $limit);
        }
        $res['data_list'] = $data;
        $this->ajax->outRight($res);
    }
}