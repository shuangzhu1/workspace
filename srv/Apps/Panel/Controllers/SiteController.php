<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 4/8/14
 * Time: 11:37 AM
 */

namespace Multiple\Panel\Controllers;


use Components\StaticFileManager;
use Models\CustomerProfile;
use Models\Site\SiteAppVersion;
use Models\Site\SiteCashReward;
use Models\Site\SiteIndustries;
use Models\Site\SiteReportReason;
use Models\Social\SocialReport;
use Models\User\UserInfo;
use Models\Wap\SiteFocus;
use Models\Wap\SiteInfo;
use Models\Wap\SiteNavs;
use Models\Wap\SitePage;
use Phalcon\Tag;
use Services\Site\IndustryManager;
use Services\Site\SensitiveManager;
use Services\Site\SiteKeyValManager;
use Util\EasyEncrypt;
use Util\Linux;
use Util\Pagination;

class SiteController extends ControllerBase
{

    //行业
    public function industryAction()#行业列表#
    {
        $list = IndustryManager::instance()->getTreeData();
        $this->view->setVar('list', $list);
        // exit;
    }

    //举报原因
    public function reportAction()#举报原因#
    {
        $type = $this->request->get('type', 'int', 1);
        $list = SiteReportReason::findList(['type=' . $type, 'order' => 'enable desc,sort asc']);
        $this->view->setVar('list', $list);
        $this->view->setVar('type', $type);
        // exit;
    }

    //版本列表
    public function versionAction()#版本列表#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 15);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $os = $this->request->get('os', 'string', -1);//操作系统

        $params[] = ['status <> 2'];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;
        $params['limit'] = $limit;

        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . strtotime($end);
        }
        if ($os != -1) {
            $params[0][] = ' os="' . $os . '"';
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SiteAppVersion::dataCount($params[0]);
        $list = SiteAppVersion::findList($params);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        $version = SiteAppVersion::getByColumnKeyList(['status = 1', 'columns' => 'GROUP_CONCAT(version) as version,GROUP_CONCAT(id) as ids,os', 'group' => 'os', 'order' => 'created desc'], 'os');
        $this->view->setVar('list', $list);
        $this->view->setVar('version_list', $version);
        $this->view->setVar('os', $os);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
    }

    //基本资料
    public function infoAction()#基本资料#
    {
        $account = $this->request->get("account", 'int', 13);
        if (!$account || $account == 13) {
            $official_info = SiteKeyValManager::init()->getByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'official_info');
            $official_info = json_decode($official_info['val'], true);
        } else {
            $official_info = SiteKeyValManager::init()->getByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'official_info_' . $account);
            $official_info = json_decode($official_info['val'], true);
        }

        $this->view->setVar('official_info', $official_info);
        $this->view->setVar('account', $account);
    }

    //app设置
    public function appSettingAction()#app设置#
    {
        $info = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_APP_SETTING, 'setting');
        $info = json_decode($info, true);
        $this->view->setVar('info', $info);
    }

    //普通敏感词
    public function sensitiveAction()#普通敏感词#
    {
        $res = SensitiveManager::getWord("normal");
        $this->view->setVar('list', $res);
    }

    //政治敏感词
    public function sensitiveLawAction()#政治敏感词#
    {
        $res = SensitiveManager::getWord("law");
        $this->view->setVar('list', $res);
    }

    //敏感词设置
    public function sensitiveSetAction()#敏感词设置#
    {
        $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'sensitive_word');
        $this->view->setVar('setting', json_decode($setting, true));
    }

    //奖励日志
    public function rewardAction()#奖励日志#
    {
        $page = $this->request->get('p', 'int', 1);
        $limit = $this->request->get('limit', 'int', 15);
        $start = $this->request->get('start', 'string', '');//开始时间
        $end = $this->request->get('end', 'string', '');//结束时间
        $uid = $this->request->get("user_id", 'int', 0);

        $sort = $this->request->get('sort', 'string', '');//排序
        $sort_order = $this->request->get('order', 'string', 'desc');//降序

        $params[] = [];
        $params['order'] = 'created desc';
        $params['offset'] = ($page - 1) * $limit;

        $params['limit'] = $limit;
        if ($uid) {
            $params[0][] = ' user_id  = ' . $uid;
        }
        if ($start) {
            $params[0][] = ' created  >= ' . strtotime($start);
        }
        if ($end) {
            $params[0][] = ' created  <= ' . (strtotime($end) + 86400);
        }
        //排序
        if ($sort) {
            $params['order'] = "$sort $sort_order, created desc";
        }

        $params[0] = $params[0] ? implode(' and ', $params[0]) : '';
        $count = SiteCashReward::dataCount($params[0]);
        $list = SiteCashReward::findList($params);
        Pagination::instance($this->view)->showPage($page, $count, $limit);
        if ($list) {
            $uids = array_column($list, 'user_id');
            $users = UserInfo::getByColumnKeyList(['user_id in (' . implode(',', $uids) . ')', 'columns' => 'user_id,username,true_name,avatar,sex'], 'user_id');
            foreach ($list as &$item) {
                $item['user_info'] = $users[$item['user_id']];
            }
        }
        $total_cash = SiteCashReward::findOne(['columns' => 'sum(money) as total', $params[0]]);

        $this->view->setVar('list', $list);
        $this->view->setVar('start', $start);
        $this->view->setVar('end', $end);
        $this->view->setVar('user_id', $uid);
        $this->view->setVar('sort', $sort);
        $this->view->setVar('sort_order', $sort_order);
        $this->view->setVar('limit', $limit);
        $this->view->setVar('p', $page);
        $this->view->setVar('total', $total_cash['total']);
    }

    //系统奖励设置
    public function rewardSettingAction()#奖励设置#
    {
        $setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, 'reward');
        $this->view->setVar('setting', json_decode($setting, true));
    }
}