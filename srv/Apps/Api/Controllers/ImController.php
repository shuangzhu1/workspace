<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 14:35
 */

namespace Multiple\Api\Controllers;


use Components\Yunxin\ServerAPI;
use JPush\Client as JPush;
use Services\Im\JPushManager;
use Services\Im\NotifyManager;
use Util\Debug;

class ImController extends ControllerBase
{
    /*--云信消息抄送--*/
    public function notifyAction()
    {
        $body = @file_get_contents('php://input');
        $data = json_decode($body, true);         // 值得注意 true
//         Debug::log("request:" . var_export($body, true), 'im');
//         Debug::log("headers:" . var_export($this->request->getHeaders(), true), 'im');

        $headers = $this->request->getHeaders();
       // Debug::log("header:" . var_export($headers, true), 'im');
        if (!empty($headers['Checksum']) && !empty($headers['Curtime']) && !empty($headers['Md5'])) {
            //验证通过
            if (ServerAPI::init()->checkSum($headers['Md5'], $headers['Curtime'], $headers['Checksum'])) {
                NotifyManager::init()->write($data);
            }
        }
    }

    public function jpushAction()
    {
        //var_dump(JPushManager::init()->pushMessage('恐龙谷V1.2版本升级了,赶紧升级吧', 'all', '', '', '', array('content-available' => true, 'extras' => ['id' => 9])));
        //var_dump(JPushManager::init()->pushMessage('恐龙谷V1.2版本升级了,赶紧升级吧', 'all', '161a3797c80b058fb4d', '', '', array('content-available' => true, 'extras' => ['id' => 9])));
    }
}