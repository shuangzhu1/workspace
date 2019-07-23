<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/9/21
 * Time: 11:59
 */

namespace Multiple\Panel\Api;


use Models\User\Users;
use Services\Admin\AdminLog;
use Services\MiddleWare\Sl\Base;
use Services\MiddleWare\Sl\Request;
use Util\Ajax;

class RentController extends ApiBase
{
    /**
     *  data = {
     * 'type': type,
     * 'subtype': subtype,
     * 'title': title,
     * 'min_price': min_price,
     * 'max_price': max_price,
     * 'service_rate': service_rate,
     * 'offline': offline,
     * 'p_title': "",
     * };
     *
     *  data = {
     * 'deadline': deadline,
     * 'deadline_immediately': deadline_immediately,
     * 'pay_due_time': pay_due_time
     * };
     */
    public function saveConfigAction()
    {
        $data = $this->request->get("data");
        $type = $this->request->get("type");

        if (!$data) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        //技能编辑
        if ($type == 'skill') {
            $skill = [
                'type' => intval($data['type']),
                'subtype' => intval($data['subtype']),
                'title' => ($data['sub_title']),
                // 'sub_title' => ($data['sub_title']),
                'min_price' => intval($data['min_price']),
                'weight' => intval($data['weight']),
                'hot' => intval($data['is_hot']),
                'max_price' => intval($data['max_price']),
                'service_rate' => floatval($data['service_rate'] / 100),
                /*  'offline' => intval($data['offline']),*/
                'default_desc' => ($data['default_desc']),
                'restrict' => intval($data['restrict']),
            ];
            $res = Request::getPost(Base::SKILL_CONFIG_UPDATE_SUBTYPE, $skill);
            if ($res && $res['curl_is_success']) {
                $content = json_decode($res['data'], true);
                if ($content['code'] == 200) {
                    AdminLog::init()->add('租人业务配置修改-技能更新', AdminLog::TYPE_RENT, $type, array('type' => "update", 'id' => $type, 'data' => $skill));

                    $this->ajax->outRight("编辑成功");
                }
            }
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
        } //时间限制
        else if ($type == 'time') {
            $data = ['deadline' => intval($data['deadline'] * 60),
                'deadline_immediately' => intval($data['deadline_immediately'] * 60),
                'pay_due_time' => intval($data['pay_due_time'] * 60),
                'automatic' => intval($data['automatic']),
                'audit_duration' => intval($data['audit_duration']),
            ];
            $res = Request::getPost(Base::SKILL_CONFIG_UPDATE_BASIC, $data
            );
            if ($res && $res['curl_is_success']) {
                $content = json_decode($res['data'], true);
                if ($content['code'] == 200) {
                    AdminLog::init()->add('租人业务配置修改-基础/时间配置更新', AdminLog::TYPE_RENT, $type, array('type' => "update", 'id' => $type, 'data' => $data));

                    $this->ajax->outRight("编辑成功");
                }
            }
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
        } //添加技能
        else if ($type == 'add_skill') {

            $skill = Request::getPost(Base::SKILL_CONFIG, []);
            $skill = json_decode($skill['data'], true);
            $skill = json_decode($skill['data']['Skill'], true);
            $last_id = 0;

            //顶级
            if (!$data['type']) {
                foreach ($skill as $s) {
                    if ($last_id < $s['type']) {
                        $last_id = $s['type'];
                    }
                }
                $skill = [
                    'type' => intval($last_id + 1),
                    'title' => ($data['title']),
                    'weight' => intval($data['weight']),
                    'icon' => $data['icon']
                ];
                $res = Request::getPost(Base::SKILL_CONFIG_UPDATE_TYPE, $skill);
                if ($res && $res['curl_is_success']) {
                    $content = json_decode($res['data'], true);
                    if ($content['code'] == 200) {
                        AdminLog::init()->add('租人业务配置修改-添加顶级技能', AdminLog::TYPE_RENT, $type, array('type' => "update", 'id' => $skill['type'], 'data' => $skill));
                        $this->ajax->outRight("编辑成功");
                    }
                }
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
            } else {
                //  $type_title = '';
                foreach ($skill as $s) {
                    if ($s['type'] == $data['type']) {
                        //  $type_title = $s['title'];
                        foreach ($s['skills'] as $sub) {
                            if ($sub['subtype'] > $last_id) {
                                $last_id = $sub['subtype'];
                            }
                        }
                    }
                }

                $skill = [
                    'type' => intval($data['type']),
                    'subtype' => 0 /*intval($last_id + 1)*/,
                    'weight' => intval($data['weight']),
                    'hot' => intval($data['is_hot']),
                    /*  'type_title' => $type_title,*/
                    'title' => ($data['title']),
                    'min_price' => intval($data['min_price']),
                    'max_price' => intval($data['max_price']),
                    'service_rate' => floatval($data['service_rate'] / 100),
                    'offline' => intval($data['offline']),
                    'restrict' => intval($data['restrict'])
                ];
                $res = Request::getPost(Base::SKILL_CONFIG_UPDATE_SUBTYPE, $skill);
                if ($res && $res['curl_is_success']) {
                    $content = json_decode($res['data'], true);
                    if ($content['code'] == 200) {
                        AdminLog::init()->add('租人业务配置修改-添加子级技能', AdminLog::TYPE_RENT, $type, array('type' => "update", 'id' => $skill['type'], 'data' => $skill));
                        $this->ajax->outRight("编辑成功");
                    }
                }
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
            }

        } //编辑顶级技能
        elseif ($type == 'edit_top_skill') {
            $skill = [
                'type' => intval($data['type']),
                'title' => ($data['title']),
                'weight' => intval($data['weight']),
                'icon' => $data['icon'],
            ];
            $res = Request::getPost(Base::SKILL_CONFIG_UPDATE_TYPE, $skill);
            if ($res && $res['curl_is_success']) {
                $content = json_decode($res['data'], true);
                if ($content['code'] == 200) {
                    AdminLog::init()->add('租人业务配置修改-编辑顶级技能', AdminLog::TYPE_RENT, $type, array('type' => "update", 'id' => $skill['type'], 'data' => $skill));
                    $this->ajax->outRight("编辑成功");
                }
            }
            $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败");
        } //子级技能移动
        elseif ($type == 'move_skill') {

            $fail_type = [];
            foreach ($data['subtype'] as $item) {
                $skill = [
                    'type' => intval($data['type']),
                    'subtype' => intval($item),
                    'to_type' => $data['to_type'],
                ];
                //   var_dump($skill);exit;
                $res = Request::getPost(Base::SKILL_CONFIG_MOVE_SUBTYPE, $skill);
                if ($res && $res['curl_is_success']) {
                    $content = json_decode($res['data'], true);
                    if ($content['code'] == 200) {
                        AdminLog::init()->add('租人业务配置修改-移动技能', AdminLog::TYPE_RENT, $type, array('type' => "update", 'id' => $skill['type'], 'data' => $skill));
                    } else {
                        $fail_type[] = $item;
                    }
                }
            }
            if (!$fail_type) {
                $this->ajax->outRight("编辑成功");
            } else {
                $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "编辑失败[" . implode(',', $fail_type) . ']');
            }


        }


    }

    //审核通过
    public function applyCheckSuccessAction()
    {
        $id = $this->request->get("id", 'int', 0);
        if (!$id) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $data = ['id' => intval($id), 'result' => 1];
        $res = Request::getPost(Base::SKILL_APPLY_CHECK, $data);
        if ($res && $res['curl_is_success']) {
            $content = json_decode($res['data'], true);
            if ($content['code'] == 200) {
                AdminLog::init()->add('租人业务技能审核通过', AdminLog::TYPE_RENT, $id, array('type' => "update", 'id' => $id, 'data' => $data));

                $this->ajax->outRight("审核成功");
            }
        }
        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "审核失败");

    }

    //审核不通过
    public function applyCheckFailAction()
    {
        $id = $this->request->get("id", 'int', 0);
        $reason = $this->request->get("reason", 'string', '');
        $type = $this->request->get("type", 'string', '');
        $data = $this->request->get("data");


        if ($type == 'check') {
            if (!$id || !$reason) {
                $this->ajax->outError(Ajax::INVALID_PARAM);
            }
            $data = ['id' => intval($id), 'result' => 0, 'reason' => $reason];
            $res = Request::getPost(Base::SKILL_APPLY_CHECK, $data);
            if ($res && $res['curl_is_success']) {
                $content = json_decode($res['data'], true);
                if ($content['code'] == 200) {
                    AdminLog::init()->add('租人业务技能审核失败', AdminLog::TYPE_RENT, $id, array('type' => "update", 'id' => $id, 'data' => $data));

                    $this->ajax->outRight("审核成功");
                }
            }
        } //删除技能


        $this->ajax->outError(Ajax::CUSTOM_ERROR_MSG, "删除失败");
    }


    /**
     * 获取某个人技能详情
     */
    function getSkillAction()
    {
        $uid = $this->request->get('uid');
        $skill = $this->sellers->query("select skill from rent_users where uid = $uid")->fetch(\PDO::FETCH_ASSOC);
        if( $skill )
            $skill = json_decode($skill['skill'],true);
        $user = Users::findOne(['id = ' . $uid,'columns' => 'id,avatar,username']);
        $html = $this->getFromOB('rent/partial/skill',['item' => $skill,'user' => $user]);
        Ajax::init()->outRight($html);
    }

    /**
     * 设置或取消用户首页推荐
     */
    public function recommendAction()
    {
        $uid = $this->request->get('uid');
        $cmd = $this->request->get('cmd');
        $res = $this->postApi('rent/backgroup/user/recommend',['uid' => $uid, 'cmd' => $cmd]);
        Ajax::outRight();
    }

    /**
     * 取消全部推荐用户
     */
    public function unRecommendAllAction()
    {
        $this->postApi('rent/backgroup/unrecommend/allusers',[]);
        Ajax::outRight();
    }
    /**
     * 下架技能
     */
    public function delSkillAction()
    {
        $reason = $this->request->get('reason');
        $data = explode('-',$this->request->get('data'));
        $uid = (int) $data[0];
        $type = (int) $data[1];
        $subtype = (int) $data[2];
        $this->postApi('rent/user/seller/skill/delete',['uid' => $uid, 'type' => $type, 'subtype' => $subtype, 'admin' => 1 ,'reason' => $reason]);
        Ajax::outRight();
    }

}