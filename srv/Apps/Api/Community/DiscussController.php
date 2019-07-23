<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2018/5/9
 * Time: 9:12
 */

namespace Community;


use Multiple\Api\Controllers\ControllerBase;
use Services\Community\CommunityDiscussManager;
use Services\Discuss\DiscussManager;
use Services\User\Behavior\Behavior;
use Util\Ajax;

class DiscussController extends ControllerBase
{
    /*发社区动态*/
    public function publishAction()
    {
        $uid = $this->uid;
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
        $allow_download = $this->request->get('allow_download', 'int', 1);//是否允许下载【0-不允许 1-允许】
        $comm_id = $this->request->get("comm_id", 'int', 0);//社区id
        $is_top = $this->request->get("is_top", 'int', 0);//是否置顶

        !$content && $content = '';
        if (!$uid || !in_array($media_type, DiscussManager::$type) || (!$media && !$content) || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }

        //检测频繁度
        Behavior::init(Behavior::TYPE_DISCUSS_PUBLISH, $uid)->checkBehavior();
        // Debug::log("data:" . var_export($_REQUEST, true), 'debug');
        $res = CommunityDiscussManager::getInstance()->publish($comm_id, $uid, $media_type, $content, $media, $open_location, $address, $lng, $lat, $allow_download, $package_id, $package_info, $area_code,$is_top);
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_PUBLISH);
        }
        $this->ajax->outRight($res);
    }

    //社区动态列表
    public function listAction()
    {
        $uid = $this->uid;
        $comm_id = $this->request->get("comm_id", 'int', 0);
        $page = $this->request->get("page", 'int', 1);//第几页
        $limit = $this->request->get("limit", 'int', 20);
        if (!$uid || !$comm_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $res = CommunityDiscussManager::getInstance()->list($comm_id, $uid, $page, $limit);
        $this->ajax->outRight($res);
    }

    /*--删除动态--*/
    public function deleteAction()
    {
        $uid = $this->uid;
        $discuss_id = $this->request->get('discuss_id', 'string', '');
        if (!$uid || !$discuss_id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (CommunityDiscussManager::getInstance()->deleteDiscuss($uid, $discuss_id)) {
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
            if (CommunityDiscussManager::getInstance()->unTopDiscuss($uid, $discuss_id)) {
                $this->ajax->outRight("取消成功", Ajax::SUCCESS_HANDLE);
            }
        } else {
            if (CommunityDiscussManager::getInstance()->topDiscuss($uid, $discuss_id)) {
                $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
            }
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

}