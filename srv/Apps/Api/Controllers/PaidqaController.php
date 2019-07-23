<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/18
 * Time: 15:23
 */

namespace Multiple\Api\Controllers;


use Components\Queue\Queue;
use Models\Site\SiteKeyVal;
use Models\User\UserVideo;
use Models\User\UserVideoQuestion;
use Phalcon\Di;
use Phalcon\Exception;
use Services\Site\SiteKeyValManager;
use Services\User\QuestionManager;
use Services\User\UserStatus;
use Util\Ajax;
use Util\Debug;

class PaidqaController extends ControllerBase
{
    /**
     *获取随机问题 todo 按权重随机返回问题
     */
    public function getSomeQuestionsAction()
    {
        $data = self::_getRandQues(5);

        Ajax::init()->outRight($data);
    }

    private static function _getRandQues($num=5)
    {
        $cache_key = 'site_key_val_cache_' . SiteKeyValManager::KEY_HOT_QUESTION;
        $questions = Di::getDefault()->get('redis')->get($cache_key);
        if(!$questions)
        {
            $questions = SiteKeyValManager::init()->getOneByKey(SiteKeyValManager::KEY_HOT_QUESTION)['val'];

        }
        $questions = json_decode($questions,true);
        $keys = array_rand($questions,$num);
        $data = [];
        foreach($keys as $key )
        {
            $data[] = $questions[$key];
        }
        return $data;
    }


    /**
     * 提问
     */
    public function askAction()
    {
        $uid = $this->request->get('uid','int','');
        $to_uid = $this->request->get('to_uid','int','');
        $question = $this->request->get('question','green','');
        $pay_id= $this->request->get('pay_id','string','');
        $is_anonymous= $this->request->get('is_anonymous','int',0);
        $money= $this->request->get('money','int',0);
        if ( $uid === '' || !$to_uid || !$question || !$pay_id ) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = QuestionManager::getInstance()->ask($uid,$to_uid,$question,$pay_id,$is_anonymous,$money);//返回值问题id
        if ($res) {
            //添加延时任务，48小时更新问题状态
            $taskid = Queue::init()->push('http://api.klgwl.com/paidqa/chStat',['pay_id' => $pay_id ],2*Queue::DAY);
            if( $taskid  )//记录task ID
                UserVideoQuestion::updateOne(['taskid' => $taskid],['id' => $res]);
            $this->ajax->outRight('');
        } else {
            $this->ajax->outError(Ajax::FAIL_QUESTION);
        }
    }

    /**
     *
     *获取待回答问题列表
     */
    public function getUnAnswersAction()
    {
        $uid = $this->request->get('uid');
        if(!$uid)
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $list = UserVideoQuestion::init()->findList(["to_uid = $uid and status = 1 and enable = 1",'order'=>'created desc','offset' => ($page - 1) * $limit, 'limit' => $limit]);
        $data = [];
        foreach( $list as $k => $item )
        {
            $data[$k]['qid'] = $item['id'];
            $data[$k]['question'] = $item['question'];
            $data[$k]['created'] = $item['created'];
            $data[$k]['uid'] = $item['is_anonymous'] ? '':$item['uid'];
            $data[$k]['username'] = $item['is_anonymous'] ? '匿名用户':json_decode($item['user_info_ask'],true)['username'];
            $data[$k]['avator'] = $item['is_anonymous'] ? UserStatus::$default_avatar:json_decode($item['user_info_ask'],true)['avatar'];
        }

        $hotQues = self::_getRandQues(3);
        $res['data_count'] = count($data);
        $res['data_list'] = $data;
        $res['hotQues'] = $hotQues;
        Ajax::init()->outRight($res);
    }

    /**
     * 发布视频答案
     */
    public function publishAction()
    {
        $uid = $this->request->get('uid');
        $url = $this->request->get('url');
        $qid = $this->request->get('qid');
        if(!$uid || !$url || !$qid )
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        $res = QuestionManager::getInstance()->publish($uid,$qid,$url);
        if($res)
        {


            if( is_bool($res) )
            {
                Ajax::init()->outRight();
            }else
            {
                Ajax::init()->outRight($res);
            }
        }
        else
            Ajax::init()->outError(Ajax::FAIL_PUBLISH);//发布失败
    }

    /**
     * 我答
     */
    public function getMyAnswersAction()
    {
        $uid = $this->request->get('uid');
        if(!$uid)
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        $page = $this->request->get('page','int',1);
        $limit = $this->request->get('limit','int',20);
        $list = UserVideoQuestion::init()->findList(['to_uid = ' . $uid .' and status <> 3 and enable = 1','order'=>'created desc','offset'=>($page -1)*$limit,'limit'=>$limit]);
        $data = [];
        foreach( $list as $k=>$v)
        {
            $data[$k]['qid'] = $v['id'];
            $data[$k]['question'] = $v['question'];
            $data[$k]['video_url'] = $v['status'] == 2 ? json_decode($v['video_info'],true)['url'] : '';
            $data[$k]['vid'] = $v['status'] == 2 ? json_decode($v['video_info'],true)['vid'] : '';
            /*if($v['status'] == 2 && ((time() - json_decode($v['video_info'],true)['created']) < 3600*2 ))//回答两小时以内可重新回答
                $data[$k]['status'] = 5;//可重新录制
            else*/
            $data[$k]['status'] = $v['status'];

            $data[$k]['money'] = $v['status'] != 1 ? $v['money'] : -1;
            $data[$k]['created'] = $v['created'];
            $data[$k]['uid'] = $v['is_anonymous'] ? '' :$v['uid'];
            $data[$k]['username'] = $v['is_anonymous'] ? '匿名用户' : json_decode($v['user_info_ask'],true)['username'];
            $data[$k]['avator'] = $v['is_anonymous'] ? UserStatus::$default_avatar : json_decode($v['user_info_ask'],true)['avatar'];
        }
        $res['data_count'] = count($data);
        $res['data_list'] = $data;
        Ajax::init()->outRight($res);

    }

    /**
     * 我问
     */
    public function getMyQuestionsAction()
    {
        $uid = $this->request->get('uid');
        if(!$uid)
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        $page = $this->request->get('page','int',1);
        $limit = $this->request->get('limit','int',20);
        $list = UserVideoQuestion::init()->findList(['uid = ' . $uid .' and enable = 1','order'=>'status,created desc','offset'=>($page -1)*$limit,'limit'=>$limit]);
        $data = [];
        foreach( $list as $k=>$v)
        {
            $data[$k]['qid'] = $v['id'];
            $data[$k]['question'] = $v['question'];
            $data[$k]['status'] = $v['status'];
            $data[$k]['video_url'] = $v['status'] == 2 ? json_decode($v['video_info'],true)['url'] : '';
            $data[$k]['vid'] = $v['status'] == 2 ? json_decode($v['video_info'],true)['vid'] : '';
            $data[$k]['money'] = $v['money'];
            $data[$k]['created'] = $v['created'];
            $data[$k]['uid'] = $v['is_anonymous'] ? '' :$v['to_uid'];
            $data[$k]['username'] = json_decode($v['user_info_answer'],true)['username'];
            $data[$k]['avator'] = json_decode($v['user_info_answer'],true)['avatar'];
        }
        $res['data_count'] = count($data);
        $res['data_list'] = $data;
        Ajax::init()->outRight($res);
    }

    public function getSummaryAction()
    {
        $uid = $this->request->get('uid');
        $type = $this->request->get('type','int',1);//1:我问；2：我答
        if( !$uid || !in_array($type,[1,2]))
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        if( $type == 1)//我问
        {
            $list = UserVideoQuestion::init()->findList(['uid = ' . $uid . ' and enable = 1 and status in(2,4)','columns' => 'money,video_info' ]);
            $data['count'] = count($list);
            $data['money'] = 0;
            $vids = [];

            foreach ($list as $k => $v)
            {
                $data['money'] += $v['money'];
                if( !empty($video = json_decode($v['video_info'],true)) )
                {
                    $vids[] =  $video['vid'];
                }
            }
            //视频总浏览量
            if( !empty($vids) )
            {
                $data['view_cnt'] = UserVideo::init()->findList(['id in(' . implode(',',$vids) . ')' , 'columns'=>'SUM(view_cnt) as view_cnt']);
                if( !is_null($data['view_cnt'][0]['view_cnt']) )
                    $data['view_cnt'] = $data['view_cnt'][0]['view_cnt'];
                else
                    $data['view_cnt'] = 0;
            }else
                $data['view_cnt'] = 0;

            Ajax::init()->outRight($data);
        }else
        {
            $list = UserVideoQuestion::init()->findList(['to_uid = ' . $uid . ' and enable = 1 and status in(2,4)','columns' => 'money,video_info' ]);
            $data['count'] = count($list);
            $data['money'] = 0;
            $vids = [];

            foreach ($list as $k => $v)
            {
                $data['money'] += $v['money'];
                if( !empty($video = json_decode($v['video_info'],true)) )
                {
                    $vids[] =  $video['vid'];
                }
            }
            //视频总浏览量
            if( !empty($vids) )
            {
                $data['view_cnt'] = UserVideo::init()->findList(['id in(' . implode(',',$vids) . ')' , 'columns'=>'SUM(view_cnt) as view_cnt']);
                if( !is_null($data['view_cnt'][0]['view_cnt']) )
                    $data['view_cnt'] = $data['view_cnt'][0]['view_cnt'];
                else
                    $data['view_cnt'] = 0;
            }
            else
                $data['view_cnt'] = 0;

            Ajax::init()->outRight($data);
        }
    }

    public function getQuesDetailAction()
    {
        $qid = $this->request->get('qid','int',0);
        $uid = $this->uid;
        if( !$qid || !$uid )
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        $res = UserVideoQuestion::findOne(['id = ' . $qid,'columns' => "id,uid,user_info_ask,to_uid,user_info_answer,question,money,pay_id,video_info,status"]);
        if( !$res )
            Ajax::init()->outError(Ajax::INVALID_PARAM);
        $data = [];
        $data['qid'] = $res['id'];
        $data['status'] = $res['status'];
        $data['question'] = $res['question'];
        $user_info_ask = json_decode($res['user_info_ask'],'true');
        $user_info_ask['uid'] = $res['uid'];
        $data['user_info_ask'] = $user_info_ask;
        $user_info_answer = json_decode($res['user_info_answer'],'true');
        $user_info_answer['uid'] = $res['to_uid'];
        $data['user_info_answer'] = $user_info_answer;
        $data['money'] = $res['money'];
        $data['pay_id'] = $res['pay_id'];
        if( $res['status'] == QuestionManager::STATUS_ANSWERED )
        {
            $data['url'] = json_decode($res['video_info'],true)['url'];
            $data['vid'] = json_decode($res['video_info'],true)['vid'];

        }
        else
        {
            $data['url'] = '';
            $data['vid'] = '';

        }
        Ajax::outRight($data);
    }

    /**
     * 问题过期回调
     */
    public function chStatAction()
    {
        $data = $this->request->get();
        $pay_id = isset($data['pay_id']) ? $data['pay_id'] : 0;
        if($pay_id != 0 && UserVideoQuestion::exist(['pay_id' => $pay_id , 'status' => 1]))
        {
            $res = UserVideoQuestion::updateOne(['status'=>QuestionManager::STATUS_OVERTIME],['pay_id' => $pay_id]);
            if( !$res )
                Debug::log('pay_id:' . $pay_id  . '更改失败','callback_qaidqa');
            else
                Debug::log('pay_id:' . $pay_id  . '更改成功',"callback_qaidqa");
        }else
            Debug::log('pay_id不合法:' . $pay_id,"callback_qaidqa");
    }
}