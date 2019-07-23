<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/11/21
 * Time: 15:34
 */

namespace Multiple\Api\Controllers;


use Models\User\UserInfo;
use Models\User\UserPersonalSetting;
use Models\User\Users;
use Models\User\UserTags;
use Models\User\UserTagsCustom;
use Services\Discuss\TagManager;
use Services\User\Square\SquareTask;
use Services\User\UserStatus;
use Util\Ajax;

class TagController extends ControllerBase
{
    //获取标签
    public function getAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get("to_uid", 'int', 0);
        if (!$to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $userTags = UserTags::findOne(['user_id=' . $to_uid, 'columns' => 'tags_name,brief,images']);
        if ($userTags) {
            $res = $userTags;
        } else {
            $res = (object)[];
        }
        $this->ajax->outRight($res);
    }

    //设置、修改标签
    public function editAction()
    {
        $uid = $this->uid;
        $tags_name = $this->request->get("tags_name", 'green'); //标签名称
        $brief = $this->request->get("brief", 'green'); //简介
        $images = $this->request->get("images", 'string', '');//图片
        $custom_tags_name = $this->request->get("cust_tags_name", 'green'); //自定义标签

        $data = [];

        if ($tags_name == 'empty') {
            $data['tags_name'] = '';
        } else if ($tags_name != '') {
            $data['tags_name'] = $tags_name;
        }

        if ($brief == 'empty') {
            $data['brief'] = '';
        } else if ($brief != '') {
            $data['brief'] = $brief;
        }
        if ($images == 'empty') {
            $data['images'] = '';
        } else if ($images != '') {
            $data['images'] = $images;
        }

        //添加过标签
        if (UserTags::exist('user_id=' . $uid)) {
            $data['modify'] = time();
            $res = UserTags::updateOne($data, 'user_id=' . $uid);
            SquareTask::init()->executeRule($uid, device_id, SquareTask::TASK_TAG);
        } else {
            $data['user_id'] = $this->uid;
            $data['created'] = time();
            $data['modify'] = $data['created'];
            $res = UserTags::insertOne($data);
            SquareTask::init()->executeRule($uid, device_id, SquareTask::TASK_TAG);
        }
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_SUBMIT);
        }

        #自定义标签处理#
        if ($custom_tags_name !== null) {
            if ($custom_tags_name == '') {
                UserTagsCustom::remove("user_id=" . $uid);
            } else {
                //存在
                if (UserTagsCustom::exist("user_id=" . $uid)) {
                    UserTagsCustom::updateOne(['modify' => time(), 'tags_name' => $custom_tags_name], 'user_id=' . $uid);
                } else {
                    UserTagsCustom::insertOne(['user_id' => $uid, 'tags_name' => $custom_tags_name, 'created' => time()]);
                }
            }
        }
        $this->ajax->outRight("提交成功", Ajax::SUCCESS_SUBMIT);
    }

    /*推荐用户*/
    public function recommendUserAction()
    {
        $uid = $this->uid;
        //  $s = $this->request->get("s", 'string', '');
        $lng = $this->request->get('lng', 'string', '');//精度
        $lat = $this->request->get('lat', 'string', '');//纬度
        $sex = $this->request->get('sex', 'int', 0);//性别
        $distance = $this->request->get('distance', 'int', 0);//多大范围内的用户
        $age_start = $this->request->get("age_start", 'int', 0);//年龄起始
        $age_end = $this->request->get("age_end", 'int', 0);//年龄结束
        $area_code = $this->request->get("area_code", 'string', '');//区号

        $page = $this->request->get('page', 'int', 1);//第几页
        $limit = $this->request->get('limit', 'int', 20);//每页数量

        if (!$lng || !$lat) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $where = 'p.user_id>0 and t.tags_name is not null ';

        if ($distance) {
            /* //暂时不考虑 负值
              $new_distance = $distance*1000;
               $length = 0.001 * ($new_distance / 100);//跨越的长度
               $start_lng = ($lng - $length);
               $end_lng = ($lng + $length);

               $start_lat = ($lat - $length);
               $end_lat = ($lat + $length);
               $where .= " and lng between $start_lng and $end_lng and lat between $start_lat and $end_lat ";*/
            $where .= " and  GetDistances(lat,lng,$lat,$lng)<=" . ($distance * 1000);
        }

        if ($sex) {
            $where .= " and p.sex=" . $sex;
        }
        if ($page > 0) {
            $limit_str = " limit " . ($page - 1) * $limit . ',' . $limit;
        } else {
            $limit_str = " limit " . $limit;
        }
        /*   if ($s) {
               $where .= " and t.tags_name like %$s%";
           }*/
        if ($age_start) {
            $birthday_start = strtotime((date('Y') - $age_start) . '-' . date('m') . '-' . date('d'));
            $where .= " and UNIX_TIMESTAMP(p.birthday)<=" . $birthday_start;
        }
        if ($age_end) {
            $birthday_end = strtotime((date('Y') - $age_end + 1) . '-' . date('m') . '-' . date('d'));
            $where .= " and UNIX_TIMESTAMP(p.birthday)>=" . $birthday_end;
        }
        if ($area_code) {
            $where .= " and l.area_code='" . $area_code . "'";
        }
        $list = $this->di->get("original_mysql")->query("select GetDistances(lat,lng,$lat,$lng) as distance,l.user_id as uid,l.created,lng,lat,t.tags_name,t.images,t.brief,p.birthday from user_location as l left join user_profile as p on l.user_id=p.user_id left join user_tags as t on t.user_id=p.user_id where " . $where . ' order by distance asc ' . $limit_str)->fetchAll(\PDO::FETCH_ASSOC);
        /*   var_dump($list);
           exit;*/
        if ($list) {
            $uids = implode(',', array_column($list, 'uid'));
            $users = Users::getByColumnKeyList(['id in(' . $uids . ') and status=' . UserStatus::STATUS_NORMAL, 'columns' => 'id as uid,username,avatar'], 'uid');
            $user_personal_setting = UserPersonalSetting::getByColumnKeyList(['owner_id=' . $uid . ' and user_id in (' . $uids . ')', 'columns' => 'user_id as uid,mark'], 'uid');//个人设置列表
            foreach ($list as $k => &$item) {
                if (!isset($users[$item['uid']])) {
                    unset($list[$k]);
                } else {
                    $item['username'] = (isset($user_personal_setting[$item['uid']]) && $user_personal_setting[$item['uid']]['mark']) ? $user_personal_setting[$item['uid']]['mark'] : $users[$item['uid']]['username'];
                    $item['avatar'] = $users[$item['uid']]['avatar'];
                }
            }
        }
        $this->ajax->outRight(array_values($list));
        // var_dump($list);
        // exit;

    }

    //获取系统/个人标签列表
    public function getSysTagsAction()
    {
        $uid = $this->uid;
        $sys_tags = TagManager::getInstance()->getUserTag();
        $custom_tags = UserTagsCustom::findOne(['user_id=' . $uid, 'columns' => 'tags_name']);
        $data = ['cust' => $custom_tags ? $custom_tags['tags_name'] : '', 'sys' => $sys_tags];
        $this->ajax->outRight($data);
    }

    //添加/编辑自定义标签
    public function editCustomTagAction()
    {
        $uid = $this->uid;
        $name = $this->request->get("name");
        $type = $this->request->get("type", 'int', 1);//1-添加 2-删除
        if (!$uid || !$name) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //添加
        if ($type == 1) {
            //存在
            if ($tags = UserTagsCustom::findOne(["user_id=" . $uid, 'columns' => 'tags_name'])) {
                $tags = explode(',', $tags['tags_name']);
                $tags[] = $name;
                $custom_tags_name = implode(',', array_unique($tags));
                UserTagsCustom::updateOne(['modify' => time(), 'tags_name' => $custom_tags_name], 'user_id=' . $uid);
            } else {
                $custom_tags_name = $name;
                UserTagsCustom::insertOne(['user_id' => $uid, 'tags_name' => $custom_tags_name, 'created' => time()]);
            }
        } //删除
        else {
            if ($tags = UserTagsCustom::findOne(["user_id=" . $uid, 'columns' => 'tags_name'])) {
                $tags = explode(',', $tags['tags_name']);
                $custom_tags_name = [];
                foreach ($tags as $item) {
                    if ($item != $name) {
                        $custom_tags_name[] = $item;
                    }
                }
                $custom_tags_name = $custom_tags_name ? implode(',', $custom_tags_name) : '';
                if ($custom_tags_name == '') {
                    UserTagsCustom::remove('user_id=' . $uid);
                } else {
                    UserTagsCustom::updateOne(['modify' => time(), 'tags_name' => $custom_tags_name], 'user_id=' . $uid);
                }
            }
        }
        $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
    }

}