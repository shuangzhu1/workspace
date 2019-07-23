<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/26
 * Time: 11:14
 */

namespace Multiple\Panel\Controllers;


use Models\Site\SiteTags;
use Models\Social\SocialComment;
use Models\Social\SocialCommentReply;
use Models\Social\SocialDiscuss;
use Models\Social\SocialDiscussBillboard;
use Models\Social\SocialDiscussTagFilter;
use Models\Social\SocialReport;
use Models\User\UserAttention;
use Models\User\UserInfo;
use Models\User\Users;
use Services\Admin\AdminLog;
use Services\Discuss\DiscussManager;
use Services\Discuss\TagManager;
use Services\Site\SiteKeyValManager;
use Services\Social\SocialManager;
use Util\Pagination;

class DiscussController extends ControllerBase
{
    //动态列表
    public function listAction()#动态列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
        $key = $this->request->get('key', 'string', '');//关键字
        $status = $this->request->get('status', 'int', '-1');//状态
        $media_type = $this->request->get('media_type', 'int', 0);//媒体类型
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $type = $this->request->get('type', 'int', 2);//类型 0-所有 1-推荐 2-有效
        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序
        $tag = $this->request->get("tag", 'int', -1);
        $is_admin = $this->request->get("is_admin", 'int', -1);

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($key) {
            $users = Users::getColumn(['id="' . $key . '" or username like "%' . $key . '%" or phone="' . $key . '"', 'id'], 'id');
            if ($users) {
                $params[0][] = 'user_id in (' . implode(',', $users) . ')';
            }
        }
        if ($status != -1) {
            $params[0][] = ' status = ' . $status;
        }
        if ($media_type) {
            $params[0][] = ' media_type = ' . $media_type;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        if ($type == 1) {
            $params[0][] = ' is_recommend  =1 ';
            $params['order'] = 'recommend_time desc,created desc';
        } elseif ($type == 2) {
            $params[0][] = ' status  =1 ';
        }
        if ($tag != -1) {
            $params[0][] = " (LOCATE('" . $tag . ",',concat(tags,','))>0) ";
        }
        if ($is_admin != -1) {
            $params[0][] = ' is_admin  = ' . $is_admin;
        }


        //权重
        $weight = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_DISCUSS, 'weight');
        $weight = json_decode($weight, true);

        $params['columns'] = "*";

        $columns = 'id,created';
        //排序
        if ($sort) {

            //权重
            if ($sort == 'weight') {
                $order = "";
                foreach ($weight as $k => $v) {
                    $order .= '+' . $k . '*' . $v['val'];
                }
                $params['columns'] .= ",(" . substr($order, 1) . ") as order_column ";
                $params['order'] = " order_column " . $sort_order . ", created desc";
                $columns .= ",(" . substr($order, 1) . ") as order_column ";
            } else if ($sort == 'created') {
                $params['order'] = " created $sort_order";
            } else if ($sort == 'fav') {
                $params['order'] = "fav_cnt $sort_order, created desc";
                $columns .= ",fav_cnt";
            } else if ($sort == 'comment') {
                $params['order'] = "comment_cnt $sort_order, created desc";
                $columns .= ",comment_cnt";
            } else if ($sort == 'like') {
                $params['order'] = "like_cnt $sort_order, created desc";
                $columns .= ",like_cnt";
            } else if ($sort == 'report') {
                $params['order'] = "report_cnt $sort_order, created desc";
                $columns .= ",report_cnt";

            } else if ($sort == 'package') {
                if ($sort_order == 'desc') {
                    $params[0][] = ' package_id<>"" ';
                } else {
                    $params[0][] = ' package_id="" ';
                }
                $params['order'] = "created desc";

            } else if ($sort == 'forward') {
                $params['order'] = "forward_cnt $sort_order, created desc";
                $columns .= ",forward_cnt";

            } else if ($sort == 'view') {
                $params['order'] = "view_cnt $sort_order, created desc";
                $columns .= ",view_cnt";
            }
        }
        $this->view->setVar('weight', $weight);
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SocialDiscuss::dataCount($params[0]);
        $list = [];
        $ids = (SocialDiscuss::getColumn([$params[0], 'columns' => $columns, 'order' => $params['order'], 'limit' => $params['limit'], 'offset' => $params['offset']], 'id'));
        if ($ids) {
            if ($sort == 'weight') {
                $order = "";
                foreach ($weight as $k => $v) {
                    $order .= '+' . $k . '*' . $v['val'];
                }
                $order = "(" . substr($order, 1) . ") desc,created desc";
                $list = SocialDiscuss::findList(['id in (' . implode(',', $ids) . ')', 'order' => $order]);
            } else {
                $list = SocialDiscuss::findList(['id in (' . implode(',', $ids) . ')', 'order' => $params['order']]);
            }
        }

        $this->view->setVar('key', $key);
        $this->view->setVar('status', $status);
        $this->view->setVar('media_type', $media_type);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('type', $type);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);
        $this->view->setVar('limit', $limit);

        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);

            $discuss_ids = array_column($list, 'id');
            $tag_filter = SocialDiscussTagFilter::getColumn(['discuss_id in (' . implode(',', $discuss_ids) . ')', 'columns' => 'discuss_id'], 'discuss_id');
            $date = date('Ymd');
            $billboard = SocialDiscussBillboard::getColumn(['discuss_id in (' . implode(',', $discuss_ids) . ') and ymd=' . $date, 'columns' => 'discuss_id'], 'discuss_id');
            foreach ($list as $k => $item) {
                if (in_array($item['id'], $tag_filter)) {
                    $list[$k]['is_filter'] = 1;
                } else {
                    $list[$k]['is_filter'] = 0;
                }
                if (in_array($item['id'], $billboard)) {
                    $list[$k]['is_billboard'] = 1;
                } else {
                    $list[$k]['is_billboard'] = 0;
                }
            }
        }
        $this->view->setVar('list', $list);

        $tags = SiteTags::findList(['type=1', 'columns' => 'id,name']);
        $this->view->setVar('tags', $tags);
        $this->view->setVar('tag', $tag);
        $this->view->setVar('is_admin', $is_admin);
        $this->view->setVar('where', base64_encode($params[0]));
        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //动态详情
    public function detailAction()#动态详情#
    {
        $params = $this->dispatcher->getParams();
        $discuss_id = $this->dispatcher->getParam(0);//动态id
        $where = !empty($params[1]) ? $params[1] : '';//where
        if (!$discuss_id) {
            $this->err(404, '无效的参数');
        }
        $discuss = SocialDiscuss::findOne("id=" . $discuss_id);
        if (!$discuss) {
            $this->err(404, '数据不存在');
        }
        //转发的原始内容
        $discuss['original_info'] = [];
        //新闻资讯
        if ($discuss['share_original_type'] == SocialManager::TYPE_NEWS) {
            $content = json_decode($discuss['content'], true);

            $discuss['content'] = $content['content'];
            $discuss['original_info'] = [
                'title' => isset($content['title']) ? $content['title'] : '',
                'news_id' => isset($content['news_id']) ? $content['news_id'] : 0,
                'media' => isset($content['media']) ? $content['media'] : '',
                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
            ];

        }//分享商品
        elseif ($discuss['share_original_type'] == SocialManager::TYPE_GOOD) {
            if ($discuss['parent_item_id_str']) {
                $top_discuss_id = explode(',', $discuss['parent_item_id_str'])[0];
                $content = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content']);
                $content = json_decode($content['content'], true);
            } else {
                $content = json_decode($discuss['content'], true);
                $discuss['content'] = $content['content'];
            }


            $discuss['original_info'] = [
                'title' => isset($content['name']) ? $content['name'] : '',
                'good_id' => isset($content['good_id']) ? $content['good_id'] : 0,
                'media' => isset($content['media']) ? explode(',', $content['media'])[0] : '',
                'media_type' => isset($content['media_type']) ? $content['media_type'] : 0,
                'brief' => isset($content['brief']) ? $content['brief'] : 0,
                'price' => isset($content['price']) ? $content['price'] / 100 : 0,
                'unit' => isset($content['unit']) ? $content['unit'] : 0,
                'shop_owner' => Users::findOne(['id = ' . $content['uid'], 'columns' => 'id,username']),
            ];
        }//第三方app分享
        elseif ($discuss['share_original_type'] == SocialManager::TYPE_SHARE) {
            if ($discuss['parent_item_id_str']) {
                $top_discuss_id = explode(',', $discuss['parent_item_id_str'])[0];
                $content = SocialDiscuss::findOne(['id=' . $top_discuss_id, 'columns' => 'content']);
                $content = json_decode($content['content'], true);
            } else {
                $content = json_decode($discuss['content'], true);
                $discuss['content'] = $content['content'];
            }


            $discuss['original_info'] = [
                'link' => isset($content['link']) ? $content['link'] : '',
                'media' => isset($content['media']) ? $content['media'] : '',
                'media_type' => isset($content['media_type']) ? $content['media_type'] : '',
                'title' => isset($content['title']) ? $content['title'] : '',
                'from' => isset($content['from']) ? $content['from'] : ''
            ];
        } else {
            if ($discuss['share_original_item_id']) {
                $original_info = SocialManager::init()->getShortDate($discuss['share_original_type'], $discuss['share_original_item_id'], 0);
                if ($original_info) {
                    $discuss['original_info'] = $original_info;
                }
            }
        }
        if ($where) {
            $next_discuss = SocialDiscuss::findOne(['id>' . $discuss_id . ' and ' . base64_decode($where), 'order' => 'id asc', 'columns' => 'id']);
            $pre_discuss = SocialDiscuss::findOne(['id<' . $discuss_id . ' and ' . base64_decode($where), 'order' => 'id desc', 'columns' => 'id']);
        } else {
            $next_discuss = SocialDiscuss::findOne(['id>' . $discuss_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'order' => 'id asc', 'columns' => 'id']);
            $pre_discuss = SocialDiscuss::findOne(['id<' . $discuss_id . ' and status=' . DiscussManager::STATUS_NORMAL, 'order' => 'id desc', 'columns' => 'id']);
        }


        $user_info = UserInfo::findOne(['user_id=' . $discuss['user_id']]);
        $user_info['discuss_cnt'] = SocialDiscuss::dataCount('user_id=' . $discuss['user_id']);
        $user_info['follower_cnt'] = UserAttention::dataCount('user_id=' . $discuss['user_id']);
        $user_info['attention_cnt'] = UserAttention::dataCount('owner_id=' . $discuss['user_id']);
        $user_info['report_cnt'] = SocialReport::dataCount('user_id=' . $discuss['user_id']);
        $logs = AdminLog::init()->getLogs(AdminLog::TYPE_DISCUSS, $discuss_id);

        if (SocialDiscussBillboard::exist('discuss_id=' . $discuss_id . " and ymd=" . date('Ymd'))) {
            $discuss['is_billboard'] = 1;
        } else {
            $discuss['is_billboard'] = 0;
        }

        if (SocialDiscussTagFilter::exist('discuss_id=' . $discuss_id)) {
            $discuss['is_filter'] = 1;
        } else {
            $discuss['is_filter'] = 0;
        }
        $this->view->setVar('item', $discuss);
        $this->view->setVar('user_info', $user_info);
        $this->view->setVar('logs', $logs);
        $this->view->setVar('next', $next_discuss ? $next_discuss['id'] : '');
        $this->view->setVar('pre', $pre_discuss ? $pre_discuss['id'] : '');
        $this->view->setVar('where', $where);
        $tags = TagManager::getInstance()->list();


        $this->view->setVar('tags', $tags);
    }

    public function billboardAction()#动态榜单#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 10);
//        $start = $this->request->get('start', 'string', '');//开始时间
//        $end = $this->request->get('end', 'string', '');//结束时间
        $type = $this->request->get('type', 'string', '');//type 0-今日榜 1-周榜


        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

//        if ($start) {
//            $params[0][] = ' created  >= ' . strtotime($start);
//        }
//        if ($end) {
//            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
//        }
        if ($type == 0) {
            $params[0][] = ' ymd  = ' . date('Ymd');
        } else {
            $params[0][] = ' ymd  >= ' . date('Ymd', strtotime("-7 days"));
        }
        //权重
        $weight = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_DISCUSS, 'weight');
        $weight = json_decode($weight, true);

        $params['columns'] = "*";

        $columns = 'discuss_id,created,order_num,ymd';

        $this->view->setVar('weight', $weight);
        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';

        $count = SocialDiscussBillboard::dataCount($params[0]);
        $list = [];
        $board = (SocialDiscussBillboard::getByColumnKeyList([$params[0], 'columns' => $columns, 'order' => $params['order'], 'limit' => $params['limit'], 'offset' => $params['offset']], 'discuss_id'));
        if ($board) {
            $ids = array_column($board, 'discuss_id');
            $list = SocialDiscuss::findList(['id in (' . implode(',', $ids) . ')', 'order' => 'created desc']);
            if ($list) {
                foreach ($list as $k => $item) {
                    $list[$k]['order_num'] = $board[$item['id']]['order_num'];
                    $list[$k]['ymd'] = $board[$item['id']]['ymd'];

                }
            }
        }

//        $this->view->setVar('start', $start);
//        $this->view->setVar('end', $end);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('type', $type);
        if ($list) {
            $user_ids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $user_ids) . ')', 'columns' => 'username,true_name,user_id'], 'user_id');
            $this->view->setVar('users', $users);
        }
        $this->view->setVar('list', $list);

        Pagination::instance($this->view)->showPage($page, $count, $limit);
    }

    //权重设置
    public function weightAction()#权重设置#
    {
        $weight = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_DISCUSS, 'weight');
        $weight = json_decode($weight, true);
        $this->view->setVar('list', $weight);
    }

    //评论
    public function commentAction()#评论#
    {
        $discuss_id = $this->dispatcher->getParam(0);//动态id
        $limit = $this->request->get("limit", 'int', 10);
        if (!$discuss_id) {
            $this->err(404, '无效的参数');
        }
        $discuss = SocialDiscuss::findOne("id=" . $discuss_id);
        if (!$discuss) {
            $this->err(404, '数据不存在');
        }
        $data = \Multiple\Panel\Plugins\SocialManager::init()->commentList(0, SocialManager::TYPE_DISCUSS, $discuss_id, $limit, 0);
        $this->view->setVar('comment', $data);
        $this->view->setVar('discuss', $discuss);
        $this->view->setVar('item_id', $discuss_id);
        $this->view->setVar('limit', $limit);
    }

}