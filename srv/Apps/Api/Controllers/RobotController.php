<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2017/1/18
 * Time: 9:38
 */

namespace Multiple\Api\Controllers;


use Services\User\UserStatus;
use Components\Yunxin\ServerAPI;
use Models\User\UserInfo;
use Models\User\UserProfile;
use Models\User\Users;
use Services\Site\AreaManager;
use Util\Ajax;
use Util\Debug;

class RobotController extends ControllerBase
{
    /*注册*/
    public function registerAction()
    {
        $username = $this->request->get('username', 'green', '');//昵称
        $avatar = $this->request->get('avatar', 'string', '');//头像
        $sex = $this->request->get('sex', 'int', 0);//性别
        $signature = $this->request->get('signature', 'green', '');//个性签名
        $photos = $this->request->get('photos', 'string', '');//照片墙 多张以英文,分割
        $province = $this->request->get('province', 'string', '');//省
        $city = $this->request->get('city', 'string', '');//市
        //Debug::log(var_export($_REQUEST, true), 'debug');
        if (!$username || !$avatar) {
            $this->ajax->outError(Ajax::INVALID_PARAM);
        }
        if ($sex == 0 || ($sex != 1 && $sex != 2)) {
            $sex = rand(1, 2);
        }
        if (Users::exist("username='" . $username . "'")) {
            $this->ajax->outError(Ajax::ERROR_NICK_HAS_BEEN_USED);
        }

        try {
            $this->db->begin();
            $user = new Users();

            $user_data = [
                "user_type" => UserStatus::USER_TYPE_ROBOT,
                "username" => $username,
                "avatar" => $avatar,
                "created" => time()
            ];
            if (!$user_id = $user->insertOne($user_data)) {
                throw new  \Exception(json_encode($user->getMessages(), JSON_UNESCAPED_UNICODE));
            }
            $user_profile = new UserProfile();

            $profile_data = [
                "sex" => $sex,
                "signature" => $signature,
                "photos" => $photos,
                "user_id" => $user_id,
                "register_type" => UserStatus::REGISTER_TYPE_ROBOT
            ];
            //传了省份信息
            if ($province) {
                $province = AreaManager::getProvinceByName($province);
                if (!$province) {
                    $province = AreaManager::getRandProvince();
                }
            } else {
                $province = AreaManager::getRandProvince();
            }
            $province_id = $province['id'];
            $province_name = $province['short_name'];

            //传了市区信息
            if ($city) {
                $city = AreaManager::getCityByName($city);
                if (!$city) {
                    $city = AreaManager::getRandCity($province_id);
                }
            } else {
                $city = AreaManager::getRandCity($province_id);
            }
            $city_id = $city['id'];
            $city_name = $city['short_name'];
            $profile_data["province_id"] = $province_id;
            $profile_data["city_id"] = $city_id;
            $profile_data["province_name"] = $province_name;
            $profile_data["city_name"] = $city_name;

            if (!$user_profile->insertOne($profile_data)) {
                throw new  \Exception(json_encode($user_profile->getMessages(), JSON_UNESCAPED_UNICODE));
            }
            /**----云信注册---**/
            $res = ServerAPI::init()->createUserId($user_id, $username, '', $avatar);
            if (!$res || $res['code'] != 200) {
                throw new \Exception('云信注册失败-' . $res['desc']);
            }
            $this->db->commit();
            $columns = 'user_id as uid,username,avatar,photos,sex,province_id,city_id,province_name,city_name,signature';
            $data = UserInfo::findOne(['user_id=' . $user_id, 'columns' => $columns]);
            $this->ajax->outRight($data);
        } catch (\Exception $e) {
            $this->db->rollback();
            Debug::log($e->getMessage(), 'error');
            $this->ajax->outError(Ajax::FAIL_REGISTER);
        }
    }
}