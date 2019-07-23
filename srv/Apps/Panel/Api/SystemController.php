<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/8
 * Time: 17:04
 */

namespace Multiple\Panel\Api;


use Models\Social\SocialComment;
use Models\Social\SocialCommentReply;
use Models\Social\SocialDiscuss;
use Models\System\SystemImageCheck;
use Models\User\UserFeedback;
use Services\Admin\AdminLog;
use Services\Site\PornManager;
use Util\Ajax;
use Util\Pagination;

class SystemController extends ApiBase
{
    //审核通过
    public function checkFeedbackAction()
    {
        $data = $this->request->getPost('data');
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $apply_data = ['check_status' => 2, 'modify' => time(), 'check_user' => $this->session->get('admin')['id']];
        foreach ($data as $item) {
            $apply = UserFeedback::findOne('id=' . $item);
            if ($apply) {
                //更新审核状态
                UserFeedback::updateOne($apply_data, ['id' => $item]);
                //记录日志
                AdminLog::init()->add('意见反馈审核通过', AdminLog::TYPE_FEEDBACK, $item, array('type' => "update", 'id' => $item));
            }
        }
        $this->ajax->outRight('');
    }

    /*审核不通过*/
    public function failFeedbackAction()
    {
        $id = $this->request->get('id', 'int', 0);
        $reason = $this->request->get('reason', 'string', '');
        if (!$id || !$reason) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $apply_data = ['check_status' => 0, 'modify' => time(), 'check_user' => $this->session->get('admin')['id'], 'check_reason' => $reason];
        $apply = UserFeedback::findOne('id=' . $id);
        if ($apply) {
            //更新审核状态
            UserFeedback::updateOne($apply_data, ['id' => $id]);
            AdminLog::init()->add('意见反馈审核通过', AdminLog::TYPE_FEEDBACK, $id, array('type' => "update", 'id' => $id));
        }
        $this->ajax->outRight('');
    }

    //获取鉴黄图片
    public function getImgAction()
    {
        $page = $this->request->getPost("page", 'int', 1);
        $limit = $this->request->getPost("limit", 'int', 9);

        $score_start = $this->request->getPost("score_start", 'float', 0);//分值起始值
        $score_end = $this->request->getPost("score_end", "float", 0);//分值结束值
        $start = $this->request->getPost("start", "string", '');//开始时间
        $end = $this->request->getPost("end", "string", '');//结束时间

        $params = [["status=1"], "offset" => ($page - 1) * $limit, 'limit' => $limit];
        if ($score_start) {
            $params[0][] = "rate>=" . $score_start;
        }
        if ($score_end) {
            $params[0][] = "rate<=" . $score_start;
        }
        if ($start) {
            $params[0][] = "created>=" . strtotime($start);
        }
        if ($end) {
            $params[0][] = "created<=" . strtotime($end);
        }
        if ($params[0]) {
            $params[0] = implode(" and ", $params[0]);
        }
        $res = SystemImageCheck::findList($params);
        $data = [];
        if ($res) {
            //图片类型
            $img_type = [
                'discuss' => '动态',
                'comment' => '评论',
                'reply' => '回复',
                'avatar' => '头像'
            ];
            foreach ($res as $item) {
                $data[] = [$this->getFromOB('system/partial/item_img', array('item' => $item,'type' => $img_type))];
            }
        }
        $count = SystemImageCheck::dataCount($params[0]);
        $bar = Pagination::getAjaxPageBar($count, $page, $limit);
        $this->ajax->outRight(['list' => $data, 'count' => $count, 'bar' => $bar]);
    }

    //忽略图片
    public function ignoreImgAction()
    {
        $data = $this->request->getPost("data");
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        PornManager::init()->ignore($data);
    }

    //忽略图片
    public function delImgAction()
    {
        $data = $this->request->getPost("data");
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        PornManager::init()->remove($data);
    }
}