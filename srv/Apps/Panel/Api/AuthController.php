<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/28
 * Time: 19:30
 */

namespace Multiple\Panel\Api;


use Components\Rules\Point\PointRule;
use Models\User\UserAuthApply;
use Models\User\UserInfo;
use Models\User\UserProfile;
use Models\User\Users;
use Services\Admin\AdminLog;
use Services\Im\ImManager;
use Services\User\AuthManager;
use Services\User\Square\SquareTask;
use Util\Ajax;
use Util\Debug;

class AuthController extends ApiBase
{
    //审核通过
    public function checkUserAction()
    {
        $data = $this->request->getPost('data');
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $apply_data = ['status' => AuthManager::AUTH_STATUS_SUCCESS, 'modify' => time(), 'check_user' => $this->session->get('admin')['id']];
        foreach ($data as $item) {
            $apply = UserAuthApply::findOne('id=' . $item);

            if ($apply) {
                $device = Users::findOne(['id=' . $apply['user_id'], 'columns' => 'last_device_id']);
                $user_data = ['true_name' => $apply['true_name']];
                $user_profile_data = ['is_auth' => 1, 'auth_desc' => ''];
                Users::updateOne($user_data, ['id' => $apply['user_id']]);//更新用户信息

                UserProfile::updateOne($user_profile_data, ['user_id' => $apply['user_id']]);//更新用户信息
                //更新审核状态
                UserAuthApply::updateOne($apply_data, ['id' => $item]);

                //送经验值 送龙豆
                PointRule::init()->executeRule($apply['user_id'], PointRule::BEHAVIOR_AUTH);
                //送红包领取次数
                SquareTask::init()->executeRule($apply['user_id'], $device['last_device_id'], SquareTask::TASK_AUTH);

                // \Components\Rules\Coin\PointRule::init()->executeRule($apply->user_id, \Components\Rules\Coin\PointRule::BEHAVIOR_AUTH);
                //记录日志
                AdminLog::init()->add('认证审核通过', AdminLog::TYPE_AUTH, $item, array('type' => "update", 'id' => $item));

                $user = Users::findOne(['id=' . $apply['user_id'], 'columns' => 'username']);//更新用户信息

                //发送IM消息
                ImManager::init()->initMsg(ImManager::TYPE_AUTH_SUCCESS, ['to_user_id' => $apply['user_id'], 'user_name' => $user['username'], 'auth_desc' => '']);
            }
        }
        $this->ajax->outRight('');
    }

    /*审核不通过*/
    public function checkFailAction()
    {
        $id = $this->request->get('id', 'int', 0);
        $reason = $this->request->get('reason', 'string', '');

        if (!$id || !$reason) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $apply_data = ['status' => AuthManager::AUTH_STATUS_FAILED, 'modify' => time(), 'check_user' => $this->session->get('admin')['id'], 'check_reason' => $reason];
        $apply = UserAuthApply::findOne('id=' . $id);
        if ($apply) {
            //更新审核状态
            UserAuthApply::updateOne($apply_data, ['id' => $id]);

            AdminLog::init()->add('认证审核失败', AdminLog::TYPE_AUTH, $id, array('type' => "update", 'id' => $id));

            $user = UserInfo::findOne(['user_id=' . $apply['user_id'], 'columns' => 'username,is_auth']);
            //后台取消认证了
            if ($user['is_auth'] == 4) {
                $user_profile_data = ['is_auth' => 0];
                UserProfile::updateOne($user_profile_data, ['user_id=' . $apply['user_id']]);//更新用户信息
            }
            //发送im消息
            ImManager::init()->initMsg(ImManager::TYPE_AUTH_FAIL, ['to_user_id' => $apply['user_id'], 'user_name' => $user['username'], "reason" => $reason]);
        }
        $this->ajax->outRight('');
    }
}
