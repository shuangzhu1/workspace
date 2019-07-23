<?php

namespace Multiple\Panel\Controllers;

use Components\ModuleManager\ModuleManager;
use Models\Group\Group;
use Models\Group\GroupReport;
use Models\Social\SocialDiscuss;
use Models\Social\SocialReport;
use Models\Statistics\ApiCallTotalCount;
use Models\System\SystemApiError;
use Models\User\Message;
use Models\User\UserCoinLog;
use Models\User\UserFeedback;
use Models\User\Users;
use Models\User\UserShow;
use Phalcon\Tag;
use Services\Im\ImManager;
use Services\Site\CacheSetting;

class IndexController extends ControllerBase
{
    public function indexAction()
    {
        $this->dispatcher->getActionName();

        Tag::setTitle('运营平台');
        $this->assets->addCss('/srv/static/panel/css/module/module.mine.css');
        /*  $mine = ModuleManager::instance(HOST_KEY, CUR_APP_ID)->getCustomerModules();
          if ($mine) {
              $this->view->setVar("mineModules", $mine);
          }*/

        $notice = '';
        /*if (!$this->customer_weibo) {
            $notice .= "您还没有绑定企业微博粉丝服务";
        }*/

        if (!empty($notice)) {
            $this->flash->notice($notice);
        }
        $this->view->setMainView('index');
    }

    public function welcomeAction()
    {
        $user_count = Users::dataCount("");//用户
        $group_count = Group::dataCount("");//群聊
        $discuss_count = SocialDiscuss::dataCount("");//动态
        $report_count = SocialReport::dataCount("");//
        $report_count += GroupReport::dataCount("");//举报
        $message_count = Message::dataCount("`to_uid`=" . ImManager::ACCOUNT_SYSTEM); //恐龙谷消息
        $show_count = UserShow::dataCount("`enable`=1");//秀场
        $error_count = SystemApiError::dataCount("ymd=" . date('Ymd'));//接口错误
      //  $api_call_total_count = ApiCallTotalCount::findOne(["ymd=" . date('Ymd'), 'columns' => 'count']);//接口总调用次数
        $redis = new CacheSetting();
        $api_call_total_count = $redis->get(CacheSetting::PREFIX_API_CALL_COUNT, date('Ymd'));

        $s_message_count = Message::dataCount("year=" . date('Y') . ' and month=' . date('m') . ' and day=' . date('d'));//总消息量
        $coin_count = UserCoinLog::findOne(['columns' => "sum(`value`) as sum", 'FROM_UNIXTIME(created,"%Y%m%d")=' . (date('Ymd'))]);//总龙豆充值

        $this->view->setVar('user_count', $user_count);
        $this->view->setVar('group_count', $group_count);
        $this->view->setVar('discuss_count', $discuss_count);
        $this->view->setVar('report_count', $report_count);
        $this->view->setVar('message_count', $message_count);
        $this->view->setVar('show_count', $show_count);
        $this->view->setVar('error_count', $error_count);
        $this->view->setVar('api_call_total_count', $api_call_total_count);
        $this->view->setVar('s_message_count', $s_message_count);
        $this->view->setVar('coin_count', $coin_count && !empty($coin_count['sum']) ? $coin_count['sum'] : 0);
        $redis = $this->di->get("redis");
        $this->view->setVar('user_online', count($redis->hGetAll(CacheSetting::KEY_USER_ONLINE_LIST)));
        $this->view->setVar('feedback_count', UserFeedback::dataCount("created>=" . strtotime(date('Y-m-d'))));
        $this->view->setVar('package_count', SocialDiscuss::dataCount("package_id>0 and created>=" . strtotime(date('Y-m-d'))));

    }

    public function noFoundAction()
    {

    }
}