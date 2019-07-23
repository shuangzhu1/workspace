<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/18
 * Time: 16:01
 */

namespace Services\User;


use Components\Queue\Queue;
use Models\User\Users;
use Models\User\UserVideo;
use Models\User\UserVideoQuestion;
use Phalcon\Mvc\User\Plugin;
use Services\Im\ImManager;
use Services\Im\SysMessage;
use Util\Ajax;
use Util\Debug;

class QuestionManager extends Plugin
{
    const STATUS_UNANSWER = 1;//等待回答
    const STATUS_ANSWERED = 2;//已回答
    const STATUS_OVERTIME = 3;//超时已退款
    const STATUS_DELETED = 4;//视频已删除
    private static $instance = null;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 提问
     * @param $uid
     * @param $to_uid
     * @param $question
     * @param $pay_id
     * @param $is_anonymous
     * @param $money
     * @return bool|int
     */
    public function ask($uid,$to_uid,$question,$pay_id,$is_anonymous,$money)
    {
        $user_info = Users::findOne(["id = " . $uid,'columns'=>'username,avatar']);
        $username_ask = $is_anonymous ? '匿名用户' : $user_info['username'];
        $to_user_info = Users::findOne(["id = $to_uid and status = 1",'columns'=>'username,avatar']);
        if( !$to_user_info )
            return false;
        $user_info ? $user_info = json_encode($user_info) : '';

        $to_user_info ? $to_user_info =json_encode($to_user_info) : '';
        $data['uid'] = $uid;
        $data['user_info_ask'] = $user_info;
        $data['to_uid'] = $to_uid;
        $data['user_info_answer'] = $to_user_info;
        $data['question'] = $question;
        $data['pay_id'] = $pay_id;
        $data['is_anonymous'] = $is_anonymous;
        $data['created'] = time();
        $data['money'] = $money;
        $data['video_info'] = '';
        $res = UserVideoQuestion::insertOne($data);
        if( $res )
        {
            // 推送系统消息给被提问者
            //to_user_id 被提问者id
            SysMessage::init()->initMsg(SysMessage::TYPE_NEW_QUESTION, ["to_user_id" => $to_uid]);

            //推送自定义消息
            //ImManager::init()->initMsg(ImManager::TYPE_PAIDQA_NEW_QUESTION,['uid_ask' => $uid,'uid_answer' => $to_uid,'qid' => $res,'question' => $question]);
            ImManager::init()->initMsg(ImManager::TYPE_PAIDQA_CHAT_NEW_QUESTION,['uid_ask' => $uid,'uid_answer' => $to_uid,'qid' => $res,'question' => $question]);
            //Debug::log("新问题推送" . var_export($a) . 'to:' . $to_uid,'debug');

        }else
        {
            return false;
        }

        return $res;

    }

    /**
     * @param $uid int 回答问题者uid
     * @param $qid int 问题id
     * @param $url string 视频资源链接
     * @return bool
     */
    public function publish($uid,$qid,$url)
    {
        $this->db->begin();
        $this->original_mysql->begin();
        $time = time();
        //根据qid获取user_video_question表中video_info字段，判断是否第一次回答
        $question = UserVideoQuestion::init()->findOne(['id = ' . $qid . ' and enable = 1']);
        if( !$question )
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG,'问题已删除');//todo 错误码
        if( $question && !empty($video = $question['video_info']))//二次回答
        {

            $video =json_decode($video,true);
            $r1 = UserVideo::init()->updateOne(['url' => $url],['id ' => $video['vid']]);
            $r2 = UserVideoQuestion::init()->updateOne(['video_info' => json_encode(['vid'=>$video['vid'],'url'=>$url,'created'=>$video['created'],'original_url'=>$video['url'],'update'=>time()],JSON_UNESCAPED_UNICODE)],['id' => $qid]);
            if( !$r1 || !$r2)
                $res = false;
            else
                $res = true;

        }else//首次回答
        {
            $vid = UserVideo::init()->insertOne(['user_id' => $uid, 'url' => $url, 'created' => $time,'qid' => $qid]);
            $res = UserVideoQuestion::init()->updateOne(['video_info'=> json_encode(['vid'=>$vid,'url'=>$url,'created'=>$time],JSON_UNESCAPED_UNICODE),'status'=>self::STATUS_ANSWERED],['id'=>$qid]);
            //推送问题被回答消息
            $ques = UserVideoQuestion::findOne(['id = ' . $qid,'columns'=>'uid,question']);
            ImManager::init()->initMsg(ImManager::TYPE_PAIDQA_CHAT_ANSWER,['uid_ask' => $ques['uid'],'uid_answer'=>$uid,'qid'=> $qid,'question'=>$ques['question']]);
            //取消延时任务
            $task = UserVideoQuestion::findOne(['id' => $qid,"columns" => 'taskid']);
            Queue::init()->delete($task['taskid']);
        }
        if(!$res)
        {
            $this->original_mysql->rollback();
            $this->db->rollback();
            return false;
        }
        $this->original_mysql->commit();
        $this->db->commit();
        /**
         * 推送消息至提问者
         * $uid     回答者uid
         * $to_uid  提问者uid
         */
        //ImManager::init()->initMsg(ImManager::TYPE_PAIDQA_ANSWER,['uid'=>$uid,'username'=>json_decode($question['user_info_answer']['username'],true),'to_uid'=>$question['uid'],'question'=>$question['question'],'url' => $url]);
        //ImManager::init()->initMsg(ImManager::TYPE_PAIDQA_ANSWER,['uid'=>$uid,'username'=>json_decode($question['user_info_answer'],true)['username'],'to_uid'=>$question['uid']]);


        return $question['pay_id'];

    }


}