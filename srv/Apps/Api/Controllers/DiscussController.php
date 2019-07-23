<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/15
 * Time: 10:57
 */

namespace Multiple\Api\Controllers;


use Models\User\UserProfile;
use Services\Discuss\DiscussManager;
use Services\Discuss\TagManager;
use Services\MiddleWare\Sl\Behavior\BehaviorManager;
use Services\User\Behavior\Behavior;
use Util\Ajax;
use Util\Debug;

class DiscussController extends ControllerBase
{
    /*--发布动态--*/
    public function publishAction()
    {
        $uid = $this->uid;
        $tags = $this->request->get('tags', 'string', '');//标签 多个标签以，分割
        $is_top = $this->request->get('is_top', 'int', 0);//是否置顶
        $media_type = $this->request->get('media_type', 'int', 1);//1-纯文本 2-视频 3-图片 4-语音 5-红包 6-商品
        $media = $this->request->get('media', 'string', '');//类型为 2/3时必填 图片多张时以，分割
        $content = $this->request->get('content');//文字内容
        $package_id = $this->request->get('package_id', 'string', '');//红包id
        $package_info = $this->request->get('package_info', 'string', '');//红包信息

        $open_location = $this->request->get('open_location', 'int', 0);//是否公开位置 0-不公开 1-公开
        $address = $this->request->get('address', 'string', '');//具体地址
        $lng = $this->request->get('lng', 'string', '');//精度 公开位置才要传
        $lat = $this->request->get('lat', 'string', '');//纬度 公开位置才有传
        $area_code = $this->request->get('area_code', 'string', '');//地区码

        $scan_type = $this->request->get('scan_type', 'int', 1);//查看类型【1-公开，2-私密，3-部分可见，4-不给谁看】
        $scan_user = trim($this->request->get('scan_user', 'string', ''));//允许查看的/禁止查看的人
        $allow_download = $this->request->get('allow_download', 'int', 1);//是否允许下载【0-不允许 1-允许】
        !$content && $content = '';
        if (!$uid || !in_array($media_type, DiscussManager::$type) || (!$media && !$content) || !in_array($scan_type, DiscussManager::$scan_type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //去除仅好友可见 ，部分好友可见，部分好友不可见
        if ($scan_type != 1 && $scan_type != 2) {
            $scan_type = 1;
            $scan_user = '';
        }

        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_PUBLISH, $uid)->checkBehavior();
        // Debug::log("data:" . var_export($_REQUEST, true), 'debug');
        $res = DiscussManager::getInstance()->publish($uid, $media_type, $content, $media, $tags, $is_top, $open_location, $address, $lng, $lat, $scan_type, $scan_user, $allow_download, $package_id, $package_info, $area_code);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_PUBLISH);
        }
        //纯文本动态
        if ($media_type == DiscussManager::TYPE_TEXT) {
            BehaviorManager::instance($uid, BehaviorManager::behavior_publish_text_discuss)->send();
        } else if ($media_type == DiscussManager::TYPE_VIDEO) {
            BehaviorManager::instance($uid, BehaviorManager::behavior_publish_video_discuss)->send();
        } else if ($media_type == DiscussManager::TYPE_PICTURE) {
            BehaviorManager::instance($uid, BehaviorManager::behavior_publish_img_discuss)->send();
        } else if ($media_type == DiscussManager::TYPE_AUDIO) {
            BehaviorManager::instance($uid, BehaviorManager::behavior_publish_audio_discuss)->send();
        } else if ($media_type == DiscussManager::TYPE_RED_PACKET) {
            BehaviorManager::instance($uid, BehaviorManager::behavior_publish_package_discuss)->send();
        } else if ($media_type == DiscussManager::TYPE_GOODS) {
            BehaviorManager::instance($uid, BehaviorManager::behavior_publish_good_discuss)->send();
        }
        $this->ajax->outRight($res);
        // $this->ajax->outRight('发布成功', Ajax::SUCCESS_PUBLISH);
    }

    /*--获取动态列表--*/
    public function listAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $type = $this->request->get('type', 'int', 0); //0-全部 1-我关注的 2-推荐 3-24小时推荐榜 4-周榜 5-城市
        $tag = $this->request->get('tag', 'int', 0); //标签

        $area_code = $this->request->get('area_code', 'string', '');//地区码
        $v_id = $this->request->get('v_id', 'int', 0);//版本号

        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 20);
        $last_id = $this->request->get('last_id', 'int', 0); //最后一条动态的id
        $first_id = $this->request->get('first_id', 'int', 0); //第一页的第一条数据的id  用于显示有多少条数据更新了

        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(DiscussManager::getInstance()->list($uid, $to_uid, $type, $tag, $area_code, $page, $limit, $first_id, $last_id, $v_id));
    }

    /*--获取我的动态/他的动态--*/
    public function myDiscussAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $type = $this->request->get('type', 'int', 0); //0-全部 1-图文 2-自创 3-视频 4-声控 6-商品
        $limit = $this->request->get('limit', 'int', 20);
        $page = $this->request->get('page', 'int', 1); //第几页

        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(DiscussManager::getInstance()->list2($uid, $to_uid, $type, $page, $limit));
    }

    /*--动态详情--*/
    public function detailAction()
    {
        $uid = $this->uid;
        $discuss_id = $this->request->get('discuss_id', 'int', 0);//动态id
        if (!$uid || !$discuss_id || $discuss_id < 0) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $detail = DiscussManager::getInstance()->detail($uid, $discuss_id);
        $this->ajax->outRight($detail);
    }

    /*--删除动态--*/
    public function deleteAction()
    {
        $uid = $this->uid;
        $discuss_id = $this->request->get('discuss_id', 'string', '');
        if (!$uid || !$discuss_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (DiscussManager::getInstance()->deleteDiscuss($uid, $discuss_id)) {
            $this->ajax->outRight("删除成功", Ajax::SUCCESS_DELETE);
        }
        $this->ajax->outError(Ajax::FAIL_DELETE);
    }

    /*--动态置顶/取消置顶--*/
    public function topAction()
    {
        $uid = $this->uid;
        $discuss_id = $this->request->get('discuss_id', 'string', '');
        $top = $this->request->get('is_top', 'int', 0);//动态是否置顶
        if (!$uid || !$discuss_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (!$top) {
            if (DiscussManager::getInstance()->unTopDiscuss($uid, $discuss_id)) {
                $this->ajax->outRight("取消成功", Ajax::SUCCESS_HANDLE);
            }
        } else {
            if (DiscussManager::getInstance()->topDiscuss($uid, $discuss_id)) {
                $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
            }
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    /*获取标签列表*/
    public function getTagsAction()
    {
        $list = TagManager::getInstance()->list();
        $this->ajax->outRight(array_values($list));
    }

    /*获取还可以置顶动态的数量*/
    public function checkTopAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            Ajax::outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(DiscussManager::checkTop($uid));
    }

    /*打赏*/
    public function rewardAction()
    {

    }

    /*设置封面*/
    public function setBgAction()
    {
        $uid = $this->uid;
        $bg = $this->request->get("bg", 'string', '');
        if (!$uid && !$bg) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = UserProfile::updateOne(['discuss_bg' => $bg], 'user_id=' . $uid);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_SUBMIT);
        }
        $this->ajax->outRight("提交成功", Ajax::SUCCESS_SUBMIT);
    }

}