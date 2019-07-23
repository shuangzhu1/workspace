<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/1/5
 * Time: 16:57
 */

namespace Multiple\Panel\Api;


use Models\Site\SiteMaterial;
use Models\Square\RedPackageFestival;
use Models\Square\RedPackagePickLog;
use Models\Square\RedPackageTaskRules;
use Models\System\SystemRedPackageAds;
use Models\User\UserInfo;
use Models\User\Users;
use OSS\OssClient;
use Phalcon\Exception;
use Services\Admin\AdminLog;
use Services\Im\SysMessage;
use Services\MiddleWare\Sl\Request;
use Services\Site\CacheSetting;
use Services\Site\SiteKeyValManager;
use Services\Upload\OssManager;
use Services\User\Square\SquareTask;
use Services\User\SquareManager;
use Services\User\UserStatus;
use Upload\Upload;
use Util\Ajax;
use Util\Debug;
use Util\EasyEncrypt;
use Util\ImgSize;

class PackageController extends ApiBase
{
    //保存基本设置
    public function settingAction()
    {
        $day_pick_limit = $this->request->getPost("day_pick_limit", 'int', 1);//每天次数限制
        $distance_limit = $this->request->getPost("distance_limit", 'int', 1);//距离限制
        $increase_time = $this->request->getPost("increase_time", 'int', 1);//红包刷新递增时间例如为10 那么红包刷新时间为0 10 20 30 40
        $clear_time = $this->request->getPost("clear_time", 'int', 1);//红包刷新清零时间 如30分钟 则30分钟后清零重新计算时间
        $keep_count = $this->request->getPost("keep_count", 'int', 1);//每天保持多少个机器人红包可用
        $money_limit = $this->request->getPost("money_limit", 'int', 1);//每天保证最大的金额 元
        $range_type = $this->request->getPost("range_type");// 允许发布的范围
        $money_limit_one = $this->request->getPost("money_limit_one", 'int', 1);// 每个机器红包的最低金额
        $total_package = $this->request->getPost("total_package", 'int', 30);// 广场显示红包总个数
        $limit_top_start = $this->request->getPost("limit_top_start", 'int', 6);// 领取达到上限显示的可用红包起始值
        $limit_top_end = $this->request->getPost("limit_top_end", 'int', 8);// 领取达到上限显示的可用红包结束值
        $enable_limit = $this->request->getPost("enable_limit");// 领取未达到上限显示的可用红包及百分比


        $data = [
            "day_pick_limit" => $day_pick_limit,
            "distance_limit" => $distance_limit,
            'increase_time' => $increase_time,
            "clear_time" => $clear_time,
            "keep_count" => $keep_count,
            'money_limit' => $money_limit,
            'money_limit_one' => $money_limit_one,
            'range_type' => $range_type,
            'total_package' => $total_package,
            'limit_top_start' => $limit_top_start,
            'limit_top_end' => $limit_top_end,
            'enable_limit' => $enable_limit ? $enable_limit : []

        ];
        $data = json_encode($data);
        if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting', ['val' => $data])) {
            AdminLog::init()->add('基础设置', AdminLog::TYPE_SQUARE_PACKAGE, '', $data);
            SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'square_package_setting', $data);

            $normal_privilege = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "normal_privilege");

            $normal_privilege = json_decode($normal_privilege, true);
            $normal_privilege = array_merge($normal_privilege, ['package_pick_count' => $day_pick_limit]);
            $normal_privilege = json_encode($normal_privilege);
            if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'normal_privilege', ['val' => $normal_privilege])) {
                SiteKeyValManager::init()->setCacheValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'normal_privilege', ($normal_privilege));
            }

            $this->ajax->outRight("编辑成功");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "保存失败");
    }

    //添加红包广告/修改红包广告
    public function addAdsAction()
    {
        $content = trim($this->request->get('content', 'string', ''));//文字内容
        $original_media = $this->request->get('media');
        $id = $this->request->get("id", 'int', 0);
        $media = '';//
        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        if (!$original_media || !$content) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "缺少必须的数据");
        }
        foreach ($original_media as $item) {
            //编辑时的原图
            if (!strpos($item, 'base64')) {
                $media .= ',' . $item;
            } else {
                $item = explode('?', $item);
                $img = ImgSize::getBase64ImgBlob($item[0]);
                //检测文件是否存在
                $md5 = md5($img[0]);
                if ($url = Upload::checkFile($md5)) {
                    $media .= ',' . $url;
                } else {
                    $name = 'ads/' . time() . rand(0, 1000) . "_s_" . $item[1] . "." . $img[1];
                    $res = $oss->putObject(OssManager::BUCKET_CIRCLE_IMG, $name, $img[0]);
                    if ($res && !empty($res['info']['url'])) {
                        $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_CIRCLE_IMG], OssManager::$bind_domain[OssManager::BUCKET_CIRCLE_IMG], $res['info']['url']);
                        $media .= ',' . $url;
                        Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $img[1], 'type' => 'img', 'size' => strlen($img[0]), 'name' => $name, 'url' => $url, 'created' => time()]);
                    }
                }
            }
        }
        $media = $media ? substr($media, 1) : '';
        if (!$id) {
            if ($id = SystemRedPackageAds::insertOne(["content" => $content, "media_type" => SquareManager::MEDIA_TYPE_PICTURE, 'type' => SquareManager::TYPE_ADS, 'media' => $media, 'created' => time()])) {
                AdminLog::init()->add('添加广告', AdminLog::TYPE_SQUARE_PACKAGE, $id, array('type' => "update", 'id' => $id));

                $this->ajax->outRight("添加成功");

            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "添加失败");
            }
        } else {
            if (SystemRedPackageAds::updateOne(["content" => $content, "media_type" => SquareManager::MEDIA_TYPE_PICTURE, 'type' => SquareManager::TYPE_ADS, 'media' => $media, 'modify' => time()], 'id=' . $id)) {
                AdminLog::init()->add('编辑广告', AdminLog::TYPE_SQUARE_PACKAGE, $id, array('type' => "update", 'id' => $id));

                $this->ajax->outRight("编辑成功");
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
            }
        }

    }

    //删除广告
    public function delAdsAction()
    {
        $id = $this->request->get('id', 'int', 0);

        if (!$id) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
        }
        if (SystemRedPackageAds::updateOne(['status' => 0], 'id=' . $id)) {
            AdminLog::init()->add('删除广告', AdminLog::TYPE_SQUARE_PACKAGE, $id, array('type' => "del", 'id' => $id));
            $this->ajax->outRight();
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "删除失败");
    }

    //添加、更新 节假日红包
    public function addFestivalPackageAction()
    {
        $content = trim($this->request->get('content', 'string', ''));//文字内容
        $send_time = trim($this->request->get('send_time', 'string', ''));//发布时间
        $money = trim($this->request->get('money', 'int', ''));//红包金额
        $num = trim($this->request->get('num', 'int', 0));//红包个数
        $app_uid = $this->request->get('app_uid', 'int', 0);//用户id

        if (!$app_uid) {
            $app_uid = Users::findOne(['user_type=' . UserStatus::USER_TYPE_OFFICIAL, 'columns' => 'id,rand() as rand', 'order' => 'rand desc']);// $this->request->get('app_uid', 'int', 0);//app_uid
            $app_uid = $app_uid['id'];
        }

        if (!$send_time) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请填写发布时间");
        } else {
            $send_time = strtotime($send_time);
            if ($send_time <= time()) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "发布时间不得小于当前时间");
            }
        }
        if (!$money) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请填写红包金额");
        }
        if ($num <= 0) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "请填写红包个数");
        }

        $original_media = $this->request->get('media');
        $id = $this->request->get("id", 'int', 0);
        $media = '';//
        $config = $this->di->get('config')->oss;
        $oss = new OssClient($config->app_key, $config->app_secret, $config->end_point);
        if (!$original_media || !$content) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "缺少必须的数据");
        }
        foreach ($original_media as $item) {
            //编辑时的原图
            if (!strpos($item, 'base64')) {
                $media .= ',' . $item;
            } else {
                $item = explode('?', $item);
                $img = ImgSize::getBase64ImgBlob($item[0]);
                //检测文件是否存在
                $md5 = md5($img[0]);
                if ($url = Upload::checkFile($md5)) {
                    $media .= ',' . $url;
                } else {
                    $name = 'ads/' . time() . rand(0, 1000) . "_s_" . $item[1] . "." . $img[1];
                    $res = $oss->putObject(OssManager::BUCKET_CIRCLE_IMG, $name, $img[0]);
                    if ($res && !empty($res['info']['url'])) {
                        $url = str_replace(OssManager::$original_domain[OssManager::BUCKET_CIRCLE_IMG], OssManager::$bind_domain[OssManager::BUCKET_CIRCLE_IMG], $res['info']['url']);
                        $media .= ',' . $url;
                        Upload::syncDb(['md5' => $md5, 'folder' => date('Ym'), 'ext' => $img[1], 'type' => 'img', 'size' => strlen($img[0]), 'name' => $name, 'url' => $url, 'created' => time()]);
                    }
                }
            }
        }
        $media = $media ? substr($media, 1) : '';

        try {
            $this->di->getShared("original_mysql")->begin();
            if (!$id) {
                if ($id = RedPackageFestival::insertOne([
                    "content" => $content,
                    "media_type" => SquareManager::MEDIA_TYPE_PICTURE,
                    'media' => $media,
                    'send_time' => $send_time,
                    'money' => $money * 100,
                    'num' => $num,
                    'status' => SquareManager::festival_wait_publish,
                    'user_id' => $app_uid,
                    'created' => time()])
                ) {
                    if (!SquareManager::init()->addFestivalPackageTask($id, date('Y-m-d H:i:s', $send_time))) {
                        throw new \Exception("更新任务失败");
                    }
                    AdminLog::init()->add('添加节日红包', AdminLog::TYPE_SQUARE_PACKAGE, $id, array('type' => "update", 'id' => $id));
                } else {
                    throw new \Exception("添加失败");
                }
            } else {
                if (RedPackageFestival::updateOne([
                    "content" => $content,
                    "media_type" => SquareManager::MEDIA_TYPE_PICTURE,
                    'media' => $media,
                    'send_time' => $send_time,
                    'money' => $money * 100,
                    'num' => $num,
                    'user_id' => $app_uid,
                    'modify' => time()
                ],
                    'id=' . $id)
                ) {
                    if (!SquareManager::init()->updateFestivalPackageTask($id, date('Y-m-d H:i:s', $send_time))) {
                        throw new \Exception("更新任务失败");
                    }
                    AdminLog::init()->add('编辑节日红包', AdminLog::TYPE_SQUARE_PACKAGE, $id, array('type' => "update", 'id' => $id));
                } else {
                    throw new \Exception("编辑失败");
                    //   $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
                }
            }
            $this->di->getShared("original_mysql")->commit();
            $this->ajax->outRight("编辑成功");
        } catch (\Exception $e) {
            $this->di->getShared("original_mysql")->rollback();
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, $e->getMessage());
        }


    }

    //删除节假日红包
    public function delFestivalPackageAction()
    {
        $id = $this->request->get('id', 'int', 0);

        if (!$id) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "参数错误");
        }
        if (SquareManager::init()->removeFestivalPackageTask($id)) {
            if (RedPackageFestival::updateOne(['status' => SquareManager::festival_deleted, 'modify' => time()], 'id=' . $id)) {
                AdminLog::init()->add('删除节假日红包', AdminLog::TYPE_SQUARE_PACKAGE, $id, array('type' => "del", 'id' => $id));
                $this->ajax->outRight();
            }
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "删除任务失败");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "删除失败");
    }

    /**
     * 公告更新，推送系统消息到客户端
     */
    /*private function noticeModifyAction($type)
    {
        //$type = $this->request->get('type','int',0);
        $body['type'] = $type;
        $where = 'type = 2 and enable = 1';
        $start = strtotime(date('Y/m/d', strtotime('-4 days')));
        $where .= ' and created >= ' . $start;
        $notices = SiteMaterial::findList([$where, 'columns' => 'id,title'], 'id');

        if (!empty($notices)) {
            $body['notices'] = $notices;
        } else//最近5天无公告，取最新一条
        {
            $notice = SiteMaterial::findOne(['type = 2 and enable = 1', 'order' => 'created desc', 'columns' => 'id,title']);
            if (!empty($notice))
                $body['notices'][] = $notice;
            else
                $body['notices'] = [];
        }
        SysMessage::init()->initMsg(SysMessage::TYPE_NOTICE_MODIFY, $body);
        Ajax::init()->outRight('推送成功');
    }*/

    //删除公告
    /*public function noticeDelAction()
    {
        $id = $this->request->get('id', 'int', 0);
        if ($id <= 0)
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, 'id不合法');
        $res = SiteMaterial::updateOne(['enable' => 0], ['id' => $id]);
        if ($res) {
            $this->noticeModifyAction(3);
            Ajax::init()->outRight('');
        } else
            Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '删除失败');

    }*/

    //添加/编辑公告
    /*public function noticeAddAction()
    {
        $id = $this->request->get('id', 'int', 0);

        $data['title'] = $this->request->getPost('title');
        $data['link'] = EasyEncrypt::encode(time() . rand(1000, 9999));
        $data['thumb'] = $this->request->getPost('thumb');
        if (strpos($data['thumb'], 'http') === false)
            $data['thumb'] = '';
        $data['content'] = $this->request->getPost('editorValue');
        $data['type'] = 2;//广场红吧公告
        $time = time();
        if (empty($id))//增加
        {
            $data['created'] = $time;
            $data['updated'] = $time;
            $res = SiteMaterial::insertOne($data);
            if ($res) {
                $this->noticeModifyAction(1);
                Ajax::init()->outRight('操作成功');
            } else
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '保存失败');
        } else//编辑
        {
            $data['updated'] = $time;
            $res = SiteMaterial::updateOne($data, ['id' => $id]);
            if ($res) {
                $this->noticeModifyAction(2);
                Ajax::init()->outRight('操作成功');
            } else
                Ajax::init()->outError(Ajax::CUSTOM_ERROR_MSG, '保存失败');
        }
    }*/

    //红包领取记录
    public function pickListAction()
    {
        $package_id = $this->session->get("package_id");
        $limit = $this->request->get('limit', 'int', 5);
        $last_id = $this->request->get("last_id", 'int', 0);
        //  $info = Request::getPost(Base::PACKAGE_PICKER, ['uid' => 13, 'redid' => $package_id, 'limit' => 500]);
        $where = "package_id='" . $package_id . "'";
        if ($last_id) {
            $where .= " and id<" . $last_id;
        }
        $list = RedPackagePickLog::findList([$where, 'limit' => $limit, 'order' => 'id desc']);
        $count = RedPackagePickLog::dataCount("package_id='" . $package_id . "'");
        $result = [];
        if ($list) {
            $last_id = $list[count($list) - 1]['id'];
            foreach ($list as $item) {
                $result[] = [$this->getFromOB('package/partial/pick-item', array('item' => $item))];
                // $last_id = $item['id'];
            }
        }
        $result = array('count' => $count, "limit" => $limit, 'data_list' => $result, 'last_id' => $last_id);
        Ajax::outRight($result);

//        if ($info && $info['curl_is_success']) {
//            $content = json_decode($info['data'], true);
//            $list = $content['data'];
//            $this->view->setVar('list', $list);
//        }

    }

    //删除黑名单
    public function removeBlacklistAction()
    {
        $user_id = $this->request->get("user_id", 'int', 0);
//        if (!Users::exist("id=" . $user_id)) {
//            Ajax::outError("该用户不存在");
//        }
        if (!$user_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $redis = $this->di->getShared("redis");
        $redis->hDel(CacheSetting::KEY_RED_PACKAGE_BLACKLIST, $user_id);
        Ajax::outRight("");
    }

    //添加黑名单
    public function addBlacklistAction()
    {
        $user_id = $this->request->get("user_id", 'int', 0);
        if (!$user_id) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        if (!Users::exist("id=" . $user_id)) {
            Ajax::outError(Ajax::CUSTOM_ERROR_MSG, "该用户不存在");
        }
        $redis = $this->di->getShared("redis");
        $redis->hSet(CacheSetting::KEY_RED_PACKAGE_BLACKLIST, $user_id, json_encode(['time' => time()]));
        Ajax::outRight("");
    }

    public function setRulesAction()
    {
        // params
        $data = $this->request->get('data');
        if (!($data)) {
            $this->ajax->outError(Ajax::ERROR_INVALID_REQUEST_PARAM);
        }

        // each do
        $this->db->begin();
        try {
            foreach ($data as $row) {
                $id = isset($row['id']) ? $row['id'] : null;
                unset($row['id']); // for update it
                $row['add_count'] = $row['quantity'];
                unset($row['quantity']);
                unset($row['action']);
                $row['limit_count'] = $row['limit'];
                unset($row['limit']);
                if (!$id) {
                    $rule = new RedPackageTaskRules();
                    $row['created'] = time();
                    if (!$rule->insertOne($row)) {
                        $ms = '';
                        foreach ($rule->getMessages() as $m) {
                            $ms .= (string)$m;
                        }
                        throw new Exception($ms);
                    }
                    if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_1) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 100, 'reward_num' => $row['add_count']]);
                    } else if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_2) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 500, 'reward_num' => $row['add_count']]);
                    } else if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_3) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 1000, 'reward_num' => $row['add_count']]);
                    } else if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_4) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 5000, 'reward_num' => $row['add_count']]);
                    } else if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_5) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 20000, 'reward_num' => $row['add_count']]);
                    }
                } else {
                    if (!RedPackageTaskRules::updateOne($row, ['id' => $id])) {
                        $ms = '';
                        throw new Exception($ms);
                    }
                    if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_1) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 100, 'reward_num' => $row['add_count']]);
                    } else if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_2) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 500, 'reward_num' => $row['add_count']]);
                    } else if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_3) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 1000, 'reward_num' => $row['add_count']]);
                    } else if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_4) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 5000, 'reward_num' => $row['add_count']]);
                    } else if ($row['behavior'] == SquareTask::TASK_SEND_ACTIVITY_5) {
                        Request::getPost(Request::ACTIVITY_REWARD_CONFIG, ['money' => 20000, 'reward_num' => $row['add_count']]);
                    }
                }
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            $this->di->get('errorLogger')->error("save user point rules failed." . $e->getMessage());
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, $e->getMessage());
        }

        // log
        $this->ajax->outRight('操作成功');
    }

    public function filterBtnsAction()
    {
        $btns = $this->request->get('data');
        $res = SiteKeyValManager::init()->setValByKey('other', 'shop_filter_btns', ['val' => $btns]);
        if ($res) {
            $this->ajax->outRight();
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG);
        }
    }
}