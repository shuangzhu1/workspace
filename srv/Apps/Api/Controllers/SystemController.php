<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/21
 * Time: 14:02
 */

namespace Multiple\Api\Controllers;


use Models\System\SystemCustomerService;
use Models\System\SystemUpgradeLog;
use Models\User\UserFeedback;
use Models\User\UserInfo;
use Services\Site\SiteKeyValManager;
use Util\Ajax;

class SystemController extends ControllerBase
{
    //客服列表
    public function customerServiceAction()
    {
        $data = [];
        $service = SystemCustomerService::getColumn(['enable=1', 'columns' => 'user_id', 'order' => 'created asc'], 'user_id');
        if ($service) {
            $data = UserInfo::findList(['user_id in (' . implode(',', $service) . ')', 'columns' => 'user_id as uid,username,avatar']);
        }
        $this->ajax->outRight($data);
    }

    /*用户反馈*/
    public function feedBackAction()
    {
        $content = $this->request->get('content', 'string', '');
        $images = $this->request->get('images', 'string', '');
        $contact = $this->request->get('contact', 'string', '');
        if (!$content || !$this->uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //每日提交已达上限
        if (UserFeedback::dataCount("user_id=" . $this->uid . " and from_unixtime(created,'%Y%m%d')=" . date('Ymd')) >= 10) {
            $this->ajax->outError(Ajax::ERROR_REQUEST_FREQUENCY);
        }
        $feedBack = new UserFeedback();
        $data = ['user_id' => $this->uid, 'content' => $content, 'contact' => $contact, 'images' => $images,'created'=>time()];
        if ($feedBack->insertOne($data)) {
            $this->ajax->outRight("提交成功",Ajax::SUCCESS_SUBMIT);
        }
        $this->ajax->outError(Ajax::FAIL_SUBMIT);
    }

    public function upgradeInfoAction()
    {
        $data = [];
        $data['uid'] = $this->request->get('uid','int','10');
        $data['device_id'] = $this->request->get('device_id','string','fail to obtain');
        $data['version'] = $this->request->get('version','string','fail to obtain');
        $data['last_version'] = $this->request->get('last_version','string','fail to obtain');
        $data['channel'] = $this->request->get('channel','string','');
        $action = (int) $this->request->get('action','int',1);
        if( in_array($action,[1,2]))
        if( $action === 1)//下载操作
        {
            $data['created'] = time();
            $res = SystemUpgradeLog::insertOne($data);
        }elseif( $action === 2 )//安装完成
        {
            $res = SystemUpgradeLog::updateOne(['is_install' => 1,'channel' => $data['channel'],'last_version' => $data['last_version']],['device_id ' => $data['device_id'] , 'version' => $data['version']]);
        }
        $res ? $this->ajax->outRight() : $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG);

    }
}