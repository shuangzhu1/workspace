<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/3/30
 * Time: 10:55
 */

namespace Multiple\Api\Controllers;


use Models\User\UserStorage;
use OSS\OssClient;
use Services\Upload\OssManager;
use STS\Core\DefaultAcsClient;
use STS\Core\Profile\DefaultProfile;
use STS\StsClient;
use Util\Ajax;
use Util\Debug;

class UploadController extends ControllerBase
{
    //OSS上传时 sts获取token
    public function getTokenAction()
    {
        /*  $uid = $this->uid;
          if (!$uid) {
              $this->ajax->outError(Ajax::INVALID_PARAM);
          }*/
        require_once ROOT . "/Library/Components/Sts/Core/Regions/EndpointConfig.php";
        $iClientProfile = DefaultProfile::getProfile(OssManager::$sts_region, OssManager::$sts_access_key, OssManager::$sts_access_key_secret);
        $client = new DefaultAcsClient($iClientProfile);
        $roleArn = OssManager::$sts_roleArn;
        $policy = OssManager::$sts_policy;
        $expire = OssManager::$expire;

        $request = new StsClient();
        $request->setRoleSessionName("client_name");
        $request->setRoleArn($roleArn);
        $request->setPolicy($policy);
        $request->setAcceptFormat("JSON");
        $request->setDurationSeconds($expire);
        $response = $client->doAction($request);
        //  var_dump($response);exit;
        $token = json_decode($response->getBody(), true)['Credentials'];
        $this->ajax->outRight(['access_key' => $token['AccessKeyId'], 'access_key_secret' => $token['AccessKeySecret'], 'expire' => $expire, 'token' => $token['SecurityToken'], 'expiration' => strtotime($token["Expiration"])]);
    }

    // 图片md5检测
    public function checkMd5Action()
    {

        //  $uid = $this->uid;
        $md5 = $this->request->get("md5", 'string', '');
        if (/*!$uid || */
        !$md5
        ) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $md5 = json_decode(htmlspecialchars_decode($md5), true);


        if (!$md5) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $storage = UserStorage::getColumn(["md5 in ('" . implode("','", $md5) . "')", 'columns' => 'url,md5'], 'url', 'md5');

        $res = array_fill(0, count($md5), "");
        if ($storage) {
            foreach ($storage as $k => $item) {
                $res[array_search($k, $md5)] = $item;
            }
        }
        Ajax::outRight($res);
    }

    // 图片上传成功
    public function successAction()
    {
        // $uid = $this->uid;
        $md5 = $this->request->get("md5", 'string', '');
        if (/*!$uid || */
        !$md5
        ) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $md5 = json_decode(htmlspecialchars_decode($md5), true);

        if (!$md5) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $keys = array_keys($md5);


        $storage = UserStorage::getColumn(["md5 in ('" . implode("','", $keys) . "')", 'columns' => 'url,md5'], 'url', 'md5');
        //有相同文件上传过,过滤掉
        if ($storage) {
            foreach ($storage as $k => $i) {
                unset($md5[$k]);
            }
        }
        if ($md5) {
            $time = time();
            //  $values = [];
            foreach ($md5 as $k => $item) {
                if (!$k) {
                    continue;
                }
                //   $storage = new  UserStorage();
                $data = ['md5' => $k, 'url' => $item, 'count' => 1, 'created' => $time, 'url_md5' => md5($item)];
                UserStorage::insertOne($data);
                // $values[] = "('" . $k . "','" . $item . "',1,$time,'" . md5($item) . "')";
            }
            //   $this->db->execute("insert into user_storage(md5,url,count,created,url_md5) values " . implode(',', $values));
        }
        Ajax::outRight("");
    }

    //oss上传成功回调
    public function callbackAction()
    {
        $data = $_REQUEST;
        Debug::log("data:" . var_export($data, true), 'upload');
        $this->ajax->outRight($data);
        exit;
    }

}