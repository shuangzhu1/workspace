<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/1
 * Time: 16:50
 */

namespace Multiple\Developer\Api;


use Components\Rsa\lib\Sign;
use Services\Site\CacheSetting;
use Util\Ajax;

class ToolsController extends ApiBase
{
    //移除黑名单
    public function removeIpAction()
    {
        $ip = $this->request->get("ip", 'string');
        $redis = $this->di->get("redis_behavior");
        if ($ip) {
            ($redis->hDel(CacheSetting::KEY_IP_BLACKLIST, $ip));
            ($redis->hDel(CacheSetting::KEY_IP_FREQUENCY, $ip));
        }
        Ajax::outRight("");
    }

    //添加黑名单
    public function addIpAction()
    {
        $ip = $this->request->get("ip", 'string');
        $redis = $this->di->get("redis_behavior");
        if ($ip) {
            ($redis->hSet(CacheSetting::KEY_IP_BLACKLIST, $ip, json_encode(['time' => time()])));
        }
        Ajax::outRight("");
    }

    //移除黑名单
    public function removeHostAction()
    {
        $host = $this->request->get("host", 'string');
        $redis = $this->di->get("redis");
        if ($host) {
            ($redis->hDel(CacheSetting::KEY_URL_SHIELD, $host));
        }
        Ajax::outRight("");
    }

    //添加黑名单
    public function addHostAction()
    {
        $host = $this->request->get("host", 'string');
        $redis = $this->di->get("redis");
        if ($host) {
            if (!preg_match('/^([a-zA-Z0-9]+)(\.[a-zA-Z0-9]+)*\.([a-zA-Z]{2,})$/', $host)) {
                Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "无效的域名");
            }
            ($redis->hSet(CacheSetting::KEY_URL_SHIELD, $host, time()));
        }
        Ajax::outRight("");
    }

    public function saveIpSettingAction()
    {
        $type = $this->request->get("type", 'string', 'main');
        $data = $this->request->get("data");

        if (!$type || !$data) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $config = json_decode(file_get_contents(ROOT . "/Data/site/api.json"), true);
        //主配置
        if ($type == 'main') {
            $config['enable'] = $data['enable'] ? true : false;
            $config['black_enable'] = $data['black_enable'] ? true : false;
            $config['black_limit'] = $data['black_limit'];

        } //接口配置
        else if ($type == 'config') {
            $config['limit'][$data['id']]['enable'] = $data['enable'] ? true : false;
            $config['limit'][$data['id']]['m_limit'] = $data['m_limit'];
            $config['limit'][$data['id']]['h_limit'] = $data['h_limit'];
            $config['limit'][$data['id']]['d_limit'] = $data['d_limit'];
        } elseif ($type == 'saveAll') {
            foreach ($data as $item) {
                $config['limit'][$item['id']]['enable'] = $item['enable'] ? true : false;
                $config['limit'][$item['id']]['m_limit'] = $item['m_limit'];
                $config['limit'][$item['id']]['h_limit'] = $item['h_limit'];
                $config['limit'][$item['id']]['d_limit'] = $item['d_limit'];
            }
        }
        if (file_put_contents(ROOT . "/Data/site/api.json", json_encode($config))) {
            Ajax::outRight();
        }
        Ajax::outError(Ajax::FAIL_HANDLE);
    }

    public function signVerifyAction()
    {
        $sign = $this->request->get("sign", 'string', '');
        $type = $this->request->get("type", 'int', 1);//业务服务器
        if ($type == 1) {
            $res = Sign::rsa_decrypt($sign, ROOT . '/Library/Components/Rsa/key/v2/rsa_private_key.pem');
        } else if ($type == 2) {
            $sign = str_replace(['_a', '_b', '_c'], ["/", "+", "="], $sign);
            $res = Sign::rsa_decrypt($sign, ROOT . '/Library/Components/Rsa/key/middle/private_key.pem');
        } else if ($type == 3) {
            $res = Sign::rsa_decrypt($sign, ROOT . '/Library/Components/Rsa/key/open/rsa_private_key.pem');
        }
        Ajax::outRight($res);
    }
}