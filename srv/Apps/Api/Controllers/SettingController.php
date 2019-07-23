<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/4
 * Time: 17:21
 */

namespace Multiple\Api\Controllers;


use Models\User\UserPersonalSetting;
use Models\User\UserSetting;
use Services\User\SkinManager;
use Util\Ajax;
use Util\Debug;

class SettingController extends ControllerBase
{
    //自己设置
    const LOOK_FANS = 'look_fans'; //允许别人查看自己的粉丝和关注用户
    const LOGIN_PROTECT = 'login_protect'; //是否开启登录保护
    const HIDE_LOCATION = 'hide_location'; //是否隐藏自己的位置

    //别人设置
    const LOOK_MY_DISCUSS = 1001; //允许查看我的动态
    const LOOK_HIS_DISCUSS = 1002; //是否查看他的动态

    //自己设置key
    public static $setting_type = [
        self::LOOK_FANS,
        self::LOGIN_PROTECT,
        self::HIDE_LOCATION
    ];
    //设置别人的key
    public static $personal_key = [
        self::LOOK_MY_DISCUSS => 'scan_my_discuss',
        self::LOOK_HIS_DISCUSS => 'scan_his_discuss'
    ];

    //设置登录保护
    public function loginProtectAction()
    {
        $uid = $this->uid;
        $type = $this->request->get('type', 'int', 1); //1-设置 2-取消
        $setting = UserSetting::findOne(['user_id=' . $uid, 'columns' => 'user_id']);
        if ($setting) {
            $res = UserSetting::updateOne(['login_protect' => $type == 1 ? 1 : 0, 'modify' => time()], ['user_id' => $setting['user_id']]);
        } else {
            $res = UserSetting::insertOne(['user_id' => $uid, 'login_protect' => $type == 1 ? 1 : 0, 'created' => time()]);
        }
        if ($res) {
            $this->ajax->outRight("设置成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    //设置
    public function setAction()
    {
        $uid = $this->uid;
        $key = $this->request->get('key', 'string', '');
        $val = $this->request->get('val', 'int', 1);
        if (!$uid || ($val != 0 && $val != 1) || !in_array($key, self::$setting_type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $setting = UserSetting::findOne(['user_id=' . $uid]);
        if ($setting) {
            $res = UserSetting::updateOne([$key => $val, 'modify' => time()], ['user_id' => $setting['user_id']]);
        } else {
            $setting = new UserSetting();
            $res = $setting->insertOne(['user_id' => $uid, $key => $val, 'created' => time()]);
        }
        if ($res) {
            $this->ajax->outRight("设置成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    //设置列表
    public function listAction()
    {
        $uid = $this->uid;
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
//        if ($uid == 50037) {
//            Debug::log("test:" . var_export($_REQUEST, true), 'debug');
//        }
        $data = ['login_protect' => 0, 'look_fans' => 1, 'hide_location' => 0];
        $setting = UserSetting::findOne(['user_id=' . $uid]);
        if ($setting) {
            $data = ['login_protect' => intval($setting['login_protect']), 'look_fans' => intval($setting['look_fans']), 'hide_location' => intval($setting['hide_location'])];
        }
        $this->ajax->outRight($data);
    }

    //个人对个人设置
    public function personalAction()
    {
        $uid = $this->uid;
        $to_uid = $this->request->get('to_uid', 'int', 0);
        $key = $this->request->get('key', 'int', 0);
        $val = $this->request->get('val', 'int', 0);

        if (!$uid || !$to_uid || !$key || !array_key_exists($key, self::$personal_key) || ($val != 1 && $val != 0) || $uid == $to_uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $personal_setting = UserPersonalSetting::findOne(['owner_id=' . $uid . ' and user_id=' . $to_uid, 'columns' => 'id']);
        $data = [];
        $data[self::$personal_key[$key]] = $val;
        if (!$personal_setting) {
            $personal_setting = new UserPersonalSetting();
            $data['owner_id'] = $uid;
            $data['user_id'] = $to_uid;
            $data['created'] = time();
            $res = $personal_setting->insertOne($data);
        } else {
            $data['modify'] = time();
            $res = UserPersonalSetting::updateOne($data, ['id' => $personal_setting['id']]);
        }
        if (!$res) {
            $this->ajax->outError(Ajax::FAIL_EDIT);
        } else {
            $this->ajax->outRight("编辑成功", Ajax::SUCCESS_EDIT);
        }
    }

    //设置皮肤
    public function setSkinAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("key", 'string', 1);
        $code = $this->request->get("val", 'string', '');

        if (!$uid || !$type || !$code || !in_array($type, SkinManager::$type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if (SkinManager::setSkin($uid, $type, $code)) {
            $this->ajax->outRight("操作成功", Ajax::SUCCESS_HANDLE);
        }
        $this->ajax->outError(Ajax::FAIL_HANDLE);
    }

    //获取皮肤设置
    public function getSkinAction()
    {
        $uid = $this->uid;
        $type = $this->request->get("key", 'string', '');
        if (!$uid) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($type && !in_array($type, SkinManager::$type)) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        $this->ajax->outRight(SkinManager::getSkin($uid, $type));
    }

}