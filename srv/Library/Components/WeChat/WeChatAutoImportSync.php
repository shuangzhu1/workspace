<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 5/5/14
 * Time: 3:41 PM
 */

namespace Components\WeChat;


use Components\UserManager;
use Models\CustomerOpenInfo;
use Models\Wap\SiteInfo;
use Phalcon\Mvc\User\Component;
use Upload\Upload;

class WeChatAutoImportSync extends Component
{
    public static function init()
    {
        return new self();
    }

    public function save(WeChatAutoImport $wx)
    {

        $customer_id = CUR_APP_ID;
        /*
        $res = $wx->openDevelopmentMode();
        $this->out($res, '1.开发者模式开启');
        */

        $account_info = $wx->getAccountInfo();
        $this->out($account_info, '1.获取微信用户信息');

        $appKey = $wx->getAppKeySecret();
        $this->out($appKey, '2.获取微信app key');
        $bindInfo['app_account'] = $account_info['app_account'];
        $bindInfo['address'] = $account_info['address'];
        $bindInfo['name'] = $account_info['name'];
        $bindInfo['desc'] = $account_info['desc'];
        $bindInfo['original_id'] = $account_info['original_id'];
        $bindInfo['email'] = $account_info['email'];
        $logo = $bindInfo['avatar'] = $this->saveImg($account_info['avatar_buff']);
        $qrcode = $bindInfo['qrcode'] = $this->saveImg($account_info['qrcode_buff']);

        $bindInfo['type'] = $account_info['type'];
        $bindInfo['is_authed'] = true;
        $bindInfo['app_id'] = $appKey['app_id'];
        $bindInfo['app_secret'] = $appKey['app_secret'];
        $bindInfo['is_binded'] = 1;
        $bindInfo['platform'] = 'wx';

        // 微站信息
        $siteInfo['site_name'] = $account_info['name'];
        $siteInfo['site_logo'] = $logo;
        $siteInfo['qrcode'] = $qrcode;
        $siteInfo['address'] = $account_info['address'];
        $siteInfo['modified'] = time();

        $site = SiteInfo::findFirst('customer_id=' . $customer_id);
        if ($site) {
            $site->update($siteInfo);
        } else {
            $siteInfo['created'] = time();
            $siteInfo['customer_id'] = $customer_id;
            $site = new SiteInfo();
            $site->create($siteInfo);
            $site = SiteInfo::findFirst('customer_id=' . $customer_id);
        }

        $this->getDI()->get('memcached')->save('site_' . CUR_APP_ID, $site->toArray());

        // 绑定信息
        $cm = CustomerOpenInfo::findFirst('customer_id=' . $customer_id);
        $res = $cm->update($bindInfo);
        $this->out($res, '3.信息入库');

        // reflash cache
        $info = CustomerOpenInfo::findFirst("customer_id='{$customer_id}'");
        $this->session->set('customer_wechat', $info);

        $res = $wx->setCallbackProfile($this->uri->appUrl('/wechat/service'), $cm->token);
        $this->out($res, '4.服务器回调配置');

        if ($res) {
            //同步粉丝数据
            $userManager = UserManager::instance();
            $res = $userManager->syncGroupFromWeChat($customer_id, $info->app_id, $info->app_secret);
            $this->out($res, '5.同步粉丝分组数据');
            if ($res) {
                $userManager->syncUserFromWechat($customer_id, $info->app_id, $info->app_secret);
                $this->out($res, '6.同步粉丝数据');
            }
        }
    }

    public function out($status, $msg)
    {
        if ($status) {
            echo $msg . '成功</br>';
        } else {
            echo '！！' . $msg . '失败</br>';
        }
    }

    public function saveImg($buff, $ext = 'png')
    {
        $upload = new Upload();
        $res = $upload->saveFile(array('buff' => $buff, 'ext' => $ext));
        return $res['url'];
    }
} 
