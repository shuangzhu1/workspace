<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/7/24
 * Time: 14:07
 */

namespace Multiple\Panel\Api;


use Models\Statistics\StatisticsShowTotal;
use Models\Statistics\StatisticsShowUser;
use Models\User\UserVideo;
use Services\Admin\AdminLog;
use Services\User\VideoManager;
use Util\Ajax;
use Util\Pagination;

class ShowController extends ApiBase
{
    public function historyAction()
    {
        $uid = $this->request->getPost("uid", 'int', 0);
        $page = $this->request->getPost("int", 'int', 1);
        $limit = $this->request->getPost("limit", 'int', 10);

        $count = StatisticsShowUser::dataCount('user_id=' . $uid);
        $bar = Pagination::getAjaxPageBar($count, $page, $limit);
        $list = StatisticsShowUser::findList(['user_id=' . $uid, 'offset' => ($page - 1) * $limit, 'limit' => $limit, 'order' => 'issue desc']);
        if ($list) {
            $total = StatisticsShowTotal::getByColumnKeyList(['issue in (' . implode(',', array_column($list, 'issue')) . ')', 'columns' => 'user_count,created,issue'], 'issue');
            foreach ($list as &$item) {
                $item['created'] = $total[$item['issue']]['created'];
                $item['user_count'] = $total[$item['issue']]['user_count'];
            }
        }
        $data = $this->getFromOB('show/partial/history', array('list' => $list, 'bar' => $bar, 'component' => 'group'));
        $this->ajax->outRight($data);
    }

    //屏蔽
    public function delAction()
    {
        $id = $this->request->get("id", 'int', 0);
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (UserVideo::exist(['id=' . $id . ' and status=' . VideoManager::STATUS_NORMAL])) {
            if (UserVideo::updateOne(['status' => VideoManager::STATUS_SHIELD], 'id=' . $id)) {
                AdminLog::init()->add('附近视频管理-屏蔽视频', AdminLog::TYPE_SHOW, $id, array('type' => "del", 'id' => $id, 'data' => $id));
            }
        }
        $this->ajax->outRight("屏蔽成功");
    }

    //批量屏蔽
    public function removeBatchAction()
    {
        $id = $this->request->get("id");
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $list = UserVideo::findList(['id in(' . implode(',', $id) . ') and status=' . VideoManager::STATUS_NORMAL, 'columns' => 'id']);
        foreach ($list as $item) {
            if (UserVideo::updateOne(['status' => VideoManager::STATUS_SHIELD], 'id=' . $item['id'])) {
                AdminLog::init()->add('附近视频管理-屏蔽视频', AdminLog::TYPE_SHOW, $item['id'], array('type' => "del", 'id' => $item['id'], 'data' => $item['id']));
            }
        }

        $this->ajax->outRight("屏蔽成功");
    }

    //恢复
    public function recoveryAction()
    {
        $id = $this->request->get("id", 'int', 0);
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (UserVideo::exist(['id=' . $id . ' and status=' . VideoManager::STATUS_SHIELD])) {
            if (UserVideo::updateOne(['status' => VideoManager::STATUS_NORMAL], 'id=' . $id)) {
                AdminLog::init()->add('附近视频管理-视频回复', AdminLog::TYPE_SHOW, $id, array('type' => "update", 'id' => $id, 'data' => $id));
            }
        }
        $this->ajax->outRight("恢复成功");
    }

    //批量恢复
    public function recBatchAction()
    {
        $id = $this->request->get("id");
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $list = UserVideo::findList(['id in(' . implode(',', $id) . ') and status=' . VideoManager::STATUS_SHIELD, 'columns' => 'id']);
        foreach ($list as $item) {
            if (UserVideo::updateOne(['status' => VideoManager::STATUS_NORMAL], 'id=' . $item['id'])) {
                AdminLog::init()->add('附近视频管理-视频回复', AdminLog::TYPE_SHOW, $item['id'], array('type' => "update", 'id' => $item['id'], 'data' => $item['id']));
            }
        }
        $this->ajax->outRight("恢复成功");
    }

    //设为推荐
    public function recommendAction()
    {
        $vid = $this->request->get('id');
        if (!$vid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = UserVideo::updateOne(['is_recommend' => 1],['id' => $vid]);
        if( $res )
        {
            AdminLog::init()->add('视频设为推荐', AdminLog::TYPE_SHOW, $vid);
            $this->ajax->outRight();
        }
        $this->ajax->ourError("操作失败");
    }

    //取消推荐
    public function unRecommendAction()
    {
        $vid = $this->request->get('id');
        if (!$vid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = UserVideo::updateOne(['is_recommend' => 0],['id' => $vid]);
        if( $res )
        {
            AdminLog::init()->add('视频取消推荐', AdminLog::TYPE_SHOW, $vid);
            $this->ajax->outRight();

        }
        $this->ajax->ourError("操作失败");
    }
    //视频列表
    public function videoAction()
    {
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $key = $this->request->get('key', 'string', '');//关键字
        $status = $this->request->get('status', 'int', '1');//状态
        $start = $this->request->get('start', 'string', '');//开始时间
        $video_id = $this->request->get('video_id', 'int', 0);//视频id
        $is_recommend = $this->request->get('is_recommend', 'int', -1);//是否推荐

        $end = $this->request->get('end', 'string', '');//结束时间
        $order = $this->request->get('order', 'string', '');//排序字段
        $sort = $this->request->getPost('sort', 'string', '');//sort

        //$params[] = ["qid=0"];
        $params[] = [];
        $params['order'] = 'v.created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;


        /*  if ($type != '-1') {
              $params[0][] = ' s.user_type = ' . $type;
          }*/
        if ($video_id) {
            $params[0][] = ' v.id = ' . $video_id;
        } else {
            if ($key) {
                $params[0][] = ' (v.user_id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '")';
            }
            if ($status != -1) {
                $params[0][] = ' v.status = ' . $status;
            }
            if ($is_recommend != -1) {
                $params[0][] = ' v.is_recommend = ' . $is_recommend;
            }
            if ($start) {
                $params[0][] = ' v.created  >= ' . strtotime($start);
            }
            if ($end) {
                $params[0][] = ' v.created  <= ' . (strtotime($end) + 86400);
            }
            if ($order && $sort) {
                $params['order'] = 'v.' . $order . " " . $sort . ", v.created desc";
            }
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $where = $params[0] ? " where " . $params[0] : '';
        $list = $this->db->query("select v.*,u.username,u.avatar from user_video as v left join users as u on v.user_id=u.id" . $where . ' order by ' . $params['order'] . ' limit ' . ($page - 1) * $limit . ',' . $limit)->fetchAll(\PDO::FETCH_ASSOC);
        $count = $this->db->query("select count(*) as count from user_video as v left join users as u on v.user_id=u.id $where ")->fetch(\PDO::FETCH_ASSOC)['count'];
        $qid = [];
        foreach( $list as $k => $v )//问答视频-》问题
        {
            if( $v['qid'] != 0)
                array_push($qid,$v['qid']);
        }
        if( !empty($qid) ){
            $questions = $this->original_mysql->query("select * from user_video_question where id in (" . implode(',',$qid) .")")->fetchAll(\PDO::FETCH_ASSOC);
            foreach( $questions as $question)
            {
                $quesList[$question['id']] = $question['question'];
            }
        }

        $data = [];
        if ($list) {
            foreach ($list as $i) {
                $data[] = [$this->getFromOB('show/partial/video', array('item' => $i,'quesList' => $quesList))];
            }
        } else {
            $data[] .= "<tr><td colspan='12'>暂无数据</td></tr>";
        }
        $bar = Pagination::getAjaxListPageBar($count, $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $count, 'bar' => $bar]);
    }
}