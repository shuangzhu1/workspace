<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/6/19
 * Time: 16:31
 */

namespace Window;


use Models\Site\SiteAppVersion;
use Util\Ajax;

class AppController extends ControllerBase
{

    public function testAction()
    {

    }

    public function versionAction()
    {
        $ver = $this->request->get('version', 'string');
        $os = $this->request->get('os', 'string');

        $item = SiteAppVersion::findOne(['os="' . $os . '"', 'order' => 'version desc,id desc']);
        /*  if (!$item) {
              Ajax::init()->outError(Ajax::ERROR_DATA_NOT_EXISTS);
          }*/

        if ($os == 'mac') {
            $download_url = 'http://www.klgwl.com/uploads/app-desk/fyj-release' . '.dmg';
        } else {
            $download_url = 'http://www.klgwl.com/uploads/app-desk/fyj-release' . '.exe';
        }

        if ($item) {
            Ajax::init()->outRight([
                    'version' => $item['version'],
                    'limit_version' => $item['limit_version'],
                    'is_too_old' => version_compare($item['limit_version'], $ver, ">"),
                    'is_new_version' => version_compare($item['version'], $ver, ">"),
                    'url' => $download_url,
                    'detail' => $item['detail']]
            );
        } else {
            Ajax::init()->outRight([
                    'version' => '1.0.0',
                    'limit_version' => '1.0.0',
                    'is_too_old' => false,
                    'is_new_version' => true,
                    'url' => $download_url,
                    'detail' => ""]
            );
        }

    }

}