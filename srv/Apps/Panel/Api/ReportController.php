<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/21
 * Time: 18:19
 */

namespace Multiple\Panel\Api;


use Components\Rules\Point\PointRule;
use Models\Group\GroupReport;
use Models\Social\SocialDiscuss;
use Models\Social\SocialReport;
use Models\User\Users;
use Services\Admin\AdminLog;
use Services\Discuss\DiscussManager;
use Services\Im\ImManager;
use Services\Social\SocialManager;
use Util\Ajax;

class ReportController extends ApiBase
{
//审核通过
    public function checkUserAction()
    {
        $data = $this->request->getPost('data');
        $type = $this->request->getPost('type');
        if (!$data || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $apply_data = ['status' => SocialManager::REPORT_STATUS_SUCCESS, 'check_time' => time(), 'check_user' => $this->admin['id']];
        $msg_data = [];//消息内容
        foreach ($data as $item) {
            if ($type == 'discuss') {
                $apply = SocialReport::findOne('id=' . $item . ' and type="discuss"');
                $msg_data['title'] = "动态";
            } else if ($type == 'user') {
                $apply = SocialReport::findOne('id=' . $item . ' and type="user"');
                $msg_data['title'] = "用户";
            } else if ($type == 'video') {
                $apply = SocialReport::findOne('id=' . $item . ' and type="video"');
                $msg_data['title'] = "视频";
            } else {
                $apply = GroupReport::findOne('id=' . $item);
                $msg_data['title'] = "群聊";
            }
            $msg_data['to_user_id'] = date('Y-m-d H:i', $apply['created']);
            $msg_data['time'] = date('Y-m-d H:i', $apply['created']);
            $msg_data['content'] = $apply['reason_content'];
            $user = Users::findOne(['id=' . $apply['reporter'], 'columns' => 'username']);
            $msg_data['username'] = $user['username'];
            $msg_data['to_user_id'] = $apply['reporter'];

            //更新审核状态
            if ($apply) {
                if ($type == 'discuss') {
                    $discuss = SocialDiscuss::findOne(['id=' . $apply['item_id']]);
                    if ($discuss) {
                        SocialDiscuss::updateOne(['status' => DiscussManager::STATUS_SHIELD], ['id' => $apply['item_id']]);
                    }
                    SocialReport::updateOne($apply_data, ['id' => $apply['id']]);
                } else if ($type == 'user') {
                    SocialReport::updateOne($apply_data, ['id' => $apply['id']]);
                } else if ($type == 'video') {
                    SocialReport::updateOne($apply_data, ['id' => $apply['id']]);
                } else {
                    GroupReport::updateOne($apply_data, ['id' => $apply['id']]);
                }
                //给举报者送经验值
                PointRule::init()->executeRule($apply['reporter'], PointRule::BEHAVIOR_REPORT);
                //给举报者发消息
                ImManager::init()->initMsg(ImManager::TYPE_REPORT_FROM, $msg_data);
                //记录日志
                AdminLog::init()->add('举报通过审核', AdminLog::TYPE_REPORT, $item, array('type' => "update", 'id' => $item));
            }
        }
        $this->ajax->outRight('');
    }

    /*审核不通过*/
    public function checkFailAction()
    {
        $id = $this->request->get('id', 'int', 0);
        $reason = $this->request->get('reason', 'string', '');
        $type = $this->request->get('type', 'string', '');
        if (!$id || !$reason || !$type) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $apply_data = ['status' => SocialManager::REPORT_STATUS_FAILED, 'check_time' => time(), 'check_user' => $this->session->get('admin')['id'], 'check_reason' => $reason];
        if ($type == 'discuss') {
            $apply = SocialReport::findOne('id=' . $id . ' and type="discuss"');
        } else if ($type == 'user') {
            $apply = SocialReport::findOne('id=' . $id . ' and type="user"');
        } else if ($type == 'user') {
            $apply = SocialReport::findOne('id=' . $id . ' and type="user"');
        } else if ($type == 'video') {
            $apply = SocialReport::findOne('id=' . $id . ' and type="video"');
        } else {
            $apply = GroupReport::findOne('id=' . $id);
        }
        if ($apply) {
            //更新审核状态
            if ($type == 'discuss') {
                SocialReport::updateOne($apply_data, ['id' => $apply['id']]);
            } else if ($type == 'user') {
                SocialReport::updateOne($apply_data, ['id' => $apply['id']]);
            } else if ($type == 'video') {
                SocialReport::updateOne($apply_data, ['id' => $apply['id']]);
            } else {
                GroupReport::updateOne($apply_data, ['id' => $apply['id']]);
            }

            AdminLog::init()->add('举报审核失败', AdminLog::TYPE_REPORT, $id, array('type' => "update", 'id' => $id));
        }
        $this->ajax->outRight('');
    }
}