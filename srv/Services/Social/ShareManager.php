<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/30
 * Time: 16:51
 */

namespace Services\Social;


use Components\Rsa\lib\Sign;
use Models\Social\SocialShare;
use Models\Social\SocialShareBackLog;
use Phalcon\Mvc\User\Plugin;
use Util\Ajax;
use Util\Cookie;
use Util\EasyEncrypt;
use Util\Encrypt;
use Util\GetClient;
use Util\Debug;
use Util\Ip;

class ShareManager extends Plugin
{
    private static $instance = null;

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**解析 分享回链spm
     * @param $spm
     * @return array|bool
     */
    private function parseSpm($spm)
    {
        $Sign = new Sign();
        $spm = $Sign->signStr($spm);//Sign::signStr($spm);
        if (!$spm) {
            return false;
        }
        $spm_str = explode('_', $spm);
        if ($spm_str) {
            return ["uid" => $spm_str[0], 'type' => $spm_str[1], "timestamp" => $spm_str[2]];
        }
        return false;
    }

    /**
     * todo 分享链接访问统计
     *
     */
    public function visitCount()
    {
        $spm = $this->request->get('spm');
        if (!$spm) {
            return;
        }
        $spm = str_replace(' ', '+', $spm);

        // var_dump($this->request->get());exit;
        $from_url = isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '';
        $ip = GetClient::Getip();
        $client = new GetClient();

        $domain = $from_url ? parse_url($from_url, PHP_URL_HOST) : '';

        $flag = false;
        //域名为空-直接浏览器网址访问
        if (!$domain) {
            $flag = true;
        }
        //自己网站跳转不用处理
        if ($from_url && $domain && strpos($domain, MAIN_DOMAIN) === false) {
            $flag = true;
        }
        if ($flag) {
            //
            $spm_info = EasyEncrypt::decode($spm);
            if (!$spm_info) {
                $spm_info = self::parseSpm($spm);
                if (!$spm_info) {
                    return;
                }
            }

            $spm_arr = explode('_', $spm_info);
            if (count($spm_arr) != 4) {
                return;
            }
            $share = SocialShare::findOne("spm='" . $spm . "'");
            $ipinfo = Ip::getAddress($ip);
            //$ipinfo = file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip);
            //$ipinfo = json_decode($ipinfo, true);exit;
            /*同一个ip 一天记录一次*/
            $log = SocialShareBackLog::findOne(["ip='" . $ip . "' and spm='" . $spm . "'", 'columns' => 'id']);
            if ($log) {
                if (Cookie::get("share_spm") == $spm) {
                    return;
                }
            }
            // "' and FROM_UNIXTIME(created, '%Y%m%d')  ='" . date("Ymd", time())."'");
            $province = @$ipinfo['province'];
            $city = @$ipinfo['city'];
            $data['ip'] = $ip;
            $data['province'] = $province ? $province : "";
            $data['city'] = $city ? $city : '';
            $data['from_domain'] = $domain;
            $data['from_url'] = $from_url;
            $data['ymd'] = date('ymd');
            $data['plate'] = Ajax::isMobile() ? 'wap' : 'pc';
            $data['system'] = $client->GetOs();
            $data['browser'] = $client->GetBrowser();
            $data['created'] = time();
            $data['spm'] = $spm;
            $data['share_type'] = $spm_arr[1];
            $data['share_user_id'] = $spm_arr[0];
            $data['share_item_id'] = $spm_arr[2];

            $data['spm'] = $spm;
            if ($share) {
                $data['share_id'] = $share['id'];
                SocialShare::updateOne('back_count=back_count+1', ['id' => $share['id']]);
            } else {
                $data['share_id'] = 0;
            }
            $log = new SocialShareBackLog();
            $log_id = $log->insertOne($data);
            Cookie::set('share_log_id', $log_id, Cookie::OneDay);
            Cookie::set('share_spm', $spm, Cookie::OneDay);
            return;
        }
    }


}