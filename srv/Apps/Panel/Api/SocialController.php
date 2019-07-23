<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/5/4
 * Time: 11:16
 */

namespace Multiple\Panel\Api;


use Models\Social\SocialComment;
use Models\Social\SocialCommentReply;
use Services\Admin\AdminLog;
use Services\Social\SocialManager;
use Util\Ajax;

class SocialController extends ApiBase
{
    //删除评论或回复
    public function removeCommentAction()
    {
        $type = $this->request->getPost("type", 'string', '');
        $item_id = $this->request->getPost("item_id", 'int', 0);
        if (!$type || !$item_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //评论
        if ($type == SocialManager::TYPE_COMMENT) {
            $data = SocialComment::findOne('id=' . $item_id . ' and status=' . SocialManager::COMMENT_STATUS_NORMAL);
            if (!$data) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $res=SocialComment::updateOne(['status'=> SocialManager::COMMENT_STATUS_SHIELD],['id'=>$data['id']]);
        } //回复
        else {
            $data = SocialCommentReply::findOne('id=' . $item_id . ' and status=' . SocialManager::COMMENT_STATUS_NORMAL);
            if (!$data) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $res=SocialCommentReply::updateOne(['status'=> SocialManager::COMMENT_STATUS_SHIELD],['id'=>$data['id']]);
        }

        if ($res) {
            //更新评论数
            if ($type == SocialManager::TYPE_COMMENT) {
                SocialManager::init()->changeCnt($data['type'], $data['item_id'], 'comment_cnt', false);
            /*    //更新评动态论数
                if ($data['comment_cnt'] > 0 && $data['type'] == SocialManager::TYPE_DISCUSS) {
                    $this->db->execute("update social_discuss set comment_cnt= comment_cnt-" . $data['comment_cnt'] . " where id=" . $data['item_id'] . " and comment_cnt>0");
                }*/
            } else {
               /* SocialManager::init()->changeCnt(SocialManager::TYPE_COMMENT, $data['comment_id'], 'comment_cnt', false);
                //如果回复的是动态,更新评论数
                if ($data['type'] == SocialManager::TYPE_DISCUSS) {
                    SocialManager::init()->changeCnt(SocialManager::TYPE_DISCUSS, $data['item_id'], 'comment_cnt', false);//更新评论数
                }*/
            }
            AdminLog::init()->add('屏蔽评论', AdminLog::TYPE_COMMENT, $item_id, array('type' => "update", 'id' => $item_id));
        }
        $this->ajax->outRight("");
    }
}