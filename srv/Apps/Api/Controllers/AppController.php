<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/12
 * Time: 11:09
 */

namespace Multiple\Api\Controllers;


use Models\Site\SiteAppVersion;
use Models\System\SystemApiError;
use Services\Site\SiteKeyValManager;
use Util\Ajax;

class AppController extends ControllerBase
{
    /*--报错机制--*/
    public function apiErrorAction()
    {
        $params = $this->request->get("params", 'string', '');//参数列表;
        $response = $this->request->get("response", 'string', '');
        $state_code = $this->request->get("state_code", 'string', '');
        $api = $this->request->get("api", 'string', '');

        SystemApiError::insertOne([
            'state_code' => $state_code,
            'params' => $params,
            'url' => $api,
            'response' => ($response),
            'app_version' => $this->app_version,
            'client_type' => $this->client_type,
            'created' => time(),
        ]);
        $this->ajax->outRight();
        // SystemApiError::save($data);
        //
    }

    /*检测app版本号*/
    public function checkVersionAction()
    {
        $version = SiteAppVersion::findOne(['os="' . $this->client_type . '" and status=1', 'order' => 'version desc,id desc,download_url']);
        if (!$version) {
            $this->ajax->outRight((object)[]);
        }
        $download_url = $version['download_url'];//$this->di->get('config')->appDownload->android;
        if ($this->client_type == "ios") {
            $download_url = $this->di->get('config')->appDownload->ios;
        }
        //当前版本没设置兼容情况 往下找
        if ($version['limit_version'] == '') {
            $next = SiteAppVersion::findOne(['os="' . $this->client_type . '" and status=1 and limit_version<>"" and id<>' . $version['id'], 'order' => 'version desc,id desc,limit_version']);
            if ($next) {
                $version['limit_version'] = $next['limit_version'];
            }
        }
        $this->ajax->outRight([
                'version' => $version['version'],
                'limit_version' => $version['limit_version'],
                'download_url' => $download_url/* . $version['version'] . '.' . ($this->client_type == "ios" ? 'ipa' : 'apk')*/,
                'detail' => $version['detail'],
                'md5' => $version['file_md5']
            ]
        );
    }

    //获取配置信息
    public function settingAction()
    {
        /*  $uid = $this->uid;
          if (!$uid) {
              Ajax::outError(Ajax::INVALID_PARAM);
          }*/
        $value = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_APP_SETTING, "setting");
        $value = json_decode($value, true);
        Ajax::outRight($value);
    }
}