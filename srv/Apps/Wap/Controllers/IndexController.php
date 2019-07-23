<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/2/23
 * Time: 19:35
 */

namespace Multiple\Wap\Controllers;


use Components\Queue\Queue;
use Models\Admin\AdminMenusCat;
use Models\Agent\Agent;
use Models\User\UserInfo;
use Models\User\Users;
use Models\User\UserVideoQuestion;
use OSS\OssClient;
use Phalcon\Crypt;
use Phalcon\Debug\Dump;
use Phalcon\Di;
use Phalcon\Mvc\View;
use Services\Site\CurlManager;
use Services\Site\SiteKeyValManager;
use Services\Site\VerifyCodeManager;
use Services\Upload\OssManager;
use Services\User\UserStatus;
use Util\Ajax;
use Util\Curl;
use Util\Debug;
use Util\EasyEncrypt;
use Util\Ip;
use Util\Qqwry;

class IndexController extends ControllerBase
{
    public function indexAction()
    {

        $this->view->disableLevel([
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true
        ]);
        $this->view->pick('index/index');

    }
    public function androidAction()
    {


        $this->view->pick('index/android');

    }

    public function openAppAction()
    {
       $this->view->pick('openapp');
    }

    public function oauth2Action()
    {
        $this->view->disableLevel([
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true
        ]);
        $this->view->pick('oauth2');
    }

    public function callbackAction()
    {

        //获取access_token
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => 5,
            'client_secret' => 'Mu3cStLvbXO90AGBIIhvZrdj3mMoBkOVqtQaaFU6',
            'redirect_uri' => 'http://wap.klgwl.com/index/callback',
            'code' => $this->request->get('code'),
        ];
        //Debug::log(var_export($data,true),'oauth');

        $token = CurlManager::init()->CURL_POST('openauth.klgwl.com/oauth/token', $data);

        if( $token['curl_is_success'] === 1 )
        {
            $client = new \GuzzleHttp\Client();

            $response = $client->request('GET', 'http://openauth.klgwl.com/api/user', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.json_decode($token['data'],true)['access_token'],
                ],
            ]);
            $userinfo =  (string) $response->getBody();
            //var_dump($userinfo);
            Debug::log('用户信息：' . var_export($userinfo,true),'oauth');
            $user = json_decode($userinfo,true);
            $this->session->set('username',$user['username']);
            $this->response->redirect('http://wap.klgwl.com/game/index?canel_auth=1');
        }
        //Debug::log(var_export($user,true),'oauth');

        $this->response->redirect('http://wap.klgwl.com/game/index?canel_auth=0');

        //Debug::log(var_export($this->request->get(),true),'oauth');
    }

    public function tokenAction()
    {
        Debug::log('后期头肯后回调：' . var_export($this->request->get(),true),'oauth');
    }

    public function gameAction()
    {

    }

    public function relationshipAction()
    {
        $res = Agent::findList(['columns' => 'id,user_id,parent_merchant']);
        $res = array_combine(array_column($res,'user_id'),$res);
        $tree = [];//关系链
        self::getCategoryTree($res,$tree);

            self::getChildrenCount($tree);

        header('Content-Type:application/json');
        echo json_encode($tree,JSON_UNESCAPED_UNICODE);
        /*var_dump($tree);*/exit;
    }

    private function getCategoryTree(&$list,&$tree)
    {
        foreach($list as $key=>&$node){
            if(isset($list[$node['parent_merchant']])){
                $list[$node['parent_merchant']]['children'][$key] = &$list[$key];
            }else{
                $tree[] = &$list[$node['user_id']];
            }
            //unset($node['parent_merchant']);
        }
        $key = array_column($tree,'user_id');
        $tree = array_combine($key,$tree);
    }

    private function getChildrenCount(&$tree)
    {
        foreach( $tree as &$item )
        {
            static $count = 0;
            if( isset($item['children'] ) )
            {
                $count += count($item['children']) ;
                /*$item['children']['childrenCount'] = count($item['children']);
                isset($item['childrenCount']) && $item['childrenCount'] += (count($item['children']) -1);*/
                self::getChildrenCount($item['children']);
            }else
            {
                $count = 0;
            }
            $item['childrenCount'] = $count;
        }

    }

    public function getMenusAction()
    {
        $menus = $this->original_mysql->query("select id,name,parent_id as pid from shop_category")->fetchAll(\PDO::FETCH_ASSOC);
        var_dump(self::getTree($menus,0));exit;
    }


    private function getTree($data,$pid)
    {
        $tree = '';
        foreach($data as $k => $v)
        {
            if($v['pid'] == $pid)
            {
                $v['child'] = self::getTree($data, $v['id']);
                $tree[] = $v;
            }
        }
        return $tree;
    }

    public function ossAction()
    {
        //http://circleimg.klgwl.com/4852/351522674171_s_582x800.png
        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        var_dump($oss->deleteObjects(OssManager::BUCKET_USER_AVATOR,['4852/351522674171_s_582x800.png']));exit;
    }
}