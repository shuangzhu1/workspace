<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/3
 * Time: 20:48
 */

namespace Multiple\Panel\Api;


use Components\Queue\Queue;
use Components\YunPian\lib\TplOperator;
use Components\Yunxin\ServerAPI;
use Models\Admin\Admins;
use Models\Site\SiteKeyVal;
use Models\Site\SiteMaterial;
use Models\System\SystemMessagePush;
use Models\User\Message;
use Models\User\MessageTimingPush;
use Models\User\UserInfo;
use Models\User\Users;
use Models\User\UserVideoQuestion;
use Services\Admin\AdminLog;
use Services\Im\ImManager;
use Services\Im\NotifyManager;
use Services\Im\SysMessage;
use Services\MiddleWare\Sl\Request;
use Services\Site\MaterialManager;
use Services\Site\SiteKeyValManager;
use Services\Site\VerifyCodeManager;
use Services\User\UserStatus;
use Upload\Upload;
use Util\Ajax;
use Util\ImgSize;
use OSS\OssClient;


class MessageController extends ApiBase
{
    /*更新短信模板*/
    public function smsUpdateAction()
    {

        $id = $this->request->getPost('id', 'int', 0);
        $content = $this->request->getPost('content', 'string', '');
        $sub_key = $this->request->getPost('sub_key', 'string', '');
        $name = $this->request->getPost('name', 'string', '');
        $unsubmit = $this->request->getPost('unsubmit', 'int', 0);
        $tpl_id = $this->request->getPost('tpl_id', 'string', '');

        if (!$id || !$content || !$sub_key || !$name) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //数据不存在
        if (!$data = SiteKeyValManager::init()->getById($id, true)) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        AdminLog::init()->add('编辑短息模板', AdminLog::TYPE_MSG_TEMPLATE, $id, array('type' => "update", 'id' => $data));

        //不需要提交至云片
        if ($unsubmit) {
            if (!$tpl_id) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '请输入云片模板id');
            }

            if (!SiteKeyVal::updateOne(['val' => $content, 'sub_key' => $sub_key, 'name' => $name, 'param' => $tpl_id], ['id' => $id])) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '编辑失败');
            } else {
                $this->ajax->outRight("编辑成功");
            }
        } else {
            if ($content != $data['val']) {
                $sms = new  TplOperator();
                $result = $sms->upd(['tpl_id' => $data['param'], 'tpl_content' => '【恐龙谷网络】' . $content]);
                if (!($result && $result->success)) {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '云片提交更新请求失败' . $result->responseData['detail']);
                }
            }
            if (!SiteKeyVal::updateOne(['val' => $content, 'sub_key' => $sub_key, 'name' => $name], ['id' => $id])) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '保存失败');
            }
            if ($content != $data['val']) {
                $this->ajax->outRight("本地更改成功,等待云片审核");
            } else {
                $this->ajax->outRight("更改成功");
            }
        }


    }

    /*更新系统消息模板*/
    public function sysUpdateAction()
    {

        $id = $this->request->getPost('id', 'int', 0);
        $content = $this->request->getPost('content', 'string', '');
        $sub_key = $this->request->getPost('sub_key', 'string', '');
        $name = $this->request->getPost('name', 'string', '');

        if (!$id || !$content || !$sub_key || !$name) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //数据不存在
        if (!$data = SiteKeyValManager::init()->getById($id, true)) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        AdminLog::init()->add('编辑系统消息模板', AdminLog::TYPE_SYS_MSG_TEMPLATE, $id, array('type' => "update", 'id' => $data));

        if (!SiteKeyVal::updateOne(['val' => $content, 'sub_key' => $sub_key, 'name' => $name], ['id' => $id])) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '编辑失败');
        } else {
            $this->ajax->outRight("编辑成功");
        }
    }


    /*删除短信模板*/

    public function smsRemoveAction()
    {
        $id = $this->request->getPost('id', 'int', 0);
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //数据不存在
        if (!$data = SiteKeyValManager::init()->getById($id, true)) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $sms = new  TplOperator();
        $result = $sms->del(['tpl_id' => $data->param]);
        AdminLog::init()->add('删除短信模板', AdminLog::TYPE_MSG_TEMPLATE, $id, array('type' => "delete", 'id' => $data));

        //云片
        if ($result && $result->responseData['code'] == 0) {
            if (!SiteKeyVal::updateOne(['enable' => 0], ['id' => $id])) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '本地删除失败');
            }
            $this->ajax->outRight("本地删除成功,等待云片审核");
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '云片提交删除请求失败' . $result->responseData['detail']);
        }
    }

    /*删除系统消息模板*/

    public function sysRemoveAction()
    {
        $id = $this->request->getPost('id', 'int', 0);
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //数据不存在
        if (!$data = SiteKeyValManager::init()->getById($id, true)) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        //$sms = new  TplOperator();
        AdminLog::init()->add('删除系统消模板', AdminLog::TYPE_SYS_MSG_TEMPLATE, $id, array('type' => "delete", 'id' => $data));
        if (!SiteKeyVal::updateOne(['enable' => 0], ['id' => $id])
        ) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '删除失败');
        }
        $this->ajax->outRight("删除成功");
    }

    /*添加短信模板*/

    public function smsAddAction()
    {
        $content = $this->request->getPost('content', 'string', '');
        $sub_key = $this->request->getPost('sub_key', 'string', '');
        $name = $this->request->getPost('name', 'string', '');
        $tpl_id = $this->request->getPost('tpl_id', 'string', '');//云片模板id

        if (!$content || !$sub_key || !$name) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //数据存在
        if (SiteKeyValManager::init()->getByVal($content, SiteKeyValManager::KEY_PAGE_SMS_TPL)) {
            $this->ajax->outError(Ajax::ERROR_DATA_HAS_EXISTS);
        }
        AdminLog::init()->add('添加短信模板', AdminLog::TYPE_MSG_TEMPLATE, '', array('type' => "add", 'id' => ''));

        if (!$tpl_id) {
            $sms = new  TplOperator();
            $result = $sms->add(['tpl_content' => '【恐龙谷网络】' . $content, 'notify_type' => 3]);
            //云片
            if ($result && $result->success) {
                $data = new SiteKeyVal();
                $res = $data->insertOne([
                    'pri_key' => SiteKeyValManager::KEY_PAGE_SMS_TPL,
                    'sub_key' => $sub_key,
                    'name' => $name,
                    'val' => $content,
                    'param' => $result->responseData['tpl_id']
                ]);

                if (!$res) {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '本地添加失败');
                }
                $this->ajax->outRight("本地添加成功,等待云片审核");
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '云片提交添加请求失败' . $result->responseData['detail']);
            }
        } else {
            $data = new SiteKeyVal();
            $res = $data->insertOne([
                'pri_key' => SiteKeyValManager::KEY_PAGE_SMS_TPL,
                'sub_key' => $sub_key,
                'name' => $name,
                'val' => $content,
                'param' => $tpl_id
            ]);
            if (!$res) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '添加失败');
            }
            $this->ajax->outRight("添加成功");
        }

    }

    /*添加系统消息模板*/

    public function sysAddAction()
    {
        $content = $this->request->getPost('content', 'string', '');
        $sub_key = $this->request->getPost('sub_key', 'string', '');
        $name = $this->request->getPost('name', 'string', '');

        if (!$content || !$sub_key || !$name) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //数据存在
        if (SiteKeyValManager::init()->getByVal($content, SiteKeyValManager::KEY_PAGE_SMS_TPL)) {
            $this->ajax->outError(Ajax::ERROR_DATA_HAS_EXISTS);
        }
        AdminLog::init()->add('添加系统消息模板', AdminLog::TYPE_SYS_MSG_TEMPLATE, '', array('type' => "add", 'id' => ''));
        $data = new SiteKeyVal();
        $res = $data->insertOne([
            'pri_key' => SiteKeyValManager::KEY_PAGE_IM_TPL,
            'sub_key' => $sub_key,
            'name' => $name,
            'val' => $content,
        ]);
        if (!$res) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '添加失败');
        }
        $this->ajax->outRight("添加成功");


    }

    /**
     * 更新热门问题
     */
    public function updateHotQuestionAction()
    {
        $id = $this->request->get('data_id');
        $type = $this->request->get('type');
        if (empty($id))
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '数据id为空');
        $cache_key = 'site_key_val_cache_' . SiteKeyValManager::KEY_HOT_QUESTION;
        $old_hot_question = json_decode($this->di->get('redis')->get($cache_key), true);
        if (!$old_hot_question)
            $old_hot_question = json_decode(SiteKeyVal::init()->findOne('id = ' . $id)['val'], true);
        switch ($type) {
            case 'add':
                $content = $this->request->get('content');
                $weight = $this->request->get('weight');
                //key重新赋值
                $i = 1;
                foreach ($old_hot_question as $v) {
                    $new_hot_question['item' . $i] = $v;
                    $i++;
                }
                $new_hot_question['item' . (count($new_hot_question) + 1)] = ['question' => $content, 'weight' => $weight, 'created' => time()];
                $res = SiteKeyVal::init()->updateOne("val = '" . json_encode($new_hot_question, JSON_UNESCAPED_UNICODE) . "'", 'id = ' . $id);
                //更新缓存
                $this->di->get('redis')->save($cache_key, json_encode($new_hot_question, JSON_UNESCAPED_UNICODE));
                if ($res)
                    Ajax::init()->outRight();
                else
                    Ajax::init()->outError('添加失败');
                break;
            case 'edit':
                $content = $this->request->get('content');
                $weight = $this->request->get('weight');
                $item_key = $this->request->get('item_key');
                $old_hot_question[$item_key] = ['question' => $content, 'weight' => $weight, 'created' => time()];
                $res = SiteKeyVal::init()->updateOne("val = '" . json_encode($old_hot_question, JSON_UNESCAPED_UNICODE) . "'", 'id = ' . $id);
                //更新缓存
                $this->di->get('redis')->save($cache_key, json_encode($old_hot_question, JSON_UNESCAPED_UNICODE));
                if ($res)
                    Ajax::init()->outRight();
                else
                    Ajax::init()->outError('保存失败');
                break;
            case 'del':
                $item_key = $this->request->get('item_key');
                unset($old_hot_question[$item_key]);
                $res = SiteKeyVal::init()->updateOne("val = '" . json_encode($old_hot_question, JSON_UNESCAPED_UNICODE) . "'", 'id = ' . $id);
                //更新缓存
                $this->di->get('redis')->save($cache_key, json_encode($old_hot_question, JSON_UNESCAPED_UNICODE));
                if ($res)
                    Ajax::init()->outRight();
                else
                    Ajax::init()->outError('删除失败');
                break;
        }
    }

    /*模板查找*/

    public function queryAction()
    {
        $tpl_id = $this->request->getPost('tpl_id', 'string', '');
        $sms = new TplOperator();
        $res = $sms->get(['tpl_id' => $tpl_id]);
        if (!$res) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '查询失败');
        }
        $this->ajax->outRight($res->responseData);
    }

    public function getUserAction()
    {
        $limit = $this->request->get('limit', 'int', 10); //每页显示的数量
        $page = $this->request->get('page', 'int', 1); //第几页
        $key = $this->request->get('key', 'string', ''); //关键字

        $where = "status=1 and user_type=" . UserStatus::USER_TYPE_NORMAL;
        if ($key) {
            if (strlen($key) == 11 && preg_match('/^1[\d]{10}$/', $key)) {
                $where .= ' and  (phone=' . $key . ' or username like "%' . $key . '%") ';
            } else if (strlen($key) >= 5 && preg_match('/^[1-9][\d]+$/', $key)) {
                $where .= ' and  (user_id=' . $key . ' or username like "%' . $key . '%")';
            } else {
                $where .= ' and username like "%' . $key . '%"';
            }
        }
        $count = UserInfo::dataCount($where); //总记录数
        $res = UserInfo::findList(array($where, 'columns' => 'user_id as id,avatar,username,sex,phone,created', 'order' => 'created desc', 'offset' => ($limit * ($page - 1)), "limit" => $limit));
        $user_arr = array();
        $result = array();
        foreach ($res as $item) {
            $user_arr[] = $item['id'];
            $item['created'] = date('Y年m月d日', $item['created']);
            $result[$item['id']] = $item;
        }
        $pageBar = $this->getPageBar($page, $limit, $count, 4);

        $this->ajax->outRight(array('limit' => $limit, 'page' => $page, 'pageBar' => $pageBar, 'count' => $count, 'res' => $result));
    }

    /**
     * @param $page --当前第几页
     * @param int $limit 每页显示的数据
     * @param $count --总的数据量
     * @param int $page_size --页面显示几个导航框(1,2,3)
     * @return string
     *
     *
     */
    public function getPageBar($page, $limit = 10, $count, $page_size = 6)
    {
        $bar = "";
        if ($count == 0) {
            return "";
        }
        $total_page = ceil($count / $limit);
        if ($page > 1) {
            $bar .= "<a href='javascript:;' data-page='1'>首页</a>";
            $bar .= "<a href='javascript:;' data-page='" . ($page - 1) . "'>上一页</a>";
        }
        if ($total_page <= $page_size) {
            for ($i = 1; $i <= $total_page; $i++) {
                if ($page == $i) {
                    $bar .= "<a href='javascript:;' class='curr' data-page='" . $i . "'>" . $i . "</a>";
                } else {
                    $bar .= "<a href='javascript:;' data-page='" . $i . "'>" . $i . "</a>";
                }

            }
        } else {
            if ($page < $page_size) {
                for ($i = 1; $i <= $page_size; $i++) {
                    if ($page == $i) {
                        $bar .= "<a href='javascript:;' class='curr' data-page='" . $i . "'>" . $i . "</a>";
                    } else {
                        $bar .= "<a href='javascript:;' data-page='" . $i . "'>" . $i . "</a>";
                    }
                }
            } else if ($page >= $page_size && $page < $total_page) {
                for ($i = $page - $page_size + 2; $i <= $page + 1; $i++) {
                    if ($page == $i) {
                        $bar .= "<a href='javascript:;' class='curr' data-page='" . $i . "'>" . $i . "</a>";
                    } else {
                        $bar .= "<a href='javascript:;' data-page='" . $i . "'>" . $i . "</a>";
                    }
                }
            } else if ($page == $total_page) {

                for ($i = $page - $page_size + 1; $i <= $page; $i++) {
                    if ($page == $i) {
                        $bar .= "<a href='javascript:;' class='curr' data-page='" . $i . "'>" . $i . "</a>";
                    } else {
                        $bar .= "<a href='javascript:;' data-page='" . $i . "'>" . $i . "</a>";
                    }
                }

            }

        }
        if ($total_page > 1 && $page != $total_page) {
            $bar .= "<a href='javascript::' data-page='" . ($page + 1) . "'>下一页</a>";

            $bar .= "<a href='javascript:;' data-page='" . ($total_page) . "' >尾页</a>";

        }
        $bar .= "<a href='javascript:;'>共" . $total_page . "页</a>";
        return $bar;
    }

    public function pushAction()
    {
        $data = $this->request->get("data");
        $msg = '';
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
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
            if (strpos($item[0], 'data:image') !== false)//前段base64上传图片
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
            } else//前端上传oss图片地址
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

                if (strpos($item[0], 'data:image') !== false)//图片为base64格式
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
                } else {
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
        $message = ["tpl_type" => $data['msg_type'], 'content' => $content, 'need_uids' => $data['uids'], 'created' => time(), 'admin_ids' => $this->admin['id'], 'user_type' => $data['user_type']];

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
                    //  $i += 500;
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
                    $this->ajax->outRight("发送成功");
                } elseif ($success_uids) {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "部分发送成功");
                } else {
                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "发送失败");
                }
            }

        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "保存失败");
        }

    }

    //定时推送消息功能：存储消息
    public function storeAction()
    {
        $message = $this->request->get('data');
        $timing = $message['timing'];
        unset($message['timing']);
        $data['type'] = 1;
        $data['data'] = json_encode($message, JSON_UNESCAPED_UNICODE);
        $data['timing_day'] = date('Ymd', strtotime($timing));
        $data['timing'] = strtotime($timing);
        $data['status'] = 1;
        $data['created'] = time();
        $res = MessageTimingPush::insertOne($data);
        if ($res) {
            //添加定时任务
            Queue::init()->push('http://admin.klgwl.com/callback/message/timing', ['id' => $res, 'timing_day' => $data['timing_day'], 'admin_id' => $this->admin['id']], ($data['timing'] - time()) * Queue::SECOND, 1, "http://admin.klgwl.com/callback/message/resNotify");
            Ajax::init()->outRight('保存成功');
        } else {
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '元数据保存失败');
        }
    }

    //取消定时推送
    public function cancelTimingAction()
    {
        $id = $this->request->get('id');
        $res = MessageTimingPush::updateOne(['enable' => 0], ['id' => $id]);
        if ($res)
            Ajax::outRight();
        else
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, '操作失败');
    }

    //获取恐龙谷消息列表
    public function getListAction()
    {
        $first_id = $this->request->get("first_id", 'int', 0);
        $last_id = $this->request->get("last_id", 'int', 0);
        $limit = $this->request->get("limit", 'int', 20);

        $uid = $this->request->get("uid", 'int', 0);
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        $user_info = Users::findOne(["id=" . $uid, "columns" => "id as uid,username,avatar"]);
        //下拉加载
        if ($first_id) {
            $data = ['list' => '', 'hide_tip' => 0, 'first_id' => 0, 'video_ids' => []];
            $msg = Message::findOne(["id=" . $first_id, 'columns' => 'send_time,id']);
            if (!$msg) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type', "((from_uid=" . $uid . ' and to_uid=' . ImManager::ACCOUNT_SYSTEM . ") or (from_uid=" . ImManager::ACCOUNT_SYSTEM . " and to_uid=$uid)) and send_time<=" . $msg['send_time'] . ' and id <>' . $first_id . " and media_type in('text','audio','video','picture','file')", 'limit' => $limit, 'order' => 'send_time desc']);
            if ($message) {
                $message = array_reverse($message);

                $pre = "";
                foreach ($message as $k => $m) {
                    if ($m['media_type'] == 'video') {
                        $data['video_ids'][] = $m['id'];
                    }
                    $data['list'][] = $this->getFromOB('message/partial/sys/item', ['item' => ['pre' => $pre, 'info' => $m, 'user_info' => $user_info]]);
                    $pre = date("YmdHi", floor($m["send_time"] / 1000));
                    if (!$data['hide_tip'] && $pre == date('YmdHi', floor($msg['send_time'] / 1000))) {
                        $data['hide_tip'] = $msg['id'];
                    }
                }
                $data['first_id'] = $message[0]['id'];
            }

        } //上拉刷新
        else if ($last_id) {
            $msg = Message::findOne(["id=" . $last_id, 'columns' => 'send_time']);
            if (!$msg) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type', "((from_uid=" . $uid . ' and to_uid=' . ImManager::ACCOUNT_SYSTEM . ") or (from_uid=" . ImManager::ACCOUNT_SYSTEM . " and to_uid=$uid)) and send_time>=" . $msg['send_time'] . ' and id <>' . $last_id . " and media_type in('text','audio','video','picture','file')", 'limit' => $limit, 'order' => 'send_time desc']);
            $data = ['list' => '', 'last_id' => 0, 'video_ids' => []];
            if ($message) {
                $message = array_reverse($message);
                $next = date("YmdHi", floor($msg['send_time'] / 1000));
                foreach ($message as $k => $m) {
                    if ($m['media_type'] == 'video') {
                        $data['video_ids'][] = $m['id'];
                    }
                    $data['list'][] = $this->getFromOB('message/partial/sys/item', ['item' => ['next' => $next, 'info' => $m, 'user_info' => $user_info]]);
                    $next = date("YmdHi", floor($m["send_time"] / 1000));
                }
                $data['last_id'] = $message[count($message) - 1]['id'];
            }
        } else {
            $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type', "(from_uid=" . $uid . ' and to_uid=' . ImManager::ACCOUNT_SYSTEM . ") or (from_uid=" . ImManager::ACCOUNT_SYSTEM . " and to_uid=$uid)" . " and media_type in('text','audio','video','picture','file')", 'limit' => $limit, 'order' => 'send_time desc']);
            $data = ['list' => '', 'first_id' => 0, 'last_id' => 0, 'video_ids' => []];
            if ($message) {
                $message = array_reverse($message);
                $data = ['list' => '', 'first_id' => 0, 'last_id' => 0, 'video_ids' => []];
                $pre = 0;
                foreach ($message as $k => $m) {
                    if ($m['media_type'] == 'video') {
                        $data['video_ids'][] = $m['id'];
                    }
                    $data['list'][] = $this->getFromOB('message/partial/sys/item', ['item' => ['pre' => $pre, 'info' => $m, 'user_info' => $user_info]]);
                    $pre = date("YmdHi", floor($m["send_time"] / 1000));
                }
                $data['last_id'] = $message[count($message) - 1]['id'];
                $data['first_id'] = $message[0]['id'];
            }
        }
        $this->ajax->outRight($data);
    }

    public function sendNormalMsgAction()
    {
        $uid = $this->request->getPost("uid", 'int', 0);
        $msg = $this->request->getPost("msg", 'string', '');
        if (!$uid || !$msg) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = ServerAPI::init()->sendMsg(ImManager::ACCOUNT_SYSTEM, 0, $uid, 0, ['msg' => $msg]);
        if ($res) {
            $message = new Message();
            $time = time();
            $data = [
                "from_uid" => ImManager::ACCOUNT_SYSTEM,
                "mix_id" => ImManager::init()->getMixId(ImManager::ACCOUNT_SYSTEM, $uid),
                'to_uid' => $uid,
                'body' => $msg,
                'type' => 1,
                'media_type' => strtolower(NotifyManager::msgType_TEXT),
                "created" => time(),
                'send_time' => substr((string)microtime(true) * 1000, 0, 13),
                "year" => date("Y", $time),
                "month" => date("m", $time),
                "day" => date("d", $time),
                "client_type" => NotifyManager::fromClientType_PC,
            ];
            $message_id = $message->insertOne($data);
        }
        $item =
            <<<EOF
  <li class="right" data-id="$message_id">
       <span class="avatar">
           <img src="/static/panel/images/logo.png"/></span>
        <div class="info">
            <p class="name"><a href="javascript:;">恐龙君</a></p>
            <div class="msg_info">
                <p class="desc grey">$msg</p>
                <span class="arrow"></span>
            </div>
        </div>
    </li>
EOF;

        $this->ajax->outRight($item);
    }

    public function checkMsgAction()
    {
        $uid = $this->request->getPost("uid", 'int', 0);
        $last_id = $this->request->getPost("last_id", 'int', 0);

        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }


        if ($last_id) {
            $msg = Message::findOne(["id=" . $last_id, 'columns' => 'send_time']);
            if (!$msg) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type', "from_uid=" . $uid . ' and to_uid=' . ImManager::ACCOUNT_SYSTEM . " and send_time>=" . $msg['send_time'] . ' and id <>' . $last_id . " and media_type in('text','audio','picture')", 'order' => 'send_time desc']);
        } else {
            $message = Message::findList(['columns' => 'id,from_uid,to_uid,send_time,body,media_type', "from_uid=" . $uid . ' and to_uid=' . ImManager::ACCOUNT_SYSTEM . " and media_type in('text','audio','picture')", 'order' => 'send_time desc']);
        }
        $data = ['list' => [], 'last_id' => 0];
        if ($message) {
            $message = array_reverse($message);
            $next = date("YmdHi", $msg['send_time']);
            $user_info = Users::findOne(["id = " . $uid, "columns" => "id as uid,username,avatar"]);
            foreach ($message as $k => $m) {
                $data['list'][] = $this->getFromOB('message/partial/sys/item', ['item' => ['next' => $next, 'info' => $m, 'user_info' => $user_info]]);
                $next = date("YmdHi", $m["send_time"]);
            }
            $data['last_id'] = $message[count($message) - 1]['id'];

        }
        $this->ajax->outRight($data);
    }

    //获取图文详情
    public function getMaterialDetailAction()
    {
        $id = $this->request->get('id');
        if (empty($id))
            Ajax::init()->outError(Ajax::INVALID_PARAM, '非法参数');
        $detail = SiteMaterial::findOne(['enable = 1 and id = ' . $id, 'columns' => 'title,thumb,link']);
        if (empty($detail))
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '文章不存在或已删除');
        $detail['link'] = MaterialManager::$urlPrefix . $detail['link'];
        Ajax::init()->outRight($detail);
    }

    //红包君获取随机机器人作为红包发起者
    public function getUidRandomAction()
    {
        //获取特殊机器人
        $unusedAccount = $this->di->get('activity')->query("select * from filiter")->fetch(\PDO::FETCH_ASSOC);
        $unusedUid = $unusedAccount['unnecessary_robot'];
        $unusedUid = explode(',',$unusedUid);
        while( true )
        {
            $robot = UserInfo::findOne(['user_id >= 50000 and user_type = 2','order' => 'rand()','columns' => 'user_id as uid,username,avatar']);
            if( !in_array($robot['uid'],$unusedUid) )
                break;
        }
        $this->ajax->outRight($robot);
    }
    //红包君自定义发送者时获取用户详情
    public function getUserInfoAction()
    {
        $uid = (int) $this->request->get('uid','int',0);
        $account_special = [
            13 => [
                'uid' => 13,
                'username' => '恐龙君',
                'avatar' => 'http://avatorimg.klgwl.com/13/13454_s_150x150.png'
            ],
            18 => [
                'uid' => 18,
                'username' => '红包君',
                'avatar' => 'http://avatorimg.klgwl.com/13/13149_s_90x90.png'
            ]
        ];
        if( in_array($uid,array_keys($account_special)) )
        {
            $this->ajax->outRight($account_special[$uid]);
        }
        $userInfo = UserInfo::findOne(['user_id = ' . $uid,'columns' => 'user_id as uid,username,avatar']);
        if( $userInfo )
            $this->ajax->outRight($userInfo);
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'请输入有效的用户ID');
    }
    //红包君发送推广红包
    public function sendPromoteRBAction()
    {
        $code = (int) $this->request->get('code');
        $uid = (int) $this->request->get('uid');
        $money = (int) $this->request->get('money');//前端已乘100
        $num = (int) $this->request->get('num');
        $content = $this->request->get('content');
        $images = $this->request->get('images');
        $url = $this->request->get('url');
        $sex = (int) $this->request->get('sex','int',0);
        $min_age = (int) $this->request->get('min_age','int',0);
        $max_age = (int) $this->request->get('max_age','int',0);
        $region = (int) $this->request->get('region','int',0);
        //获取当前登录用户
        $admin = $this->session->get('admin');
        //验证短信验证码
        $phone = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER,'phone_bound_for_send_redbag_from_web');
        $send_res = VerifyCodeManager::init()->checkVerifyCode($phone,VerifyCodeManager::$codetype[VerifyCodeManager::CODE_SEND_REDBAG_FROM_WEB],'web',$admin['id'],$code);
        if( $send_res !== '1' )
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'短信验证码不正确，请重新输入');
        $gameextra = [
            'type' => 5,
            'rule' => json_encode([
                "content" => $content,
                "images"  => $images,
                "url"     => $url,
                "sex"     => $sex,
                "min_age" => $min_age,
                "max_age" => $max_age,
                "region"  => $region
            ])
        ];

        $res = Request::getPost(Request::SEND_RED_PACKAGE,[
            'uid'       => $uid,
            'to_uid'    => 18,
            'num'       => $num,
            'random'    => 1,
            'money'     => $money,
            'agent'     => 12,
            'gameextra' => json_encode($gameextra)
        ],true);
        if( $res )
            $this->ajax->outRight($res);
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'发送失败');

    }
    //发送红包君红包时验证当前登录用户密码
    public function checkPwdAction()
    {
        $pwd = $this->request->get('pwd');
        $pwd = sha1($pwd);
        $admin = $this->session->get('admin');
        if( $pwd === $admin['security_password'] )
        {
            //发送短信验证码
            $phone = SiteKeyValManager::init()->getCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER,'phone_bound_for_send_redbag_from_web');
            VerifyCodeManager::init()->sendPhoneVerifyCode($phone,VerifyCodeManager::$codetype[VerifyCodeManager::CODE_SEND_REDBAG_FROM_WEB],'web',$admin['id']);
            $this->ajax->outRight();
        }else
        {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'安全密码错误，请重新输入');
        }
    }
    //发送红包君红包前检查安全密码是否已设置
    public function securityPasswordIsSetAction()
    {
        $user = $this->session->get('admin');
        $admin = Admins::findOne(['id = ' . $user['id']]);
        if( $admin && !empty($admin['security_password']) )
            $this->ajax->outRight(1);
        else
            $this->ajax->outRight(0);
    }
}