<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/15
 * Time: 17:06
 */

namespace Multiple\Panel\Controllers;

use OSS\OssClient;
use Services\Site\SiteKeyValManager;
use Util\Ajax;
use Util\LatLng;
use Util\Pagination;
use Util\Time;

/**
 * 悬赏活动
 * Class ActivityController
 * @package Multiple\Panel\Controllers
 */
class ActivityController extends ControllerBase
{
    /**
     * 悬赏活动相关配置
     */
    public function indexAction()#基础配置#
    {
        if( $this->request->isPost() )
        {
            $defined_reward_num =  $this->request->getPost('defined_reward_num');
            $defined_reward_num['min_reward'] = intval($defined_reward_num['min_reward'] * 100);
            $defined_reward_num['min_num'] = intval($defined_reward_num['min_num']);
            $default_reward_num =  $this->request->getPost('default_reward_num');
            $game_list = $this->request->getPost('game_list');

            foreach($default_reward_num as $k => &$v)
            {
                $v['reward'] = floatval($v['reward']) * 100;
                $v['num'] = intval($v['num']);
                $v['limit'] = intval($v['limit']);
                $v['diamond'] = intval($v['diamond']);
                $v['inc_num'] = intval($v['inc_num']);
            }

            foreach( $game_list as $kk => $vv)
            {
                $game_list[$kk]['game_type'] = intval($vv['game_type']);
                $game_list[$kk]['game_status'] = intval($vv['game_status']);
                if( strpos($vv['game_logo'],'data:image') !== false )//图片为base64数据
                {
                    preg_match('/^(data:\s*image\/(\w+);base64,)/', $vv['game_logo'], $result);
                    $content = base64_decode(str_replace($result[1],'',$vv['game_logo']));
                    $imgInfo = getimagesizefromstring($content);
                    $ext_type = $result[2];
                    $objName = Time::getMillisecond() . rand(1000, 9999) . "_s_" . $imgInfo[0] . "x" . $imgInfo[1]  . '.' . $ext_type;
                    $config = $this->config;
                    $client = new OssClient($config->oss['app_key'],$config->oss['app_secret'],$config->oss['end_point']);
                    $res = $client->putObject('klg-useravator',$objName,$content);
                    if( $res )
                        $game_list[$kk]['game_logo'] = $res['info']['url'];
                    else
                        Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'Logo上传失败');
                }
                if( strpos($vv['game_logo_select'],'data:image') !== false )//图片为base64数据
                {
                    preg_match('/^(data:\s*image\/(\w+);base64,)/', $vv['game_logo_select'], $result);
                    $content = base64_decode(str_replace($result[1],'',$vv['game_logo_select']));
                    $imgInfo = getimagesizefromstring($content);
                    $ext_type = $result[2];
                    $objName = Time::getMillisecond() . rand(1000, 9999) . "_s_" . $imgInfo[0] . "x" . $imgInfo[1]  . '.' . $ext_type;
                    $config = $this->config;
                    $client = new OssClient($config->oss['app_key'],$config->oss['app_secret'],$config->oss['end_point']);
                    $res = $client->putObject('klg-useravator',$objName,$content);
                    if( $res )
                        $game_list[$kk]['game_logo_select'] = $res['info']['url'];
                    else
                        Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'Logo上传失败');
                }
            }
            $game_list = array_values($game_list);
            $expire = (int) $this->request->getPost('expire');
            $reward_limit = (int) $this->request->getPost('reward_limit');
            //保存配置
            $this->postApi('activity/config/update/basegame',['defined_reward_num' => json_encode($defined_reward_num),'default_reward_num' => json_encode($default_reward_num),'game_list' => json_encode($game_list),'expire' => $expire,'reward_limit' => $reward_limit]);
            Ajax::init()->outRight();
        }
        $config = $this->postApi('activity/config/check',['type' => 0]);
        $this->view->setVar('config',$config);
    }

    /**
     * 红包雨活动配置
     */
    public function redBagRainAction()#红包雨配置#
    {
        if( $this->request->isPost() )
        {
            $data['count_down'] =(int) $this->request->getPost('count_down');
            $data['redbag_num'] =(int) $this->request->getPost('redbag_num');
            $data['interval'] =(int) $this->request->getPost('interval');
            $data['commit'] =(int) $this->request->getPost('commit');
            $data['idle'] =(int) $this->request->getPost('idle');
            $this->postApi('activity/config/update/rb/rain',$data);
            Ajax::init()->outRight();
        }
        $config = $this->postApi('activity/config/check',['type' => 1]);
        $this->view->setVar('config',$config);
    }
    /**
     * 趣味问答配置
     */
    public function qaAction()#问答配置#
    {
        if( $this->request->isPost() )
        {
            $data['count_down'] =(int) $this->request->getPost('count_down');
            $data['round'] =(int) $this->request->getPost('round');
            $data['interval'] =(int) $this->request->getPost('interval');
            $data['rank_gap'] =(int) $this->request->getPost('rank_gap');
            $this->postApi('activity/config/update/knowledge',$data);
            Ajax::init()->outRight();
        }
        $config = $this->postApi('activity/config/check',['type' => 2]);
        $this->view->setVar('config',$config);
    }

    /**
     * 参与活动的机器人配置
     */
    public function toyAction()#机器人配置#
    {
        if( $this->request->isPost() )
        {
            $post = $this->request->getPost();
            foreach( $post as $k => &$v)
            {
                if( strpos($k,'ac_cost') !== false )#金额x100
                {
                    $tmp = explode(',',str_replace('，',',',$v));
                    foreach( $tmp as  &$vv)
                    {
                        $vv = $vv * 100;
                    }
                    $v = implode(',',$tmp);

                }else
                {
                    $v = (int) $v;
                }

                if(in_array($k,['ac_top','new_ac','join','quick_reply','join_user_ac']) && $v == 0)#关闭功能
                    $v = -1;

            }
            $this->postApi('toy/config/update',$post,'http://120.76.47.205:8800/');
            Ajax::init()->outRight();
        }
        $config = $this->postApi('toy/config/check',[],'http://120.76.47.205:8800/');
        $this->view->setVar('config',$config);
    }

    /**
     * 机器人配置中ios-test，ios是否提测中
     */
    public function iosTestAction()
    {
        $ios_test = (int) $this->request->get('ios_test','int',0);
        $this->postApi('toy/config/update',['ios_test' => $ios_test],'http://120.76.47.205:8800/');
        Ajax::init()->outRight();
    }

    /**
     * 题库分类管理
     */
    public function classifyAction()#题库管理#
    {
        if( $this->request->isPost() )
        {
            $option = (int) $this->request->getPost('operate','int');
            $id = (int) $this->request->getPost('id','int');
            $type_name = $this->request->getPost('type_name');
            $data = [];
            switch ($option){
                case 1 :
                    $data['operate'] = 1;
                    $data['type_name'] = $type_name;
                    break;
                case 2 :
                    $data['operate'] = 2;
                    $data['type_name'] = $type_name;
                    $data['id'] = $id;
                    break;
                case 3 :
                    $data['operate'] = 3;
                    $data['id'] = $id;
                    break;
            }
            $this->postApi('activity/question/bank/classify/option',$data);
            Ajax::init()->outRight();
        }
        $classify = $this->postApi('activity/question/bank/classify/option',[]);
        $this->view->setVar('classify',$classify);
    }

    /**
     * 题库管理
     */
    public function questionBankAction()#题库列表#
    {
        if( $this->request->isPost() )
        {
            $type = (int) $this->request->getPost('type','int');//题库id
            if( $this->request->hasFiles() )//上传excel文件
            {
                $file = $this->request->getUploadedFiles(true);
                if(!empty($file) )
                {
                    $file = $file[0];
                    $this->postApi('activity/question/bank/question/uploadbyfile',['type' => $type,'file_data' => file_get_contents($file->getTempName())]);
                    Ajax::init()->outRight();
                }else
                {
                    Ajax::init()->outError('文件上传失败');
                }
            }
            $id = (int) $this->request->getPost('id','int');//问题id
            $operate = (int) $this->request->getPost('operate','int');//操作类型：增删改
            $answer = (int) $this->request->getPost('answer','int');//答案 0-3
            $answer_type = (int) $this->request->getPost('answer_type','int');//单选、多选 0/1
            $desc = $this->request->getPost('desc');//问题
            $level = (int) $this->request->getPost('level','int');//问题
            $options = json_encode($this->request->getPost('options'),JSON_UNESCAPED_UNICODE);//选项
            $data = [];
            switch ($operate)
            {
                case 1 :
                    $data['type'] = $type;
                    $data['desc'] = $desc;
                    $data['options'] = $options;
                    $data['answer'] = $answer;
                    $data['answer_type'] = $answer_type;
                    $data['operate'] = $operate;
                    $data['level'] = $level;
                    break;
                case 2 :
                    $data['type'] = $type;
                    $data['id'] = $id;
                    $data['desc'] = $desc;
                    $data['options'] = $options;
                    $data['answer'] = $answer;
                    $data['answer_type'] = $answer_type;
                    $data['operate'] = $operate;
                    $data['level'] = $level;
                    break;
                case 3 :
                    $data['type'] = $type;
                    $data['id'] = $id;
                    $data['operate'] = $operate;
                    break;
            }
            $this->postApi('activity/question/bank/question/option',$data);
            Ajax::init()->outRight();
        }
        $keyword = $this->request->get('keyword','string','');
        $p =(int) $this->request->get('p','int',1);
        $limit = (int) $this->request->get('limit','int',20);
        $type = (int) $this->request->get('type','int',1);
        $where = "where type = " . $type;
        if(!empty($keyword))
            $where .= " and description like '%". $keyword ."%'";
        $count = $this->question_bank->query("select count(1) as num from question $where ")->fetch(\PDO::FETCH_ASSOC)['num'];
        $classify = $this->postApi('activity/question/bank/classify/option',[]);
        //$list = $this->postApi('activity/question/bank/question/option',['offset' => ($p-1)*$limit,'Limit' => $limit,'type' => $type]);
        $list = $this->question_bank->query("select * from question $where  order by id desc limit " . ($p -1)*$limit . ",$limit")->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($keyword))
        {
            foreach($list as &$item)
            {
                $item['description'] = str_replace($keyword,'<span class="red">' . $keyword. '</span>',$item['description']);
            }
        }

        Pagination::instance($this->view)->showPage($p,$count,$limit);
        $this->view->setVar('list',$list);
        $this->view->setVar('classify',$classify);
        $this->view->setVar('type',$type);

    }

    /**
     * 题库编辑后，通知服务器重载题库数据
     */
    public function reloadAction()
    {
        if( $this->request->isPost() )
        {
            $id = (int) $this->request->getPost('id');
            $this->postApi('activity/question/bank/question/reload',['id' => $id]);
            Ajax::init()->outRight();
        }
    }
    public function shortReplyAction()#快捷回复#
    {
        $shortReply = json_decode(SiteKeyValManager::init()->getOneByKey('chat_room','shortReply')['val'],true);
        $ac_type = $this->request->get('ac_type','int',0);
        $keys = array_column($shortReply,'type');
        $shortReply = array_combine($keys,$shortReply);
        if( $this->request->isPost() )
        {
            $word = $this->request->getPost('word');//增加单个短语
            $key = $this->request->getPost('key','int',-1);
            $type = $this->request->getPost('type','string');//
            $words = $this->request->getPost('words');//排好序的所有短语
            switch($type)
            {
                case 'edit' ://编辑和添加
                    if($key >= 0)//修改
                    {
                        $shortReply[$ac_type]['phrases'][$key] = $word;
                        $shortReply = array_values($shortReply);
                        $res = SiteKeyValManager::init()->setValByKey('chat_room','shortReply',['val' => json_encode($shortReply,JSON_UNESCAPED_UNICODE)]);

                    }else//新增
                    {
                        $shortReply[$ac_type]['phrases'][] = $word;
                        $shortReply = array_values($shortReply);
                        $res = SiteKeyValManager::init()->setValByKey('chat_room','shortReply',['val' => json_encode($shortReply,JSON_UNESCAPED_UNICODE)]);
                    }
                    break;
                case 'del' ://删除
                    unset($shortReply[$ac_type]['phrases'][$key]);
                    $shortReply[$ac_type]['phrases'] = array_values($shortReply[$ac_type]['phrases']);
                    $shortReply = array_values($shortReply);
                    $res = SiteKeyValManager::init()->setValByKey('chat_room','shortReply',['val' => json_encode($shortReply,JSON_UNESCAPED_UNICODE)]);
                    break;
                case 'order' ://排好序的所有
                    $shortReply[$ac_type]['phrases'] = array_values($words);
                    $shortReply = array_values($shortReply);
                    $res = SiteKeyValManager::init()->setValByKey('chat_room','shortReply',['val' => json_encode($shortReply,JSON_UNESCAPED_UNICODE)]);
                    break;
            }


            if( $res )
                Ajax::init()->outRight();
            else
                Ajax::init()->outError('操作失败');

        }
        $this->view->setVar('words',$shortReply[$ac_type]);
        $this->view->setVar('ac_type',$ac_type);
        $this->view->pick('activity/shortReply');
    }

    /**
     * 阶段概要
     */
    public function summaryAction()#阶段概要#
    {
        $begin = $this->request->get('begin','int',date('Y/m/d',strtotime( "-1 day")));
        $end = $this->request->get('end','int',date('Y/m/d',strtotime( "-1 day")));
        $this->view->setVar('begin',$begin);
        $this->view->setVar('end',$end);
    }

    /**
     * 活动趋势
     */
    public function trendAction()#活动趋势#
    {
        $range = $this->postApi('forms/range',['type' => 'activity']);
        $this->view->setVar('begin',date("Y/m/d",strtotime($range['begin'])));
        $this->view->setVar('end',date("Y/m/d",strtotime($range['end'])));
    }

    /**
     * 用户发起活动列表
     */
    public function recordAction()#活动列表#
    {
        $p = $this->request->get('p','int',1);
        $limit = $this->request->get('limit','int',15);
        $uid = $this->request->get('uid','int',0);
        $type = $this->request->get('type','int',-1);
        $launch_start = $this->request->get('launch_start','string','');
        $launch_end = $this->request->get('launch_end','string','');
        $play_start = $this->request->get('play_start','string','');
        $play_end = $this->request->get('play_end','string','');

        $where = ['uid >= 5000' ];

        if( !empty($uid) )
        {
            $where[] = "uid = " . $uid;
        }
        if( $type != -1 )
        {
            $where[] = "type = " . $type;
        }
        if( !empty($launch_start) )
        {
            $where[] = " created >= " . strtotime( $launch_start );
        }
        if( !empty($launch_end) )
        {
            $where[] = " created <= " . strtotime( $launch_end . " +1 day" );
        }

        if( !empty($play_start) )
        {
            $where[] = " start_time >= " . strtotime( $play_start );
        }
        if( !empty($play_end) )
        {
            $where[] = " created <= " . strtotime( $play_end . " + 1 day" );
        }
        if( !empty($where) )
        {
            $where = implode(' and ',$where);
            $where = 'where ' . $where;
        }
        $count = $this->activity->query("select count(1) from activity " . $where)->fetch(\PDO::FETCH_ASSOC)['count(1)'];
        $list = $this->activity->query("select * from activity " . $where . ' order by created desc' . " limit " . ($p -1 ) * $limit . ',' . $limit)->fetchAll(\PDO::FETCH_ASSOC);
        /*if( $list )
        {
            $location = [];
            foreach( $list as $item )
            {
                $tmp = LatLng::getAddress($item['lng'],$item['lat'],'gaode');
                $location[$item['id']] =
                    (is_string($tmp['province']) ? $tmp['province'] : '未知') . ' / ' .
                    (is_string($tmp['city']) ? $tmp['city'] : '未知') . ' / ' .
                    (is_string($tmp['district']) ? $tmp['district'] : '未知');
            }

            $this->view->setVar('location',$location);
        }*/
        $this->view->setVar('list',$list);
        $this->view->setVar('type',$type);
        Pagination::instance($this->view)->showPage($p,$count,$limit);
    }

}