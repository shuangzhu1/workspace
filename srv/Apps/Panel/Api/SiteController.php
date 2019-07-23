<?php
/**
 * Created by PhpStorm.
 * User: yanue
 * Date: 4/8/14
 * Time: 11:37 AM
 */

namespace Multiple\Panel\Api;


use Components\Yunxin\ServerAPI;
use Models\Site\SiteAppVersion;
use Models\Site\SiteIndustries;
use Models\Site\SiteKeyVal;
use Models\Site\SiteReportReason;
use Models\Site\SiteTags;
use Services\Admin\AdminLog;
use Services\Discuss\TagManager;
use Services\Im\ImManager;
use Services\Site\IndustryManager;
use Services\Site\SensitiveManager;
use Services\Site\SiteKeyValManager;
use Services\Social\SocialManager;
use Services\Upload\OssManager;
use Util\Ajax;
use Util\Debug;
use Util\FilterUtil;
use Util\Validator;

class SiteController extends ApiBase
{
    //编辑/添加标签
    public function editTagAction()
    {
        $tag_id = $this->request->getPost('tag_id', 'int', 0);
        $type = $this->request->getPost('type', 'int', 1);//标签类型 1-动态 2-用户
        $name = $this->request->getPost('name', 'string', '');
        $enable = $this->request->getPost('enable', 'int', 1);
        $thumb = $this->request->getPost('thumb', 'string', '');

        $sort_num = $this->request->getPost('sort_num', 'int', 50); //排序字段 越小越靠前
        $extra = $this->request->get("extra", 'string', '');
        if ($name == '') {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '名称不能为空');
        }
        $data = ['name' => $name, 'enable' => $enable == 1 ? 1 : 0, 'sort_num' => $sort_num, 'thumb' => $thumb, 'extra' => $extra];
        //编辑
        if ($tag_id > 0) {
            $tag = SiteTags::findOne('id=' . $tag_id);
            if (!$tag) {
                $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
            }
            $res = SiteTags::updateOne($data, ['id' => $tag_id]);
        } else {
            if (SiteTags::findOne(['name="' . $name . '"'])) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '该标签已存在');
            }
            $tag = new SiteTags();
            $data['created'] = time();
            $data['type'] = $type;
            $res = $tag->insertOne($data);
        }
        if ($res) {
            if ($type == TagManager::TYPE_DISCUSS) {
                TagManager::getInstance()->list(true);//更新缓存
            } else {
                TagManager::getInstance()->getUserTag(true);//更新缓存
            }
            if ($tag_id) {
                AdminLog::init()->add('修改动态标签', AdminLog::TYPE_TAGS, $tag_id, ['type' => 'update', 'id' => $tag_id]);
                $this->ajax->outRight("编辑成功");
            } else {
                AdminLog::init()->add('添加动态标签', AdminLog::TYPE_TAGS, $res, ['type' => 'add', 'id' => $res]);
                $this->ajax->outRight("添加成功");
            }
        } else {
            $this->ajax->outError($tag_id ? "编辑失败" : "添加失败");
        }

    }

    //禁用标签
    public function lockTagAction()
    {
        $tag_id = $this->request->get('tag_id', 'int', 0);
        if (!$tag_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $tag = SiteTags::findOne(['id=' . $tag_id, 'columns' => 'type']);
        if (!$tag) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (SiteTags::updateOne(['enable' => 0], ['id' => $tag_id])) {
            if ($tag['type'] == TagManager::TYPE_DISCUSS) {
                TagManager::getInstance()->list(true);//更新缓存
            } else {
                TagManager::getInstance()->getUserTag(true);//更新缓存
            }
            AdminLog::init()->add('禁用动态标签', AdminLog::TYPE_TAGS, $tag_id, ['type' => 'add', 'id' => $tag_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    //解除禁用标签
    public function unLockTagAction()
    {
        $tag_id = $this->request->get('tag_id', 'int', 0);
        if (!$tag_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $tag = SiteTags::findOne(['id=' . $tag_id, 'columns' => 'type']);
        if (!$tag) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        if (SiteTags::updateOne(['enable' => 1], ['id' => $tag_id])) {
            if ($tag['type'] == TagManager::TYPE_DISCUSS) {
                TagManager::getInstance()->list(true);//更新缓存
            } else {
                TagManager::getInstance()->getUserTag(true);//更新缓存
            }
            AdminLog::init()->add('动态标签解除禁用', AdminLog::TYPE_TAGS, $tag_id, ['type' => 'add', 'id' => $tag_id]);
            $this->ajax->outRight("设置成功");
        } else {
            $this->ajax->outError("设置失败");
        }
    }

    /*添加行业*/
    public function addIndustryAction()
    {
        $parent_id = $this->request->getPost('parent_id', 'int', 0);
        $name = $this->request->getPost('name', 'string', '');
        if (!$name) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '请输入行业名称');
        }
        if (SiteIndustries::findOne(['name="' . $name . '" and parent_id=' . $parent_id])) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '该行业已存在');
        }
        $industry = new SiteIndustries();
        $industry_data = [
            'name' => $name,
            'parent_id' => $parent_id,
            'parent_id_str' => $parent_id,
            'created' => time()
        ];
        if ($id = $industry->insertOne($industry_data)) {
            IndustryManager::instance()->industries(true);//更新缓存
            AdminLog::init()->add('添加行业', AdminLog::TYPE_INDUSTRY, $id, ['type' => 'add', 'id' => $id]);
            $this->ajax->outRight('添加成功');
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '添加失败:');
    }

    //编辑举报原因
    public function editReasonAction()
    {
        $id = $this->request->getPost('id', 'int', 0);
        $content = $this->request->getPost('content', 'string', '');
        $enable = $this->request->getPost('enable', 'int', 1);
        $sort = $this->request->getPost('sort', 'int', 10);
        if (!$content || !$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$reason = SiteReportReason::findOne(['id=' . $id])) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $data = [
            'modify' => time(),
            'sort' => $sort,
            'content' => $content,
            'enable' => $enable
        ];

        if (SiteReportReason::updateOne($data, ['id' => $id])) {
            SocialManager::init()->getReportReason($reason['type'], null, true);//更新缓存
            AdminLog::init()->add('编辑举报原因', AdminLog::TYPE_REPORT_REASON, $id, ['type' => 'update', 'id' => $id]);
            $this->ajax->outRight("编辑成功");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '编辑失败');

    }

    //举报原因可用不可用
    public function reasonEnableAction()
    {
        $id = $this->request->getPost('id', 'int', 0);
        $enable = $this->request->getPost('enable', 'int', 1);
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$reason = SiteReportReason::findOne(['id=' . $id])) {
            $this->ajax->outError(Ajax::ERROR_DATA_NOT_EXISTS);
        }
        $data = [
            'modify' => time(),
            'enable' => $enable
        ];
        if (SiteReportReason::updateOne($data, ['id' => $id])) {
            SocialManager::init()->getReportReason($reason['type'], null, true);//更新缓存
            AdminLog::init()->add('编辑举报原因', AdminLog::TYPE_REPORT_REASON, $id, ['type' => 'update', 'id' => $id]);
            $this->ajax->outRight("编辑成功");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '编辑失败');
    }

    //添加举报原因
    public function addReasonAction()
    {
        $content = $this->request->getPost('content', 'string', '');
        $type = $this->request->getPost('type', 'int', 1);
        $enable = $this->request->getPost('enable', 'int', 1);
        $sort = $this->request->getPost('sort', 'int', 10);

        if (!$content) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (SiteReportReason::findOne(['content="' . $content . '" and type=' . $type])) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '该原因已经添加过了');
        }
        $reason = new SiteReportReason();
        $reason_data = [
            'created' => time(),
            'content' => $content,
            'type' => $type,
            'sort' => $sort,
            'enable' => $enable
        ];
        if ($id = $reason->insertOne($reason_data)) {
            SocialManager::init()->getReportReason($type, null, true);//更新缓存
            AdminLog::init()->add('添加举报原因', AdminLog::TYPE_REPORT_REASON, $id, ['type' => 'add', 'id' => $id]);
            $this->ajax->outRight("添加成功");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '添加失败');
    }

    //添加新版本
    public function addVersionAction()
    {
        $version_id = $this->request->getPost('version_id', 'int', 0);//版本号

        $version = $this->request->getPost('version', 'string', ''); //版本号
        $os = $this->request->getPost('os', 'string', '');//系统 android/ios
        $limit_version = $this->request->getPost('limit_version', 'string', ''); //向下兼容版本号
        $detail = $this->request->getPost('detail', 'string', ''); //详情
        $app = $this->request->getPost('app'); //包地址
        $status = $this->request->getPost('status'); //版本状态;0 => 待发布，1 => 已发布
        if (!$version) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '请输入版本号');
        }
        if (!$detail) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '请输入版本详情');
        }
        if (!preg_match('/^[\d]+\.([\d]+\.[\d]+)?$/', $version)) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '请输入正确的版本号');
        }
        if ($limit_version == '-1') {
            $limit_version = $version;
        }
        //编辑
        if ($version_id) {
            $version_info = SiteAppVersion::findOne(['id=' . $version_id . ' and status <> 2']);
            if (!$version_info) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '版本不存在');
            }
            if (SiteAppVersion::exist('os="' . $os . '" and version="' . $version . '" and id<>' . $version_id . ' and status <> 2')) {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '该版本已存在');
            }
            $data = ['version' => $version, 'limit_version' => $limit_version, 'detail' => $detail ,'status' => $status];
            if ($app) {
                // $file_md5 = $app['md5'];//文件md5
                $data['download_url'] = OssManager::$bind_domain[OssManager::BUCKET_APK] . $app['url'];
                $data['file_md5'] = $app['md5'];
//                //不是最新版的
//                if (SiteAppVersion::findOne(["os='" . $os . "' and version>'" . $version . "' and is_deleted=0", 'id'])) {
//                  /*  $info = pathinfo($app);
//                    $file_md5 = md5_file($app);
//                    $path = ROOT . '/download/' . $os . '/' . $version . '_' . (date('YmdHi')) . '.' . $info['extension'];
//                    rename($app, $path);*/
//
//                } //是最新版的
//                else {
//                    $info = pathinfo($app);
//                    $file_md5 = md5_file($app);
//
//                    //先移动之前的包
//                    if (file_exists(ROOT . '/download/' . $os . '/klg.' . $info['extension'])) {
//                        $path = ROOT . '/download/' . $os . '/' . $version . '_' . (date('YmdHi')) . '.' . $info['extension'];
//                        rename(ROOT . '/download/' . $os . '/klg.' . $info['extension'], $path);
//                    }
//                    //操作当前包
//                    $path = ROOT . '/download/' . $os . '/klg.' . $info['extension'];
//                    rename($app, $path);
//                }
            }

            /*   if (isset($file_md5)) {
                   $data['file_md5'] = $file_md5;
               }*/
            if (SiteAppVersion::updateOne($data, 'id=' . $version_id)) {

                $json_file = file_get_contents(ROOT . '/Data/site/version/' . $os . ".json");
                $content = json_decode($json_file, true);
                unset($content[$version_info['version']]);
                $content[$version] = [
                    'version' => $version,
                    'limit_version' => $limit_version,
                    'detail' => $detail,
                    'id' => $version_id,
                    'download_url' => $app ? $data['download_url'] : $version_info['download_url'],
                    'file_md5' => $app ? $data['file_md5'] : $version_info['file_md5'],
                ];

                file_put_contents(ROOT . '/Data/site/version/' . $os . ".json", json_encode($content, JSON_UNESCAPED_UNICODE));

                AdminLog::init()->add('版本更新', AdminLog::TYPE_VERSION, $version_id, $data);
                $this->ajax->outRight("操作成功");
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '版本发布失败');
            }

        } //添加
        else {
            $newest_version = SiteAppVersion::findOne(['os="' . $os . '" and status <> 2', 'order' => 'created desc']);

            $file_md5 = '';//文件md5
//            if ($newest_version) {
//                if (version_compare($newest_version['version'], $version, '>=')) {
//                    $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '发布的版本必须高于上一个版本');
//                }
//            }
            $data = ['os' => $os,
                'version' => $version,
                'created' => time(),
                'limit_version' => $limit_version,
                'detail' => $detail,
                'file_md5' => $file_md5,
                'download_url' => '',
                'status' => $status
            ];
            if ($app) {
                $data['download_url'] = OssManager::$bind_domain[OssManager::BUCKET_APK] . $app['url'];
                $data['file_md5'] = $app['md5'];
                //  $info = pathinfo($app);
//                $file_md5 = md5_file($app);
//                //已经存在至少一个版本
//                if ($version_info = SiteAppVersion::findOne(['is_deleted=0'])) {
//                    //先移动之前的包
//                    if (file_exists(ROOT . '/download/' . $os . '/klg.' . $info['extension'])) {
//                        $path = ROOT . '/download/' . $os . '/' . $version_info['version'] . '_' . (date('YmdHi')) . '.' . $info['extension'];
//                        rename(ROOT . '/download/' . $os . '/klg.' . $info['extension'], $path);
//                    }
//                }
//                //操作当前包
//                $path = ROOT . '/download/' . $os . '/klg.' . $info['extension'];
//                rename($app, $path);
            }
            $app_version = new SiteAppVersion();
            if ($id = $app_version->insertOne($data)) {

                $json_file = file_get_contents(ROOT . '/Data/site/version/' . $os . ".json");
                $content = $json_file ? json_decode($json_file, true) : [];
                $content[$version] = [
                    'id' => $id,
                    'version' => $version,
                    'limit_version' => $limit_version,
                    'detail' => $detail,
                    'file_md5' => $data['file_md5'],
                    'download_url' => $data['download_url']
                ];
                file_put_contents(ROOT . '/Data/site/version/' . $os . ".json", json_encode($content, JSON_UNESCAPED_UNICODE));

                AdminLog::init()->add('版本发布', AdminLog::TYPE_VERSION, $version_id, $data);

                $this->ajax->outRight("操作成功");
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '版本发布失败');
            }
        }
    }

    /**
     * 发布、取消发布新版本
     */
    public function releaseAction()
    {
        $action = $this->request->get('action');
        $id = $this->request->get('id');
        if( !$id || !$action )
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'参数非法');
        $action == 'release' ? $status = 1 : $status = 0;
        $status === 1 ? $release_at = time() : $release_at = 0;
        $res = SiteAppVersion::updateOne(['status' => $status,'release_at' => $release_at],['id' => $id ]);
        if( $res )
            $this->ajax->outRight();
        else
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG,'发布失败');
    }
    //删除版本
    public function delVersionAction()
    {
        $version_id = $this->request->get("version_id", 'int', 0);
        if (!$version_id) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '版本id为空');
        }
        $version_info = SiteAppVersion::findOne(['id=' . $version_id]);
        if (!$version_info) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '版本不存在');
        }
        if (SiteAppVersion::updateOne(['status' => 2], 'id=' . $version_id)) {
            $json_file = file_get_contents(ROOT . '/Data/site/version/' . $version_info['os'] . ".json");
            $content = json_decode($json_file, true);
            unset($content[$version_info['version']]);
            file_put_contents(ROOT . '/Data/site/version/' . $version_info['os'] . ".json", json_encode($content, JSON_UNESCAPED_UNICODE));
            AdminLog::init()->add('删除版本', AdminLog::TYPE_VERSION, $version_id);
            $this->ajax->outRight("删除成功");
        } else {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, '删除失败');
        }
    }

    //基本信息
    public function infoAction()
    {
        $account = $this->request->get("account", 'int', 13);
        $name = trim($this->request->getPost('name', 'string', ''));
        $logo = trim($this->request->getPost('logo', 'string', ''));
        $background = trim($this->request->getPost('background', 'string', ''));
        $telephone = trim($this->request->getPost('telephone', 'string', ''));
        $email = trim($this->request->getPost('email', 'string', ''));
        $introduce = trim($this->request->getPost('introduce', 'string', ''));
        $qq = trim($this->request->getPost('qq', 'int', ''));
        if (!$name || !$logo || !$background || !$telephone || !$email || !$introduce || !$qq || !$account) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!Validator::validEmail($email)) {
            $this->ajax->outError(Ajax::ERROR_EMAIL_IS_INVALID);
        }
        if (!Validator::validateTelePhone($telephone)) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "非法的座机");
        }
        if (!Validator::validateUrl($logo)) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "无效的头像地址");
        }
        if (!Validator::validateUrl($background)) {
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "无效的头像背景");
        }
        $data = ["name" => $name, 'logo' => $logo, 'background' => $background, 'telephone' => $telephone, 'email' => $email, 'introduce' => $introduce, 'qq' => $qq];
        if ($account == 13) {
            $res = SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'official_info', ['val' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        } else {
            $res = SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, 'official_info_' . $account, ['val' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        }
        if ($res) {
            //云信接口调用
            ServerAPI::init()->updateUinfo($account, $name, $logo);
            AdminLog::init()->add('平台基本信息设置', AdminLog::TYPE_APP_SETTING, '', $data);
            $this->ajax->outRight("编辑成功");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "保存失败");
    }

    //app设置
    public function appSettingAction()
    {
        $version = $this->request->getPost("version", 'int', 1);//展示版本信息
        $wallet = $this->request->getPost("wallet", 'int', 1);//展示红包及钱包信息
        $reward = $this->request->getPost("reward", 'int', 1);//展示打赏
        $skill = $this->request->getPost("skill", 'int', 1);//展示技能管理
        $package_promote = $this->request->getPost("package_promote", 'int', 1);//开启红包君推广功能

        $video_red_package = $this->request->getPost("video_red_package", 'int', 1);//视频问答红包功能
        $vip = $this->request->getPost("vip", 'int', 1);//开启vip功能
        $private_room = $this->request->getPost("private_room", 'int', 1);//开启私密房间功能
        $h5_game = $this->request->getPost("h5_game", 'int', 1);//开启h5小游戏
        $gift_income = $this->request->getPost("gift_income", 'int', 1);//开启礼物收益入口


        $data = ["wallet" => $wallet, 'vip' => $vip, 'private_room' => $private_room, "version" => $version, 'reward' => $reward, 'skill' => $skill, 'video_red_package' => $video_red_package, 'package_promote' => $package_promote, 'h5_game' => $h5_game, 'gift_income' => $gift_income];
        if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_APP_SETTING, 'setting', ['val' => json_encode($data, JSON_UNESCAPED_UNICODE)])) {
            AdminLog::init()->add('app设置', AdminLog::TYPE_APP_SETTING, '', $data);

            $this->ajax->outRight("编辑成功");
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "保存失败");

    }

    public function imgCheckSettingAction()
    {
        $enable = $this->request->getPost("enable", 'int', 1);//开启鉴黄
        $enable_discuss = $this->request->getPost("enable_discuss", 'int', 1);//动态鉴黄
        $enable_comment = $this->request->getPost("enable_comment", 'int', 1);//评论鉴黄
        $enable_avatar = $this->request->getPost("enable_avatar", 'int', 1);//头像鉴黄
        $score = $this->request->getPost("score", 'int', 0);//分值
        $data = ["enable" => $enable, "enable_discuss" => $enable_discuss, "enable_comment" => $enable_comment,"enable_avatar" =>$enable_avatar, "score" => $score];
        if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, 'img_check', ['val' => json_encode($data, JSON_UNESCAPED_UNICODE)])) {
            $this->ajax->outRight("编辑成功");
        }
    }

    //添加敏感词
    public function addWordAction()
    {
        $words = $this->request->get("words", 'string', '');
        $type = $this->request->get("type", 'string', '');

        if ($words) {
            SensitiveManager::saveWord($type, $words);
        }
    }

    //搜索敏感词
    public function searchWordAction()
    {
        $type = $this->request->get("type", 'string', 'normal');
        $word = $this->request->get("word", 'string', '');

        if ($word) {
            $res = SensitiveManager::searchWord($type, $word);
            $this->ajax->outRight($res);
        }

        /* if ($words) {
             SensitiveManager::saveWord('normal', $words);
         }*/
    }

    //删除敏感词
    public function removeWordAction()
    {
        $word = $this->request->get("word", 'string', '');
        $type = $this->request->get("type", 'string', 'normal');
        if ($word) {
            $res = SensitiveManager::removeWord($type, $word);
            $this->ajax->outRight($res);
        }
    }

    //敏感词设置
    public function setSensitiveAction()
    {
        $data = $this->request->getPost("data", 'string', '');
        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $original_setting = SiteKeyValManager::init()->getValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "sensitive_word");

        $res = SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_PAGE_OTHER, "sensitive_word", ['val' => json_encode($data)]);
        if ($res) {
            if ($original_setting) {
                $original_setting = json_decode($original_setting, true);
                if ($original_setting['rule'] != $data['rule']) {
                    //更新缓存
                    SensitiveManager::setCache("normal");
                    SensitiveManager::setCache("law");
                }
            } else {
                //更新缓存
                SensitiveManager::setCache("normal");
                SensitiveManager::setCache("law");
            }
            $this->ajax->outRight("设置成功");
        }

        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
    }

    public function rewardSettingAction()
    {
        $enable = $this->request->getPost("enable", 'int', 1);//开启鉴黄
        $discuss_recommend = $this->request->getPost("discuss_recommend");//动态推荐
        $share = $this->request->getPost("share");//动态分享
        $robot_discuss_package = $this->request->getPost("robot_discuss_package");//动态红包

        $data = ["enable" => $enable, "discuss_recommend" => $discuss_recommend, "share" => $share, 'robot_discuss_package' => $robot_discuss_package];
        if (SiteKeyValManager::init()->setValByKey(SiteKeyValManager::KEY_SYSTEM_SETTING, 'reward', ['val' => json_encode($data, JSON_UNESCAPED_UNICODE)])) {
            $this->ajax->outRight("编辑成功");
        }
    }


}