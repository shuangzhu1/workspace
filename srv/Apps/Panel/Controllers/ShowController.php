<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/17
 * Time: 10:40
 */

namespace Multiple\Panel\Controllers;


use Models\User\UserInfo;
use Models\User\Users;
use Models\User\UserShow;
use Models\User\UserVideo;
use Util\Pagination;

class ShowController extends ControllerBase
{
    public function listAction()#选手列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $key = $this->request->get('key', 'string', '');//关键字
        $status = $this->request->get('status', 'int', '-1');//状态
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $order = $this->request->get('order', 'string', '');//排序字段
        $sort_order = $this->request->get('sort', 'string', 'desc');//降序
        $type = $this->request->get('type', 'int', 1);//类型

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;
        if ($key) {
            $params[0][] = 'v.user_id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '"';
        }
        /*  if ($type != '-1') {
              $params[0][] = ' s.user_type = ' . $type;
          }*/
        if ($status != -1) {
            $params[0][] = ' enable = ' . $status;
        }
        if ($start) {
            $params[0][] = ' v.created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' v.created  <= ' . (strtotime($end) + 86400);
        }
        //排序
        if ($order && $sort_order) {
            //当前得分
            if ($order == 'score') {
                $params['order'] = " (score) $sort_order";
            } //当前排名
            else if ($order == 'rank') {
                $params['order'] = " (score) $sort_order ,created desc";
            } //魅力值
            else if ($order == 'charm') {
                $params['order'] = " s.charm $sort_order";
            } else {
                $params['order'] = "$order $sort_order";
            }
        }
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $where = $params[0] ? " where " . $params[0] : '';
        //echo "select s.*,u.username,u.phone,p.charm from user_show as s left join users as u on  s.user_id=u.id left join user_profile as p on s.user_id=p.user_id  " . $where . " order by " . $params['order'] . ' limit ' . ($page - 1) * $limit . ',' . $limit;exit;


        // $user_order = UserShow::getColumn(['enable=1', 'order' => 'score desc,created desc', 'columns' => '(charm-dislike_cnt) as score,user_id','limit'=>10000], 'user_id');

        $list = $this->db->query("select v.*,u.username,u.avatar from (select user_id,max(created) as created,count(1) as video_count from user_video group by user_id) as v left join users as u on v.user_id=u.id" . $where . ' order by ' . $params['order'] . ' limit ' . ($page - 1) * $limit . ',' . $limit)->fetchAll(\PDO::FETCH_ASSOC);
        $count = $this->db->query("select count(*) as count from (select user_id,max(created) as created,count(1) as video_count from user_video group by user_id) as v left join users as u on v.user_id=u.id $where ")->fetch(\PDO::FETCH_ASSOC)['count'];

        // $list = $this->db->query("select v.*,u.username,u.phone from user_video as v left join users as u on  s.user_id=u.id left join user_profile as p on v.user_id=p.user_id  " . $where . " order by " . $params['order'] . ' limit ' . ($page - 1) * $limit . ',' . $limit)->fetchAll(\PDO::FETCH_ASSOC);
        //  $count = $this->db->query("select count(*) as count  from user_show as s left join users as u on  s.user_id=u.id left join user_profile as p on s.user_id=p.user_id  " . $where)->fetch(\PDO::FETCH_ASSOC)['count'];
        /*   if ($list) {
               foreach ($list as &$item) {
                   $k = array_search($item['user_id'], $user_order);
                   $item['rank'] = $k !== false ? $k + 1 : 0;
               }
           }*/
        $this->view->setVar('list', $list);
        $this->view->setVar('key', $key);
        $this->view->setVar('status', $status);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('order', $order);
        $this->view->setVar('sort_order', $sort_order);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('type', $type);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //选手详情
    public function detailAction()#选手详情#
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        if (!$user_id) {
            $this->err("404", "无效的参数");
            return;
        }

        $userShow = UserVideo::findList(["user_id=" . $user_id . " and qid=0", 'order' => 'created desc']);
        if ($userShow) {
            $user_info = UserInfo::findOne(['user_id=' . $user_id, 'columns' => 'username,avatar,sex,grade,is_auth,charm']);
            $this->view->setVar('show', $userShow);
            $this->view->setVar('user', $user_info);
        }
    }

    //视频列表
    public function videoAction()#视频列表#
    {
        $video_id = $this->request->get("video_id", 'int', 0);
        $this->view->setVar('video_id', $video_id);
    }
}