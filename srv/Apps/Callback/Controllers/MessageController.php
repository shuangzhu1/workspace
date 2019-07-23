<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24
 * Time: 14:38
 */

namespace Multiple\Callback\Controllers;


use Models\System\SystemMessagePush;
use Models\User\MessageTimingPush;
use Models\User\UserInfo;
use OSS\OssClient;
use Services\Im\ImManager;
use Services\Im\SysMessage;
use Services\User\UserStatus;
use Upload\Upload;
use Util\Ajax;
use Util\Debug;
use Util\ImgSize;

class MessageController extends ControllerBase
{
    //恐龙君定时推送消息回调
    public function timingAction()
    {

        $params = $this->request->get();
        $res = MessageTimingPush::findOne(['id = ' . $params['id'] . ' and timing_day = ' . $params['timing_day'] .' and enable = 1']);
        if( $res )
        {
            $data = json_decode($res['data'],true);
            $msg = '';
            if (!$data) {
                Ajax::outError(Ajax::INVALID_PARAM);
            }

            //全部用户
            if ($data['user_type'] == 1) {

                $data['uids'] = implode(',', UserInfo::getColumn(['status=' . UserStatus::USER_TYPE_NORMAL, 'columns' => 'user_id as id'], 'id'));
            } //部分用户
            else if ($data['user_type'] == 2) {
                if ($data['uids'] == '') {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "没有选择用户");
                }
            } else {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }

            //文字+链接
            if ($data['msg_type'] == 1) {
                if ($data['content'] == '') {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "内容不能为空");
                }
                $msg = str_replace('<br>', '', $data['content']);
                $msg = str_replace('&nbsp;', '', $msg);
                $msg = str_replace('<p>', '', $msg);
                $msg = str_replace('</p>', '', $msg);

                $content = $msg;
            } //单图加标题
            else if ($data['msg_type'] == 2) {
                if ($data['title'] == '') {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "标题不能为空");
                }
                if ($data['link'] == '') {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "链接地址不能为空");
                }
                if ($data['thumb'] == '') {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请选择图片");
                }

                $item = explode('?', $data['thumb']);
                if( strpos($item[0],'data:image') !== false )//前段base64上传图片
                {

                    $img = ImgSize::getBase64ImgBlob($item[0]);

                    $config = $this->di->get('config')->oss;
                    $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
                    $md5 = md5($img[0]);
                    if ($url = Upload::checkFile($md5)) {
                        $data['thumb'] = $url;
                    } else {
                        $name = ImManager::ACCOUNT_SYSTEM . '/' . ImManager::ACCOUNT_SYSTEM . rand(0, 1000) . "_s_" . $item[1] . "." . $img[1];
                        $res = $oss->putObject("klg-chatimg", $name, $img[0]);
                        if ($res && !empty($res['info']['url'])) {
                            $url = str_replace('http://klg-chatimg.oss-cn-shenzhen.aliyuncs.com/', 'http://chatimg.klgwl.com/', $res['info']['url']);
                            Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $img[1], 'type' => 'img', 'size' => strlen($img[0]), 'name' => $name, 'url' => $url, 'created' => time()]);
                            $data['thumb'] = $url;
                        } else {
                            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "云信图片上传失败");
                        }
                    }
                }else//前端上传oss图片地址
                {
                    $data['thumb'] = $item[0];
                }

                $content = json_encode(['title' => $data['title'], 'link' => $data['link'], 'thumb' => $data['thumb']], JSON_UNESCAPED_UNICODE);
                $msg = $data['message'] ? $data['message'] : '图文消息';
            } //多图加标题文字
            else if ($data['msg_type'] == 3) {
                if (count($data['media_data']) < 1) {
                    $this->ajax->outError(Ajax::INVALID_PARAM);
                }
                $config = $this->di->get('config')->oss;
                $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
                $content = [];
                foreach ($data['media_data'] as $media) {
                    $temp = ['title' => $media['title'], 'link' => $media['link']];
                    $item = explode('?', $media['thumb']);

                    if( strpos($item[0],'data:image') !== false)//图片为base64格式
                    {
                        $img = ImgSize::getBase64ImgBlob($item[0]);
                        $md5 = md5($img[0]);
                        if ($url = Upload::checkFile($md5)) {
                            $temp['thumb'] = $url;
                        } else {
                            $name = ImManager::ACCOUNT_SYSTEM . '/' . ImManager::ACCOUNT_SYSTEM . rand(0, 1000) . "_s_" . $item[1] . "." . $img[1];
                            $res = $oss->putObject("klg-chatimg", $name, $img[0]);
                            if ($res && !empty($res['info']['url'])) {
                                $url = str_replace('http://klg-chatimg.oss-cn-shenzhen.aliyuncs.com/', 'http://chatimg.klgwl.com/', $res['info']['url']);
                                Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $img[1], 'type' => 'img', 'size' => strlen($img[0]), 'name' => $name, 'url' => $url, 'created' => time()]);
                                $temp['thumb'] = $url;
                            } else {
                                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "云信图片上传失败");
                            }
                        }
                    }else
                    {
                        $temp['thumb'] = $item[0];
                    }


                    $content[] = $temp;
                }
                $content = json_encode(['data_list' => $content], JSON_UNESCAPED_UNICODE);
                $msg = $data['message'] ? $data['message'] : '图文消息';
            } else {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            $push_message = new SystemMessagePush();
            $message = ["tpl_type" => $data['msg_type'], 'content' => $content, 'need_uids' => $data['uids'], 'created' => time(), 'admin_ids' => $params['admin_id'], 'user_type' => $data['user_type']];

            //消息推送
            if ($push_id = $push_message->insertOne($message)) {

                $success_uids = []; //成功发送消息的用户
                $uids = explode(',', $data['uids']);
                $is_success = true;

                //云信最多支持 500 人一次
                if (count($uids) > 500) {
                    $i = 0;
                    $batch = array_splice($uids, $i, 500);
                    while ($batch) {
                      //$i += 500;
                        if (SysMessage::init()->initMsg(SysMessage::TYPE_SYSTEM_PUSH, ['to_user_id' => json_encode($batch), 'msg' => $msg, 'ext' => $content, 'tpl_type' => $data['msg_type']])) {
                            $success_uids = array_merge($success_uids, $batch);
                        } else {
                            $is_success = false;
                        }
                        $batch = array_splice($uids, $i, 500);
                    }
                } else {
                    if ($res = SysMessage::init()->initMsg(SysMessage::TYPE_SYSTEM_PUSH, ['to_user_id' => json_encode($uids), 'msg' => $msg, 'ext' => $content, 'tpl_type' => $data['msg_type']])) {
                        $success_uids = array_merge($success_uids, $uids);
                    } else {
                        $is_success = false;
                    }
                }

                $res = $this->original_mysql->query("update system_message_push set status=" . ($is_success ? 1 : ($success_uids ? 3 : 2)) . ",success_uids='" . implode(',', $success_uids) . "' where id=" . $push_id);

                if ($res) {
                    if ($is_success) {
                        MessageTimingPush::updateOne(['status' => 2],['id' => $params['id']]);
                        $this->ajax->outRight("发送成功");
                    } elseif ($success_uids) {
                        MessageTimingPush::updateOne(['status' => 2],['id' => $params['id']]);
                        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "部分发送成功");
                    } else {
                        MessageTimingPush::updateOne(['status' => 3],['id' => $params['id']]);
                        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "发送失败");
                    }
                }

            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "保存失败");
            }
        }

    }
    //结果回调
    public function resNotifyAction()
    {
        Debug::log(var_export($this->request->get(),true),'callback/notify/message');
    }
}