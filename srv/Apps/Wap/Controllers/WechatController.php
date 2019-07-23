<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/11
 * Time: 11:14
 */

namespace Multiple\Wap\Controllers;


use Components\WeChat\RequestFactory;
use Models\User\UserInfo;
use Models\User\UserThirdParty;
use OSS\OssClient;
use Services\Upload\OssManager;
use Services\User\UserStatus;
use Upload\Upload;

class WechatController extends ControllerBase
{

    public function authAction()
    {
        $config = $this->di->get("config")->wechat;
        $this->response->redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $config->app_id . "&redirect_uri=http://wap.klgwl.com/wechat/getOpenInfo&response_type=code&scope=snsapi_base&state=123#wechat_redirect");
    }

    /**上传微信头像
     * @param $url
     * @return bool|int|mixed|string
     */
    public function uploadImg($url)
    {
        // 设置运行时间为无限制
        set_time_limit(0);

        $url = trim($url);
        $curl = curl_init();
        // 设置你需要抓取的URL
        curl_setopt($curl, CURLOPT_URL, $url);
        // 设置header
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 运行cURL，请求网页
        $file = curl_exec($curl);
        // 关闭URL请求
        curl_close($curl);
        $md5 = md5($file);

        $upload = new Upload();
        $url = $upload::checkFile($md5, 'md5,ext,url,size,name');
        if ($url) {
            return $url;
        } else {
            $bucket = OssManager::BUCKET_USER_AVATOR;
            $name = date("Ymd") . '/' . time() . rand(0, 100000) . "_s_750x750.jpg";
            $config = $this->di->get('config')->oss;
            $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
            $res = $oss->putObject($bucket, $name, $file);
            if ($res && !empty($res['info']['url'])) {
                $url = str_replace(OssManager::$original_domain[$bucket], OssManager::$bind_domain[$bucket], $res['info']['url']);
                Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => 'jpg', 'type' => 'img', 'size' => strlen($file), 'name' => $name, 'url' => $url, 'created' => time()]);
            }
            return $url;
        }

    }

    public function getOpenInfoAction()
    {
        $config = $this->di->get("config")->wechat;
        $code = $this->request->get('code', 'string', false);
        $request = RequestFactory::create("SnsAccessToken", CUR_APP_ID, $config->app_id,  $config->app_secret);
        $request->set('code', $code);
        $request->run();
        if ($request->isFailed()) {
            var_dump($request->getErrorMessage());
            var_dump("网页内授权获取用户微信openid失败:无法获取openid");
            exit;
        } else {
            $result = $request->getResult();
            $union_id = $result["unionid"];
            $open_id = $result["openid"];
          //  $third_user = UserThirdParty::findOne(["union_id='" . $union_id . "'", 'columns' => 'user_id']);

            $request = RequestFactory::create("SnsUserInfo", CUR_APP_ID, $config->app_id,  $config->app_secret);
            $request->set('accessToken', $result['accessToken']);
            $request->set('openid', $open_id);
            $request->run();
            $result = $request->getResult();

            $this->session->set("open_id", $open_id);
            // $upload = new Upload();

            // echo file_get_contents($result['headimgurl']);
            // var_dump($upload->getContentFromUrl($result['headimgurl']));
            //没有账号
//            if ($third_user) {
//                $this->session->set("uid", $third_user['user_id']);
//            } else {
//                $request = RequestFactory::create("SnsUserInfo", CUR_APP_ID, $config->app_id,  $config->app_secret);
//                $request->set('accessToken', $result['accessToken']);
//                $request->set('openid', $result['openid']);
//                $request->run();
//                $result = $request->getResult();
//                if ($request->isFailed()) {
//                    var_dump($request->getErrorMessage());
//                    var_dump("网页内获取用户信息失败");
//                    exit;
//                }
//                $img = str_replace('/132', '/0', $result['headimgurl']);
//                $username = $result['nickname'];
//                $sex = $result['sex'];
//                $sex = in_array($sex, [0, 1, 2]) ? $sex : 0;
//                if ($img) {
//                    //上传头像
//                    $avatar = $this->uploadImg($img);
//                } else {
//                    $avatar = UserStatus::$default_avatar;
//                }
//                //注册
//                $uid = \Multiple\Wap\Helper\UserStatus::init()->registerThirdUser($open_id, $union_id, UserStatus::LOGIN_QQ, $username, $avatar, $sex);
//                $this->session->set("uid", $uid);
//            }
            $this->response->redirect($this->session->get("callback_url"));

        }
    }
}