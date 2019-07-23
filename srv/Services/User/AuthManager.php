<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 2016/12/21
 * Time: 10:23
 */

namespace Services\User;


use Components\Yunxin\ServerAPI;
use Models\User\UserAuthApply;
use Models\User\UserInfo;
use Models\User\UserProfile;
use Phalcon\Mvc\User\Plugin;
use Services\Im\ImManager;
use Services\Site\IndustryManager;
use Services\Site\VerifyCodeManager;
use Util\Ajax;
use Util\Validator;

class AuthManager extends Plugin
{
    private static $instance = null;
    const AUTH_TYPE_JOBBER = 1;//职场名人
    const AUTH_TYPE_ENTERTAINER = 2;//娱乐明星
    const AUTH_TYPE_SPORTS = 3;//体育明星
    const AUTH_TYPE_GOVERNMENT = 4;//政府人员

    const AUTH_STATUS_SUCCESS = 1;//审核通过
    const AUTH_STATUS_SENDING = 2;//正在审核
    const AUTH_STATUS_FAILED = 3;//审核失败

    //认证类型
    public static $auth_type = [
        self::AUTH_TYPE_JOBBER,
        self::AUTH_TYPE_ENTERTAINER,
        self::AUTH_TYPE_SPORTS,
        self::AUTH_TYPE_GOVERNMENT,
    ];
    public static $auth_type_name = [
        self::AUTH_TYPE_JOBBER => '职场名人',
        self::AUTH_TYPE_ENTERTAINER => '娱乐明星',
        self::AUTH_TYPE_SPORTS => '体育明星',
        self::AUTH_TYPE_GOVERNMENT => '政府人员',
    ];
    public static $status = [
        self::AUTH_STATUS_SUCCESS => '审核通过',
        self::AUTH_STATUS_SENDING => '待审核',
        self::AUTH_STATUS_FAILED => '审核失败',
    ];

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**名人认证申请
     * @param $uid
     * @param $data
     * @return bool
     */
    //  public function apply($uid, $type, $data)
    public function apply($uid, $data)
    {
//        //职场名人
//        if ($type == self::AUTH_TYPE_JOBBER) {
//            if (!$data['industry']) {
//                Ajax::outError(Ajax::INVALID_PARAM);
//            }
//            if (!$industry = IndustryManager::instance()->getIndustriesById($data['industry'])) {
//                Ajax::outError(Ajax::INVALID_PARAM);
//            }
//            $data['industry_id'] = $data['industry'];
//            $data['industry'] = $industry['name'];
//        } else {
//            $data['industry_id'] = 0;
//            $data['industry'] = '';
//        }
        //已经认证过了
        if (UserInfo::findOne(['user_id=' . $uid . " and is_auth=1", 'columns' => 'is_auth'])) {
            Ajax::outError(Ajax::ERROR_SUBMIT_REPEAT);
        }

        //已经提交过一次认证 且正在审核中
        if (UserAuthApply::findOne(['user_id=' . $uid . ' and status=' . self::AUTH_STATUS_SENDING, 'columns' => '1'])) {
            Ajax::outError(Ajax::ERROR_SUBMIT_REPEAT);
        }
        //身份证已被使用
        if (UserAuthApply::exist("status <>" . self::AUTH_STATUS_FAILED . " and user_id<>$uid and id_card='" . $data['id_card'] . "'")) {
            Ajax::outError(Ajax::ERROR_ID_CARD_HAS_BEEN_USED);
        }

        $data['user_id'] = $uid;
        //  $data['type'] = $type;
        $data['created'] = time();
        if (UserAuthApply::insertOne($data)) {
            $user = UserInfo::findOne(['user_id=' . $uid, 'columns' => 'is_auth']);
            if (!TEST_SERVER) {
                //发送消息给审核人员
                ServerAPI::init()->sendBatchMsg(ImManager::ACCOUNT_SYSTEM, json_encode([50000, 50037, 60034, 62181, 62185, 64014]), 0, json_encode(['msg' => "有人提交了认证申请,赶紧登录后台查看吧"]));
            }
            //  ServerAPI::init()->sendMsg(ImManager::ACCOUNT_SYSTEM, 0, 50037, 0, ['msg' => "有人提交了认证申请,赶紧登录后台查看吧"]);

            //后台取消认证了
            if ($user['is_auth'] == 4) {
                $user_profile_data = ['is_auth' => 0];
                UserProfile::updateOne($user_profile_data, "user_id=" . $uid);//更新用户信息
            }
            return true;
        }
        return false;
    }

    /**获取认证详情
     * @param $uid
     * @return array|string
     */
    public function detail($uid)
    {
        $auth = UserProfile::findOne(['user_id=' . $uid, 'columns' => 'is_auth']);
        //返回上一次成功的审核结果,否则返回上一次的数据
        if ($auth && $auth['is_auth'] == 1) {
            $detail = UserAuthApply::findOne(['user_id=' . $uid . ' and (status=1 or status=2)', 'columns' => 'true_name,id_card,card_front,card_back,card_hand,status,check_reason', 'order' => 'created desc']);
        } else {
            $detail = UserAuthApply::findOne(['user_id=' . $uid, 'columns' => 'true_name,id_card,card_front,card_back,card_hand,status,check_reason', 'order' => 'created desc']);
        }
        return $detail ? $detail : '';
    }
}
