<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/14
 * Time: 16:39
 * 秀场机器人任务
 */

namespace Multiple\Panel\Controllers;


use Components\Curl\CurlManager;
use Components\Passport\Identify;
use Models\User\UserShow;
use Util\Ajax;
use Util\Curl;

class ShowsquareController  extends ControllerBase
{
    //创建任务
    function addTaskAction()
    {
        //取得参加秀场uid
        $res = UserShow::init()->findList(['columns'=>'user_id','enable=1']);
        foreach ($res as $k => $v)
        {
            $uids[] = $res[$k]['user_id'];
        }
        $post = $this->request->getPost();
        $data = [];
        $data['uids'] =  preg_replace("/(\s+)/",',',trim($post['data']['uids']));
        $data['start'] =  strtotime($post['data']['start']);
        $data['end'] =  strtotime($post['data']['end']);
        $data['score'] = $post['data']['score'];
        $data['e_type'] = 'RSA';
        $data['timestamp'] = time();
        $data['sign'] = Identify::init()->buildRequestMysign($data,'RSA');
        //待操作的uid
        $uids_oper = explode(',',$data['uids']);
        foreach( $uids_oper as $k => $v)
        {
            if(!in_array($v,$uids))
            {
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,"用户{$v}不在秀场中！");
            };
        }
        $result = CurlManager::init()->CURL_POST("http://service.klgwl.com/starshow/mission/make",$data);
        if( $result['curl_is_success'] != 1 )
        {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'CURL请求发送失败，请重试');
        }else
        {
            if( json_decode($result['data'],true)['code'] == 200 )
            {
                Ajax::init()->outRight("操作成功");
                //var_dump(json_decode($result['data'],true)['data']);exit;
                $this->view->setVar('list',$data);
            }else
            {
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,json_decode($result['data'],true)['data']);
            }

        }

    }
    //取消任务
    function abortTaskAction()
    {
        $id = (int) $this->request->getPost('id');
        $type = (int) $this->request->getPost('type');
        $data = [];
        $data['id'] =$id;
        $data['type'] =$type;
        $data['e_type'] = 'RSA';
        $data['timestamp'] = time();
        $data['sign'] = Identify::init()->buildRequestMysign($data,'RSA');
        $result = CurlManager::init()->CURL_POST("http://service.klgwl.com/starshow/mission/cancel",$data);
        if( $result['curl_is_success'] != 1 )
        {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'CURL请求发送失败，请重试');
        }else
        {
            if( json_decode($result['data'],true)['code'] == 200 )
            {
                Ajax::init()->outRight('操作成功');
            }else
            {
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,json_decode($result['data'],true)['data']);
            }

        }
    }
    //任务列表
    function listAction()
    {
        if( $this->request->isPost() )//搜索uid
        {
            $uids = $this->request->getPost('uids');
            if( !empty($uids) ){

                $uids = preg_replace("/(\s+)/",',',trim($uids));
                $data = [];
                $data['uids'] = $uids;
                $data['e_type'] = 'RSA';
                $data['timestamp'] = time();
                $data['sign'] = Identify::init()->buildRequestMysign($data,'RSA');
                $result = CurlManager::init()->CURL_POST("http://service.klgwl.com/starshow/mission/fliter",$data);
                if( $result['curl_is_success'] != 1 )
                {
                    Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'CURL请求发送失败，请重试');
                }else
                {
                    if( json_decode($result['data'],true)['code'] == 200 )
                    {
                        $list = json_decode($result['data'],true)['data'];
                        $this->view->setVar('list',$list);
                        $this->view->setVar('uids',str_replace(',',' ',$uids));
                    }else
                    {
                        Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,json_decode($result['data'],true)['data']);
                    }
                }
            }


        }

            //获取正在执行任务列表
            $data = [];
            $data['type'] =  isset($_GET['type']) ?  (int) $this->request->get('type') : 0;
            $data['e_type'] = 'RSA';
            $data['timestamp'] = time();
            $data['sign'] = Identify::init()->buildRequestMysign($data,'RSA');
            $result = CurlManager::init()->CURL_POST("http://service.klgwl.com/starshow/mission/check",$data);
            if( $result['curl_is_success'] != 1 )
            {
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'CURL请求发送失败，请重试');
            }else
            {
                if( json_decode($result['data'],true)['code'] == 200 )
                {
                    $list = json_decode($result['data'],true)['data'];
                    $this->view->setVar('list',$list);
                    $this->view->setVar('type',$data['type']);
                }else
                {
                    Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,json_decode($result['data'],true)['data']);
                }

            }
        }






}